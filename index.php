<?php

/**
 * condition: AND
 * SELECT * FROM l_urls WHERE ( SELECT COUNT(1) FROM l_links WHERE l_links.url_id = l_urls.id AND l_links.tag_id = ANY( SELECT id FROM l_tags WHERE tag = 'hot' OR tag = 'sexy' ) ORDER BY l_links.utc_added DESC ) = 2
 */

require 'inc.bootstrap.php';



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

// Get url info //
else if ( isset($_REQUEST['url']) ) {
	header('Content-type: application/json; charset="utf-8"');
	exit(GetUrlUsage($_REQUEST['url']));
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
 * Get URL usage from db
 */
function GetUrlUsage( $f_szUrl ) {
	global $db;

	$urls = $db->select('l_urls', array('url' => $f_szUrl))->all();
	$tags = array();
	$ids = array_map(function($url) use ($db, &$tags) {
		$url->tags = $db->fetch('SELECT t.tag FROM l_links l, l_tags t WHERE t.id = l.tag_id AND l.url_id = ?', array($url->id))->fields('tag');
		$tags = array_merge($tags, $url->tags);
		return (int)$url->id;
	}, $urls);

	if ( $tags ) {
		$tags = array_count_values($tags);
		uksort($tags, 'strnatcasecmp');
	}
	else {
		$tags = new stdClass;
	}

	return json_encode(compact('urls', 'ids', 'tags'));

} // END GetUrlUsage()


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
	$arrTags = unaliasTags(explode(' ', str_replace('/', ' ', valid_tags($f_szTags))));
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

	$arrTags = unaliasTags(explode(" ", str_replace('/', ' ', str_replace('+', ' ', valid_tags($f_szTags)))));
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

	$script = $_SERVER['PHP_SELF'];
	$szBasePath = rtrim(dirname($script), '/') . '/';

	$mobile = isset($_GET['mobile']) || ( isset($_SERVER['HTTP_USER_AGENT']) && is_int(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobi')) );

	// $arrInTags = explode(' ', $g_szTag);
	// $szWhereClause = 1 == count($arrInTags) ? "(l_tags.tag = '".$arrInTags[0]."')" : "(l_tags.tag = '".implode("' OR l_tags.tag = '", $arrInTags)."')";

	if ( '~new' == $g_szTag ) {
		$szQuery = 'SELECT * FROM l_urls ORDER BY id DESC LIMIT 250;';
	}
	else {
		$tags = unaliasTags(preg_split('#[\/\s]+#', $g_szTag));
		$and = !strstr($g_szTag, "/");

		$szQuery = $db->replaceholders('
			SELECT u.*, COUNT(1) as matching
			FROM l_links l, l_tags t, l_urls u
			WHERE l.url_id = u.id AND l.tag_id = t.id AND t.tag in (?)
			GROUP BY u.id
		', array($tags));
		if ( $and ) {
			$szQuery .= $db->replaceholders(' HAVING matching = ?', array(count($tags)));
		}
		$szQuery .= ' ORDER BY u.id DESC';
	}

	$arrUrls = $db->fetch($szQuery)->all();

	require 'tpl.index.php';

} // END ViewUrlsByTag()


