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

<? if (!$mobile): ?>
	<menu type="context" id="url_popup">
		<!-- <command class="default" id="cmd-open" label="open" /> -->
		<command id="cmd-copy" label="copy" />
		<command id="cmd-edit" label="edit title" />
	</menu>

	<script src="http://code.jquery.com/jquery-latest.min.js"></script>
	<script>document.URI = '<?= $szBasePath ?>'</script>
	<script src="<?= $szBasePath ?>11nk5.js"></script>
<? endif ?>

</body>

</html>
