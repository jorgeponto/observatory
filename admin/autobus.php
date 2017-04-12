<?php
// Headers no cache
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
require_once('../main.php');
if (!isset($_SESSION['Admin'])) {
	exit('!');
}
$worksFilepath = LIBPATH.'db/autobus_control.php';
$worksLifetime = 60*EXEC_TIME;
$isWorking = false;
$fail='';

require(LIBPATH.'lib/HTMLHeader.php');

echo '<h2>'.Lang('procAutobus').'</h2>
<p>'.Lang('procNotice', EXEC_TIME).'</p>
<h3 class="post-title">'.Lang('info').'</h3>
<ul style="list-style-type:none">'."\n";
@ob_end_flush();
@ob_start();
clearstatcache();

if (is_file($worksFilepath)) {
	_debug(Lang('procFileExists'));
	include($worksFilepath);
	if (isset($works['Start'])) {
		$elapsedTime = time() - $works['Started'];
		if ($elapsedTime > $worksLifetime) {
			_debug(Lang('procPrevFinished', formatTime($elapsedTime - $worksLifetime)));
			unlink($worksFilepath);
		} else {
			$isWorking = true;
			$txt = array(formatTime($elapsedTime), formatTime($worksLifetime - $elapsedTime));
			_debug(Lang('procIsActive', $txt));
		}
	} else {
		_debug(Lang('procFileError'));
		unlink($worksFilepath);
	}
}
if (!$isWorking) {
	saveWorks($worksFilepath);
	require(LIBPATH.'lib/examinator.php');
	$examinator = new eXaminator();
	@set_time_limit($worksLifetime);
	$startTime = time();
	@ignore_user_abort(TRUE);
	if (isset($_GET['e'])) {
		$pid = base64_decode(urldecode($_GET['e']));
		_debug($db->autobusDelPage($pid));
	}
	autoBus($worksLifetime, $startTime, $examinator, $db);
	_debug('<strong>'.Lang('procOk').'</strong>');
	unlink($worksFilepath);
}
if ($fail != '') {
	// Error en alguna página
	_debug('<strong>'.Lang('adminAutobusA').'</strong>');
	echo '</ul>
<div class="alert alert-error"><a href="autobus.php?e='.urlencode(base64_encode($fail)).'">'.Lang('adminAutobusB').'</a></div>
<hr/>'."\n";
} else {
	echo '</ul>'."\n";
}
if (!defined('FINISHED')) {
	echo '<div class="alert alert-success"><a href="autobus.php">'.Lang('adminAutobusC').'</a></div>'."\n";
}
echo '<hr/>
<p><a href="./" class="btn btn-primary" role="button">'.Lang('back').'</a></p>';
require(LIBPATH.'lib/HTMLFooter.php');

////////////////////////////////////

function saveWorks($worksFilepath) {
	$start = time();
	$a = array(
		'Start' => date('d-m-Y H:i:s', $start),
		'Started' => $start);
	$txt = '<?php
$works = '.var_export($a, true);
	$txt .= "\n?>";
	if (!file_put_contents($worksFilepath, $txt)) {
		_debug(Lang('procFileWriteErr').' '.$worksFilepath);
		exit;
	} else {
		_debug(Lang('procFileWriteOk'));
	}
}
function _debug($message) {
	echo '<li>'.$message.'</li>'."\n";
	ob_flush();
    flush();
}
function formatTime($seconds) {
	$textos = array('h' => 'h.', 'm' => 'm.', 's' => 's.');
	$periods = array('h' => 3600, 'm' => 60, 's' => 1);
	$durations = array();
	foreach ($periods as $period => $seconds_in_period) {
		if ($seconds >= $seconds_in_period) {
			$durations[$period] = floor($seconds / $seconds_in_period);
			$seconds -= $durations[$period] * $seconds_in_period;
		}
	}
	$response = '';
	foreach ($durations as $unit => $value) {
		$response .= $value.'<em>'.$textos[$unit].'</em> ';
	}
	return $response;
} // formatTime

function autoBus($worksLifetime, $startTime, $examinator, $db) {
	global $fail;
	// Primer sitio con autobus pendiente
	if ($site = $db->autobusGetSite()) {
		$siteid = $site['siteid'];
	} else {
		_debug('<strong>'.Lang('procNoTasks').'</strong>');
		define('FINISHED', true);
		return;
	}
	$pages = $db->autobusGetPages($siteid);
	if ($pages->num_rows < 1) {
		$close = $db->autobusClose($siteid, 'OK');
		return autoBus($worksLifetime, $startTime, $examinator, $db); // Next site
	}
	_debug(Lang('procWorkingSite', $siteid));
	while($p = $pages->fetch_array(MYSQLI_ASSOC)) {
		$elapsed = round(time() - $startTime);
		if (($elapsed >= $worksLifetime) || (($worksLifetime - $elapsed) < 3)) {
			updateSite($siteid, $db); // Esta función está en main.php
			return;
		}
		$pid = $p['pid'];
		$info = unserialize(stripslashes($p['info']));
		$pagecode = gzuncompress($p['pagecode']);
		$start = microtime(true);
		_debug(Lang('page').' '.$pid.': <a href="'.$info['url'].'">'.$info['url'].'</a>');
		$examinator->parserPage($info, $pagecode, $start);
		$tot = $examinator->tot;
		//$nodes = $examinator->nodes;
		if (!$updatep = $db->saveApiAutobus($pid, $tot)) {
			_debug(Lang('procAutobusError', $pid));
			$fail = $pid;
			return;
		}
		sleep(1);
		$mem = memory_get_usage();
	}
	updateSite($siteid, $db);
	return autoBus($worksLifetime, $startTime, $examinator, $db);
} // autoBus

?>
