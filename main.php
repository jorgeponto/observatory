<?php
$abs = preg_replace('@\\\@', '/', dirname(__FILE__));
define('ABSPATH', $abs.'/');
$host = $_SERVER['HTTP_HOST'];
switch ($host) {
	case 'www.acessibilidade.gov.pt':
	case 'acessibilidade.gov.pt':
		define('WORKSPACE', 'web');
		define('HOST', 'http://www.acessibilidade.gov.pt/observatorio/');
		define('BASE', '/observatorio/');
		define('BASELIB', '/ampluslib/');
		define('HOSTLIB', 'http://www.acessibilidade.gov.pt/ampluslib/');
		break;
	case 'localhost':
		define('WORKSPACE', 'local');
		define('HOST', 'http://localhost/a-oia/');
		define('BASE', '/a-oia/');
		define('BASELIB', '/ampluslib/');
		define('HOSTLIB', 'http://localhost/ampluslib/');
		break;
}

$abs2 = preg_replace('@\/[^\/]+$@', BASELIB, $abs);
define('LIBPATH', $abs2);

require_once(LIBPATH.'config.php');

?>
