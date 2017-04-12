<?php
$msg = '';

if (isset($_POST['option'])) {
	$name = trim($_POST['name']);
	$pass = trim($_POST['pass']);
	$tags = $_POST['tags'];
	if ($_POST['option'] == 'new') {
		$db->insertUser($name, $pass, $tags);
		$msg = '<div class="alert alert-success">'.Lang('monUserAdded').'</div>'."\n";
	} else {
		$db->updateUser($name, $pass, $tags, $_POST['option']);
		$msg = '<div class="alert alert-success">'.Lang('monUserUpdated').'</div>'."\n";
	}
}

echo $msg;

if (isset($_GET['uid'])) {
	$uid = intval($_GET['uid']);
	$u = $db->getUsersId($uid);
	$cats = $db->getCats();
	$tags = unserialize($u['cats']);
	echo '<h3 class="post-title">'.Lang('monUserUpdate').'</h3>';
	formUser($u['name'], $u['pass'], $cats, $tags, $uid);
} else {
	echo '<h3 class="post-title">'.Lang('users').'</h3>
<ul style="list-style-type:none">'."\n";
	$users = $db->getUsers();
	$cats = $db->getCats();
	foreach ($users as $k => $v) {
		echo '<li><a href="./?opt=users&amp;uid='.$k.'" title="'.$v['pass'].'">'.$v['name'].'</a></li>'."\n";
	}
	echo '</ul>
<hr/>
<h3 class="post-title">'.Lang('monUserNew').'</h3>'."\n";
	formUser('', '', $cats, array(), 'new');
}

//////////////////////////////////

function formUser($name, $pass, $cats, $tags, $opt) {
	echo '<form action="./?opt=users" method="post">
<p><label for="name">'.Lang('monUserName').'
<input type="text" id="name" name="name" size="20" value="'.stripslashes($name).'" required /></label></p>
<p><label for="pass">'.Lang('userPass').'
<input type="text" id="pass" name="pass" size="10" value="'.stripslashes($pass).'" required /></label></p>
<h3>'.Lang('catsTags').'</h3>
<ul style="list-style-type:none">';
	foreach ($cats as $k => $v) {
		echo '<li><label for="t'.$k.'"><input type="checkbox" name="tags[]" id="t'.$k.'" value="'.$k.'"';
		echo (in_array($k, $tags))? ' checked="checked"' : '';
		echo ' /> '.$v['name'].'</label></li>'."\n";
	}
	echo '</ul>
<p><input type="hidden" name="option" value="'.$opt.'" />
<input type="submit" class="boton" value="'.Lang('adminSubmit').'"></p>
</form>'."\n";
}

?>
