<?php
mb_internal_encoding("UTF-8");

add_action('wp_enqueue_scripts', function() {
	wp_enqueue_style('child-theme', get_stylesheet_directory_uri() . '/style.css');	
	printt("style.css loaded");

    $styles = [
        'charts' => 'stats.php'
    ];

    foreach ($styles as $name => $template) {
        if (is_page_template($template) ) {
            $handle = 'child' . $name . '-theme';
            $src = get_stylesheet_directory_uri() . '/' . $name . '.css';
            wp_enqueue_style($handle, $src);
            printt($name . '.css loaded');
        }
    }
});


//------------------------------------------------для woocommerce не трогать
//скроем вкладку Загрузки на странице Мой аккаунт
add_filter( 'woocommerce_account_menu_items', 'remove_downloads_my_account', 999 );
function remove_downloads_my_account( $items ) {
    unset( $items['downloads'] ); // Удаляет вкладку "Загрузки"
    return $items;
}


// Убираем обязательность полей "Имя" и "Фамилия" в профиле
add_filter( 'woocommerce_save_account_details_required_fields', 'remove_required_account_fields' );

function remove_required_account_fields( $required_fields ) {
    unset( $required_fields['account_first_name'] ); // Убирает обязательность для имени
    unset( $required_fields['account_last_name'] );  // Убирает обязательность для фамилии
    return $required_fields;
}


//разрешаем ВРЕМЕННО добавление только одного товара в корзину
add_filter( 'woocommerce_add_to_cart_validation', 'restrict_cart_to_one_item', 10, 2 );

function restrict_cart_to_one_item( $passed, $product_id ) {
    // Проверяем, есть ли уже товар в корзине
    if ( WC()->cart->get_cart_contents_count() > 0 ) {
        // Выводим уведомление и запрещаем добавление товара
        wc_add_notice( __( 'Вы можете добавить в корзину только один товар.', 'woocommerce' ), 'error' );
        return false; // Возвращаем false, чтобы не добавлять товар
    }
    return $passed;
}


//добавим страницу условия
function set_woocommerce_terms_page() {
    update_option('woocommerce_terms_page_id', 7422); 
}
add_action('init', 'set_woocommerce_terms_page');

// Сохраняем состояние чекбокса в метаданные заказа
add_action('woocommerce_checkout_update_order_meta', 'save_popup_terms_checkbox');
function save_popup_terms_checkbox( $order_id ) {

    // Проверяем наличие чекбокса в $_POST
    if ( isset($_POST['terms']) ) {
        update_post_meta( $order_id, '_popup_accept_terms', true );
    } else {
        update_post_meta( $order_id, '_popup_accept_terms', false );
    }
}

// Отображаем состояние галочки в админке на странице заказа
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_popup_terms_checkbox_in_admin', 10, 1 );
function display_popup_terms_checkbox_in_admin( $order ){
    $accepted_popup_terms = get_post_meta( $order->get_id(), '_popup_accept_terms', true );
    echo '<p><strong>Согласие с условиями (Popup):</strong> ' . ( $accepted_popup_terms ? 'Да' : 'Нет' ) . '</p>';
}

//function woocommerce_support() {
 //   add_theme_support( 'woocommerce' );
//}
//
//add_action( 'after_setup_theme', 'woocommerce_support' );

//удаляем вкладку детали на сингл продукт
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );
function woo_remove_product_tabs( $tabs ) {
unset( $tabs['additional_information'] ); // Remove the additional information tab
unset( $tabs['description'] ); // Убираем вкладку "Описание"
return $tabs;
}

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

add_action('woocommerce_before_single_product', 'woocommerce_template_single_title', 10);
add_action('woocommerce_before_single_product', 'woocommerce_template_single_excerpt', 20);



function show_description() {
    global $product;
    echo '<h6 style="margin:30px 0">';
	echo $product->get_description();
	echo '</h6>';
}
add_action('woocommerce_before_single_product','show_description', 30);

//меняем статус 
add_action('woocommerce_thankyou', 'halyk_epay_debug_order_status_thankyou');

function halyk_epay_debug_order_status_thankyou($order_id) {
     if( ! $order_id ) return;
    // Получим данные заказа
    $order = wc_get_order( $order_id );
	//print_r( $order->get_date_paid() );
    // Если статус заказа processing изменим его на pending 
    if(( $order->get_status() == 'completed') or ($order->get_status() == 'pending'))
        $order->update_status( 'processing' );
}




//---------------------------------------------------------------------------------
// hasMerchantReturnPolicy, shippingDetails B offers
add_filter('woocommerce_structured_data_product', 'add_custom_structured_data', 10, 2);
function add_custom_structured_data($markup, $product) {
    // Получаем базовую цену товара
    $price = $product->get_price();
    
    // Политика возвратов
    $markup['offers']['hasMerchantReturnPolicy'] = array(
        '@type' => 'MerchantReturnPolicy',
        'applicableCountry' => 'RU',
        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
        'merchantReturnDays' => 14,
        'returnMethod' => 'https://schema.org/ReturnByMail',
        'returnFees' => 'https://schema.org/FreeReturn' // Добавлено поле
    );

    // Условия доставки
    $markup['offers']['shippingDetails'] = array(
        '@type' => 'OfferShippingDetails',
        'shippingRate' => array(
            '@type' => 'MonetaryAmount',
            'value' => '0',
            'currency' => 'RUB'
        ),
        'shippingDestination' => array(
            '@type' => 'DefinedRegion',
            'addressCountry' => 'RU'
        ),
        'deliveryTime' => array(
            '@type' => 'ShippingDeliveryTime',
            'handlingTime' => array(
                '@type' => 'QuantitativeValue',
                'minValue' => 1,
                'maxValue' => 3,
                'unitCode' => 'DAY' // Добавлено поле
            ),
            'transitTime' => array(
                '@type' => 'QuantitativeValue',
                'minValue' => 1,
                'maxValue' => 5,
                'unitCode' => 'DAY' // Добавлено поле
            )
        )
    );

    // Спецификация цены
    $markup['offers']['priceSpecification'] = array(
        '@type' => 'PriceSpecification',
        'price' => $price,
        'priceCurrency' => get_woocommerce_currency(),
        'priceValidUntil' => date('Y-m-d', strtotime('+1 month')) // Установка срока действия цены
    );

    // Добавляем обязательные поля уровня offers
    $markup['offers']['availability'] = 'https://schema.org/' . ($product->is_in_stock() ? 'InStock' : 'OutOfStock');
    $markup['offers']['price'] = $price;
    $markup['offers']['priceCurrency'] = get_woocommerce_currency();

    return $markup;
}





function read_json($file_path) {
    $full_path = get_template_directory() . '/' . $file_path;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        return json_decode($content, true);
    }
    return null;
}

//вывод значения в консоль (для дебага)
function printt($variable) {
    echo "<script>console.log('" . json_encode($variable) . "');</script>";
}

//вернуть видимые границы для таблицы (для дебага)
function custom_table_styles() {
	return;//отключил
    echo '<style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        table, th, td {
            border: 1px solid black;
        }
    </style>';
}
add_action('wp_head', 'custom_table_styles');

//Обработка xls файла
function getField($lable, $name) {
 if( get_field('field_name') ): ?>
   <p><b><?php echo $lable?>: </b> <?php the_field($name); ?></p>
<?php endif; 
}
?>
