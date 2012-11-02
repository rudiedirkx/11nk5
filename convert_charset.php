<?php

header('Content-type: text/html; charset="utf-8"');

require 'inc.db.php';

$titles = $db->select_fields('l_urls', 'id, title', '1');
//print_r($titles);

$db->query("SET NAMES 'utf8'");

$utf8_titles = $db->select_fields('l_urls', 'id, title', '1');
//print_r($utf8_titles);


foreach ( $titles AS $id => $title ) {
	$utf8_title = $utf8_titles[$id];
	if ( $title != $utf8_title ) {
		$title2 = @iconv("UTF-8", "ISO-8859-1//TRANSLIT", $title);
		$utf8_title2 = @iconv("UTF-8", "ISO-8859-1//TRANSLIT", $utf8_title);

		var_dump($id);
		echo 'title: ';
		var_dump($title);
		echo 'utf8_title: ';
		var_dump($utf8_title);
		echo "\n";
		echo 'title2: ';
		var_dump($title2);
		echo 'utf8_title2: ';
		var_dump($utf8_title2);
		echo "\n";

		if ( !$title2 ) {
			$good_title = $utf8_title;
		}
		else {
			$good_title = $title;
		}

		var_dump($good_title);
#		echo 'saved: ';
#		var_dump($db->update('l_urls', array('title' => $good_title), array('id' => $id)));
		echo "\n\n";
	}
}

