<?php

/**
 * Add a LINK between TAGS and a URL
 */
function _AddLink( $f_szUrl, $f_szTitle, $f_szTags ) {
	global $db;

	$szUrl	 = trim($f_szUrl);
	$arrTags = array_filter(unaliasTags(explode(' ', str_replace('/', ' ', valid_tags($f_szTags)))));
	$szTitle = trim($f_szTitle);
	if ( !$arrTags ) {
		return 'Missing tags';
	}
	if ( !$szUrl ) {
		return 'Missing URL';
	}

	// Add url
	$iUrlId = AddUrl($szUrl, $szTitle);

	foreach ( $arrTags AS $szTag ) {
		// Add tag
		$iTagId = AddTag($szTag);

		// Insert relation
		try {
			$db->insert('l_links', array(
				'url_id'	=> $iUrlId,
				'tag_id'	=> $iTagId,
				'utc_added'	=> time(),
			));
		}
		catch ( db_exception $ex ) {}
	}

	return 'OK';

} // END _AddLink()


/**
 * Add a URL
 */
function AddUrl( $f_szUrl, $f_szTitle ) {
	global $db;

	$iUrl = $db->select_one('l_urls', 'id', array('url' => $f_szUrl));
	if ( $iUrl ) {
		return $iUrl;
	}

	$arrInsert = array('url' => $f_szUrl, 'title' => $f_szTitle);
	$db->insert('l_urls', $arrInsert);

	return $db->insert_id();

} // END AddUrl()


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
	return trim(strtolower(preg_replace("%[^~a-zA-Z0-9\.\-\+_//]%", '', $f_szTag)), '/');
}
