<?php
$updateform = (isset($updateform))? $updateform : '';

if (defined('OFF')) {
	$url = $tot['info']['url'];
} else {
	$url = '<a href="'.$tot['info']['url'].'">'.$tot['info']['url'].'</a>
<a href="'.$href.'&amp;e=code" title="'.Lang('viewPageTitle').'"><img src="'.BASELIB.'img/see2.png" alt="'.Lang('viewPageTitle').'" width="24" height="24" class="ico" /></a>';
}

$scr = round($tot['info']['score']);
$ico = resIcon($scr);
echo '<div class="grid wfull top-bg">
<div style="width:60%;margin:auto;padding:1em 0em 3em 0em;">
<h2 class="relatorio">Relatório de práticas de acessibilidade Web (WCAG 2.0 do W3C)</h2>
'.$updateform.'<!-- <h3>'.Lang('page').'</h3> -->
<ul id="summary">
<li><strong>'.Lang('pageUri').':</strong> '.$url.'</li>
<li><strong>Título:</strong> '.pageTitle($tot['info']['title']).'</li>
<li><strong>'.Lang('numElems').':</strong> '.$tot['info']['htmlTags'].'</li>
<li><strong>'.Lang('pageSize').':</strong> '.Convert_Bytes($tot['info']['size']).'</li>
<li><strong>'.Lang('monSiteDate').':</strong> '.date(Lang('dateFormat'), strtotime($tot['info']['date'])).'</li>
</ul>
</div>
</div>

<h3 class="post-title" style="width:60%;margin:auto;">Sumário</h3>
<div class="grid ">
<div class="row">
<div class="c1"></div>
<div class="c2">
<div id="scoreamplus" class="bar'.$ico.'">Índice<br/><em>Access</em>Monitor <strong>'.(float)$tot['info']['score'].'</strong></div>
</div>
<div class="c7">'."\n";

include(LIBPATH.'lib/tests.php');
include(LIBPATH.'lib/testsResults.php');
include(LIBPATH.'lib/seeElems.php');

$hidden = array('w3cValidatorErrors', 'w3cValidator', 'title', 'titleNo');
$info = array('ok'=>array(), 'err'=>array(), 'war'=>array());
$infotot = array('ok'=>0, 'err'=>0, 'war'=>0, 'tot'=>0);
$infoak = array(
	'A' => array('ok'=>0, 'err'=>0, 'war'=>0),
	'AA' => array('ok'=>0, 'err'=>0, 'war'=>0),
	'AAA' => array('ok'=>0, 'err'=>0, 'war'=>0)
);
$classes = array('war'=>'cellY', 'ok'=>'cellG', 'err'=>'cellR');
$alts = array('ok'=>'alt="Prática aceitável: " title="esta prática parece estar correta"', 'err'=>'alt="Prática não aceitável: " title="esta prática não parece estar correta"', 'war'=>'alt="Prática para ver manualmente: " title="há dúvidas sobre o significado desta prática. Verifique-a manualmente"');

foreach ($tot['results'] as $ee => $r) {
	list($sco, $pond, $res, $cant) = explode("@", $r);
	$ele = $tests[$ee]['elem'];
	$tes = $tests[$ee]['test'];
	$refs = $tests[$ee]['ref'];
	$lev = $tests[$ee]['level'];
	$techfail = ($refs[0] == 'F')? Lang('relationF') : Lang('relationT');
	if (isset($tot['elems'][$tes])) {
		if ($tes=='titleOk') {
			$tnum = $tot['info']['title'];
		} else if ($tes=='lang') {
			$tnum = $tot['info']['lang'];
		} else {
			$tnum = $tot['elems'][$tes];
		}
	} else {
		$tnum = 0;
	}
	
	$msg = formatTestText($testsResults[$ee], $cant);
	$txt = cleanCode($msg);
	$result = '';
	/*
	if ($sco < 4) {
		if (strpos($lev, 'A') === false) {
			$result = 'war';
		} else {
			$result = 'err';
		}
	} else if ($sco > 8) {
		$result = 'ok';
	} else {
		$result = 'war';
	} */
	
	if ($testsColors[$ee] == 'R') {
		$result = 'err';
	} else if ($testsColors[$ee] == 'Y') {
		$result = 'war';
	} else if ($testsColors[$ee] == 'G') {
		$result = 'ok';
	}
	
	$level = strtoupper($lev);
	
	$seeall = (!in_array($ele, $hidden))? testView($ele, $elems[$ele], $tot['elems'][$ele], $ee).' ' : '';
	
	$info[$ee][$result] = array('txt'=>$txt, 'lev'=>$level, 'see'=>testView($tes, $elems[$tes], $tnum, $ee), 'seeall'=>$seeall);
	$infoak[$level][$result]++;
	
} // foreach results

echo '<table class="amplusinfonums">
<caption>Testes AccessMonitor realizados (práticas)</caption>
<thead>
<tr>
<th rowspan="2" scope="col" class="thgral">P</th>
<th colspan="4" scope="col" class="thgral">Nº práticas encontradas</th>
</tr>
<tr>
<th scope="col">aceitáveis</th>
<th scope="col">não aceitáveis</th>
<th scope="col">p/ver manualm/</th>
<th scope="col">Total</th>
</tr>
</thead>'."\n";

$tbody = '';
foreach ($infoak as $k => $v) {
	$tbody .= '<tr>
	<th scope="row">'.$k.'</th>'."\n";
	$sum = 0;
	foreach ($v as $kk => $vv) {
		$tbody .= ' <td class="'.$classes[$kk].'">';
		if ($vv > 0) {
			$tbody .= $vv.'</td>'."\n";
			$infotot[$kk] += $vv;
			$infotot['tot'] += $vv;
			$sum += $vv;
		} else {
			$tbody .= '-</td>'."\n";
		}
	}
	$tbody .= ($sum > 0)? '	<td>'.$sum.'</td>'."\n" : '	<td>&nbsp;</td>'."\n";
	$tbody .= '</tr>'."\n";
}

$tfoot = '<tr class="foot">
	<th scope="row">Total</th>'."\n";
foreach ($infotot as $k => $v) {
	$tfoot .= ($v > 0)? '	<td>'.$v.'</td>'."\n" : '	<td>&nbsp;</td>'."\n";
}
$tfoot .= '</tr>'."\n";

echo '<tfoot>
'.$tfoot.'</tfoot>
<tbody>
'.$tbody.'</tbody>
</table>
</div>
<div class="c2"></div>
</div>
</div>

<div class="grid ">
<div class="row">
<div class="c12">
<h3 class="post-title">Resultados</h3>
<table class="amplusinfo">
<caption>Práticas de acessibilidade encontradas na página pelo AccessMonitor</caption>
<tr>
<th scope="col">Prática encontrada</th>
<th scope="col">P</th>
<th scope="col">Detalhe</th>
</tr>'."\n";

foreach ($testsResults as $k => $text) {
	if (!isset($info[$k])) {
		continue;
	}
	foreach ($info[$k] as $kk => $vv) {
		echo '<tr class="row'.$testsColors[$k].'">
<td class="left"><img src="'.BASELIB.'img/ico'.$kk.'.png" '.$alts[$kk].' class="ico" />
'.$vv['txt'].'</td>
<td>'.$vv['lev'].'</td>
<td>'.$vv['seeall'].$vv['see'].'</td>
</tr>'."\n";
	}
}

echo '</table>
<br/>
</div>
</div>
</div>'."\n";


//echo '<pre>'.print_r($info, true).'</pre>';

/*
if (isset($_SESSION['Admin'])) {
	echo '<div style="width:60%;margin:auto;">
<h4>'.Lang('admin').'</h4>
<pre>'.print_r($tot, true).'</pre>
</div>'."\n";
}*/

///// Funciones

function testView(&$ele, &$txt, &$tnum, $id) {
	global $href, $tot;
	$txt = preg_replace('|<(/)?[^>]+>|U', '', $txt);
	$txt = str_replace('"', '', $txt);
	$r = $txt.': '.$tnum;
	if (defined('OFF')) {
		return;
	}
	if ($ele=='dtdOld') {
		return;
	}
	if ($ele=='w3cValidatorErrors') {
		return '<a href="'.VALIDATOR.'check?uri='.urlencode($tot['info']['url']).'&charset=%28detect+automatically%29&doctype=Inline&group=0" target="_blank" title="'.$r.'"><img src="'.BASELIB.'img/see2.png" alt="'.Lang('viewPageTitle').'" width="24" height="24" class="ico" /></a>';
	}
	/*if (($ele=='w3cValidatorErrors') || ($ele=='dtdOld')) {
		return;
	}*/
	if (($tnum > 0) || ($ele=='langNo') || ($ele=='langCodeNo') || ($ele=='langExtra') || ($ele=='titleChars')) {
		return '<a href="'.$href.'&amp;e='.$ele.'" title="'.$r.'"><img src="'.BASELIB.'img/see2.png" alt="'.Lang('viewPageTitle').'" width="24" height="24" class="ico" /></a>';
	}
	return;
} // testView

function cleanCode($txt) {
	return str_replace(array('<code>','</code>'), '', $txt);
}

function formatTestA($matches) { return $matches[1]; }
function formatTestB($matches) { return $matches[2]; }
function formatTestText($txt, $a) {
	if ($a == 1) {
		$txt = preg_replace('@\([^\)]+\)@mU', '', $txt);
		$txt = preg_replace_callback('@\[([^\|]+)\|([^\]]+)\]@U', 'formatTestA', $txt);
	} else {
		$txt = str_replace('(x)', '~x~', $txt);
		$txt = str_replace(array('(',')'), '', $txt);
		$txt = str_replace('~x~', '(x)', $txt);
		$txt = preg_replace_callback('@\[([^\|]+)\|([^\]]+)\]@U', 'formatTestB', $txt);
	}
	return sprintf($txt, $a);
} //formatTestText



?>
