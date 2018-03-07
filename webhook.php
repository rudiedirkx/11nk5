<?php

/**
 * Input format:
 *
 * >
 * > Title (optional)
 * >
 * > URL
 * >
 * > Tags
 * >
 */

require 'inc.bootstrap.php';

header('Content-type: text/plain');

$body = trim(@$_REQUEST['title'] . "\n\n" . @$_REQUEST['notes']);

if ( preg_match('#https?://\S+#', $body, $match, PREG_OFFSET_CAPTURE) ) {
	$url = $match[0][0];
	$title = trim(substr($body, 0, $match[0][1])) ?: $url;
	$tags = trim(substr($body, $match[0][1] + strlen($url)));

	$rsp = _AddLink($url, $title, $tags);
	if ( substr($rsp, 0, 2) === 'OK' ) {
		exit($rsp);
	}

	header('HTTP/1.1 500 Error');
	exit($rsp);
}

header('HTTP/1.1 400 Error');
exit("Bad input");
