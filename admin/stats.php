<?php
require('../main.php');
$title = Lang('statistics');
$bc = '<a href="'.BASE.'admin/">'.Lang('admin').'</a> &rArr;';
require(LIBPATH.'lib/HTMLHeader.php');
echo '<h2>'.Lang('statistics').'</h2>'."\n";

if (!isset($_GET['opt'])) {
	require(LIBPATH.'lib/HTMLFooter.php');
}

require(LIBPATH.'lib/seeElems.php');
$k1 = date('Y'); // año 2015
$k2 = date('n'); // mes 1-12
$k3 = date('j'); // día 1-31

if ($_GET['opt'] == 'sites') {
	$values = array(1=>12, 2=>23, 3=>34, 4=>45, 5=>56, 6=>67, 7=>78, 8=>89, 9=>90, 10=>90);
	$stats = array();
	$statstot = array(); // para ordenar stats
	$statspag = array(); // para ordenar stats
	$sites = array();
	$records = array();
	$query = $db->getSiteToStats();
	$xsites = 0;
	while($s = $query->fetch_array(MYSQLI_ASSOC)) {
		$sites[$s['sid']] = array(
			'values'=>array(12=>0, 23=>0, 34=>0, 45=>0, 56=>0, 67=>0, 78=>0, 89=>0, 90=>0),
			'scores'=>array(),
			'pages'=>0,
			'score'=>0,
			'conform'=>array('A'=>0, 'AA'=>0, 'AAA'=>0),
			'errors'=>array()
		);
		$records[$s['sid']] = $s['recordstats'];
		$xsites++;
	}
	echo '<p>'.Lang('sitesNum', $xsites).'</p>'."\n";
	$query = $db->getPagesToStats();
	$xpages = 0;
	while($p = $query->fetch_array(MYSQLI_ASSOC)) {
		$s = $p['site'];
		$sites[$s]['scores'][] = $p['score'];
		$val = floor($p['score']); // 1, 2 ...
		$value = $values[$val]; // 12, 23 ...
		$sites[$s]['values'][$value]++;
		$sites[$s]['pages']++;
		$sites[$s]['score'] += $p['score'];
		$tot = unserialize(gzuncompress($p['tot']));
		list($A, $AA, $AAA) = explode('@', $tot['info']['conform']);
		if ($A==0) {
			$sites[$s]['conform']['A']++; // Sitio sin errores A
			if ($AA==0) {
				$sites[$s]['conform']['AA']++;
				if ($AAA==0) {
					$sites[$s]['conform']['AAA']++;
				}
			}
		}
/*
$sites[$s]['conform']['A'] += $A;
$sites[$s]['conform']['AA'] += $AA;
$sites[$s]['conform']['AAA'] += $AAA;
*/
		foreach ($elemStats as $k => $v) {
			if (($k=='a') || ($k=='hx')) {
				if (!isset($tot['elems'][$k])) {
					@$stats[$s][$k]['t'] += 1;
					@$stats[$s][$k]['p']++;
					@$statstot[$s][$k] += 1;
					@$statspag[$s][$k]++;
				}
			} else {
				if (isset($tot['elems'][$k])) {
					if (($k=='langNo') || ($k=='langCodeNo') || ($k=='langExtra') || ($k=='titleNo')) {
						$n = 1;
					} else {
						$n = $tot['elems'][$k];
					}
					@$stats[$s][$k]['t'] += $n;
					@$stats[$s][$k]['p']++;
					@$statstot[$s][$k] += $n;
					@$statspag[$s][$k]++;
				}
			}
		}
		$xpages++;
	} // while
	echo '<p>'.Lang('pagesNum', $xpages).'</p>'."\n";
	foreach ($sites as $s => $site) {
		foreach ($site['values'] as $k => $v) {
			if ($v > 0) {
				$por = round((($v * 100) / $site['pages']), 1);
				$sites[$s]['values'][$k] = $por;
			}
		}
		$vals = implode(",", $sites[$s]['values']);
		$sites[$s]['values'] = $vals;
		
		$sites[$s]['scores'] = implode(",", $site['scores']);
		$sites[$s]['conform'] = implode(",", $site['conform']);
		$score = number_format(($site['score'] / $site['pages']), 1);
		$score = ($score == 10)? (int) 10 : $score;
		$sites[$s]['score'] = $score;
		// Errors
		// Ordenar
		@array_multisort($statstot[$s], SORT_DESC, $statspag[$s], SORT_DESC, $stats[$s]);
		if (isset($stats[$s])) {
			// Para eliminar un warning inexplicable (Invalid argument supplied for foreach...)
			foreach ($stats[$s] as $k => $v) {
				$sites[$s]['errors'][$k] = implode(',', $v);
			}
		}
		$sitestats = $sites[$s];
		$sitestats['day'] = $k3;
		if ($records[$s] == '') {
			// No hay stats del sitio
			$record[$k1][$k2] = $sitestats;
		} else {
			$record = unserialize(gzuncompress($records[$s]));
			if (isset($record[$k1][$k2])) {
				// Eliminar el mes actual
				unset($record[$k1][$k2]);
			}
			$record[$k1][$k2] = $sitestats;
		}
		ksort($record);
		// Guardar
		$update = $db->updateSiteStats($s, $sites[$s], gzcompress(serialize($record)));
	}
	echo '<p>'.Lang('statsUpdateOk', $xsites).'</p>
<p><a href="'.BASE.'admin/stats.php?opt=all" class="btn btn-primary" role="button">'.Lang('statsUpdateAll').'</a></p>'."\n";

} else if ($_GET['opt'] == 'all') {

	// Estadísticas generales
	$tags = $db->getCatsOia();
	$values = array(1=>12, 2=>23, 3=>34, 4=>45, 5=>56, 6=>67, 7=>78, 8=>89, 9=>90, 10=>90);
	$stats = array();
	$statstot = array(); // para ordenar stats
	$statspag = array(); // para ordenar stats
	$allstats = array();
	$alltot = array();
	$allpag = array();
	$categories = array();
	$records = array();
	$categories = array();

	$liststats = array();
	$dbstats = $db->getStatsList();
	while($s = $dbstats->fetch_array(MYSQLI_ASSOC)) {
		$liststats[$s['code']] = $s['recordstats'];
	}

	// Se inicializan las categorías y all (general)
	foreach ($tags as $k => $v) {
		$categories[$k] = array(
			'values'=>array(12=>0, 23=>0, 34=>0, 45=>0, 56=>0, 67=>0, 78=>0, 89=>0, 90=>0),
			'scores'=>array(),
			'pages'=>0,
			'sites'=>0,
			'score'=>0,
			'conform'=>array('A'=>0, 'AA'=>0, 'AAA'=>0),
			'errors'=>array()
		);
		$records[$k] = (isset($liststats[$k]))? $liststats[$k] : '';
	}
	$all = array(
			'values'=>array(12=>0, 23=>0, 34=>0, 45=>0, 56=>0, 67=>0, 78=>0, 89=>0, 90=>0),
			'scores'=>array(),
			'pages'=>0,
			'sites'=>0,
			'cats'=>0,
			'score'=>0,
			'conform'=>array('A'=>0, 'AA'=>0, 'AAA'=>0),
			'errors'=>array()
		);

	$query = $db->getSiteToStats(true); // $full = true
	while($s = $query->fetch_array(MYSQLI_ASSOC)) {
		$st = unserialize(stripslashes($s['stats']));
		$cats = explode(',', $s['cat']);
		$catnum = 1;
		
		foreach ($cats as $cat) {
			$oia = true; // Estadísticas generales?
			if (($cat == '') || !array_key_exists($cat, $tags)) {
				continue;
			} else {
				if ($tags[$cat]['oia']=='n') {
					$oia = false; // No incluir la categoría en el observatorio
				}
			}
			$categories[$cat]['scores'][] = $st['score'];
			$val = floor($st['score']); // 1, 2 ...
			$value = $values[$val]; // 12, 23 ...
			$categories[$cat]['values'][$value]++;
			$categories[$cat]['pages'] += $st['pages'];
			$categories[$cat]['sites']++;
			$categories[$cat]['score'] += $st['score'];
			list($A, $AA, $AAA) = explode(',', $st['conform']);
			if ($A == $st['pages']) {
				$categories[$cat]['conform']['A']++; // Sitio sin errores A
				if ($AA == $st['pages']) {
					$categories[$cat]['conform']['AA']++;
					if ($AAA == $st['pages']) {
						$categories[$cat]['conform']['AAA']++;
					}
				}
			}
/*			
$categories[$cat]['conform']['A'] += $A;
$categories[$cat]['conform']['AA'] += $AA;
$categories[$cat]['conform']['AAA'] += $AAA;
*/
			foreach ($st['errors'] as $k => $v) {
				list($num, $pag) = explode(',', $v);
				@$stats[$cat][$k]['t'] += $num;
				@$stats[$cat][$k]['p'] += $pag;
				@$stats[$cat][$k]['s'] += 1; // sitios
				@$statstot[$cat][$k] += $num;
				@$statspag[$cat][$k] += $pag;
				if ($catnum==1) {
					// Para no sumar 2 veces los mismos errores
					if ($oia) {
						@$allstats[$k]['t'] += $num;
						@$allstats[$k]['p'] += $pag;
						@$allstats[$k]['s'] += 1; // sitios
						@$alltot[$k] += $num;
						@$allpag[$k] += $pag;
					}
				}
			}
			
			if ($oia) {
				if ($catnum==1) {
					$all['sites']++;
					$all['pages'] += $st['pages'];
				}
				$catnum++;
			}
		} // foreach cats
		
	} // while
	
	// Actualizar estadísticas de cada categoría
	foreach ($categories as $c => $category) {
		if ($category['sites'] == 0) {
			if (isset($liststats[$c])) {
				$db->updateCatStats($c, '', '');
			} else {
				$db->updateCatStats($c, '', '', true); // true=insert
			}
			continue;
		}
		foreach ($category['values'] as $k => $v) {
			if ($v > 0) {
				$por = round((($v * 100) / $category['sites']), 1);
				$categories[$c]['values'][$k] = $por;
			}
		}
		$vals = implode(",", $categories[$c]['values']);
		$categories[$c]['values'] = $vals;
		$categories[$c]['scores'] = implode(",", $category['scores']);
		$categories[$c]['conform'] = implode(",", $category['conform']);
		$score = number_format(($category['score'] / $category['sites']), 1);
		$score = ($score == 10)? (int) 10 : $score;
		$categories[$c]['score'] = $score;
		$val = floor($score); // 1, 2 ...
		$value = $values[$val]; // 12, 23 ...
		
		if ($tags[$c]['oia'] != 'n') {
			$all['cats']++;
			$all['conform']['A'] += $category['conform']['A'];
			$all['conform']['AA'] += $category['conform']['AA'];
			$all['conform']['AAA'] += $category['conform']['AAA'];
			$all['score'] += $score;
			$all['scores'][] = $score;
			$all['values'][$value]++;
		}
		
		// Errors
		// Ordenar
		@array_multisort($statstot[$c], SORT_DESC, $statspag[$c], SORT_DESC, $stats[$c]);
		if (isset($stats[$c])) {
			// Para eliminar un warning inexplicable (Invalid argument supplied for foreach...)
			foreach ($stats[$c] as $k => $v) {
				$categories[$c]['errors'][$k] = implode(',', $v);
			}
		}
		// Record
		$catstats = $categories[$c];
		$catstats['day'] = $k3;
		if ($records[$c] == '') {
			// No hay stats de la categoría
			$record[$k1][$k2] = $catstats;
		} else {
			$record = @unserialize(gzuncompress($records[$c]));
			if (isset($record[$k1][$k2])) {
				// Eliminar el mes actual
				unset($record[$k1][$k2]);
			}
			$record[$k1][$k2] = $catstats;
		}
		ksort($record);
		// Guardar
		if (isset($liststats[$c])) {
			$db->updateCatStats($c, $categories[$c], gzcompress(serialize($record)));
		} else {
			$db->updateCatStats($c, $categories[$c], gzcompress(serialize($record)), true); // true=insert
		}
	}
	
	// All (general)
	sort($all['scores']);
	$all['scores'] = implode(",", $all['scores']);
	$score = number_format(($all['score'] / $all['cats']), 1);
	$score = ($score == 10)? (int) 10 : $score;
	$all['score'] = $score;
	$all['conform'] = implode(",", $all['conform']);
	array_multisort($alltot, SORT_DESC, $allpag, SORT_DESC, $allstats);
	foreach ($allstats as $k => $v) {
		$all['errors'][$k] = implode(',', $v);
	}
	foreach ($all['values'] as $k => $v) {
		if ($v > 0) {
			$por = round((($v * 100) / $all['cats']), 1);
			$all['values'][$k] = $por;
		}
	}
	$vals = implode(",", $all['values']);
	$all['values'] = $vals;
	$all['day'] = $k3;
		
	// Ver si se debe insertar una fila o actualizar la última
	if (isset($liststats['all'])) {
		$record = unserialize(gzuncompress($liststats['all']));
		if (isset($record[$k1][$k2])) {
			// Eliminar el mes actual
			unset($record[$k1][$k2]);
		}
		$record[$k1][$k2] = $all;
		$db->updateCatStats('all', $all, gzcompress(serialize($record)));
	} else {
		$allarray = array();
		$allarray[$k1][$k2] = $all;
		$db->updateCatStats('all', $all, gzcompress(serialize($allarray)), true); // true=insert
	}
	echo '<div class="alert alert-success">'.Lang('statsUpdateAllOk').'</div>
<p><a href="./" class="btn btn-primary" role="button">'.Lang('back').'</a></p>';
}

require(LIBPATH.'lib/HTMLFooter.php');
?>
