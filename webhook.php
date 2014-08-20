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

$body = trim(@$_REQUEST['title'] . "" . @$_REQUEST['notes']);

/* DEBUG *
$body = '
http://oele.boele.com/blaaa

oele boele bla';
/* DEBUG */

if ( preg_match('#https?://\S+#', $body, $match, PREG_OFFSET_CAPTURE) ) {
	$url = $match[0][0];
	$title = trim(substr($body, 0, $match[0][1])) ?: $url;
	$tags = trim(substr($body, $match[0][1] + strlen($url)));
// var_dump($title, $url, $tags);
// exit;

	_AddLink($url, $title, $tags);
}

exit("Not good ennough input...");
