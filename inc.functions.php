<?php

function AddTag( $f_szTag ) {
	global $db;

	$f_szTag = trim(strtolower($f_szTag));
	$iTag = $db->select_one('l_tags', 'id', 'tag = ? ORDER BY id ASC', array($f_szTag));

	if ( $iTag ) {
		return $iTag;
	}

	$arrInsert = array('tag' => $f_szTag);
	$db->insert('l_tags', $arrInsert);

	return $db->insert_id();

}

function unaliasTags( $tags ) {
	$aliases = getAliasTags($tags);
	if ( $aliases ) {
		$tags = array_flip(array_merge($tags, $aliases));
		foreach ( $aliases AS $alias => $tag ) {
			unset($tags[$alias]);
		}
		$tags = array_values(array_flip($tags));
	}

	return $tags;
}

function getAliasTags( $input = null ) {
	global $db;
	$sql = 'SELECT a.alias, t.tag FROM l_aliases a, l_tags t WHERE a.tag_id = t.id';
	if ( $input ) {
		$sql .= $db->replaceholders(' AND a.alias IN (?)', array($input));
	}
	$sql .= ' ORDER BY a.alias';
	return $db->fetch_fields($sql);
}

function html( $html ) {
	return htmlspecialchars($html, ENT_COMPAT, 'UTF-8');
}

function valid_tags( $f_szTags ) {
	return implode(' ', array_filter(array_map('valid_tag', explode(' ', $f_szTags))));
}

function valid_tag( $f_szTag ) {
	return strtolower(preg_replace("%[^~a-zA-Z0-9\.\-\+_//]%", '', $f_szTag));
}
