<?php

/**
 * Input format:
 *
 * >
 * > Title
 * >
 * > URL
 * >
 * > Tags
 * >
 */

require 'inc.bootstrap.php';

header('Content-type: text/plain');

echo "I AM A LOCAL WEBHOOK\n\n";

$note = trim(@$_REQUEST['notes'] ?: @$_REQUEST['title']);

// debug //
$note = 'Oele

http://oele.boele.com/blaaa

oele boele bla';
// debug //

if ( preg_match('#https?://\S+#', $note, $match, PREG_OFFSET_CAPTURE) ) {
	$title = trim(substr($note, 0, $match[0][1]));
	$url = $match[0][0];
	$tags = trim(substr($note, $match[0][1] + strlen($url)));

	_AddLink($url, $title, $tags);
}
