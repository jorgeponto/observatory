<?php
if (!isset($_GET['cat'])) {
	header('Location: ./');
	exit;
}
$catid = $_GET['cat'];
require_once('../main.php');
$bc = '<a href="'.BASE.'admin/">'.Lang('admin').'</a> &rArr;';
$tags = $db->getCatsOia();
if (!isset($_GET['ajax'])) {
	$cat = $tags[$catid]; // PARA QUÉ????
}

if (isset($_GET['site'])) {
	$site = $db->getSiteInfo($_GET['site']);
	$bc .= ' <a href="'.BASE.'admin/info.php?cat='.$_GET['cat'].'">'.$tags[$catid]['name'].'</a> &rArr;';

	if (isset($_GET['page'])) {
		$page = $db->getPageInfo($_GET['page']);
		$bc .= ' <a href="'.BASE.'admin/info.php?cat='.$_GET['cat'].'&amp;site='.$_GET['site'].'">'.$site['name'].'</a> &rArr;';
		require(LIBPATH.'lib/HTMLHeader.php');
		$tot = unserialize(gzuncompress($page['tot']));
		$href = '';
		define('OFF',true);
		require(LIBPATH.'lib/report.php');

	} else {
		$title = $site['name'];
		require(LIBPATH.'lib/HTMLHeader.php');
		echo '<h2><span>'.Lang('site').':</span> '.$site['name'].'</h2>'."\n";
		$pages = $db->getPageList($_GET['site']);
		$i=0;
		echo '<div id="ajax">
<table class="sortable">
<caption>'.Lang('pages').'</caption>
<tr>
<th scope="col" class="mini">#</th>
<th scope="col">'.Lang('page').'</th>
<th scope="col" class="mini">'.Lang('score').'</th>
</tr>'."\n";
		while($p = $pages->fetch_array(MYSQLI_ASSOC)) {
			$i++;
			echo '<tr>
<td class="center">'.$i.'</td>
<td class="left"><a href="'.BASE.'admin/info.php?cat='.$_GET['cat'].'&amp;site='.$_GET['site'].'&amp;page='.$p['pid'].'">'.pageTitle($p['title']).'</a>
	<a href="'.BASE.'admin/?delpage='.$_GET['cat'].'_'.$_GET['site'].'_'.$p['pid'].'" onclick="return Confirma(\''.Lang('adminDelConfirmP').'\')" title="'.Lang('adminDelSite').'"><img src="'.BASELIB.'img/delete.png" alt="'.Lang('adminDelSite').'" class="ico"/></a><br/><a href="'.$p['uri'].'" class="pageuri">'.$p['uri'].'</a>';
			if ($p['error'] != '') {
				echo '<br/><strong>'.Lang('error').': '.$p['error'].'</strong>';
			}
			echo '</td>
<td>'.$p['score'].'</td>
</tr>'."\n";
		}
		echo '</table>
	</div>'."\n";
	}
} else {
	// Categoría
	if (!isset($_GET['ajax'])) {
		$title = $tags[$catid]['name'];
		require(LIBPATH.'lib/HTMLHeader.php');
		echo '<h2><span>'.Lang('catsTagSingle').':</span> '.$tags[$catid]['name'].'</h2>'."\n";
	} else {
		header("Content-type: text/html; charset=utf-8");
	}
	echo '<div id="ajax">
<table class="sortable">
<caption>'.Lang('sites').'</caption>
<tr>
<th scope="col" class="mini">#</th>
<th scope="col">'.Lang('site').'</th>
<th scope="col" class="mini">'.Lang('score').'</th>
<th scope="col" class="mini">'.Lang('pages').'</th>
</tr>'."\n";
	$sites = $db->getSiteListByCat($_GET['cat']);
	$i=0;
	while($s = $sites->fetch_array(MYSQLI_ASSOC)) {
		$i++;
		echo '<tr>
<td class="center">'.$i.'</td>
<td class="left"><a href="'.BASE.'admin/info.php?cat='.$_GET['cat'].'&amp;site='.$s['sid'].'">'.$s['name'].'</a>
<a href="'.BASE.'admin/?opt=sites&amp;edit='.$s['sid'].'" title="'.Lang('adminEditSite').'"><img src="'.BASELIB.'img/edit.png" alt="'.Lang('adminEditSite').'" class="ico"/></a>
<a href="'.BASE.'admin/?delsite='.$s['sid'].'" onclick="return Confirma(\''.Lang('adminDelConfirm').'\')" title="'.Lang('adminDelSite').'"><img src="'.BASELIB.'img/delete.png" alt="'.Lang('adminDelSite').'" class="ico"/></a></td>
<td>'.$s['scores'].'</td>
<td>'.$s['pages'].'</td>
</tr>'."\n";
	}
	echo '</table>
</div>'."\n";
}

if (!isset($_GET['ajax'])) {
	require(LIBPATH.'lib/HTMLFooter.php');
}


?>
