<?php

require 'inc.bootstrap.php';

header('Content-type: application/javascript');

$message = 'Nothing to save...';
if ( isset($_REQUEST['url'], $_REQUEST['title'], $_REQUEST['tags']) ) {
	_AddLink($_REQUEST['url'], $_REQUEST['title'], $_REQUEST['tags']);
	$message = 'Link saved!';
}

?>

alert('<?= addslashes($message) ?>');
document.querySelector('.loading-11nk5').remove();
