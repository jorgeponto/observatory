<?php
require('../main.php');
if (isset($_GET['site']) && ($_GET['site']=='')) { unset($_GET['site']); }
if (!isset($_GET['site'])) {
	// Inicio
	header('Location:'.BASE);
	exit;
}

$site = $db->getSiteInfoReport($_GET['site']);

$stats = unserialize(stripslashes($site['stats']));
$vals = explode(',', $stats['values']);
// Gráfico radar
$scores = explode(',', $stats['scores']);
shuffle($scores);

$quartis = array();

// Gráficos estadísticas
$data = '';
$data2 = '';
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
	$data2title = Lang('chartErrorFreq').' ('.Lang('oiaByPagesNum').')';
	$clas = ($clas=='')? ' class="even"' : '';
	$tablescores .= '<tr'.$clas.'>
<td>'.$v.'</td>
<td>'.number_format($vals[$k], 1).'</td>
<td>'.number_format($sum, 1).'</td>
</tr>'."\n";
}
$errorcount = count($stats['errors']);
if ($errorcount > 0) {
	require(LIBPATH.'lib/seeElems.php');
	// Modificar texto en elementos a y hx
	$elems['a'] = Lang('chartErrorA');
	$elems['hx'] = Lang('chartErrorHx');
	$tablerrors = '';
	$pages=array();
	$data2 .= "\t\t\t".'["'.Lang('error').'", "'.Lang('pages').'"]';
	foreach ($stats['errors'] as $k => $v) {
		list($e, $p) = explode(",", $v);
		$tablerrors[$k] = array(
			'level' => $elemStats[$k]['lev'],
			'pages' => $p,
			'desc' => $elems[$k],
			'tot' => $e
		);
		$pages[] = $p;
		
		$quartis[$k] = array();
		$q1 = ($e > 4)? round(0.25*$e) : $e;
		$quartis[$k]['q1'] = array('x'=>$q1, 'a'=>array());
		
		$q2 = round($e/2);
		if ($q2 > $q1) {
			$quartis[$k]['q2'] = array('x'=>$q2, 'a'=>array());
			$q3 = round(0.75*$e);
			if ($q3 > $q2) {
				$quartis[$k]['q3'] = array('x'=>$q3, 'a'=>array());
				if ($q3 < $e) {
					$quartis[$k]['q4'] = array('x'=>$e, 'a'=>array());
				}
			}
		}
	}
	array_multisort($pages, SORT_DESC, $tablerrors);
	$table='';
	$clas = '';
	$x = 0;
	$datados = array();
	foreach ($tablerrors as $v) {
		if ($x < 10) {
			$clas = ($clas=='')? ' class="even"' : '';
			$table .= '<tr'.$clas.'>
	<td class="left">'.$v['desc'].'</td>
	<td><strong>'.$v['pages'].'</strong></td>
	<td class="center">'.$v['level'].'</td>'."\n";
			$datados[] = array(
				'pages'=>$v['pages'],
				'error'=>strip_tags($v['desc'])
			);
		}
		$x++;
		$lev = $v['level'];
		if ($topsum[$lev] < 5) {
			$top[$lev] .= '<li>'.Lang('oiaTopErrors', array($v['pages'], $v['desc'])).'</li>'."\n";
			$topsum[$lev]++;
		}
	}
	//rsort($datados);
	foreach ($datados as $v) {
		$data2 .= ",\n\t\t\t".'["'.$v['error'].'", '.$v['pages'].']';
	}		
}

$title = $site['name'];
$bc = Lang('site');
require(LIBPATH.'lib/HTMLHeader.php');
$scr = round($site['scores']);
$ico = resIcon($scr);
echo '<h2><span>'.Lang('site').':</span> '.$site['name'].'</h2>
<ul id="pagemenu">
<li><a href="#amostra" class="btn btn-primary" role="button">'.Lang('monSiteSample').'</a></li>
<li><a href="#scores" class="btn btn-primary" role="button">'.Lang('oiaHistoMenu').'</a></li>'."\n";
$erros='';
if ($errorcount > 0) {
	echo '<li><a href="#erros10" class="btn btn-primary" role="button">'.Lang('oiaErrMenu').'</a></li>
<li><a href="#erros5" class="btn btn-primary" role="button">'.Lang('oiaErrPrioMenu').'</a></li>'."\n";
	$erros='<li><a href="#erros" class="btn btn-primary" role="button">'.Lang('oiaErrDistMenu').'</a></li>'."\n";
}
if (count($scores) < 300) {
	echo '<li><a href="#mancha" class="btn btn-primary" role="button">'.Lang('oiaChartMenu').'</a></li>'."\n";
}
echo $erros.'</ul>
<h3 id="amostra" class="post-title">'.Lang('monSiteSample').'</h3>
<div class="alert alert-info">
<div id="hscore" class="bar'.$ico.'">'.(float)$site['scores'].'<span>'.Lang('score').'</span></div>
<ul id="summary">
<li>'.Lang('pages').': <strong>'.$site['pages'].'</strong></li>
<li>'.Lang('monSiteDate').': <strong>'.date(Lang('dateFormat'), strtotime($site['timestats'])).'</strong></li>
</ul>
</div>'."\n";

echo '<div style="clear:both"></div>
<hr class="sep"/>
<h3 id="scores" class="post-title2">'.Lang('oiaHistograma').' ('.Lang('oiaByPages').') <button class="changer btn btn-info btn-mini" id="grafico_1">'.Lang('viewDataTable').'</button></h3>
<div id="scores-chart" class="grafico_1"></div>
<table id="scores-table" class="table table-striped grafico_1" style="display:none">
<caption>'.Lang('oiaHistoTable').' ('.Lang('oiaByPages').')</caption>
<tr>
<th>'.Lang('chartDataInt').'</th>
<th class="col20">'.Lang('chartDataFrec').' (%)</th>
<th class="col20">'.Lang('chartDataAcum').' (%)</th>
</tr>'."\n".$tablescores.'</table>'."\n";

if ($errorcount > 0) {
	echo '<hr class="sep"/>
<h3 id="erros10" class="post-title2">'.Lang('oiaErrors').' ('.Lang('oiaByPagesNum').') <button class="changer btn btn-info btn-mini" id="grafico_2">'.Lang('viewDataTable').'</button></h3>
<div id="errors-chart" class="grafico_2"></div>
<table id="errors-table" class="table table-striped grafico_2" style="display:none">
<caption>'.Lang('oiaErrorsTable').' ('.Lang('oiaByPagesNum').')</caption>
<tr>
<th class="left col50">'.Lang('chartDesc').'</th>
<th class="col20">'.Lang('pages').'</th>
<th>'.Lang('priority').'</th>
</tr>
'.$table.'</table>'."\n";

	echo '<hr class="sep"/>
<h3 id="erros5" class="post-title2">'.Lang('oiaErrorsPriority').' ('.Lang('oiaByPagesNum').')</h3>
<ul class="toplist">'."\n";
	foreach ($top as $k => $v) {
		if ($v != '') {
			echo '<li><strong>'.Lang('oiaErrPriorities', $k).'</strong>
<ol>
'.$v.'</ol></li>'."\n";
		}
	}
	echo '</ul>'."\n";
}

if (count($scores) < 300) {
	echo '<hr class="sep"/>
<h3 id="mancha" class="post-title2">'.Lang('oiaChartGoogle').'</h3>
<p class="center"><img src="http://chart.apis.google.com/chart?chxr=0,0,10&amp;chxt=y&amp;chs=300x300&amp;cht=r&amp;chco=CC0000&amp;chds=0,10&amp;chd=t:'.implode(',', $scores).'&amp;chm=B,FF000080,0,0,0" width="300" height="300" alt=""/></p>'."\n";
}

if ($errorcount > 0) {
	$pages = $db->getPageListTot($_GET['site']);
	while($p = $pages->fetch_array(MYSQLI_ASSOC)) {
		$tot = unserialize(gzuncompress($p['tot']));
		foreach ($tablerrors as $k => $v) {
			$n=0;
			if (array_key_exists($k, $tot['elems'])) {
				if (($k=='langNo') || ($k=='langCodeNo') || ($k=='langExtra') || ($k=='titleNo')) {
					$n = 1;
				} else {
					$n = $tot['elems'][$k];
				}
			} else {
				if (($k=='a') || ($k=='hx')) {
					if (!array_key_exists($k, $tot['elems'])) {
						$n = 1;
					}
				}
			}
			if ($n==0) {
				continue;
			}
				
			if ($n <= $quartis[$k]['q1']['x']) {
				$q = 'q1';
			} else {
				if (isset($quartis[$k]['q2']) && ($v <= $quartis[$k]['q2']['x'])) {
					$q = 'q2';
				} else {
					if (isset($quartis[$k]['q3']) && ($v <= $quartis[$k]['q3']['x'])) {
						$q = 'q3';
					} else {
						if (isset($quartis[$k]['q4'])) {
							$q = 'q4';
						} else {
							continue;
						}
						
					} // tres
				} // dos
			} // uno
			$quartis[$k][$q]['a'][] = $n;
		}
	}
	$qs = (array('q1', 'q2', 'q3', 'q4'));
	echo '<hr class="sep"/>
<h3 id="erros" class="post-title2">'.Lang('oiaErrDistribut').'</h3>
<table class="table table-striped">
<caption>'.Lang('oiaErrDistTable').'</caption>
<tr>
<th>'.Lang('oiaErrDistThA').'</th>
<th>'.Lang('oiaErrDistThB').'</th>
<th>'.Lang('oiaErrDistThC').'</th>
<th>'.Lang('oiaErrDistThD').'</th>
<th>'.Lang('priority').'</th>
</tr>'."\n";
	
	foreach ($tablerrors as $k => $v) {
		echo '<tr>
	<td class="left">'.$v['desc'].'</td>
	<td><strong>'.$v['tot'].'</strong></td>
	<td>'.$v['pages'].'</td>
	<td class="left"><ul>'."\n";
		foreach ($qs as $q) {
			if (isset($quartis[$k][$q])) {
				$pags = count($quartis[$k][$q]['a']);
			} else {
				continue;
			}
			if ($pags == 0) {
				continue;
			}
			if ($pags == 1) {
				echo '<li>'.Lang('oiaErrQuartisA', $quartis[$k][$q]['a'][0]).'</li>'."\n";
			} else {
				sort($quartis[$k][$q]['a'], SORT_NUMERIC);
				$first = $quartis[$k][$q]['a'][0];
				$last = end($quartis[$k][$q]['a']);
				if ($first == $last) {
					echo '<li>'.Lang('oiaErrQuartisB', array($first, $pags)).'</li>'."\n";
				} else {
					echo '<li>'.Lang('oiaErrQuartisC', array($first, $last, $pags)).'</li>'."\n";
				}
			}
		}
		echo '</ul></td>
	<td class="center">'.$v['level'].'</td>'."\n";
	}
	echo '</table>'."\n";
}

// http://www.acessibilidade.gov.pt/observatorio/sitios/13

//echo '<pre style="display:none">'.print_r($quartis, true).'</pre>';

require(LIBPATH.'lib/HTMLFooter.php');

?>
