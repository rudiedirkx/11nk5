<?php

require 'inc.bootstrap.php';

$aliases = getAliasTags();
$aliases[''] = '';

// Save aliases
if (isset($_POST['source'], $_POST['target'])) {
// echo '<pre>';
// print_r($_POST);

	// Prepare from _POST
	$inserts = array();
	foreach ($_POST['source'] AS $i => $source) {
		$target = @$_POST['target'][$i];
		if ( $source && $target ) {
			$exists = $db->select_one('l_tags', 'id', array('tag' => $source));
			if ( $exists ) {
				exit('<p>&quot;' . html($source) . "&quot; is an existing tag. You can't create it as an alias!</p>");
			}

			$targetTagId = $db->select_one('l_tags', 'id', array('tag' => $target));
			if ( $targetTagId ) {
				$inserts[] = array(
					'alias' => $source,
					'tag_id' => $targetTagId,
				);
			}
			else {
				exit('<p>&quot;' . html($target) . "&quot; isn't a valid target tag. It no existo!</p>");
			}
		}
	}
// print_r($inserts);

	// Update db
	if ( $inserts ) {
		$db->begin();

		$db->delete('l_aliases', '1');

		foreach ( $inserts AS $insert ) {
			try {
				$db->insert('l_aliases', $insert);
			}
			catch ( db_exception $ex ) {
				$db->rollback();
				exit('<pre>' . $ex . '</pre>');
			}
		}

		$db->commit();
		header('Location: aliases.php?_' . rand(0, 999));
	}

	exit;
}

// Move between tags
else if ( isset($_POST['from'], $_POST['to']) ) {
	$from = $_POST['from'];
	$to = $_POST['to'];
	$message = '';

	$fromTagId = $db->select_one('l_tags', 'id', array('tag' => $from));
	if ( $fromTagId ) {
		$toTagId = AddTag($to);

		$db->begin();
		try {
			// Copy links
			$db->execute('INSERT INTO l_links SELECT url_id, ?, `utc_added` FROM l_links l WHERE tag_id = ? AND NOT EXISTS (SELECT * FROM l_links WHERE url_id = l.url_id AND tag_id = ?)', array($toTagId, $fromTagId, $toTagId));
			$movedTags = $db->affected_rows();
			// Delete old links
			$db->execute('DELETE FROM l_links WHERE tag_id = ?', array($fromTagId));
			// Delete old tag
			$db->execute('DELETE FROM l_tags WHERE id = ?', array($fromTagId));

			$db->commit();

			$message = 'Moved+' . $movedTags . '+tags';

			// Add alias
			try {
				$db->insert('l_aliases', array('alias' => $from, 'tag_id' => $toTagId));
			}
			catch ( db_exception $ex ) {
				// Doesn't matter
			}
		}
		catch ( db_exception $ex ) {
			$db->rollback();
			echo '<pre>&gt; ' . $ex->query . ':<br><br>' . $ex->getMessage() . '</pre>';
			throw $ex;
			exit;
		}
	}

	header('Location: aliases.php?message=' . $message . '&_' . rand(0, 999));
	exit;
}

?>

<?if (@$_GET['message']):?>
	<p style="font-weight: bold"><?= $_GET['message'] ?></p>
<?endif?>

<h1>Aliases</h1>

<form method="post" action>
	<table border=1 cellspacing=0 cellpadding=5>
		<thead>
			<tr>
				<th>Source (anything)</th>
				<th></th>
				<th>Target (exists)</th>
			</tr>
		</thead>
		<tbody>
			<?foreach ($aliases as $source => $target):?>
				<tr>
					<td><input name="source[]" value="<?=html($source)?>" /></td>
					<td> &nbsp; =&gt; &nbsp; </td>
					<td><input name="target[]" value="<?=html($target)?>" /></td>
				</tr>
			<?endforeach?>
		</tbody>
	</table>
	<p><input type="submit" /></p>
</form>

<h2>Move between tags &amp; create alias</h3>

<form method="post" action>
	<p>From: <input name="from" placeholder="I am being used incorrectly" /> (this tag exists and will be deleted)</p>
	<p>To: <input name="to" placeholder="I am perfect for this job" /> (this tag exists or will be created)</p>
	<p><input type="submit" /></p>
</form>

<?php
