<?php
// http://demos.bavotasan.com/tango/shortcodes/ 
// https://themes.bavotasan.com/themes/tango-wordpress-theme/
// http://getbootstrap.com/css/
header("Content-type: text/html; charset=utf-8");
$pagetitle = 'Observatório Português da Acessibilidade Web';
$pageh1 = '<span>Observatório Português da</span> Acessibilidade Web';
$pagedesc = '';
$htm='Completar';
if (isset($title)) {
	if ($title=='accessmonitor') {
		$htm='';
		$pagetitle = 'AccessMonitor';
		//$pageh1 = 'AccessMonitor';
		//$pagedesc = '<p>Validador de práticas de acessibilidade Web (<abbr title="Web Content Accessibility Guidelines" lang="en">WCAG</abbr> 2.0).</p>'."\n";
	} else if ($title=='monitor') {
		$pagetitle = 'Sistema de Monitorização';
		$pageh1 = 'Sistema de Monitorização';
	}
}

$title	= (isset($title))? '. '.$title : 'Observatório Português da Acessibilidade Web';
$bc 	= (isset($bc))? '<a href="'.HOST.'">'.Lang('startPage').'</a> &rArr; '.$bc : '&nbsp;';
$chart = '';
$data2title = (isset($data2title))? $data2title : Lang('chartRepeatedErrors');
$base = (isset($base))? $base : '';
if (isset($data)) {
	$chart = '<script src="https://www.google.com/jsapi"></script>
<script>
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawChart);
	function drawChart() {
		var data = new google.visualization.DataTable();
		data.addColumn("string", "'.Lang('chartDataInt').'");
		data.addColumn("number", "'.Lang('chartDataFrec').'");
		data.addColumn("number", "'.Lang('chartDataAcum').'");
		data.addRows([
'.$data.'
		]);
		var options = {
			width: 600, height: 300,
			hAxis: {title: "'.Lang('chartDataInt').' ('.Lang('scores').')", titleTextStyle: {color: "#900"}},
			vAxis: {title: "'.Lang('chartDataFrec').' (%)", titleTextStyle: {color: "#900"}},
			series: {1: {type: "line", pointSize: "5"}},
		};
		var chart = new google.visualization.ColumnChart(document.getElementById("scores-chart"));
		chart.draw(data, options);'."\n";
	if (isset($data2) && ($data2 != '')) {
		$chart .= '		var data2 = google.visualization.arrayToDataTable([
'.$data2.'
        ]);
		new google.visualization.BarChart(document.getElementById("errors-chart")).draw(data2, {title:"'.$data2title.'", colors:["#843511"], legend:{position:"none"}, hAxis:{gridlines:{count:10}}, width:600, height:300});'."\n";
	}
	$chart .= '	}
</script>'."\n";
}

if ($htm=='') {
	// Amplus tiene un encabezado distinto
	// Código HTML quitado
	/* <nav><?php echo $bc; ?></nav>
				<?php echo '<h1 id="skipcontent">'.$pageh1.'</h1>'."\n".$pagedesc; ?>
	*/
	$htm = '<nav class="amplusnav">você está em <a href="/">acesso</a> &rArr;</nav>
				<h1 id="skipcontent" class="center"><img src="'.BASELIB.'img/accessmonitor_50.png" alt="'.Lang('amplusLogoAlt').'" /></h1>';
} else {
	$htm = '<nav>'.$bc.'</nav>
				<h1 id="skipcontent">'.$pageh1.'</h1>';
}


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
<link rel="stylesheet" href="'.HOSTLIB.'amplus.css" />'."\n".$chart;
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
					<!-- alteração JF - logo: home h1 sem link; interiores div com link -->
						<div id="site-logo"><a href="http://www.acessibilidade.gov.pt/" title="Ir para a página de entrada da Unidade ACESSO da FCT" rel="home"><img src="http://www.acessibilidade.gov.pt/wordpress/wp-content/uploads/2013/06/unidade_acesso1.png" alt="Unidade ACESSO da FCT" /></a></div>
				</div> <!--hgroup-->
			</div><!-- .c12 -->
		</header><!-- #header .row -->
	</div><!-- grid -->
	<div class="top-line"></div>
	<div class="grid ">
		<div id="main" class="row">
			<div id="primary" class="c12">
				<?php echo $htm; ?>
