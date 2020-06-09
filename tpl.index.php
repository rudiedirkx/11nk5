<!doctype html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta charset="utf-8" />
	<link rel="stylesheet" href="<?= $szBasePath ?>11nk5.css" />
	<title>//11nk5: <?= html(trim($g_szTag)) ?></title>
	<link rel="shortcut icon" href="<?= $szBasePath ?>favicon.ico" type="image/x-icon" />
</head>

<body>

<ul id="notices"></ul>

<ul data-num-urls="<?= count($arrUrls) ?>" id="urls">
<?php

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

		$title = trim($objUrl->title) ?: $url;

		echo '
			<li data-id="' . $id . '">
				<a contextmenu="url_popup" data-id="' . $id . '" title="[' . html($domain) . '] ' . html($szTags) . '" id="url_' . $id . '" href="' . $szOutUrl . '">' . html( 0 < strlen(trim($objUrl->title)) ? trim($objUrl->title) : $url ) . '</a>
				<span>['.html($domain).'] '.html($szTags).'</span>
			</li>' .
		PHP_EOL;
	}
}

?>
</ul>

<?php

$https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off';
$protocol = $https ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']);
$base = $protocol . '://' . $domain . str_replace('//', '/', $path . '/') . 'bookmarklet.php';
$bookmarklet = preg_replace('#[\r\n\t]#', '', str_replace('__BASE__', $base, file_get_contents(__DIR__ . '/bookmarklet.js')));

?>

<br />
<p><a href="<?= html($bookmarklet) ?>">Drag this to your bookmarks</a> or (<a href="#" onclick="prompt('Copy this:', '<?= html(addslashes($bookmarklet)) ?>'); return false">copy it</a>)</p>

<? if (!$mobile): ?>
	<menu type="context" id="url_popup">
		<menuitem id="cmd-copy" label="Copy">Copy</menuitem>
		<menuitem id="cmd-edit" label="Edit title">Edit title</menuitem>
	</menu>

	<script src="//code.jquery.com/jquery-latest.min.js"></script>
	<script>document.URI = '<?= $szBasePath ?>index.php'</script>
	<script src="<?= $szBasePath ?>11nk5.js"></script>
<? endif ?>

</body>

</html>
