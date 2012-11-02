<?php

ini_set('date.timezone', 'Europe/Amsterdam');
header('Content-type: text/html; charset="utf-8"');
require 'inc.config.php';

/**
 * condition: AND
 * SELECT * FROM l_urls WHERE ( SELECT COUNT(1) FROM l_links WHERE l_links.url_id = l_urls.id AND l_links.tag_id = ANY( SELECT id FROM l_tags WHERE tag = 'hot' OR tag = 'sexy' ) ORDER BY l_links.utc_added DESC ) = 2
 */

define( 'URL_SLASH_OFFSET', null );

error_reporting(E_ALL & ~E_STRICT);
//header("HTTP/1.0 200 OK", true, 200);

// echo $_SERVER['REQUEST_URI']; exit;

$uri = $_SERVER['REQUEST_URI'];
if ( 1 < count($x = explode('?', $uri, 2)) ) {
	$_SERVER['REQUEST_URI'] = $x[0];
	parse_str($x[1], $g);
	$_GET += $g;
}

$g_szRequestUri = urldecode(substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '/', URL_SLASH_OFFSET)));
if ( '/tags/' == substr($g_szRequestUri, 0, 6) ) $g_szRequestUri = substr($g_szRequestUri, 5);
$g_szBaseUri = rtrim(substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '/', URL_SLASH_OFFSET)),'/\\');

require 'inc.db.php';
if ( !$db ) {
	header('HTTP/1.1 500 Connection error', true, 500);
	exit('Connection error');
}

require 'Xnary.php';
Xnary::$range = implode(range('A', 'Z')) . implode(range('0', '9'));

define('chrome', isset($_GET['via']) && 'chrome' === $_GET['via']);

/** DEBUG **
$arrLinks = $db->fetch("SELECT t.tag, u.url FROM l_tags t, l_links l, l_urls u WHERE t.id = l.tag_id AND u.id = l.url_id ORDER BY t.tag ASC");
$arrLinksByTags = array();
foreach ( $arrLinks AS $l ) {
	if ( empty($arrLinksByTags[$l['tag']]) ) {
		$arrLinksByTags[$l['tag']] = array();
	}
	array_push($arrLinksByTags[$l['tag']], $l['url']);
}
echo base64_encode(serialize($arrLinksByTags));
exit;
/** DEBUG **/


// Add url //
if ( isset($_GET['url'], $_GET['title'], $_GET['tags']) ) {
	exit(_AddLink($_GET['url'], $_GET['title'], $_GET['tags']));
}

// Copy url //
else if ( isset($_GET['url'], $_GET['tags']) ) {
	//sleep(2);
	exit(CopyUrlToTags((int)$_GET['url'], $_GET['tags']));
}

// Rename url //
else if ( isset($_GET['url'], $_GET['title']) ) {
	//sleep(2);
	exit(ChangeUrlTitle((int)$_GET['url'], $_GET['title']));
}

// Rewrite from code
else if ( !empty($_GET['code']) ) {
	$code = strtoupper($_GET['code']);

	$id = Xnary::toInt($code);

	if ( $url = $db->select_one('l_urls', 'url', array('id' => (int)$id)) ) {
		header('Location: ' . $url);
	}

	exit('#' . $id);
}

// View tags //
$szTags = isset($_GET['tags']) ? trim($_GET['tags']) : ( isset($_GET['t']) ? trim($_GET['t']) : '' );
ViewUrlsByTag( valid_tags($szTags) );
exit;









$g_arrHooks = array(
	'/~out/#/'		=> 'LocationUrlOut',
	'/~add'			=> 'AddUrlFromPost',
	'/~title/#/*/'	=> 'ChangeUrlTitle',
	'/~copy/#/*/'	=> 'CopyUrlToTags',
);



foreach ( $g_arrHooks AS $szPath => $szHook )
{
	$szMatch = str_replace('#', '([0-9]+)', $szPath);
	$szMatch = str_replace('*', '([^/]+)', $szMatch);
	if ( preg_match('#^'.$szMatch.'$#', $g_szRequestUri, $parrMatches) )
	{
		array_shift($parrMatches);
		$arrCallback = array(
			'hook'	 => $szHook,
			'params' => $parrMatches,
			'path'   => $szPath,
		);
		break;
	}
}

if ( !isset($arrCallback) || !is_callable($arrCallback['hook']) )
{
	ViewUrlsByTag( valid_tags(rtrim(substr($g_szRequestUri,1),'/\\')) );
	exit;
}

call_user_func_array($arrCallback['hook'], $arrCallback['params']);
exit;








function AddTag( $f_szTag )
{
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


function AddUrl( $f_szUrl, $f_szTitle )
{
	global $db;

	$iUrl = $db->select_one('l_urls', 'id', array('url' => $f_szUrl, 'title' => $f_szTitle));
	if ( $iUrl ) {
		return $iUrl;
	}

	$arrInsert = array('url' => $f_szUrl, 'title' => $f_szTitle);
	$db->insert('l_urls', $arrInsert);

	return $db->insert_id();

} // END AddUrl()


function AddUrlFromPost()
{
	if ( !isset($_POST['url'], $_POST['tags']) )
	{
		return ViewUrlsByTag();
	}

	exit(_AddLink($_POST['url'], '', $_POST['tags']));

} // END AddUrlFromPost()


function _AddLink( $f_szUrl, $f_szTitle, $f_szTags )
{
	global $db;

	$szUrl	 = trim($f_szUrl);
	$arrTags = explode(' ', str_replace('/', ' ', valid_tags($f_szTags)));
	$szTitle = trim($f_szTitle);
	if ( !$arrTags || !$szUrl ) {
		return empty($_REQUEST['ajax']) ? ( chrome ? 'DUMBASS' : '<script type="text/javascript">history.go(-1);</script>' ) : 'ERROR:'.__LINE__;
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


function CopyUrlToTags( $f_iUrlId, $f_szTags = "" )
{
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
		$iAdded += (int)$db->insert('l_links', $arrInsert);
	}

	exit('OK'.$iAdded);

} // END CopyUrlToTags()


function ChangeUrlTitle( $f_iUrlId, $f_szTitle = "" )
{
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


function LocationUrlOut( $f_iUrlId )
{
	global $db;

	$szUrl = $db->select_one('l_urls', 'url', 'id = '.(int)$f_iUrlId);
	if ( $szUrl ) {
		$db->update('l_urls', '`out` = `out`+1', 'id = '.(int)$f_iUrlId);
		header('Location: '.$szUrl);
		exit;
	}

	return ViewUrlsByTag();

} // END LocationUrlOut()


function ViewUrlsByTag( $f_szTags = '' )
{
	global $g_szBaseUri, $szRequestUri, $db;

	$g_szTag = $f_szTags;

	$szWebPath = dirname($_SERVER['PHP_SELF']).'/';

	$mobile = isset($_GET['mobile']) || ( isset($_SERVER['HTTP_USER_AGENT']) && is_int(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobi')) );

	?>
<!doctype html>
<html>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta charset="utf-8" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<title>//11nk5: <?=html(trim($g_szTag))?></title>
<style>
* { margin: 0; padding: 0; }
menu, command { display: block; }
html, body {
	overflow: auto;
}
body {
	padding: 6px;
	font-family: verdana, arial;
	font-size: 120%;
}
input {
	border: solid 1px #999;
	background-color: #eee;
	cursor: default;
	background-color: #ddd;
}
input.button,
a.hand {
	cursor: pointer;
}
command {
	cursor: pointer;
}

#urls {
	list-style-type: none;
	line-height: 135%;
}
#urls:before {
	display: block;
	content: attr(data-num-urls) " results:";
	max-width: 200px;
	padding-bottom: 3px;
	border-bottom: solid 1px #bbb;
	margin-bottom: 1px;
}
#urls li a {
	font-weight: bold;
	color: midnightblue;
	text-decoration: none;
}
#urls li a:visited {
	color: #9696E9;
}
#urls li a:hover,
#urls li a:focus,
#urls li a:active {
	color: lime;
}
#urls a + span {
	color: #ccc;
}

#url_popup {
	position: absolute;
	background-color: #ccc;
	width: 100px;
	border: solid 1px #fff;
}
#url_popup:not(.show) {
	display: none;
}
#url_popup a {
	display: block;
	padding: 3px 6px;
	color: #000;
	text-decoration: none;
}
#url_popup a:not(:first-child) {
	border-top: solid 1px #fff;
}
#url_popup a:hover,
#url_popup a:focus,
#url_popup a:active {
	background-color: #ddd;
}

#notices {
	display: block;
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 0;
	text-align: center;
}
#notices > li {
	display: inline-block;
	background: #ddd;
	border-radius: 0 0 10px 10px;
	padding: 3px 10px;
	box-shadow: 0 1px 9px 0 green;
	cursor: crosshair;
}
#notices > li.fail {
	box-shadow: 0 1px 9px 0 red;
}
#notices.hide li,
#notices > li:not(:last-child) {
	display: none;
}

@media all and (min-width: 1000px) {
	body {
		font-size: 80%;
	}
	#urls a + span {
		display: none;
	}
}
</style>
</head>

<body>

<ul id="notices"></ul>

<?php if ( 0 && !$mobile ) { ?>
	<p align="center">
		<a href="javascript:var _loc=document.location;var _tags=prompt('Tags:','');var _title=prompt('Title:',document.title);if ( _title && _tags ){_url='http://hotblocks.nl/tags/index.php?url='+encodeURIComponent(_loc)+'&title='+encodeURIComponent(_title)+'&tags='+encodeURIComponent(_tags)+'';var img=document.createElement('img');img.style.display='none';img.src=_url;document.body.appendChild(img);}void(0);">Save this link to your favs to save 11nk5</a>
		<span> OR </span>
		<a href="/11nk5@hotblocks.nl.xpi" onclick="var o=this;InstallTrigger.install({'Hotblocks 11nk5 databank':{URL:o.href,IconURL:'http://www.hotblocks.nl/wallstreet_16x16.ico',Hash:'sha1:<?php echo '52ba2401bdcf6f12ed3b616b977d72fc1f08b6e4';/*sha1_file($_SERVER['DOCUMENT_ROOT'].'/11nk5@hotblocks.nl.xpi', false);*/ ?>',toString:function(){return this.URL;}}});return false;">[Firefox Add-on]</a>
		<span> OR </span>
		<a href="/hotblocks_11nk5.crx">Chrome Addon</a>
	</p>

	<br />
	<br />
<?php } ?>

<?php

$arrInTags = explode(" ", $g_szTag);
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

$arrUrls = $db->fetch($szQuery)->allObjects();
echo '<ul data-num-urls="' . count($arrUrls) . '" id="urls">' . PHP_EOL;
foreach ( $arrUrls AS $objUrl ) {
	$id = (int)$objUrl->id;

	// URL modification
	$url = $objUrl->url;
	if ( 90 < strlen($url) )
	{
		$url = substr($url, 0, 40) . "...." . substr($url, -40);
	}

	// TAGS
	$arrTags = $db->fetch_fields('
	SELECT
		l_tags.tag
	FROM
		l_tags,
		l_links
	WHERE
		(l_tags.id = l_links.tag_id) AND
		(l_links.url_id = ' . $id . ')
	ORDER BY
		tag ASC;');
	$szTags = implode(', ', $arrTags);

//	$szOutUrl = $g_szBaseUri . '/~out/' . $objUrl->id . '/';
	$szOutUrl = $objUrl->url;

	$url = $objUrl->url;

	$arrWebsite = @parse_url($objUrl->url);
	if ( is_array($arrWebsite) && isset($arrWebsite['host']) ) {
		$domain = 0 === strpos($arrWebsite['host'], 'www.') ? substr($arrWebsite['host'], 4) : $arrWebsite['host'];

		$title = trim($objUrl->title);
		$title or $title = $url;

		echo '
			<li>
				<a contextmenu="url_popup" data-id="' . $id . '" title="[' . html($domain) . '] ' . html($szTags) . '" id="url_' . $id . '" href="' . $szOutUrl . '">' . html( 0 < strlen(trim($objUrl->title)) ? trim($objUrl->title) : $url ) . '</a>
				<span>['.html($domain).'] '.html($szTags).'</span>
			</li>' .
		PHP_EOL;
	}
}

?>
</ul>

<?php if ( !$mobile ) { ?>
	<menu type="context" id="url_popup">
		<!-- <command class="default" id="cmd-open" label="open" /> -->
		<command id="cmd-copy" label="copy" />
		<command id="cmd-edit" label="edit title" />
	</menu>

	<script src="http://code.jquery.com/jquery-latest.min.js"></script>
	<script>document.URI = '<?php echo $szWebPath; ?>'</script>
	<script src="<?php echo $szWebPath; ?>11nk5.js"></script>
<?php } ?>

</body>

</html>
<?php

} // END ViewUrlsByTag()







// M I S C //

function html( $html ) {
	return htmlspecialchars($html);
}

function valid_tags( $f_szTags ) {
	return preg_replace('/\s\s+/', ' ', implode(' ', array_map('valid_tag', explode(' ', $f_szTags))));

} // END valid_tags()

function valid_tag( $f_szTag ) {
	return strtolower(preg_replace("%[^~a-zA-Z0-9\.\-\+_//]%", '', $f_szTag));

} // END valid_tag()


