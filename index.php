<?php

/**
 * condition: AND
 * SELECT * FROM l_urls WHERE ( SELECT COUNT(1) FROM l_links WHERE l_links.url_id = l_urls.id AND l_links.tag_id = ANY( SELECT id FROM l_tags WHERE tag = 'hot' OR tag = 'sexy' ) ORDER BY l_links.utc_added DESC ) = 2
 */

// Always
header('Content-type: text/html; charset="utf-8"');

// Config
require 'inc.config.php';
ini_set('date.timezone', 'Europe/Amsterdam');
error_reporting(E_ALL & ~E_STRICT);

// Request URI
$uri = $_SERVER['REQUEST_URI'];
if ( 1 < count($x = explode('?', $uri, 2)) ) {
	$_SERVER['REQUEST_URI'] = $x[0];
	parse_str($x[1], $g);
	$_GET += $g;
}

// Database
require 'inc.db.php';
if ( !$db ) {
	header('HTTP/1.1 500 Connection error', true, 500);
	exit('Connection error');
}

// Xnary
require 'Xnary.php';
Xnary::$range = implode(range('A', 'Z')) . implode(range('0', '9'));



// Add url //
if ( isset($_REQUEST['url'], $_REQUEST['title'], $_REQUEST['tags']) ) {
	exit(_AddLink($_REQUEST['url'], $_REQUEST['title'], $_REQUEST['tags']));
}

// Copy url //
else if ( isset($_REQUEST['url'], $_REQUEST['tags']) ) {
	exit(CopyUrlToTags((int)$_REQUEST['url'], $_REQUEST['tags']));
}

// Rename url //
else if ( isset($_REQUEST['url'], $_REQUEST['title']) ) {
	exit(ChangeUrlTitle((int)$_REQUEST['url'], $_REQUEST['title']));
}

// Redirect from code //
else if ( !empty($_GET['code']) ) {
	$code = strtoupper($_GET['code']);

	$id = Xnary::toInt($code);

	if ( $url = $db->select_one('l_urls', 'url', array('id' => (int)$id)) ) {
		header('Location: ' . $url);
	}

	exit('#' . $id);
}

// View tags //
$szTags = trim(@$_GET['tags']) ?: trim(@$_GET['t']) ?: '';
ViewUrlsByTag(valid_tags($szTags));









/**
 * Add a TAG
 */
function AddTag( $f_szTag ) {
	global $db;

	$f_szTag = strtolower($f_szTag);
	$iTag = $db->select_one('l_tags', 'id', 'tag = ? ORDER BY id ASC', array(trim($f_szTag)));

	if ( $iTag ) {
		return $iTag;
	}

	$arrInsert = array('tag' => $f_szTag);
	$db->insert('l_tags', $arrInsert);

	return $db->insert_id();

} // END AddTag()


/**
 * Add a URL
 */
function AddUrl( $f_szUrl, $f_szTitle ) {
	global $db;

	$iUrl = $db->select_one('l_urls', 'id', array('url' => $f_szUrl));
	if ( $iUrl ) {
		return $iUrl;
	}

	$arrInsert = array('url' => $f_szUrl, 'title' => $f_szTitle);
	$db->insert('l_urls', $arrInsert);

	return $db->insert_id();

} // END AddUrl()


/**
 * Add a LINK between TAGS and a URL
 */
function _AddLink( $f_szUrl, $f_szTitle, $f_szTags ) {
	global $db;

	$szUrl	 = trim($f_szUrl);
	$arrTags = explode(' ', str_replace('/', ' ', valid_tags($f_szTags)));
	$szTitle = trim($f_szTitle);
	if ( !$arrTags || !$szUrl ) {
		return 'ERROR:' . __LINE__;
	}

	// Add url
	$iUrlId = AddUrl($szUrl, $szTitle);

	foreach ( $arrTags AS $szTag ) {
		// Add tag
		$iTagId = AddTag($szTag);

		// Insert relation
		try {
			$db->insert('l_links', array(
				'url_id'	=> $iUrlId,
				'tag_id'	=> $iTagId,
				'utc_added'	=> time(),
			));
		}
		catch ( db_exception $ex ) {}
	}

	exit('OK');

} // END _AddLink()


/**
 * Copy URL to TAGS
 */
function CopyUrlToTags( $f_iUrlId, $f_szTags = "" ) {
	if ( empty($f_szTags) ) {
		exit('No tags, no copy!');
	}

	global $db;

	$arrTags = explode(" ", str_replace('/', ' ', str_replace('+', ' ', valid_tags($f_szTags))));
	$iUrlId = (int)$f_iUrlId;

	$iAdded = 0;
	foreach ( $arrTags AS $szTag ) {
		// Add tag
		$iTagId = AddTag($szTag);

		// Insert relation
		$arrInsert = array(
			'url_id'	=> $iUrlId,
			'tag_id'	=> $iTagId,
			'utc_added'	=> time(),
		);
		try {
			$iAdded += (int)$db->insert('l_links', $arrInsert);
		}
		catch ( db_exception $ex ) {}
	}

	exit('OK' . $iAdded);

} // END CopyUrlToTags()


/**
 * Change URL title
 */
function ChangeUrlTitle( $f_iUrlId, $f_szTitle = "" ) {
	if ( empty($f_szTitle) ) {
		exit('No title, no change!');
	}

	global $db;

	$arrUpdate = array('title' => $f_szTitle);
	if ( $db->update('l_urls', $arrUpdate, 'id = '.(int)$f_iUrlId) ) {
		exit('OK');
	}

	exit('Update failed!');

} // END ChangeUrlTitle()


/**
 * Main method: view URLS by TAGS
 */
function ViewUrlsByTag( $f_szTags = '' ) {
	global $db;

	$g_szTag = $f_szTags;

	$szBasePath = dirname($_SERVER['PHP_SELF']) . '/';

	$mobile = isset($_GET['mobile']) || ( isset($_SERVER['HTTP_USER_AGENT']) && is_int(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobi')) );

	$arrInTags = explode(' ', $g_szTag);
	$szWhereClause = 1 == count($arrInTags) ? "(l_tags.tag = '".$arrInTags[0]."')" : "(l_tags.tag = '".implode("' OR l_tags.tag = '", $arrInTags)."')";

	if ( '~new' == $g_szTag ) {
		$szQuery = 'SELECT * FROM l_urls ORDER BY id DESC LIMIT 50;';
	}
	else if ( strstr($g_szTag, "/") ) {
		# OR #
		$arrTags = explode(" ", preg_replace("/([ ]{2,})/", " ", trim(str_replace("+", " ", str_replace("/", " ", $g_szTag)))));
		$szQuery = "
			SELECT
				DISTINCT l_urls.*
			FROM
				l_links,
				l_tags,
				l_urls
			WHERE
				(l_links.tag_id = l_tags.id) AND
				(l_links.url_id = l_urls.id) AND
				(l_tags.tag = '".implode("' OR l_tags.tag = '", $arrTags)."')
			ORDER BY
				l_links.utc_added DESC;";
	}
	else
	{
		# AND #
		$arrTags = explode(" ", preg_replace("/([ ]{2,})/", " ", trim(str_replace("+", " ", str_replace("/", " ", $g_szTag)))));
		$szQuery = "
			SELECT
				u.*,
				COUNT(DISTINCT t.tag) AS num_tags
			FROM
				l_links l,
				l_tags t,
				l_urls u
			WHERE
				l.tag_id = t.id AND
				u.id = l.url_id AND
				t.tag IN('".implode("','", $arrTags)."')
			GROUP BY
				l.url_id
			HAVING
				num_tags = ".count($arrTags)."
			ORDER BY
				l.utc_added DESC;";
	}

	$arrUrls = $db->fetch($szQuery)->all();

	require 'tpl.index.php';

} // END ViewUrlsByTag()







// M I S C //

function html( $html ) {
	return htmlspecialchars($html, ENT_COMPAT, 'UTF-8');
}

function valid_tags( $f_szTags ) {
	return implode(' ', array_filter(array_map('valid_tag', explode(' ', $f_szTags))));
}

function valid_tag( $f_szTag ) {
	return strtolower(preg_replace("%[^~a-zA-Z0-9\.\-\+_//]%", '', $f_szTag));
}


