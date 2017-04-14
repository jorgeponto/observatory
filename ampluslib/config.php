<?php
if (!isset($_SESSION)) { session_start(); }

switch (WORKSPACE) {
	case 'web':
		date_default_timezone_set('Europe/Lisbon');
		ini_set("display_errors", "on");
		error_reporting(E_ALL);
		// error_reporting(0);
		// Dbase
		define('DB_NAME', 'acesso_cms');
		define('DB_USER', 'fct');
		define('DB_PW', 'fct0314');
		define('DB_HOST', 'localhost');
		define('LANGUAGE', 'pt-PT');
		define('ADMINPW', 'hackers');
		define('VALIDATOR', 'http://validador-html.fccn.pt/');
		break;
	case 'local':
		date_default_timezone_set('America/Buenos_Aires');
		ini_set("display_errors", "on");
		error_reporting(E_ALL);
		// error_reporting(0);
		// Dbase
		define('DB_NAME', 'examinator');
		define('DB_USER', 'root');
		define('DB_PW', 'rami1911');
		define('DB_HOST', 'localhost');
		define('LANGUAGE', 'es');
		define('ADMINPW', 'iddqd');
		break;
}

// Nombre de las tablas en la base datos
define('TABLE_SITEMAP', 'amp_sitemap');
define('TABLE_SITES', 'amp_sites');
define('TABLE_PAGES', 'amp_pages');
define('TABLE_STATS', 'amp_stats');
define('TABLE_CATS', 'amp_cats');
define('TABLE_USERS', 'amp_users');
define('TABLE_MON_SITES', 'amp_mon_sites');
define('TABLE_MON_PAGES', 'amp_mon_pages');
define('TABLE_MON_USERS', 'amp_mon_users');
define('TABLE_MON_SITEMAP', 'amp_mon_sitemap');

// Admin
define('EXEC_TIME', 20); // Tiempo de ejecución de sitemaps, autobus, siteupdate

define('HOY', date('n')); // Mes actual (1~12) para actualizar las revisiones
define('CURLCOOKIE', LIBPATH.'db/cookie.txt'); // Archivo para leer/escribir las cookies (curl)

// WCAG 2.0
define('WCAG20_URL', 'http://www.acessibilidade.gov.pt/w3/TR/WCAG20/');
define('WCAG20_TEC', 'http://www.acessibilidade.gov.pt/w3/TR/WCAG20-TECHS/');
define('WCAG20_UND', 'http://www.acessibilidade.gov.pt/w3/TR/UNDERSTANDING-WCAG20/');

/////////////////////////////////////

require_once(LIBPATH.'lib/lang.php');
require_once(LIBPATH.'lib/dbase.php');
$db = new dataBase();

/* FUNCIONES */

function HTMLoginForm($error='') {
	echo ($error != '')? "\n".'<p class="msg error" style="margin-bottom:1em">'.$error.'</p>' : '';
	echo '<form action="'.BASE.'admin/" method="post">
<fieldset>
<legend>'.Lang('adminLogin').'</legend>
<div><input type="password" name="login" value="" title="'.Lang('userPass').'" autofocus="autofocus" required="required"/>
<input type="submit" class="boton" value="'.Lang('adminSubmit').'"/></div>
</fieldset>
</form>'."\n";
} // HTMLoginForm

// Necesaria en see
function Lang($txt, $arg=false) {
	global $lang;
	if (!$arg) {
		return $lang[$txt];
	} else {
		if (is_array($arg)) {
			eval('$return = sprintf($lang[$txt], "'.implode('","', $arg).'");');
			return $return;
		} else {
			return sprintf($lang[$txt], $arg);
		}
	}
} // Lang

function Convert_Bytes(&$length) {
	if ($length < 1024) {
		return $length.' bytes';
	} elseif($length < 1024000) {
		return round(($length / 1024), 1).' KB <em>('.$length.' bytes)</em>';
	} else {
		return round(($length / 1048576), 1).' MB <em>('.$length.' bytes)</em>';
	}
}

function shortener($string, $length) {
	$words = str_word_count($string,1,'(),~:áéíóúñ0...9');
	if (count($words) > $length) {
		return implode(' ',array_slice($words,0,$length)).'...';
	} else {
		return $string;
	}
}

function pageTitle($txt) {
	return ($txt=='')? Lang('noTitle') : $txt;
}

function base64url_encode($data) { 
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
} 

function base64url_decode($data) { 
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
} 

function updateSite($site, $db) {
	$qpages = $db->getPagesToCalculate($site);
	if ($qpages->num_rows < 1) {
		return;
	}
	$pages = 0;
	$score = 0;
	$conform = array('A'=>0,'AA'=>0,'AAA'=>0);
	while($p = $qpages->fetch_array(MYSQLI_ASSOC)) {
		$pages++;
		$score += $p['score'];
		list($A, $AA, $AAA) = explode('@', $p['conform']);
		$conform['A'] += $A;
		$conform['AA'] += $AA;
		$conform['AAA'] += $AAA;
	}
	$conform = implode('@',$conform);
	$score = number_format(($score / $pages), 1);
	$score = ($score == 10)? (int) 10 : $score;
	$db->updateSiteCalc($score, $pages, $conform, $site);
	return;
} // updateSite

function updateStatsSiteMonitor($site, $db) {
	require(LIBPATH.'lib/seeElems.php');
	$k1 = date('Y'); // año 2015
	$k2 = date('n'); // mes 1-12
	$k3 = date('j'); // día 1-31
	$values = array(1=>12, 2=>23, 3=>34, 4=>45, 5=>56, 6=>67, 7=>78, 8=>89, 9=>90, 10=>90);
	$stats = array();
	$statstot = array(); // para ordenar stats
	$statspag = array(); // para ordenar stats
	$sites = array(
		'values'=>array(12=>0, 23=>0, 34=>0, 45=>0, 56=>0, 67=>0, 78=>0, 89=>0, 90=>0),
		'scores'=>array(),
		'pages'=>0,
		'score'=>0,
		'conform'=>array('A'=>0, 'AA'=>0, 'AAA'=>0),
		'errors'=>array()
	);
	$records = array();
	$query = $db->getSiteStatsMonitor($site);
	$records = $query['recordstats'];
	
	$query = $db->getPagesToCalculateMonitor($site);
	$xpages = 0;
	while($p = $query->fetch_array(MYSQLI_ASSOC)) {
		$sites['scores'][] = $p['score'];
		$val = floor($p['score']); // 1, 2 ...
		$value = $values[$val]; // 12, 23 ...
		$sites['values'][$value]++;
		$sites['pages']++;
		$sites['score'] += $p['score'];
		$tot = unserialize(gzuncompress($p['tot']));
		list($A, $AA, $AAA) = explode('@', $tot['info']['conform']);
		if ($A==0) {
			$sites['conform']['A']++; // Sitio sin errores A
			if ($AA==0) {
				$sites['conform']['AA']++;
				if ($AAA==0) {
					$sites['conform']['AAA']++;
				}
			}
		}
		foreach ($elemStats as $k => $v) {
			if (($k=='a') || ($k=='hx')) {
				if (!isset($tot['elems'][$k])) {
					@$stats[$k]['t'] += 1;
					@$stats[$k]['p']++;
					@$statstot[$k] += 1;
					@$statspag[$k]++;
				}
			} else {
				if (isset($tot['elems'][$k])) {
					if (($k=='langNo') || ($k=='langCodeNo') || ($k=='langExtra') || ($k=='titleNo')) {
						$n = 1;
					} else {
						$n = $tot['elems'][$k];
					}
					@$stats[$k]['t'] += $n;
					@$stats[$k]['p']++;
					@$statstot[$k] += $n;
					@$statspag[$k]++;
				}
			}
		}
		$xpages++;
	} // while
	foreach ($sites['values'] as $k => $v) {
		if ($v > 0) {
			$por = round((($v * 100) / $sites['pages']), 1);
			$sites['values'][$k] = $por;
		}
	}
	$vals = implode(",", $sites['values']);
	$sites['values'] = $vals;
	
	$sites['scores'] = implode(",", $sites['scores']);
	$sites['conform'] = implode(",", $sites['conform']);
	$score = number_format(($sites['score'] / $sites['pages']), 1);
	$score = ($score == 10)? (int) 10 : $score;
	$sites['score'] = $score;
	// Errors
	// Ordenar
	@array_multisort($statstot, SORT_DESC, $statspag, SORT_DESC, $stats);
	// Para eliminar un warning inexplicable (Invalid argument supplied for foreach...)
	foreach ($stats as $k => $v) {
		$sites['errors'][$k] = implode(',', $v);
	}
	$sitestats = $sites;
	$sitestats['day'] = $k3;
	if ($records == '') {
		// No hay stats del sitio
		$record[$k1][$k2] = $sitestats;
	} else {
		$record = unserialize(gzuncompress($records));
		if (isset($record[$k1][$k2])) {
			// Eliminar el mes actual
			unset($record[$k1][$k2]);
		}
		$record[$k1][$k2] = $sitestats;
	}
	ksort($record);
	// Guardar
	$update = $db->updateSiteStatsMonitor($site, $sites, gzcompress(serialize($record)));
	/*echo '<pre>'.print_r($sites, true).'</pre>
	<pre>'.print_r($record, true).'</pre>';*/
}

function Absolute_URL(&$base, &$url, $frag=false) {
	@extract(parse_url($url));
	do {
		extract(parse_url($base), EXTR_PREFIX_ALL, "B");
		if (!isset($scheme)) { // Base scheme
			$scheme = $B_scheme;
		} else if ($scheme != $B_scheme) { // Not relative
			break;
		}
		if (isset($host) || isset($port)) { // Not relative
			break;
		}
		if (isset($B_host)) $host = $B_host; // Base host
		if (isset($B_port)) $port = $B_port; // Base port
		if (!isset($path)) { // Base path
			$path=$B_path;
			if (!isset($query) && isset($B_query)) { // Base query
				$query=$B_query;
			}
		} else if (!preg_match("@^/@", $path)) { // URI don't start with '/'
			$ppath = "";
			if (isset($B_path)) { // Base have path
				$ppath = $B_path;
				$ppath = preg_replace("@/[^/]*$@", "/", $ppath);
			} else {
				$ppath = "/";
			}
			$path = $ppath.$path;
			$oldpath = "";
			do {
				$oldpath = $path;
				$path = preg_replace('@/\./@','/',$path);
			} while($path != $oldpath);
			$path = preg_replace('@/\.$@', '/', $path);
			do {
				$oldpath = $path;
				$path = preg_replace('@/[^/]*/\.\./@','/',$path);}
				while($path != $oldpath);
				$path = preg_replace('@/[^/]/\.\.$@','/',$path);
				$path = preg_replace('@/\.\./@','/',$path);
		}
	} while(0);
	if (!isset($path)) $path = '/';
	// Make URI
	if (isset($scheme)) { $url = "$scheme:"; }
	if (isset($host)) {
		$url .= "//$host";
		if (isset($port)) { $url .= ":$port"; }
	}
	if (isset($path)) $url .= $path;
	if (isset($query)) {
		if (!isset($path)) $url .= "/";
		$url .= "?$query";
	}
	if ($frag) {
		if (isset($fragment)) $url .= "#$fragment";
	}
	return $url;
} // End function Absolute_URL

function resIcon(&$r) {
	switch ($r) {
		case 10: return 'A';
		case 9: case 8: return 'B';
		case 7: case 6: return 'C';
		case 5: case 4: return 'D';
		case 3: case 2: return 'E';
		case 1: return 'F';
	}
} // resIcon

$urlfrom = utf8_decode("áàãâéêíóôõúüçñ");
$urlto   = "aaaaeeiooouucn";
function urlCat($txt) {
	global $urlfrom, $urlto;
	$url = strtolower(utf8_decode($txt));
	$url = strtr($url, $urlfrom, $urlto);
	return str_replace(array(' ',','), array('-',''), $url);
}
?>
