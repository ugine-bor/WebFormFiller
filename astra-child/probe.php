<?php /* Template Name: probe */ 

get_header();

wp_enqueue_script('amcharts-core', 'https://cdn.amcharts.com/lib/5/index.js', array(), null, true);
wp_enqueue_script('amcharts-maps', 'https://cdn.amcharts.com/lib/5/map.js', array('amcharts-core'), null, true);
wp_enqueue_script('amcharts-geodata', 'https://cdn.amcharts.com/lib/5/geodata/kazakhstanLow.js', array('amcharts-maps'), null, true);
wp_enqueue_script('amcharts-animated', 'https://cdn.amcharts.com/lib/5/themes/Animated.js', array('amcharts-core'), null, true);

wp_enqueue_script('probe', get_stylesheet_directory_uri() . '/js/probe.js', array('amcharts-core', 'amcharts-maps', 'amcharts-geodata', 'amcharts-animated'), '1.0', true);


try {
    $wsdl = 'https://alexsoft.kz:44321/dashboard/ws/ServiceDashboard.1cws?wsdl';

    $options = [
        'login' => 'ServiceUser',
        'password' => 'jY1sebeqW3crp',
        'trace' => 1,                 // для отладки запросов и ответов
        'exceptions' => true          // выбрасывать исключения при ошибках
    ];
	
	

    $client = new SoapClient($wsdl, $options);

    $params = [
        'ID' => '',
		'MonthFrom' => '',
		'MonthTo' => ''
    ];

    $response = $client->__soapCall('GetOrganizationSum', [$params]); //, [$params]
	$response = json_encode((array) $response->return);

} catch (SoapFault $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>

<script>
var soapResponse = <?php echo $response; ?>;
</script>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>

            <div class="entry-content">
                <div id="kazakhstanMap" style="width: 100%; height: 500px;"></div>

                <?php
                the_content();
                ?>
            </div>
        </article>
    </main>
</div>

<?php
get_footer();
?>