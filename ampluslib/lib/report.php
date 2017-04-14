<?php
$updateform = (isset($updateform))? $updateform : '';

if (defined('OFF')) {
	$url = $tot['info']['url'];
} else {
	$url = '<a href="'.$tot['info']['url'].'">'.$tot['info']['url'].'</a>
<a href="'.$href.'&amp;e=code" title="'.Lang('viewPageTitle').'"><img src="'.BASELIB.'img/see.png" alt="'.Lang('viewPageTitle').'" width="18" height="18" class="ico" /></a>';
}

$scr = round($tot['info']['score']);
$ico = resIcon($scr);
echo '<h2><span>página:</span> '.pageTitle($tot['info']['title']).'</h2>
'.$updateform.'<h3 class="post-title">'.Lang('titleReport').'</h3>
<div class="alert alert-info">
<div id="hscore" class="bar'.$ico.'">'.(float)$tot['info']['score'].'<span>'.Lang('score').'</span></div>
<ul id="summary">
<li>'.Lang('pageUri').': '.$url.'</li>
<li>'.Lang('numElems').': '.$tot['info']['htmlTags'].'. '.Lang('pageSize').': '.Convert_Bytes($tot['info']['size']).'</li>
<li>'.Lang('monSiteDate').': '.date(Lang('dateFormat'), strtotime($tot['info']['date'])).'</li>
</ul>
</div>

<h3 class="post-title">'.Lang('resultsHx', count($tot['results'])).'</h3>'."\n";

include(LIBPATH.'lib/tests.php');
include(LIBPATH.'lib/txtTechniques.php');
include(LIBPATH.'lib/testsResults.php');
include(LIBPATH.'lib/seeElems.php');
$userdisc = array('ubli','ulow','uphy','ucog','uage');
$cantidades = array('A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0,'F'=>0);
$A = ''; $B =''; $C =''; $D = ''; $E = ''; $F = '';
$AA = ''; $BB =''; $CC =''; $DD = ''; $EE = ''; $FF = '';
$hidden = array('all', 'w3cValidator');
$table = array(
	'A'=>array('A'=>array(), 'a'=>array(), 'rows'=>0),
	'AA'=>array('AA'=>array(), 'aa'=>array(), 'rows'=>0),
	'AAA'=>array('AAA'=>array(), 'aaa'=>array(), 'rows'=>0),
	'r'=>0, 'p'=>0, 'tot'=>0);
$rownum = 1;
foreach ($tot['results'] as $ee => $r) {
	list($sco, $pond, $res, $cant) = explode("@", $r);
	$ele = $tests[$ee]['elem'];
	$tes = $tests[$ee]['test'];
	$refs = $tests[$ee]['ref'];
	$lev = $tests[$ee]['level'];
	$techfail = ($refs[0] == 'F')? Lang('relationF') : Lang('relationT');
	$nota = (LANGUAGE=='es')? ' (en inglés)' : '';
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
	$scrcrd = resIcon($sco);
	$cantidades[$scrcrd]++;
	$row = $scrcrd.$scrcrd; // Alternate color foreach result
	$$row = ($$row == '')? ' rowcolor' : '';
	$msg = formatTestText($testsResults[$ee], $cant);
	//<h4 class="res"><span class="clip pos-'.$sco.'">('.Lang('gradeAlt').' '.$sco.')</span>'.$msg.'</h4>
	$$scrcrd .= '<div class="container'.$$row.'">
<h4 class="res"><span class="clip pos-'.$sco.'">('.Lang('gradeAlt').' '.$sco.')</span> '.$msg;

	$$scrcrd .= testView($tes, $elems[$tes], $tnum, $ee);

	$$scrcrd .= '</h4>
<div class="accordion">
<h4>'.$refs.': '.$techs[$refs].'</h4>
<div>'.$txtTechniques[$refs].'
<p><a href="'.WCAG20_TEC.$refs.'.html">WCAG 2.0: '.$refs.' '.$nota.'</a></p>
<p>'.$techfail.'</p>
<ol>'."\n";
	$scstmp = explode(',', $tests[$ee]['scs']);
	foreach ($scstmp as $s) {
		$s = trim($s);
		if ($s != '') {
			$$scrcrd .= '<li>'.Lang('wcagSC').' '.$s.' <em>('.Lang('wcagLev').' '.$scs[$s][1].')</em>
<a href="'.WCAG20_UND.$scs[$s][0].'.html">'.Lang('wcagUnd').' '.$s.'</a></li>'."\n";
		}
	}
	$$scrcrd .= '</ol>
</div>
</div>
</div>'."\n"; // container

	if (strpos($lev, 'A') !== false) {
		$key=$lev;
		if ($sco==10) {
			$class='scoreok';
		} else if ($sco<6) {
			$class='scorerror';
		} else {
			$class='scorewar';
		}
	} else {
		$key=strtoupper($lev);
		if ($sco==10) {
			$class='scoreok';
		} else {
			$class='scorewar';
		}
	}

	// Scorecard
	$table[$key][$lev][] = '<tr class="'.$class.'">
<td class="left">'.$msg.'</td>
</tr>'."\n";
	$table[$key]['rows']++;
} // foreach results

// See max $cantidades
$cantemp = $cantidades;
asort($cantemp);
$maxCant = end($cantemp);
echo '<ul class="nav nav-tabs" id="myTab">'."\n";

$x = 1;
$results = '';
foreach ($cantidades as $k => $v) {
	if ($v > 0) {
		$active = ($x==1)? 'active' : '';
		echo '<li class="'.$active.'"><a href="#res'.$x.'">'.Lang('Score_'.$k).' ('.$v.')</a></li>'."\n";
		$height = '';
		if ($maxCant > $v) {
			$height = ' style="padding-bottom:'.round(($maxCant - $v)*2.5).'em"';
		}
		$tnum = ($v == 1)? Lang('seeTest') : Lang('seeTests');
		$results .= '<div class="tab-pane '.$active.'" id="res'.$x.'"'.$height.'>
<h3>'.Lang('Score_'.$k).': '.$v.' '.$tnum.'</h3>'."\n".$$k."</div>\n\n";
		$x++;
	}
}
echo '<li><a href="#scorecrd">'.Lang('modeScore').'</a></li>
</ul>
<div class="tab-content">'."\n".$results."\n";

// scorecard
$levels = array('A'=>'a','AA'=>'aa','AAA'=>'aaa');
echo '<div class="tab-pane" id="scorecrd">
<h3>'.Lang('modeScore').'</h3>
<table id="scorcard">
<caption>'.Lang('fullList').'</caption>
<tr>
<th scope="col"><abbr title="'.Lang('priority').'">Prio.</abbr></th>
<th scope="col">'.Lang('testCase').'</th>
</tr>'."\n";
foreach ($levels as $k => $v) {
	$first=true;
	if ($table[$k]['rows'] == 0) { continue; }
	if ($table[$k]['rows'] == 1) {
		$row = (isset($table[$k][$k][0]))? $table[$k][$k][0] : $table[$k][$v][0]; // Tiene que ser A o a
		echo preg_replace('@^(<tr[^>]*>)@', '$1<th scope="row">'.$k.'</th>', $row);
	} else {
		if (count($table[$k][$k]) > 0) {
			foreach ($table[$k][$k] as $key => $row) {
				if ($key == 0) {
					echo preg_replace('@^(<tr[^>]*>)@', '$1<th scope="rowgroup" rowspan="'.$table[$k]['rows'].'">'.$k.'</th>', $row);
					$first=false;
				} else {
					echo $row;
				}
			}
		}
		if (count($table[$k][$v]) > 0) {
			foreach ($table[$k][$v] as $key => $row) {
				if ($first && ($key == 0)) {
					echo preg_replace('@^(<tr[^>]*>)@', '$1<th scope="rowgroup" rowspan="'.$table[$k]['rows'].'">'.$k.'</th>', $row);
					$first=false;
				} else {
					echo $row;
				}
			}
		}
	}
}

echo '</table>
</div>
</div>'."\n";

if (isset($_SESSION['Admin'])) {
	echo '<div class="accordion">
<h4>'.Lang('admin').'</h4>
<pre>'.print_r($tot, true).'</pre>
</div>'."\n";
}

///// Funciones

function testView(&$ele, &$txt, &$tot, $id) {
	global $href;
	$txt = preg_replace('|<(/)?[^>]+>|U', '', $txt);
	$txt = str_replace('"', '', $txt);
	$r = $txt.': '.$tot;
	if (defined('OFF')) {
		return;
	}
	if (($ele=='w3cValidatorErrors') || ($ele=='dtdOld')) {
		return;
	}
	if (($tot > 0) || ($ele=='langNo') || ($ele=='langCodeNo') || ($ele=='langExtra') || ($ele=='titleChars')) {
		return '<br/><a href="'.$href.'&amp;e='.$ele.'" title="'.$r.'" role="button" class="btn btn-link">- '.Lang('seeElems').'</a>';
	}
	return;
} // testView

function formatTestA($matches) { return $matches[1]; }
function formatTestB($matches) { return $matches[2]; }
function formatTestText($txt, $a) {
	if ($a == 1) {
		$txt = preg_replace('@\([^\)]+\)@mU', '', $txt);
		$txt = preg_replace_callback('@\[([^\|]+)\|([^\]]+)\]@U', 'formatTestA', $txt);
	} else {
		$txt = str_replace(array('(',')'), '', $txt);
		$txt = preg_replace_callback('@\[([^\|]+)\|([^\]]+)\]@U', 'formatTestB', $txt);
	}
	return sprintf($txt, $a);
} //formatTestText
?>
