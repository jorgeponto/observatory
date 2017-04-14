<?php


class dataBase {
	public $db = '';

	function __construct() {
		$this->db = new mysqli(DB_HOST, DB_USER, DB_PW, DB_NAME);
		if ($this->db->connect_errno) {
			$this->error($this->db->connect_error);
		}
	}

	/* FUNCIONES SOBRE ALTAS, MODIFICACIONES Y BAJAS DE SITIOS */

	// Agregar un sitio
	function addNewSite($sname, $shome, $scat) {
		$shome = $this->esc($shome);
		$result = $this->db->prepare("INSERT INTO ".TABLE_SITES." (name, home, cat) VALUES (?, ?, ?)");
		$result->bind_param('sss', $sname, $shome, $scat);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return $this->db->insert_id;
	}
	// Actualizar un sitio
	function updateSite($sid, $sname, $shome, $scat) {
		$result = $this->db->prepare("UPDATE ".TABLE_SITES." SET name=?, home=?, cat=? WHERE sid=?");
		$result->bind_param('sssi', $sname, $shome, $scat, $sid);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}
	// Agregar una fila en sitemap para el nuevo sitio
	function addSitemapRow($newid, $sitemap) {
		if (count($sitemap) != 0) {
			$sitemap = $this->esc($sitemap);
			$status = 'sitemap';
		} else {
			$sitemap = '';
			$status = '';
		}
		$result = $this->db->prepare("INSERT INTO ".TABLE_SITEMAP." (siteid, uris, status) VALUES (?, ?, ?)");
		$result->bind_param('iss', $newid, $sitemap, $status);
		if (!$result->execute()) {
			return false;
		}
		return true;
	}
	// Actualizar los datos en sitemap
	function updateSitemap($sitemap, $sid, $status) {
		$sitemap = $this->esc($sitemap);
		$result = $this->db->prepare("UPDATE ".TABLE_SITEMAP." SET uris=?, status=? WHERE siteid=?");
		$result->bind_param('ssi', $sitemap, $status, $sid);
		if (!$result->execute()) {
			return false;
		}
		return true;
	}
	// Obtener la información de un sitio
	function getSiteInfo($siteid) {
		if (!$result = $this->db->query("SELECT name, home, cat FROM ".TABLE_SITES." WHERE sid = {$siteid} LIMIT 1")) {
			$this->error($this->db->error);
		}
		if ($result->num_rows < 1) {
			$this->error(Lang('adminSiteNoInfo'));
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Eliminar toda la información de un sitio
	function deleteSite($delsite) {
		$info = '';
		if (!$result1 = $this->db->query("DELETE FROM ".TABLE_SITES." WHERE sid = ".$delsite)) {
			$info .= '<p class="msg error">'.Lang('adminErrDelSite').'</p>';
		}
		if (!$result2 = $this->db->query("DELETE FROM ".TABLE_PAGES." WHERE site = ".$delsite)) {
			$info .= '<p class="msg error">'.Lang('adminErrDelPages').'</p>';
		}
		if (!$result3 = $this->db->query("DELETE FROM ".TABLE_SITEMAP." WHERE siteid = ".$delsite)) {
			$info .= '<p class="msg error">'.Lang('adminErrDelSitemap').'</p>';
		}
		$this->db->query("OPTIMIZE TABLE ".TABLE_SITES);
		$this->db->query("OPTIMIZE TABLE ".TABLE_PAGES);
		$this->db->query("OPTIMIZE TABLE ".TABLE_SITEMAP);
		return $info;
	}
	// Eliminar una página
	function deletePage($page) {
		$info = '';
		if (!$result = $this->db->query("DELETE FROM ".TABLE_PAGES." WHERE pid = ".$page)) {
			$info .= '<p class="msg error">'.Lang('adminErrDelPages').'</p>';
		}
		$this->db->query("OPTIMIZE TABLE ".TABLE_PAGES);
		return $info;
	}
	// Eliminar varias páginas
	function deletePages($q) {
		if (!$result = $this->db->query("DELETE FROM ".TABLE_PAGES." WHERE ".$q)) {
			return 0;
		}
		return $this->db->affected_rows;
	}

	/* FUNCIONES RELACIONADAS CON SITEMAPS (CARGA DE LAS PÁGINAS DE LOS SITIOS) */

	// Primer sitio con sitemap pendiente
	function sitemapGetSite() {
		if (!$result = $this->db->query("SELECT siteid, uris, errors FROM ".TABLE_SITEMAP." WHERE status = 'sitemap' ORDER BY siteid ASC LIMIT 1")) {
			$this->error($this->db->error);
		}
		if ($result->num_rows < 1) {
			return false;
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Lista de página ya incorporadas al sitio
	function sitemapGetPages($site) {
		if (!$result = $this->db->query("SELECT uri, hash FROM ".TABLE_PAGES." WHERE site=".$site)) {
			$this->error($this->db->error);
		}
		$pages = array();
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$pages[$row['hash']] = $row['uri'];
		}
		return $pages;
	}
	// Lista de página para limpiar las repetidas
	function sitemapGetPagesToClean($site) {
		if (!$result = $this->db->query("SELECT pid, title, uri, score, hash FROM ".TABLE_PAGES." WHERE site=".$site)) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Finaliza el proceso sitemap
	function sitemapClose($site, $uris, $status, $info='') {
		$uris = $this->esc($uris);
		$result = $this->db->prepare("UPDATE ".TABLE_SITEMAP." SET uris=?, status=?, info=? WHERE siteid=?");
		$result->bind_param('sssi', $uris, $status, $info, $site);
		$result->execute();
		return;
	}
	// Actualiza la tabla antes de detener sitemap (tiempo agotado)
	function sitemapUpdateTable($map, $status) {
		if ($result = $this->db->prepare("UPDATE ".TABLE_SITEMAP." SET uris=?, errors=?, status=? WHERE siteid=?")) {
			$uris = $this->esc($map['uris']);
			$errors = $this->esc($map['errors']);
			if ($result->bind_param('sssi', $uris, $errors, $status, $map['siteid'])) {
				if ($result->execute()) {
					return true;
				}
			}
		}
		return false;
	}
	// Guarda la página
	function sitemapSavePage($site, $url, $hash, $info, $pagecode) {
		$pagecode = gzcompress($pagecode, 9);
		$url = $this->esc($url);
		$info = $this->esc($info);
		$result = $this->db->prepare("INSERT INTO ".TABLE_PAGES." (site, uri, hash, info, pagecode) VALUES (?, ?, ?, ?, ?)");
		if (!$result->bind_param('issss', $site, $url, $hash, $info, $pagecode)) {
			return false;
		}
		if (!$result->execute()) {
			return false;
		}
		return $this->db->insert_id;
	}
	// Actualiza el número de páginas del sitio
	function sitemapUpdateSite($site) {
		$add = 1;
		$result = $this->db->prepare("UPDATE ".TABLE_SITES." SET pages=(pages + ?) WHERE sid=?");
		$result->bind_param('ii', $add, $site);
		if (!$result->execute()) {
			return false;
		}
		return true;
	}

	/* FUNCIONES RELACIONADAS CON AUTOBUS (REVISIÓN DE LAS PÁGINAS) */

	// Primer sitio con autobus pendiente
	function autobusGetSite() {
		if (!$result = $this->db->query("SELECT M.siteid FROM ".TABLE_SITEMAP." AS M, ".TABLE_SITES." AS S
	WHERE S.sid = M.siteid AND M.status = 'autobus' ORDER BY M.siteid ASC LIMIT 1")) {
			$this->error($this->db->error);
		}
		if ($result->num_rows < 1) {
			return false;
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Lista de páginas a revisar
	function autobusGetPages($site) {
		if (!$result = $this->db->query("SELECT pid, uri, info, pagecode FROM ".TABLE_PAGES." WHERE site={$site} AND tot=''")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Finaliza el proceso autobus
	function autobusClose($site, $status) {
		$result = $this->db->prepare("UPDATE ".TABLE_SITEMAP." SET status=? WHERE siteid=?");
		$result->bind_param('si', $status, $site);
		$result->execute();
		return;
	}
	// Elimina una página con error
	function autobusDelPage($pid) {
		if ($result = $this->db->query("DELETE FROM ".TABLE_PAGES." WHERE pid=".$pid)) {
			return 'La última página se eliminó correctamente';
		}
		return 'No se logró eliminar la última página';
	}
	// Guardar los resultados devueltos por la API (error)
	function saveApiAutobusError($pid, $error) {
		$date = gmdate('Y-m-d H:i:s');
		if ($result = $this->db->prepare("UPDATE ".TABLE_PAGES." SET error=?, date=? WHERE pid=?")) {
			if ($result->bind_param('ssi', $error, $date, $pid)) {
				if ($result->execute()) {
					return true;
				}
			}
		}
		return false;
	}
	// Guardar los resultados devueltos por la API
	function saveApiAutobus($pid, $tot) {
		$title = $this->esc($tot['info']['title']);
		$score = $tot['info']['score'];
		$conform = $tot['info']['conform'];
		$tot = gzcompress(serialize($tot), 9);
		$pagecode = ''; // ya no es necesario
		$revs = 1;
		$date = gmdate('Y-m-d H:i:s');
		$info = ''; // ya no es necesaria
		if ($result = $this->db->prepare("UPDATE ".TABLE_PAGES." SET title=?, score=?, conform=?, tot=?, pagecode=?, revs=(revs+?), date=?, info=? WHERE pid=?")) {
			if ($result->bind_param('sdsssissi', $title, $score, $conform, $tot, $pagecode, $revs, $date, $info, $pid)) {
				if ($result->execute()) {
					$this->db->query("OPTIMIZE TABLE ".TABLE_PAGES);
					return true;
				}
			} else {
				$this->error($this->db->error);
			}
		} else {
			$this->error($this->db->error);
		}
		return false;
	}

	/* FUNCIONES PARA ACTUALIZAR LOS DATOS DE LOS SITIOS */

	// Obtener la información de las páginas de un sitio
	function getPagesToCalculate($site) {
		if (!$result = $this->db->query("SELECT score, conform FROM ".TABLE_PAGES." WHERE site={$site} AND tot<>''")) {
			return false;
		}
		return $result;
	}
	// Actualiza el score del sitio
	function updateSiteCalc($score, $pages, $conform, $site) {
		$stats = '0000-00-00'; // Marcar para actualizar estadísticas
		$date = gmdate('Y-m-d H:i:s');
		if (!$result = $this->db->prepare("UPDATE ".TABLE_SITES." SET scores=?, pages=?, conform=?, date=?, timestats=? WHERE sid=?")) {
			$this->error($this->db->error);
		}
		if (!$result->bind_param('disssi', $score, $pages, $conform, $date, $stats, $site)) {
			$this->error($this->db->error);
		}
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}

	// Categorías
	function getCats() {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_CATS." WHERE name<>''")) {
			$this->error($this->db->error);
		}
		$cats = array();
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$cats[$row['cid']] = array('name'=>$row['name'], 'oia'=>$row['oia']);
		}
		return $cats;
	}
	function getCatsOia() {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_CATS." WHERE name<>''")) {
			$this->error($this->db->error);
		}
		$cats = array();
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$cats[$row['code']] = array('name'=>$row['name'], 'oia'=>$row['oia']);
		}
		return $cats;
	}
	function insertCat($cid, $name, $oi) {
		$code = str_pad($cid, 3, '0', STR_PAD_LEFT);
		$result = $this->db->prepare("INSERT INTO ".TABLE_CATS." (cid, code, name, oia) VALUES (?, ?, ?, ?)");
		$result->bind_param('isss', $cid, $code, $name, $oi);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
	}
	function updateCat($cid, $name, $oi) {
		$result = $this->db->prepare("UPDATE ".TABLE_CATS." SET name=?, oia=? WHERE cid=?");
		$result->bind_param('ssi', $name, $oi, $cid);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
	}
	
	// Usuarios
	function getUsers() {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_USERS)) {
			$this->error($this->db->error);
		}
		$users = array();
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$users[$row['uid']] = array('name'=>$row['name'], 'pass'=>$row['pass'], 'cats'=>$row['cats']);
		}
		return $users;
	}
	function insertUser($name, $pass, $tags) {
		$cats = serialize($tags);
		$result = $this->db->prepare("INSERT INTO ".TABLE_USERS." (name, pass, cats) VALUES (?, ?, ?)");
		$result->bind_param('sss', $name, $pass, $cats);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return $this->db->insert_id;
	}
	function updateUser($name, $pass, $tags, $uid) {
		$cats = serialize($tags);
		$result = $this->db->prepare("UPDATE ".TABLE_USERS." SET name=?, pass=?, cats=? WHERE uid=?");
		$result->bind_param('sssi', $name, $pass, $cats, $uid);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
	}
	function getUsersId($uid) {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_USERS." WHERE uid=".$uid." LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	
	/* Funciones relacionadas con los informes */

	// Obtener la lista de sitios
	function getSiteList() {
		if (!$result = $this->db->query("SELECT S.sid, S.name, S.cat, M.status, S.timestats FROM ".TABLE_SITES." AS S, ".TABLE_SITEMAP." as M WHERE S.sid = M.siteid")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener info de un sitio para informe
	function getSiteInfoReport($sid) {
		if (!$result = $this->db->query("SELECT name, pages, scores, conform, timestats, stats, recordstats FROM ".TABLE_SITES." WHERE sid='{$sid}' LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Obtener la lista de sitios de una categoría
	function getSiteListByCat($cat) {
		if (!$result = $this->db->query("SELECT S.sid, S.name, S.home, S.scores, S.pages, S.stats, S.conform FROM ".TABLE_SITES." AS S, ".TABLE_SITEMAP." as M WHERE S.sid = M.siteid AND M.status = 'OK' AND S.cat LIKE '%".$cat.",%' ORDER BY S.scores DESC, pages DESC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Realizar una búsqueda de sitios
	function getSiteSearch($txt) {
		if (!$result = $this->db->query("SELECT S.sid, S.name, S.home, S.scores, S.pages, S.cat FROM ".TABLE_SITES." AS S, ".TABLE_SITEMAP." as M WHERE S.sid = M.siteid AND M.status = 'OK' AND (S.name LIKE '%$txt%' OR S.home LIKE '%$txt%') ORDER BY S.scores DESC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener la lista de páginas de un sitio
	function getPageList($site) {
		if (!$result = $this->db->query("SELECT pid, title, uri, score, conform, revs, date, error FROM ".TABLE_PAGES." WHERE site={$site} AND tot<>'' ORDER BY score DESC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener $tot de la lista de páginas de un sitio para quartis de errores
	function getPageListTot($site) {
		if (!$result = $this->db->query("SELECT tot FROM ".TABLE_PAGES." WHERE site={$site} AND tot<>''")) {
			$this->error($this->db->error);
		}
		return $result;
	}

	// Obtener la información de las páginas de un sitio para un directorio
	function getDirSitesToStats($q) {
		if (!$result = $this->db->query("SELECT sid, cat, name, home, scores, stats FROM ".TABLE_SITES." WHERE ".$q)) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener la información de una página (informe admin)
	function getPageInfo($pid) {
		if (!$result = $this->db->query("SELECT title, uri, tot, score, error FROM ".TABLE_PAGES." WHERE pid = {$pid} LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	
	/* Funciones para las estadísticas */

	// Obtener los datos de los sitios para las estadísticas
	function getSiteToStats($full=false) {
		if ($full) {
			$q = 'S.stats, S.cat';
		} else {
			$q = 'S.recordstats';
		}
		if (!$result = $this->db->query("SELECT S.sid, {$q} FROM ".TABLE_SITES." AS S, ".TABLE_SITEMAP." as M WHERE S.sid = M.siteid AND M.status = 'OK' ORDER BY S.scores ASC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener la información de las páginas de un sitio para las estadísticas
	function getPagesToStats() {
		if (!$result = $this->db->query("SELECT site, score, tot FROM ".TABLE_PAGES." WHERE tot<>'' ORDER BY score ASC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener la lista de estadísticas
	function getStatsList() {
		if (!$result = $this->db->query("SELECT code, recordstats FROM ".TABLE_STATS." ORDER BY catid ASC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Actualiza las estadísticas de una categoría
	function updateCatStats($cat, $stats, $record, $new=false) {
		if ($stats == '') {
			$date = '0000-00-00';
			$sites = 0;
			$pages = 0;
			$score = '0.0';
			$errors = '';
			$scores = '';
			$conform = '';
			$values = '';
			$stats = '';
		} else {
			$date = gmdate('Y-m-d H:i:s');
			$sites = $stats['sites'];
			$pages = $stats['pages'];
			$score = $stats['score'];
			$errors = $this->esc($stats['errors']);
			$scores = $stats['scores'];
			$conform = $stats['conform'];
			$values = $this->esc($stats['values']);
		}
		if ($cat=='all') {
			$cats = $stats['cats'];
		} else {
			$cats = 1;
		}
		if ($new) {
			if (!$result = $this->db->prepare("INSERT INTO ".TABLE_STATS." (date, cats, sites, pages, score, errors, scores, conform, vals, recordstats, code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
				$this->error($this->db->error);
			}
		} else {
			if (!$result = $this->db->prepare("UPDATE ".TABLE_STATS." SET date=?, cats=?, sites=?, pages=?, score=?, errors=?, scores=?, conform=?, vals=?, recordstats=? WHERE code=?")) {
				$this->error($this->db->error);
			}
		}
		if (!$result->bind_param('siiidssssss', $date, $cats, $sites, $pages, $score, $errors, $scores, $conform, $values, $record, $cat)) {
			$this->error($this->db->error);
		}
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}
	// Actualiza las estadísticas de un sitio
	function updateSiteStats($site, $stats, $record) {
		$info = ''; // Stats actualizadas
		$statsdb = $this->esc($stats);
		$date = gmdate('Y-m-d H:i:s');
		if (!$result = $this->db->prepare("UPDATE ".TABLE_SITES." SET pages=?, scores=?, conform=?, timestats=?, stats=?, recordstats=?, info=? WHERE sid=?")) {
			$this->error($this->db->error);
		}
		if (!$result->bind_param('idsssssi', $stats['pages'], $stats['score'], $stats['conform'], $date, $statsdb, $record, $info, $site)) {
			$this->error($this->db->error);
		}
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}
	// Obtener las últimas estadísticas generales
	function getLastStats($cat='all') {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_STATS." WHERE code='{$cat}' LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Obtener los datos de las categorías
	function getCatsToStats() {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_STATS." WHERE code<>'all' ORDER BY score DESC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener los datos de todas las categorías, incluyendo all
	function getStatsFull() {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_STATS." ORDER BY score DESC, sites DESC, pages DESC")) {
			$this->error($this->db->error);
		}
		return $result;
	}

	/* FUNCIONES PARA LA ACTUALIZACIÓN MASIVA DE LOS SITIOS */

	// Cuántas páginas para controlar
	function pageCountChanges($mes) {
		if (!$result = $this->db->query("SELECT count(*) AS changes FROM ".TABLE_PAGES." WHERE MONTH(date)<>{$mes} AND info=''")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Cuántas páginas para actualizar
	function pageCountUpdates($mes) {
		if (!$result = $this->db->query("SELECT count(*) AS changes FROM ".TABLE_PAGES." WHERE info<>''")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Obtener la lista de páginas que cambiaron
	function pageListChanges($mes) {
		if (!$result = $this->db->query("SELECT pid, uri, site, hash FROM ".TABLE_PAGES." WHERE MONTH(date)<>{$mes} AND info='' ORDER BY site ASC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener la lista de páginas para actualizar la revisión
	function pageListUpdates() {
		if (!$result = $this->db->query("SELECT pid, uri, site, info, pagecode FROM ".TABLE_PAGES." WHERE info<>'' ORDER BY site ASC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Guardar los resultados de la actualización (sin cambios)
	function saveUnchangedPages($pid, $error) {
		$date = gmdate('Y-m-d H:i:s');
		$result = $this->db->prepare("UPDATE ".TABLE_PAGES." SET date=?, error=? WHERE pid=?");
		$result->bind_param('ssi', $date, $error, $pid);
		if (!$result->execute()) {
			return false;
		}
		return true;
	}
	// Guardar los resultados de la actualización (páginas con cambios)
	function saveChangedPages($pid, $hash, $info, $pagecode) {
		$error = '';
		$changes = 1;
		$pagecode = gzcompress($pagecode, 9);
		$info = $this->esc($info);
		$result = $this->db->prepare("UPDATE ".TABLE_PAGES." SET error=?, changes=(changes+?), hash=?, info=?, pagecode=? WHERE pid=?");
		$result->bind_param('sisssi', $error, $changes, $hash, $info, $pagecode, $pid);
		if (!$result->execute()) {
			return false;
		}
		return true;
	}
	// Obtener el código de una página
	function getPageCode($pid) {
		if (!$result = $this->db->query("SELECT tot, nodes, pagecode FROM ".TABLE_PAGES." WHERE pid = {$pid} LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}



	/* FUNCIONES PARA EL MONITOR */
	
	// Obtener la lista de sitios del monitor
	function getSiteListMonitor() {
		//if (!$result = $this->db->query("SELECT S.*, M.status FROM ".TABLE_MON_SITES." AS S, ".TABLE_MON_SITEMAP." as M WHERE S.sid = M.siteid")) {
		if (!$result = $this->db->query("SELECT S.sid, S.oia, S.userid, S.name, S.short, S.home, S.timestats, M.status FROM ".TABLE_MON_SITES." AS S, ".TABLE_MON_SITEMAP." as M WHERE S.sid = M.siteid")) {
			$this->error($this->db->error);
		}
		return $result;
		//return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Ver usuario del monitor
	function getUserMonitor($username, $pass) {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_MON_USERS." WHERE username='{$username}' AND pass='{$pass}' LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener la lista de sitios del monitor por usuario
	function getSitesMonitorUser($uid) {
		if (!$result = $this->db->query("SELECT short, name FROM ".TABLE_MON_SITES." WHERE userid LIKE '%,".$uid.",%'")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Obtener info de un sitio por su nombre
	function getSiteInfoMonitor($short) {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_MON_SITES." WHERE short='{$short}' LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Agregar un sitio al monitor
	function addNewSiteMonitor($oia, $userid, $name, $short, $home) {
		$home = $this->esc($home);
		$result = $this->db->prepare("INSERT INTO ".TABLE_MON_SITES." (oia, userid, name, short, home) VALUES (?, ?, ?, ?, ?)");
		$result->bind_param('issss', $oia, $userid, $name, $short, $home);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return $this->db->insert_id;
	}
	// Actualiza un sitio en el monitor
	function updateSiteMonitor($userid, $name, $short, $home, $sid) {
		if (!$result = $this->db->prepare("UPDATE ".TABLE_MON_SITES." SET userid=?, name=?, short=?, home=? WHERE sid=?")) {
			$this->error($this->db->error);
		}
		if (!$result->bind_param('ssssi', $userid, $name, $short, $home, $sid)) {
			$this->error($this->db->error);
		}
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}
	// Marcar el nuevo sitio en el observatorio
	function markNewMonitorSite($newsite, $oia) {
		if (!$result = $this->db->prepare("UPDATE ".TABLE_SITES." SET monitor=? WHERE sid=?")) {
			$this->error($this->db->error);
		}
		if (!$result->bind_param('ii', $newsite, $oia)) {
			$this->error($this->db->error);
		}
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}
	// Páginas de un sitio del Monitor
	function getPagesMonitor($sid) {
		if (!$result = $this->db->query("SELECT pid, title, uri, score, conform, hash FROM ".TABLE_MON_PAGES." WHERE site=".$sid." ORDER BY score DESC")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Páginas en el Observatorio de un sitio del Monitor
	/*function getPagesOiaMonitor($oia) {
		if (!$result = $this->db->query("SELECT title, uri FROM ".TABLE_PAGES." WHERE site=".$oia)) {
			$this->error($this->db->error);
		}
		return $result;
	}*/
	// Guarda una página en el monitor
	function savePageMonitor($site, $tot, $nodes, $pagecode) {
		$title = $this->esc($tot['info']['title']);
		$uri = $this->esc($tot['info']['url']);
		$score = $tot['info']['score'];
		$conform = $tot['info']['conform'];
		$revs = 1;
		$date = gmdate('Y-m-d H:i:s');
		$hash = $tot['info']['hash'];
		$tot = gzcompress(serialize($tot), 9);
		$pagecode = gzcompress($pagecode, 9);
		$nodes = gzcompress(serialize($nodes), 9);
		$result = $this->db->prepare("INSERT INTO ".TABLE_MON_PAGES." (site, title, uri, tot, nodes, score, conform, revs, date, hash, pagecode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		if (!$result->bind_param('issssdsisss', $site, $title, $uri, $tot, $nodes, $score, $conform, $revs, $date, $hash, $pagecode)) {
			$this->error($this->db->error);
		}
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}
	// Obtener la información de una página del monitor
	function getPageInfoMonitor($pid) {
		//if (!$result = $this->db->query("SELECT title, uri, tot, score, error FROM ".TABLE_MON_PAGES." WHERE pid = {$pid} LIMIT 1")) {
		if (!$result = $this->db->query("SELECT P.title, P.uri, P.tot, P.score, P.error, S.name, S.short, S.sid FROM ".TABLE_MON_PAGES." AS P, ".TABLE_MON_SITES." AS S WHERE P.pid = {$pid} AND P.site=S.sid LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Obtener el código de una página del monitor
	function getPageCodeMonitor($pid) {
		//if (!$result = $this->db->query("SELECT tot, nodes, pagecode FROM ".TABLE_MON_PAGES." WHERE pid = {$pid} LIMIT 1")) {
		if (!$result = $this->db->query("SELECT P.title, P.tot, P.nodes, P.pagecode, S.name, S.short FROM ".TABLE_MON_PAGES." AS P, ".TABLE_MON_SITES." AS S WHERE P.pid = {$pid} AND P.site=S.sid LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Usuarios
	function getUsersMonitor() {
		if (!$result = $this->db->query("SELECT * FROM ".TABLE_MON_USERS)) {
			$this->error($this->db->error);
		}
		$users = array();
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$users[$row['uid']] = array('name'=>$row['username'], 'pass'=>$row['pass'], 'info'=>$row['info']);
		}
		return $users;
	}
	function insertUserMonitor($name, $pass) {
		$result = $this->db->prepare("INSERT INTO ".TABLE_MON_USERS." (username, pass) VALUES (?, ?)");
		$result->bind_param('ss', $name, $pass);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return $this->db->insert_id;
	}
	function updateUserMonitor($name, $pass, $uid) {
		$result = $this->db->prepare("UPDATE ".TABLE_MON_USERS." SET username=?, pass=? WHERE uid=?");
		$result->bind_param('ssi', $name, $pass, $uid);
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
	}
	// Eliminar un usuario del monitor
	function deleteUsersMonitor($uid) {
		$info = '';
		if ($result = $this->db->query("DELETE FROM ".TABLE_MON_USERS." WHERE uid = ".$uid)) {
			$info .= '<div class="alert alert-success">'.Lang('monUserDelOk').'</div>';
			$this->db->query("OPTIMIZE TABLE ".TABLE_MON_USERS);
		} else {
			$info .= '<div class="alert alert-error">Error: '.$this->db->error.'</div>';
		}
		return $info;
	}
	// Eliminar toda la información de un sitio del monitor
	function deleteSiteMonitor($delsite) {
		$info = '';
		if (!$result1 = $this->db->query("DELETE FROM ".TABLE_MON_SITES." WHERE sid = ".$delsite)) {
			$info .= '<p class="msg error">'.Lang('adminErrDelSite').'</p>';
		}
		if (!$result2 = $this->db->query("DELETE FROM ".TABLE_MON_PAGES." WHERE site = ".$delsite)) {
			$info .= '<p class="msg error">'.Lang('adminErrDelPages').'</p>';
		}
		if (!$result3 = $this->db->query("DELETE FROM ".TABLE_MON_SITEMAP." WHERE siteid = ".$delsite)) {
			$info .= '<p class="msg error">'.Lang('adminErrDelSitemap').'</p>';
		}
		$this->db->query("OPTIMIZE TABLE ".TABLE_MON_SITES);
		$this->db->query("OPTIMIZE TABLE ".TABLE_MON_PAGES);
		$this->db->query("OPTIMIZE TABLE ".TABLE_MON_SITEMAP);
		return $info;
	}
	// Agregar una fila en sitemap para el nuevo sitio del Monitor
	function addSitemapRowMonitor($newid, $sitemap) {
		if (count($sitemap) != 0) {
			$sitemap = $this->esc($sitemap);
			$status = 'sitemap';
		} else {
			$sitemap = '';
			$status = '';
		}
		$result = $this->db->prepare("INSERT INTO ".TABLE_MON_SITEMAP." (siteid, uris, status) VALUES (?, ?, ?)");
		$result->bind_param('iss', $newid, $sitemap, $status);
		if (!$result->execute()) {
			return false;
		}
		return true;
	}
	// Primer sitio con sitemap pendiente en el monitor
	function sitemapGetSiteMonitor() {
		if (!$result = $this->db->query("SELECT siteid, uris, errors FROM ".TABLE_MON_SITEMAP." WHERE status = 'sitemap' ORDER BY siteid ASC LIMIT 1")) {
			$this->error($this->db->error);
		}
		if ($result->num_rows < 1) {
			return false;
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Lista de página ya incorporadas al sitio del monitor
	function sitemapGetPagesMonitor($site) {
		if (!$result = $this->db->query("SELECT uri, hash FROM ".TABLE_MON_PAGES." WHERE site=".$site)) {
			$this->error($this->db->error);
		}
		$pages = array();
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$pages[$row['hash']] = $row['uri'];
		}
		return $pages;
	}
	// Finaliza el proceso sitemap en el monitor
	function sitemapCloseMonitor($site, $uris, $status, $info='') {
		$uris = $this->esc($uris);
		$result = $this->db->prepare("UPDATE ".TABLE_MON_SITEMAP." SET uris=?, status=?, info=? WHERE siteid=?");
		$result->bind_param('sssi', $uris, $status, $info, $site);
		$result->execute();
		return;
	}
	// Actualiza la tabla antes de detener sitemap del monitor (tiempo agotado)
	function sitemapUpdateTableMonitor($map, $status) {
		if ($result = $this->db->prepare("UPDATE ".TABLE_MON_SITEMAP." SET uris=?, errors=?, status=? WHERE siteid=?")) {
			$uris = $this->esc($map['uris']);
			$errors = $this->esc($map['errors']);
			if ($result->bind_param('sssi', $uris, $errors, $status, $map['siteid'])) {
				if ($result->execute()) {
					return true;
				}
			}
		}
		return false;
	}
	// Guarda la página del monitor
	function sitemapSavePageMonitor($site, $url, $hash, $info, $pagecode) {
		$pagecode = gzcompress($pagecode, 9);
		$url = $this->esc($url);
		$info = $this->esc($info);
		$result = $this->db->prepare("INSERT INTO ".TABLE_MON_PAGES." (site, uri, hash, info, pagecode) VALUES (?, ?, ?, ?, ?)");
		if (!$result->bind_param('issss', $site, $url, $hash, $info, $pagecode)) {
			return false;
		}
		if (!$result->execute()) {
			return false;
		}
		return $this->db->insert_id;
	}
	// Actualiza el número de páginas del sitio en el monitor
	function sitemapUpdateSiteMonitor($site) {
		$add = 1;
		$result = $this->db->prepare("UPDATE ".TABLE_MON_SITES." SET pages=(pages + ?) WHERE sid=?");
		$result->bind_param('ii', $add, $site);
		if (!$result->execute()) {
			return false;
		}
		return true;
	}
	// Elimina una página con error en el monitor
	function autobusDelPageMonitor($pid) {
		if ($result = $this->db->query("DELETE FROM ".TABLE_MON_PAGES." WHERE pid=".$pid)) {
			return Lang('procAutobusDelPageA');
		}
		return Lang('procAutobusDelPageB');
	}
	// Primer sitio con autobus pendiente en el monitor
	function autobusGetSiteMonitor() {
		if (!$result = $this->db->query("SELECT M.siteid FROM ".TABLE_MON_SITEMAP." AS M, ".TABLE_MON_SITES." AS S
	WHERE S.sid = M.siteid AND M.status = 'autobus' ORDER BY M.siteid ASC LIMIT 1")) {
			$this->error($this->db->error);
		}
		if ($result->num_rows < 1) {
			return false;
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Lista de páginas a revisar - Autobus del monitor
	function autobusGetPagesMonitor($site) {
		if (!$result = $this->db->query("SELECT pid, uri, info, pagecode FROM ".TABLE_MON_PAGES." WHERE site={$site} AND tot=''")) {
			$this->error($this->db->error);
		}
		return $result;
	}
	// Finaliza el proceso autobus en el monitor
	function autobusCloseMonitor($site, $status) {
		$result = $this->db->prepare("UPDATE ".TABLE_MON_SITEMAP." SET status=? WHERE siteid=?");
		$result->bind_param('si', $status, $site);
		$result->execute();
		return;
	}
	// Guardar los resultados devueltos por el autobus del monitor
	function saveApiAutobusMonitor($pid, $tot, $nodes) {
		$title = $this->esc($tot['info']['title']);
		$score = $tot['info']['score'];
		$conform = $tot['info']['conform'];
		$tot = gzcompress(serialize($tot), 9);
		$nodes = gzcompress(serialize($nodes), 9);
		$revs = 1;
		$date = gmdate('Y-m-d H:i:s');
		$info = ''; // ya no es necesaria
		if ($result = $this->db->prepare("UPDATE ".TABLE_MON_PAGES." SET title=?, score=?, conform=?, tot=?, nodes=?, revs=(revs+?), date=?, info=? WHERE pid=?")) {
			if ($result->bind_param('sdsssissi', $title, $score, $conform, $tot, $nodes, $revs, $date, $info, $pid)) {
				if ($result->execute()) {
					$this->db->query("OPTIMIZE TABLE ".TABLE_MON_PAGES);
					return true;
				}
			} else {
				$this->error($this->db->error);
			}
		} else {
			$this->error($this->db->error);
		}
		return false;
	}
	// Obtener la información de las páginas de un sitio del monitor
	function getPagesToCalculateMonitor($site) {
		if (!$result = $this->db->query("SELECT score, tot, conform FROM ".TABLE_MON_PAGES." WHERE site={$site} AND tot<>''")) {
			return false;
		}
		return $result;
	}
	// Actualiza las estadísticas de un sitio
	function updateSiteStatsMonitor($site, $stats, $record) {
		$info = '';
		$statsdb = $this->esc($stats);
		$date = gmdate('Y-m-d H:i:s');
		if (!$result = $this->db->prepare("UPDATE ".TABLE_MON_SITES." SET pages=?, score=?, conform=?, date=?, timestats=?, stats=?, recordstats=?, info=? WHERE sid=?")) {
			$this->error($this->db->error);
		}
		if (!$result->bind_param('idssssssi', $stats['pages'], $stats['score'], $stats['conform'], $date, $date, $statsdb, $record, $info, $site)) {
			$this->error($this->db->error);
		}
		if (!$result->execute()) {
			$this->error($this->db->error);
		}
		return true;
	}
	// Obtener los datos de los sitios para las estadísticas
	function getSiteStatsMonitor($site) {
		if (!$result = $this->db->query("SELECT stats, recordstats FROM ".TABLE_MON_SITES." WHERE sid={$site} LIMIT 1")) {
			$this->error($this->db->error);
		}
		return $result->fetch_array(MYSQLI_ASSOC);
	}
	// Eliminar varias páginas del monitor
	function deletePagesMonitor($q) {
		if (!$result = $this->db->query("DELETE FROM ".TABLE_MON_PAGES." WHERE ".$q)) {
			return 0;
		}
		return $this->db->affected_rows;
	}
	// Agregar una página al monitor
	function addPageToMonitor($site, $tot, $nodes, $pagecode) {
		$title = $this->esc($tot['info']['title']);
		$uri = $tot['info']['url'];
		$score = $tot['info']['score'];
		$conform = $tot['info']['conform'];
		$hash = $tot['info']['hash'];
		$tot = gzcompress(serialize($tot), 9);
		$nodes = gzcompress(serialize($nodes), 9);
		$revs = 1;
		$date = gmdate('Y-m-d H:i:s');
		$pagecode = gzcompress($pagecode, 9);
		$result = $this->db->prepare("INSERT INTO ".TABLE_MON_PAGES." (site, title, uri, tot, score, conform, revs, date, hash, nodes, pagecode) VALUES (?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?)");
		if (!$result) {
			$this->error($this->db->error);
		}
		if (!$result->bind_param('isssdsissss', $site, $title, $uri, $tot, $score, $conform, $revs, $date, $hash, $nodes, $pagecode)) {
			$this->error($this->db->error);
			return false;
		}
		if (!$result->execute()) {
			return false;
		}
		return $this->db->insert_id;
	}
	

	


	/* FUNCIONES GENERALES */

	// Escapa los caracteres especiales (también serializa los arrays)
	function esc($text) {
		if (is_array($text)) {
			$text = serialize($text);
		}
		return $this->db->real_escape_string($text);
	}

	// Muestra el error en la base de datos y detiene el proceso
	function error($e) {
		if (!defined('HTMLHEADER')) {
			require(LIBPATH.'lib/HTMLHeader.php');
			echo '<h2>'.Lang('error').'</h2>'."\n";
		}
		echo '<div class="alert alert-error" lang="en">'.$e.'</div>'."\n";
		require(LIBPATH.'lib/HTMLFooter.php');
	}

} // class dataBase

?>
