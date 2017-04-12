<?php
require('../main.php');

if (!isset($_SESSION['Admin']) && isset($_COOKIE['Admin'])) { $_SESSION['Admin'] = 1; }

$error = '';
if (isset($_POST['login'])) {
	if ($_POST['login'] == ADMINPW) {
		$_SESSION['Admin'] = 1;
		setcookie('Admin', 1, time()+604800, '/'); // 1 week
	} else {
		$error .= Lang('adminPwErr');
		if (isset($_SESSION['Admin'])) {
			unset($_SESSION['Admin']);
		}
	}
}
if (isset($_GET['log']) && ($_GET['log'] == 'out')) {
	unset($_SESSION['Admin']);
	setcookie('Admin', '', time()-3600, '/');
}
if (!isset($_SESSION['Admin'])) {
	require(LIBPATH.'lib/HTMLHeader.php');
	echo '<h2>'.Lang('admin').'</h2>'."\n";
	HTMLoginForm($error);
	require(LIBPATH.'lib/HTMLFooter.php');
}

// OK
$include = '';
if (isset($_GET['opt'])) {
	if ($_GET['opt'] == 'cats') {
		$include = 'cats.php';
	} else if ($_GET['opt'] == 'sites') {
		$include = 'sites.php';
	} else if ($_GET['opt'] == 'pages') {
		$include = 'pages.php';
	} else if ($_GET['opt'] == 'users') {
		$include = 'users.php';
	}
}

if (isset($_GET['delpage'])) {
	list ($dcat, $dsit, $dpag) = explode('_', $_GET['delpage']);
	$error = $db->deletePage($dpag);
	if ($error == '') {
		updateSite($dsit, $db);
		$error = '<div class="alert alert-success">'.Lang('adminDelPageOk').' (<a href="'.BASE.'admin/info.php?cat='.$dcat.'&amp;site='.$dsit.'">'.Lang('info').'</a>)</div>'."\n";
	}
}

if (isset($_GET['delsite'])) {
	$delete = $db->deleteSite($_GET['delsite']);
	if ($delete == '') {
		$error = '<div class="alert alert-success">'.Lang('adminDelSiteOk', $_GET['delsite']).'</div>'."\n";
	} else {
		$error = '<div class="alert alert-error">'.Lang('adminDelSiteErr', $_GET['delsite']).'</div>'."\n".$delete;
	}
}

$search = (isset($_POST['search']))? trim($_POST['search']) : '';

$bc = ($include=='')? '' : '<a href="'.BASE.'admin/">'.Lang('admin').'</a> &rArr;';
require(LIBPATH.'lib/HTMLHeader.php');

echo '<h2>'.Lang('admin').'</h2>
<ul id="menuadmin">
<li><a href="./" id="ainicio" class="btn btn-primary" role="button">'.Lang('startPage').'</a></li>
<li><a href="./?opt=sites" class="btn btn-primary" role="button">'.Lang('adminAddSite').'</a></li>
<li><a href="./?opt=cats" class="btn btn-primary" role="button">'.Lang('catsTags').'</a></li>
<li><a href="./?opt=users" class="btn btn-primary" role="button">'.Lang('users').'</a></li>'."\n";
echo (isset($_SESSION['Admin']))? '<li><a href="./?log=out" class="btn btn-primary" role="button">'.Lang('adminLogout').'</a></li>'."\n" : '';
echo '</ul>
<form action="./" method="post" id="fsearch">
<div><input type="text" id="search" title="'.Lang('search').'" name="search" value="" /> 
<input type="submit" class="boton" value="'.Lang('adminSubmit').'" /></div>
</form>'.$error."\n";
if ($include != '') {
	require(ABSPATH.'admin/'.$include);
	require(LIBPATH.'lib/HTMLFooter.php'); // exit
}

/* Buscar? */
if ($search != '') {
	echo '<h3 class="post-title">'.Lang('searchRes').'</h3>'."\n";
	//$search = preg_replace('/[^a-zA-Z0-9\s]+/', '', $search);
	$search = str_replace(array('"',"'",'?','%','=','*',','), '', $search);
	$query = $db->getSiteSearch($search);
	if ($query->num_rows < 1) {
		echo '<p>'.Lang('searchFail', $search).'</p>'."\n";
	} else {
		$arg = array($search, $query->num_rows);
		$tags = $db->getCatsOia();
		echo '<p>'.Lang('searchOk', $arg).'</p>
<table class="sortable">
<caption>'.Lang('sites').'</caption>
<tr>
<th scope="col" class="mini">#</th>
<th scope="col">'.Lang('site').'</th>
<th scope="col" class="mini">'.Lang('score').'</th>
<th scope="col" class="mini">'.Lang('pages').'</th>
<th scope="col">'.Lang('diretorio').'</th>
</tr>'."\n";
		$i=0;
		while($r = $query->fetch_array(MYSQLI_ASSOC)) {
			if ($i < 25) {
				$i++;
			} else {
				echo '<tr><td colspan="5" class="left">(...)</td></tr>'."\n";
				break;
			}
			$cat = explode(',', $r['cat']);
			$result = '';
			$categs = '';
			foreach ($tags as $k => $v) {
				if (in_array($k, $cat)) {
					if ($result == '') {
						$result .= '<tr>
<td class="center">'.$i.'</td>
<td class="left"><a href="'.BASE.'admin/info.php?cat='.$k.'&amp;site='.$r['sid'].'">'.$r['name'].'</a>
<a href="'.BASE.'admin/?opt=sites&amp;edit='.$r['sid'].'" title="'.Lang('adminEditSite').'"><img src="'.BASELIB.'img/edit.png" alt="'.Lang('adminEditSite').'" class="ico"/></a>
<a href="'.BASE.'admin/?delsite='.$r['sid'].'" onclick="return Confirma(\''.Lang('adminDelConfirm').'\')" title="'.Lang('adminDelSite').'"><img src="'.BASELIB.'img/delete.png" alt="'.Lang('adminDelSite').'" class="ico"/></a><br/><a href="'.$r['home'].'" class="pageuri">'.$r['home'].'</a></td>
<td>'.$r['scores'].'</td>
<td>'.$r['pages'].'</td>
<td class="left">'."\n";
					}
					$categs .= '<a href="'.BASE.'admin/info.php?cat='.$k.'" style="display:block">'.$v['name'].'</a>'."\n";
				}
			}
			echo $result.$categs.'</td>
</tr>'."\n";
		}
		echo '</table>'."\n";
		require(LIBPATH.'lib/HTMLFooter.php');
	}
}

/* Start Page */
$listsites = $db->getSiteList();
$sitemap = '';
$autobus = '';
$undefined = '';
$stats = '';

$tags = $db->getCatsOia();
$cats = array();
foreach ($tags as $k => $v) {
	$cats[$k] = 0;
}
$errors = '';

while($s = $listsites->fetch_array(MYSQLI_ASSOC)) {
	$icons = ' <a href="'.BASE.'admin/?opt=sites&amp;edit='.$s['sid'].'" title="'.Lang('adminEditSite').'"><img src="'.BASELIB.'img/edit.png" alt="'.Lang('adminEditSite').'" class="ico"/></a>
<a href="'.BASE.'admin/?delsite='.$s['sid'].'" onclick="return Confirma(\''.Lang('adminDelConfirm').'\')" title="'.Lang('adminDelSite').'"><img src="'.BASELIB.'img/delete.png" alt="'.Lang('adminDelSite').'" class="ico"/></a>';
	if ($s['status'] == 'OK') {
		if ($s['timestats'] == '0000-00-00 00:00:00') {
			$stats = Lang('statsLack');
		} else {
			if (date('n', strtotime($s['timestats'])) != HOY) {
				$stats = Lang('statsDateDiff');
			}
		}
		$catlist = explode(',', $s['cat']);
		foreach ($catlist as $c) {
			if (($c == '')) {
				continue;
			}
			if (array_key_exists($c, $tags)) {
				@$cats[$c]++;
			} else {
				$errors .= '<li>'.$s['name'].$icons.'</li>'."\n";
			}
		}
	} else {
		$tmp = '<li>'.$s['name'].$icons.'</li>'."\n";
		if ($s['status'] == 'sitemap') {
			$sitemap .= $tmp;
		} else if ($s['status'] == 'autobus') {
			$autobus .= $tmp;
		} else {
			$undefined .= $tmp;
		}
	}
}
if ($sitemap != '') {
	echo '<h3 class="post-title">'.Lang('adminSitemap').'</h3>
<ul>'."\n".$sitemap.'</ul>
<div class="alert alert-error"><a href="'.BASE.'admin/sitemaps.php">'.Lang('adminSitemapMsg').'</a></div>'."\n";
}
if ($autobus != '') {
	echo '<h3 class="post-title">'.Lang('adminAutobus').'</h3>
<ul>'."\n".$autobus.'</ul>
<div class="alert alert-error"><a href="'.BASE.'admin/autobus.php">'.Lang('adminAutobusMsg').'</a></div>'."\n";
}
if ($undefined != '') {
	echo '<h3 class="post-title">'.Lang('adminUndef').'</h3>
<ul>'."\n".$undefined.'</ul>'."\n";
}
if ($sitemap.$autobus.$undefined == '') {
	if ($stats != '') {
		echo '<h3 class="post-title">'.Lang('statistics').'</h3>
<div class="alert alert-error"><a href="'.BASE.'admin/stats.php?opt=sites">'.Lang('statsUpdate').'</a></div>'."\n";
	}
}
$query = $db->getCatsToStats();
$warning = '';
$listcat = '';
$listcat2 = '';
$listnum = 1;
$listnum2 = 1;
while($c = $query->fetch_array(MYSQLI_ASSOC)) {
	$cat = $c['code'];
	
	if (!array_key_exists($cat, $tags)) {
		continue;
	}
	
	if (array_key_exists($cat, $cats)) {
		if ($c['sites'] != $cats[$cat]) {
			$warning = Lang('statsVerify');
		}
		if ($c['sites'] > 0) {
			if ($tags[$cat]['oia']=='n') {
				$listcat2 .= '<tr><td class="center">'.$listnum2.'</td><td class="left"><a href="'.BASE.'admin/info.php?cat='.$c['code'].'">'.$tags[$cat]['name'].'</a></td><td>'.$c['score'].'</td><td>'.$c['sites'].'</td></tr>'."\n";
				$listnum2++;
			} else {
				$listcat .= '<tr><td class="center">'.$listnum.'</td><td class="left"><a href="'.BASE.'admin/info.php?cat='.$c['code'].'">'.$tags[$cat]['name'].'</a></td><td>'.$c['score'].'</td><td>'.$c['sites'].'</td></tr>'."\n";
				$listnum++;
			}
		}
		unset($cats[$cat]);
	} else {
		if ($tags[$cat]['oia']=='n') {
			$listcat2 .= '<tr><td class="center">'.$listnum2.'</td><td class="left"><a href="'.BASE.'admin/info.php?cat='.$cat.'">'.$cat.'</a></td><td>'.Lang('error').'</td><td>'.$c['sites'].'</td></tr>'."\n";
			$listnum2++;
		} else {
			$listcat .= '<tr><td class="center">'.$listnum.'</td><td class="left"><a href="'.BASE.'admin/info.php?cat='.$cat.'">'.$cat.'</a></td><td>'.Lang('error').'</td><td>'.$c['sites'].'</td></tr>'."\n";
			$listnum++;
		}
	}
}

if (count($cats) > 0) {
	$warning = Lang('statsDateDiff');
	foreach ($cats as $k => $v) {
		$listcat2 .= '<tr><td class="center">'.$listnum2.'</td><td class="left"><a href="'.BASE.'admin/info.php?cat='.$k.'">'.$k.'</a> (???)</td><td>(???)</td><td>(???)</td></tr>'."\n";
		$listnum2++;
	}
}

if ($warning != '') {
	echo '<p class="center">'.$warning.'</p>
<div class="alert alert-error"><a href="'.BASE.'admin/stats.php?opt=sites">'.Lang('statsUpdate').'</a></div>'."\n";
}

echo '<table class="sortable">
<caption>'.Lang('adminCats').' '.Lang('oiaCatsOia').'</caption>
<tr>
<th scope="col" class="mini">#</th>
<th scope="col">'.Lang('diretorio').'</th>
<th scope="col" class="mini">'.Lang('score').'</th>
<th scope="col" class="mini">'.Lang('sites').'</th>
</tr>'."\n".$listcat.'</table>'."\n";
if ($listcat2 != '') {
	echo '<table class="sortable" style="margin-top:2.5em">
<caption>'.Lang('adminCats').'</caption>
<tr>
<th scope="col" class="mini">#</th>
<th scope="col">'.Lang('diretorio').'</th>
<th scope="col" class="mini">'.Lang('score').'</th>
<th scope="col" class="mini">'.Lang('sites').'</th>
</tr>'."\n".$listcat2.'</table>'."\n";
}

if ($errors != '') {
	echo '<h3 class="post-title">'.ucfirst(Lang('errors')).'</h3>
<ul>'."\n".$errors.'</ul>'."\n";
}

require(LIBPATH.'lib/HTMLFooter.php');
?>
