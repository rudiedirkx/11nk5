<?php

require_once('inc.db.php');

exit(serialize(array(
	'urls' => $db->select('l_urls'),
	'tags' => $db->select('l_tags'),
	'links' => $db->select('l_links')
)));

?>