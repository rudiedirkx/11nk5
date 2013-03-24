<?php

require 'inc.bootstrap.php';

$aliases = getAliasTags();
$aliases[''] = '';

if (isset($_POST['source'], $_POST['target'])) {
// echo '<pre>';
// print_r($_POST);

	// Prepare from _POST
	$inserts = array();
	foreach ($_POST['source'] AS $i => $source) {
		$target = @$_POST['target'][$i];
		if ( $source && $target ) {
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
		header('Location: aliases.php');
	}

	exit;
}

?>

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

<?php
