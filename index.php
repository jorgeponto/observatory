<?php
require('main.php');

$cats = $db->getStatsFull();
$trs = '';
$i=0;
$tags = $db->getCatsOia();

while($c = $cats->fetch_array(MYSQLI_ASSOC)) {
	$code = $c['code'];
	if ($code == 'all') {
		$scr = round($c['score']);
		$ico = resIcon($scr);
		$summary = '<h2>Amostra Global</h2>
<div class="alert alert-info">
<ul id="summary">
<li>'.Lang('sites').': <strong>'.$c['sites'].'</strong></li>
<li>'.Lang('pages').': <strong>'.$c['pages'].'</strong></li>
<li>'.Lang('monSiteDate').': <strong>'.date(Lang('dateFormat'), strtotime($c['date'])).'</strong></li>
</ul>
</div>'."\n";
	} else {
		if (!array_key_exists($code, $tags)) {
			continue;
		}
		if ($tags[$code]['oia']=='n') {
			continue;
		}
		if ($c['sites']==0) {
			continue;
		}
		list($A, $AA, $AAA) = explode(",", $c['conform']);
		$i++;
		$trs .= '<tr>
<td class="center">'.$i.'</td>
<td class="left"><a href="'.BASE.'tags/'.$c['code'].'">'.$tags[$code]['name'].'</a></td>
<td>'.$c['score'].'</td>
<td>'.$c['sites'].'</td>
<td>'.$A.'</td>
<td>'.$AA.'</td>
<td>'.$AAA.'</td>
</tr>'."\n";
	}
}

require(LIBPATH.'lib/HTMLHeader.php');
echo $summary;

echo '<table class="sortable">
<caption>'.Lang('oiaDirs').'</caption>
<tr>
<th rowspan="2" scope="col" class="mini">'.Lang('rank').'</th>
<th rowspan="2" scope="col">'.Lang('oiaSiteNameTable').'</th>
<th rowspan="2" scope="col" class="mini">'.Lang('score').'</th>
<th rowspan="2" scope="col" class="mini">'.Lang('sites').'</th>
<th colspan="3" scope="col">'.Lang('oiaConformSiteTh').'</th>
</tr>
<tr>
<th scope="col" class="mini">A</th>
<th scope="col" class="mini">AA</th>
<th scope="col" class="mini">AAA</th>
</tr>'."\n".$trs.'</table>
<p>'.Lang('oiaConformP').'</p>'."\n";

require(LIBPATH.'lib/HTMLFooter.php');

?>
