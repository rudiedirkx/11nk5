<?php

$bookmarks = simplexml_load_file('export_2011-08-17.xml');
$tree = array();
foreach ( $bookmarks->database->table AS $bookmark ) {
	$title = stripslashes((string)$bookmark->column[0]);
//	$title = iconv('ISO-8859-1', 'ASCII//TRANSLIT//IGNORE', $title);

	$url = (string)$bookmark->column[1];

	$tags = array_filter(explode(' ', $bookmark->column[2]));

	if ( $title && $url && $tags ) {
		sort($tags);

		eval('$tree["'.implode('"]["', $tags).'"][] = (object)array("url" => $url, "title" => $title, "tags" => $tags);');
	}
}

header('Content-type: text/plain');
printItems($tree);

function printItems($tree) {
	echo "<DL>\n";
	foreach ( $tree AS $k => $item ) {
		if ( is_int($k) ) {
			echo '<DT><a href="'.$item->url.'" title="'.implode(' ', $item->tags).'">'.$item->title."</a></DT>\n";
		}
		else {
			echo "<DT>\n";
			echo "<H3>".$k."</H3>\n";
			printItems($item);
			echo "</DT>\n";
		}
	}
	echo "</DL>\n";
}

//echo '<pre>'.htmlspecialchars(print_r($tree, 1));
