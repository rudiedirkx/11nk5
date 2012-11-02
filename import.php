<?php

exit;

require_once('inc.db.php');

$arrLinks = unserialize(base64_decode(file_get_contents('export.txt')));
//print_r($arrLinks);

foreach ( $arrLinks AS $szTag => $arrUrls ) {
	$x = $db->fetchExAll('SELECT id FROM l_tags WHERE tag = \''.$szTag.'\';');
	if ( 0 < count($x) ) {
		$iTagId = (int)$x[0]['id'];
	}
	else {
		$db->insert('l_tags', array('tag' => $szTag));
		$iTagId = (int)$db->lastInsertId();
	}
	foreach ( $arrUrls AS $szUrl ) {
		$x = $db->fetchExAll('SELECT id FROM l_urls WHERE url = \''.addslashes(stripslashes(stripslashes($szUrl))).'\';');
		if ( 0 < count($x) ) {
			$iUrlId = (int)$x[0]['id'];
		}
		else {
			$db->insert('l_urls', array('url' => $szUrl, 'title' => $szUrl));
			$iUrlId = (int)$db->lastInsertId();
		}
		$x = $db->fetchExAll('SELECT count(1) AS n FROM l_links WHERE tag_id = '.$iTagId.' AND url_id = '.$iUrlId.';');
		if ( !$x[0]['n'] ) {
			$db->insert('l_links', array('tag_id' => $iTagId, 'url_id' => $iUrlId));
		}
	}
}

?>