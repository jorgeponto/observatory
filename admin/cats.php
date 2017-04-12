<?php
$msg = '';
if (isset($_POST['cats'])) {
	unset($_POST['cats']);
	$oia = $_POST['oia'];
	unset($_POST['oia']);
	foreach ($_POST as $k => $v) {
		$exp = explode('_', $k, 2);
		$cid = $exp[1];
		$text = stripslashes(trim($v));
		$oi = (in_array($cid, $oia))? '' : 'n';
		if (($exp[0] == 'nn') && ($text != '')) {
			$db->insertCat($cid, $text, $oi);
		} else {
			$db->updateCat($cid, $text, $oi);
		}
	}
	$msg = '<div class="alert alert-success">'.Lang('catsUpdated').'</div>'."\n";
}

echo $msg;
$cats = $db->getCats();
echo '<h3 class="post-title">'.Lang('catsTags').'</h3>
<form action="./?opt=cats" method="post">
<ul style="list-style-type:none">'."\n";
$x = 0;
foreach ($cats as $k => $v) {
	echo '<li><input type="text" name="id_'.$k.'" value="'.$v['name'].'" title="ID: '.$k.'" class="bigtxt" />
	<input type="checkbox" name="oia[]" value="'.$k.'" title="'.Lang('catsInclude').'"';
	echo ($v['oia']=='')? ' checked="checked"' : '';
	echo ' /></li>'."\n";
	$x = $k;
}
$x++;
echo '<li><input type="text" name="nn_'.$x.'" value="" title="'.Lang('catsNewCat').'" class="bigtxt" />
	<input type="checkbox" name="oia[]" value="'.$x.'" title="'.Lang('catsInclude').'" /></li></li>
</ul>
<p><input type="submit" value="'.Lang('adminSubmit').'" name="cats" class="boton" /></p>
</form>'."\n";


?>
