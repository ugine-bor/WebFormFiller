<?php /* Template Name: stats */ ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>

<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/pie.js"></script>
<script src="https://cdn.amcharts.com/lib/5/radar.js"></script>

<script src="https://cdn.amcharts.com/lib/5/map.js"></script>
<script src="https://cdn.amcharts.com/lib/5/geodata/data/countries2.js"></script>

<script src="https://cdn.amcharts.com/lib/5/hierarchy.js"></script>



<script src="<?php echo get_stylesheet_directory_uri() . '/js/charts.js'; ?>"></script>

<?php get_header(); ?>

<?php
$url = strtok($_SERVER["REQUEST_URI"],'?');


if (function_exists('get_soap_data')) {
	$typs = ['xy', 'dragpie', 'radiuspie' , 'sidexy', 'donut', 'radar', 'sidexy'];
	$i=1;
	printt($typs);
	$typ = 'xy';
	

	$data1 = ["return" => ["StringB" => [
		["typeAntrag" => "WEP", "quantity" => 4],
		["typeAntrag" => "EKS", "quantity" => 4],
		["typeAntrag" => "KI", "quantity" => 3],
		["typeAntrag" => "HA", "quantity" => 6],
		["typeAntrag" => "EK", "quantity" => 4]
	]]];

	$data2 = ["return" => ["StringB" => [
		["typeAntrag" => "WEP", "quantity" => 1],
		["typeAntrag" => "EKS", "quantity" => 5]
	]]];

	$data3 = ["return" => ["StringB" => [
		["typeAntrag" => "EK", "quantity" => 5],
		["typeAntrag" => "EKS", "quantity" => 4],
		["typeAntrag" => "KI", "quantity" => 1],
		["typeAntrag" => "WEP", "quantity" => 5],
		["typeAntrag" => "HA", "quantity" => 6]
	]]];

	$data4 = ["return" => ["StringB" => [
		["typeAntrag" => "WEP", "quantity" => 2],
		["typeAntrag" => "EKS", "quantity" => 6],
		["typeAntrag" => "EK", "quantity" => 2],
		["typeAntrag" => "KI", "quantity" => 6],
		["typeAntrag" => "HA", "quantity" => 4]
	]]];

	$data5 = ["return" => ["StringB" => [
		["typeAntrag" => "EK", "quantity" => 3],
		["typeAntrag" => "EKS", "quantity" => 1],
		["typeAntrag" => "KI", "quantity" => 3],
		["typeAntrag" => "HA", "quantity" => 4]
	]]];

	$data6 = ["return" => ["StringB" => [
		["typeAntrag" => "EK", "quantity" => 5],
		["typeAntrag" => "EKS", "quantity" => 4]
	]]];

	// Create the final combined array
	$combinedObject = [
		"String1" => $data1["return"]["StringB"],
		"String2" => $data2["return"]["StringB"],
		"String3" => $data3["return"]["StringB"],
		"String4" => $data4["return"]["StringB"],
		"String5" => $data5["return"]["StringB"],
		"String6" => $data6["return"]["StringB"]
	];

	
    for ($month = 1; $month <= 6; $month++) {
        //$soap_data = get_soap_data($month);
		//printt(['aaa', $soap_data]);
        if (true) { //if ($soap_data->return) {
            //$antragdata = json_decode(json_encode($soap_data), true)['return']['StringB'];
			$antragdata = $combinedObject["String$month"];
            ?>

			<h2>Данные за месяц <?php echo $month; ?>:</h2>
            <div class="soap-data">
                <div id="chartdiv-<?php echo $month; ?>" class="chart-container"></div>
			<div id="table-container-<?php echo $month; ?>" class="tablcont"></div>
			</div>
			
				<script>
					createTable(<?php echo json_encode($antragdata); ?>, 'table-container-<?php echo $month; ?>' );
					createChart('<?php echo $typ; ?>', <?php echo json_encode($antragdata); ?>, 'chartdiv-<?php echo $month; ?>', false);
				</script>

            <?php
        }
		$typ = $typs[$i];
		$i+=1;
    }
	
	// additional graphs
	?>
	<br>
	<br>
	<h2>Другие данные:</h2>
	
	<div id="chartdiv-<?php echo $month; ?>" style="width: 100%; height: 500px;" class="chart-container"></div>
	<script>
		createChart('map', null, 'chartdiv-<?php echo $month; ?>');
	</script>
	
	<div id="chartdiv-<?php echo $month+1; ?>" style="width: 100%; height: 500px;" class="chart-container"></div>
	<script>
		createChart('tree', null, 'chartdiv-<?php echo $month+1; ?>');
	</script>
	
	<div id="chartdiv-<?php echo $month+2; ?>" style="width: 100%; height: 500px;" class="chart-container"></div>
	<script>
		createChart('info', null, 'chartdiv-<?php echo $month+2; ?>');
	</script>
	<?php
	
} else {
    echo '<p>SOAP function not available.</p>';
}


?>
<?php get_footer(); ?>