<?php
require('../main.php');
if (isset($_GET['cat']) && ($_GET['cat']=='')) { unset($_GET['cat']); }
if (!isset($_GET['cat'])) {
	// Inicio
	header('Location:'.BASE);
	exit;
}

$catid = $_GET['cat'];
$tags = $db->getCatsOia();
$oki = true;

if ($tags[$catid]['oia'] == 'n') {
	if (!isset($_SESSION['User'])) {
		$oki = false;
	} else {
		if (!in_array($catid, $_SESSION['User']['tags'])) {
			$oki = false;
		}
	}
	if (isset($_SESSION['Admin'])) {
		$oki = true;
	}
}

if (!$oki) {
	require(LIBPATH.'lib/HTMLHeader.php');
	echo '<h2>'.$tags[$catid]['name'].'</h2>
<div class="alert alert-error">'.Lang('monReverAuth').'</div>'."\n";
	require(LIBPATH.'lib/HTMLFooter.php');
}

$cat = $db->getLastStats($catid);
$vals = explode(',', $cat['vals']);
$errs = unserialize(stripslashes($cat['errors']));
$confs = $cat['conform'];
// Gráficos estadísticas
$data = '';
$data2 = '';
$data2title = Lang('chartErrorFreq').' ('.Lang('oiaBySitesNum').')';
$values = array(0=>'1-2', 1=>'2-3', 2=>'3-4', 3=>'4-5', 4=>'5-6', 5=>'6-7', 6=>'7-8', 7=>'8-9', 8=>'9-10');
$x = 0;
$tablescores = '';
$clas = '';
$top = array('A'=>'', 'AA'=>'', 'AAA'=>'');
$topsum = array('A'=>0, 'AA'=>0, 'AAA'=>0);
foreach ($values as $k => $v) {
	$x += $vals[$k];
	$sum = ($k==8)? 100 : round($x, 1);
	$data .= "\t\t\t".'["['.$v.']", '.$vals[$k].', '.$sum.']';
	$data .= ($k != 90)? ",\n" : '';
	$clas = ($clas=='')? ' class="even"' : '';
	$tablescores .= '<tr'.$clas.'>
<td>'.$v.'</td>
<td>'.number_format($vals[$k], 1).'</td>
<td>'.number_format($sum, 1).'</td>
</tr>'."\n";
}

$errorcount = count($errs);
if ($errorcount > 0) {
	require(LIBPATH.'lib/seeElems.php');
	// Modificar texto en elementos a y hx
	$elems['a'] = Lang('chartErrorA');
	$elems['hx'] = Lang('chartErrorHx');
	$tablerrors = '';
	$sitios=array();
	$data2 .= "\t\t\t".'["'.Lang('error').'", "'.Lang('sites').'"]';
	foreach ($errs as $k => $v) {
		list($e, $p, $sites) = explode(",", $v);
		$tablerrors[] = array(
			'level' => $elemStats[$k]['lev'],
			'sites' => $sites,
			'desc' => $elems[$k]
		);
		$sitios[] = $sites;
	}
	array_multisort($sitios, SORT_DESC, $tablerrors);
	$table='';
	$clas = '';
	$x = 0;
	$datados = array();
	foreach ($tablerrors as $v) {
		if ($x < 10) {
			$clas = ($clas=='')? ' class="even"' : '';
			$table .= '<tr'.$clas.'>
	<td class="left">'.$v['desc'].'</td>
	<td><strong>'.$v['sites'].'</strong></td>
	<td class="center">'.$v['level'].'</td>'."\n";
			$datados[] = array(
				'sites'=>$v['sites'],
				'error'=>strip_tags($v['desc'])
			);
		}
		$x++;
		$lev = $v['level'];
		if ($topsum[$lev] < 5) {
			$top[$lev] .= '<li>'.Lang('oiaTagsTopErrors', array($v['sites'], $v['desc'])).'</li>'."\n";
			$topsum[$lev]++;
		}
	}
	//rsort($datados);
	foreach ($datados as $v) {
		$data2 .= ",\n\t\t\t".'["'.$v['error'].'", '.$v['sites'].']';
	}
}

$title = $tags[$catid]['name'];
$bc = 'tag';
require(LIBPATH.'lib/HTMLHeader.php');
$scr = round($cat['score']);
$ico = resIcon($scr);

echo '<h2><span>'.Lang('catsTagSingle').':</span> '.$tags[$catid]['name'].'</h2>
<ul id="pagemenu">
<li><a href="#amostra" class="btn btn-primary" role="button">'.Lang('monSiteSample').'</a></li>
<li><a href="#scores" class="btn btn-primary" role="button">'.Lang('oiaHistoMenu').'</a></li>'."\n";
if ($errorcount > 0) {
	echo '<li><a href="#erros10" class="btn btn-primary" role="button">'.Lang('oiaErrMenu').'</a></li>
<li><a href="#erros5" class="btn btn-primary" role="button">'.Lang('oiaErrPrioMenu').'</a></li>'."\n";
}
echo '<li><a href="#ranking" class="btn btn-primary" role="button">'.Lang('oiaRankMenu').'</a></li>
</ul>
<h3 id="amostra" class="post-title">'.Lang('monSiteSample').'</h3>
<div class="alert alert-info">
<div id="hscore" class="bar'.$ico.'">'.(float)$cat['score'].'<span>'.Lang('score').'</span></div>
<ul id="summary">
<li>'.Lang('oiaBySitesNum').': <strong>'.$cat['sites'].'</strong></li>
<li>'.Lang('oiaByPagesNum').': <strong>'.$cat['pages'].'</strong></li>
<li>'.Lang('monSiteDate').': <strong>'.date(Lang('dateFormat'), strtotime($cat['date'])).'</strong></li>
</ul>
</div>'."\n";

echo '<div style="clear:both"></div>
<hr class="sep"/>
<h3 id="scores" class="post-title2">'.Lang('oiaHistograma').' ('.Lang('oiaBySites').') <button class="changer btn btn-info btn-mini" id="grafico_1">'.Lang('viewDataTable').'</button></h3>
<div id="scores-chart" class="grafico_1"></div>
<table id="scores-table" class="table table-striped grafico_1" style="display:none">
<caption>'.Lang('oiaHistoTable').' ('.Lang('oiaBySites').')</caption>
<tr>
<th>'.Lang('chartDataInt').'</th>
<th class="col20">'.Lang('chartDataFrec').' (%)</th>
<th class="col20">'.Lang('chartDataAcum').' (%)</th>
</tr>'."\n".$tablescores.'</table>'."\n";

if ($errorcount > 0) {
	echo '<hr class="sep"/>
<h3 id="erros10" class="post-title2">'.Lang('oiaErrors').' ('.Lang('oiaBySitesNum').') <button class="changer btn btn-info btn-mini" id="grafico_2">'.Lang('viewDataTable').'</button></h3>
<div id="errors-chart" class="grafico_2"></div>
<table id="errors-table" class="table table-striped grafico_2" style="display:none">
<caption>'.Lang('oiaErrorsTable').' ('.Lang('oiaBySitesNum').')</caption>
<tr>
<th class="left col50">'.Lang('chartDesc').'</th>
<th class="col20">'.Lang('sites').'</th>
<th>'.Lang('priority').'</th>
</tr>
'.$table.'</table>'."\n";

	echo '<hr class="sep"/>
<h3 id="erros5" class="post-title2">'.Lang('oiaErrorsPriority').' ('.Lang('oiaBySitesNum').')</h3>
<ul class="toplist">'."\n";
	foreach ($top as $k => $v) {
		if ($v != '') {
			echo '<li><strong>'.Lang('oiaErrPrioritiesSites', $k).'</strong>
<ol>
'.$v.'</ol></li>'."\n";
		}
	}
	echo '</ul>'."\n";
}

echo '<hr class="sep"/>
<h3 id="ranking" class="post-title2">'.Lang('oiaRanking').'</h3>
<table class="sortable">
<caption>'.Lang('oiaRankMenu').'</caption>
<tr>
<th rowspan="2" scope="col" class="mini">'.Lang('rank').'</th>
<th rowspan="2" scope="col">'.Lang('site').'</th>
<th rowspan="2" scope="col" class="mini">'.Lang('score').'</th>
<th rowspan="2" scope="col" class="mini">'.Lang('pages').'</th>
<th colspan="3" scope="col">'.Lang('oiaConformTh').'</th>
</tr>
<tr>
<th scope="col" class="mini">A</th>
<th scope="col" class="mini">AA</th>
<th scope="col" class="mini">AAA</th>
</tr>'."\n";
$query = $db->getSiteListByCat($catid);
$i=0;
while($s = $query->fetch_array(MYSQLI_ASSOC)) {
	list($A, $AA, $AAA) = explode(",", $s['conform']);
	$i++;
	echo '<tr>
<td class="center">'.$i.'</td>
<td class="left"><a href="'.BASE.'sitios/'.$s['sid'].'">'.$s['name'].'</a></td>
<td>'.$s['scores'].'</td>
<td>'.$s['pages'].'</td>
<td>'.$A.'</td>
<td>'.$AA.'</td>
<td>'.$AAA.'</td>
</tr>'."\n";
}
echo '</table>
<p>'.Lang('oiaConformP').'</p>'."\n";

require(LIBPATH.'lib/HTMLFooter.php');
?>
