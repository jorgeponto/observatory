<?php
header("Content-type: text/html; charset=utf-8");
$pagetitle = 'AccessMonitor';
$pageh1 = 'AccessMonitor';
$pagedesc = 'o validador de práticas de acessibilidade Web';

$nav = 'você está em <a href="/">acesso</a> &rArr;';
$nav .= (isset($bc))? ' <a href="'.HOST.'">accessmonitor</a> &rArr;' : '';
$base = (isset($base))? $base : '';

// Para saber si los errores en la base de datos deben escribir header
define('HTMLHEADER', true);
?>
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="pt-PT"><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8" lang="pt-PT"><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9" lang="pt-PT"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="pt-PT"><!--<![endif]-->
<head>
<meta charset="UTF-8" />
<!--[if IE]<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php echo $base; ?><title><?php echo $pagetitle; ?></title>
<link rel="stylesheet" id="theme_stylesheet-css" href="http://www.acessibilidade.gov.pt/wordpress/wp-content/themes/tango/style.css?ver=3.5.1" type="text/css" media="all" />
<script type="text/javascript" src="http://www.acessibilidade.gov.pt/wordpress/wp-includes/js/jquery/jquery.js?ver=1.8.3"></script>
<script type="text/javascript" src="http://www.acessibilidade.gov.pt/wordpress/wp-content/themes/tango/library/js/modernizr.min.js?ver=2.6.2"></script>
<link rel="stylesheet" href="http://www.acessibilidade.gov.pt/wordpress/wp-content/plugins/related-posts/static/themes/plain.css?version=3.4.5" />
<?php
	echo '<script type="text/javascript" src="'.HOSTLIB.'jquery-ui.min.js"></script>
<link rel="stylesheet" href="'.HOSTLIB.'amplus.css" />'."\n";
?>
</head>

<body class="home blog custom-background">
<div id="page">
	<div class="grid ">
		<header id="header" class="row">
			<div class="c12">
				<div class="go-to-maincontent">
					<a href="#skipcontent" accesskey="2">Saltar para o corpo principal da página (tecla de atalho: 2)</a>
				</div>
				<div class="hgroup">
						<nav style="margin-top:1em"><?php echo $nav; ?></nav>
						<div id="site-logo"><img src="<?php echo BASELIB; ?>img/accessmonitor_50.png" alt="<?php echo Lang('amplusLogoAlt'); ?>" /></div>
				</div> <!--hgroup-->
			</div><!-- .c12 -->
		</header><!-- #header .row -->
	</div><!-- grid -->
	<div class="top-line"></div>
	<h1 id="skipcontent" class="assistive-text">AccessMonitor</h1>

