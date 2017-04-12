<?php

$result = '';
if (isset($_POST['siteform'])) {
	/* New site */
	$sid = $_GET['sid'];
	$shome = trim(stripslashes($_POST['shome']));
	if (!strstr($shome, '://')) {
		$shome = 'http://'.$shome;
	}
	$sname = trim(stripslashes($_POST['sname']));
	$scat = '';
	if (isset($_POST['tags'])) {
		foreach ($_POST['tags'] as $c) {
			$scat .= $c.',';
		}
	}
	if (($sname=='') || ($shome=='') || ($scat=='') || !isset($_POST['tags'])) {
		echo '<h3 class="post-title">'.Lang('error').'</h3>
<div class="alert alert-error">'.Lang('adminFormError').'</div>'."\n";
		formSite($sid, $sname, $scat, $shome, $db);
	} else {
		$pagelist = array();
		if (trim($_POST['suris']) != '') {
			urisList(trim($_POST['suris']));
		}
		if ($res = saveSite($sid, $sname, $shome, $scat, $pagelist, $db)) {
			echo $res.'<p><a href="./" class="btn btn-primary" role="button">'.Lang('back').'</a></p>'."\n";
		}
	}
} else if (isset($_POST['updateform'])) {
	/* Actualizar */
	$sid = $_GET['sid'];
	$shome = $_POST['shome'];
	$sname = stripslashes($_POST['sname']);
	$scat = '';
	if (isset($_POST['tags'])) {
		foreach ($_POST['tags'] as $c) {
			$scat .= $c.',';
		}
	}
	if (($sname=='') || ($shome=='') || ($scat=='') || !isset($_POST['tags'])) {
		echo '<h3 class="post-title">'.Lang('error').'</h3>
<div class="alert alert-error">'.Lang('adminFormError').'</div>'."\n";
		formSite($sid, $sname, $scat, $shome, $db);
	} else {
		$pagelist = array();
		if (trim($_POST['suris']) != '') {
			urisList(trim($_POST['suris']));
		}
		if ($res = saveSite($sid, $sname, $shome, $scat, $pagelist, $db)) {
			echo $res.'<p><a href="./" class="btn btn-primary" role="button">'.Lang('back').'</a></p>'."\n";
		}
	}
} else if (isset($_GET['edit'])) {
	$s = $db->getSiteInfo($_GET['edit']);
	echo $result.'<h3 class="post-title">'.Lang('adminUpdateSite').'</h3>'."\n";
	formSite($_GET['edit'], $s['name'], $s['cat'], $s['home'], $db);
} else {
	echo '<h3 class="post-title">'.Lang('adminNewSite').'</h3>'."\n";
	formSite('new', '', '', '', $db);
}

//////////////////////

function formSite($sid, $sname, $scat, $shome, $db) {
	$tags = $db->getCatsOia();
	$hidden = ($sid=='new')? 'siteform' : 'updateform';
	$pagesnum = array(10,20);
	$cat = explode(',',$scat);
	$noia = '';
	echo '<form action="./?opt=sites&amp;sid='.$sid.'" method="post" id="siteform">
<p><label for="sname">'.Lang('adminFormSite').'
<input type="text" id="sname" name="sname" size="75" style="width:60%" value="'.stripslashes($sname).'" required></label></p>
<p><label for="shome">'.Lang('adminFormUri').'
<input type="text" id="shome" name="shome" size="50" style="width:60%" value="'.$shome.'" required></label></p>
<fieldset style="background-color:#ffd">
<legend>'.Lang('catsTags').'</legend>
<ul class="admincats">'."\n";

	foreach ($tags as $k => $v) {
		$item = '<li><label for="'.$k.'"><input type="checkbox" name="tags[]" id="'.$k.'" value="'.$k.'"';
		$item .= (in_array($k, $cat))? ' checked="checked"' : '';
		$item .= '> '.$v['name'].'</label></li>'."\n";
		
		if ($v['oia']=='n') {
			$noia .= $item;
		} else {
			echo $item;
		}
	}
	echo '</ul>
</fieldset>';
	if ($noia != '') {
		echo '<fieldset>
<legend>'.Lang('catsTagsMore').'</legend>
<ul class="admincats">'."\n".$noia.'</ul>
</fieldset>'."\n";
	}
	echo '<p style="clear:both"><label for="suris"><strong>'.Lang('adminUriList').'</strong><br>
<textarea cols="100" rows="15" name="suris" id="suris" style="width:99%"></textarea></label></p>
<p class="center">
<input type="hidden" name="'.$hidden.'" value="1">
<input type="submit" class="boton" value="'.Lang('adminSubmit').'"></p>
</form>
<script type="text/javascript">
var serror="'.Lang('adminFormError').'"; var sname="'.Lang('adminFormSite').'"; var shome="'.Lang('adminFormUri').'"; var tags="'.Lang('catsTags').'";
</script>'."\n";
} // newSitemap



function urisList($uris) {
	global $pagelist;
	$lines = preg_split("/\r\n/", $uris);
	foreach ($lines as $line) {
		if (trim($line) != '') {
			
			if (strpos($line, '#') !== false) {
				$exp = explode('#', $line);
				$line = $exp[0];
			}
			
			$line = str_replace('&amp;', '&', trim($line));
			if (!strstr($line, '://')) {
				$line = 'http://'.$line;
			}
			
			$line2 = $line;
			if (substr($line, -1) == '/') {
				$line2 = rtrim($line, '/');
			}
			
			
			if (!in_array($line, $pagelist) && !in_array($line2, $pagelist)) {
				$pagelist[] = $line;
			}
		}
	}
}

function saveSite($sid, $sname, $shome, $scat, $sitemap, $db) {
	$result = '';
	if ($sid == 'new') {
		$newid = $db->addNewSite($sname, $shome, $scat);
		$result .= '<div class="alert alert-success clear">'.Lang('adminNewSiteOk').'</div>'."\n";
		if ($newsitemap = $db->addSitemapRow($newid, $sitemap)) {
			$result .= '<div class="alert alert-success">'.Lang('adminNewSitemapOk').'</div>'."\n";
		} else {
			$result .= '<div class="alert alert-error">'.Lang('adminNewSitemapErr').'</div>'."\n";
		}
	} else {
		$update = $db->updateSite($sid, $sname, $shome, $scat);
		$result .= '<div class="alert alert-success clear">'.Lang('adminUpdateSiteOk').'</div>'."\n";
		if (count($sitemap) != 0) {
			if ($upsitemap = $db->updateSitemap($sitemap, $sid, 'sitemap')) {
				$result .= '<div class="alert alert-success">'.Lang('adminPageListOk').'</div>';
			} else {
				$result .= '<div class="alert alert-error">'.Lang('adminPageListErr').'</div>';
			}
		}
	}
	return $result;
} // saveSite
?>
