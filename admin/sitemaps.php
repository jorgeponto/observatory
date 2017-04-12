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
$worksFilepath = LIBPATH.'db/sitemap_control.php';
$worksLifetime = 60*EXEC_TIME;
$isWorking = false;

require(LIBPATH.'lib/HTMLHeader.php');

echo '<h2>'.Lang('procSitemap').'</h2>
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
	require(LIBPATH.'lib/curl.php');
	$curl = new myCurl();
	@set_time_limit($worksLifetime);
	$startTime = time();
	@ignore_user_abort(TRUE);
	siteMap($worksLifetime, $startTime, $curl, $db);
	_debug('<strong>'.Lang('procOk').'</strong>');
	unlink($worksFilepath);
	$curl->close();
}

echo '</ul>
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

function siteMap($worksLifetime, $startTime, $curl, $db) {
	// Primer sitio con sitemap pendiente
	if ($site = $db->sitemapGetSite()) {
		$map = array(
			'siteid'=>$site['siteid'],
			'errors'=>@unserialize(stripslashes($site['errors'])),
			'uris'=>@unserialize(stripslashes($site['uris'])));
	} else {
		_debug('<strong>'.Lang('procNoTasks').'</strong>');
		return;
	}
	$pages = $db->sitemapGetPages($map['siteid']);
	// Check if there are pages in the site
	if (!is_array($map['uris']) || (count($map['uris']) == 0)) {
		$db->sitemapClose($map['siteid'], '', 'autobus', '');
		return siteMap($worksLifetime, $startTime, $curl, $db);
	} else {
		_debug(Lang('procWorkingSite', $map['siteid']));
		foreach ($map['uris'] as $k => $v) {
			$elapsed = round(time() - $startTime);
			if (($elapsed >= $worksLifetime) || (($worksLifetime - $elapsed) < 3)) {
				// Si terminó el tiempo o falta poco para terminar
				if ($end = $db->sitemapUpdateTable($map,'sitemap')) {
					_debug(Lang('procSitemapOk'));
				} else {
					_debug(Lang('procSitemapErr'));
				}
				return;
			}
			// OK. Let's go
			$uri = array_shift($map['uris']);
			$uri = rawurlencode($uri);
			if (in_array($uri, $pages)) {
				continue;
			}
			$curl->uri = $uri;
			$curl->getPage();
			if ($curl->error != '') {
				@$map['errors'][$uri] = $curl->error;
				_debug(Lang('error').' '.$uri);
				$db->sitemapUpdateTable($map,'sitemap');
			} else {
				$hash = $curl->info['hash'];
				$url = $curl->info['url'];
				
				if (array_key_exists($hash, $pages)) {
					@$map['errors'][$url] = $pages[$hash];
					continue;
				}
				
				if (isset($curl->info['encoding']) && ($curl->info['encoding'] != 'utf-8')) {
					$curl->pagecode = utf8_encode($curl->pagecode);
				}
				// Guardar página
				$error = '';
				if (trim($curl->pagecode) != '') {
					if ($idPage = $db->sitemapSavePage($map['siteid'], $url, $hash, $curl->info, $curl->pagecode)) {
						if ($upSite = $db->sitemapUpdateSite($map['siteid'])) {
							$pages[$hash] = $url;
							_debug('<a href="'.$url.'">'.$url.'</a>');
						} else {
							$error = Lang('error').' ('.Lang('sitemapErrorA').')';
						}
					} else {
						$error = Lang('error').' ('.Lang('sitemapErrorA').')';
					}
				} else {
					$error = Lang('error').' ('.Lang('sitemapErrorB').')';
				}
				// Error
				if ($error != '') {
					@$map['errors'][$url] = $error;
					_debug(Lang('error').' '.$url);
					$db->sitemapUpdateTable($map,'sitemap');
				}
			}
			sleep(1);
			$mem = memory_get_usage();
		}
		$db->sitemapUpdateTable($map,'autobus');
		return siteMap($worksLifetime, $startTime, $curl, $db);
	}
} // siteMap
?>
