<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);
?>

<p>
	<?php
	$user_id = $current_user->ID;
	//echo "<script>console.log('" . $user_id . "');</script>";
	printf(
		/* translators: 1: user display name 2: logout url */
		wp_kses( __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ), $allowed_html ),
		'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
		esc_url( wc_logout_url() )
	);
	?>
</p>

<p>
	<?php
	/* translators: 1: Orders URL 2: Address URL 3: Account URL. */
	$dashboard_desc = __( 'На главной странице аккаунта Вы можете посмотреть Ваши <a href="%1$s">недавние заказы</a>, а также <a href="%3$s">изменить пароль и основную информацию</a>.', 'woocommerce' );
	if ( wc_shipping_enabled() ) {
		/* translators: 1: Orders URL 2: Addresses URL 3: Account URL. */
		$dashboard_desc = __( 'На главной странице аккаунта вы можете посмотреть Ваши <a href="%1$s">недавние заказы</a>, а также <a href="%3$s">изменить пароль и основную информацию</a>.', 'woocommerce' );
	}
	printf(
		wp_kses( $dashboard_desc, $allowed_html ),
		esc_url( wc_get_endpoint_url( 'orders' ) ),
		esc_url( wc_get_endpoint_url( 'edit-address' ) ),
		esc_url( wc_get_endpoint_url( 'edit-account' ) )
	);
	?>
</p>

<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );
?>
<div class="custom-account-buttons">
    <!-- Ссылка на страницу -->
    Ознакомьтесь с <a href="<?php echo esc_url( home_url( '/terms-and-conditions' ) ); ?>" class="custom-link">
        пользовательским соглашением
    </a>. Вы можете удалить все личные данные из базы данных на нашем сервере, нажав кнопку ниже
   <!-- Кнопка для запроса к WSDL сервису -->
    <form method="POST" action="">
        <button type="submit" name="wsdl_request" class="woocommerce-button button custom-wsdl-button">
            Удалить личные данные
        </button>
    </form>
</div>

<?php
// Обработка запроса к WSDL
if (isset($_POST['wsdl_request'])) {
	// Удаление сессионных данных
	unset($_SESSION['addAccess']);
	unset($_SESSION['formData']);
	unset($_SESSION['orderid']);
	/////////////////////////////
	
     $data = array(    
    "action" => "DeleteDate",
      "message"=> array(
        "message_id" => 12345,
        "from"=> array(
            "id"=>  $user_id,
            "is_bot"=>  false,
            "first_name"=>  wp_get_current_user()->first_name,
            "last_name"=>  wp_get_current_user()->last_name,
            "language_code"=>  "ru"
        ),
        "chat"=> array(
            "id"=>  $user_id,
            "first_name"=>  wp_get_current_user()->first_name,
            "last_name"=>  wp_get_current_user()->last_name,
            "type"=>  "private"
        )
    )
  );

		  $jsonData = json_encode($data, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES);
	// Добавляем вывод в консоль
    add_action('wp_footer', function() use ($jsonData) {
        echo '<script>console.log("JSON Data: ", ' . json_encode($jsonData) . ');</script>';
    });

		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, WSDL_BOT_URL_DEL);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic ' . base64_encode(WSDL_BOT_PASS)));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  
		  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		  $server_output = curl_exec($ch);
		  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		 

			if ($server_output === false) {
				$error = curl_error($ch);
				echo "<div id='result'><p style='display: block; border: 1px solid #ff4d4d; background-color: #ffe6e6;'>Ошибка: " . htmlspecialchars($error) . "</p></div>";
				//echo "<script>console.error(" . json_encode('Ошибка: ' . $error) . ");</script>";
				//return false;
			} else {
				 if ($http_code != 200) {
               echo "<div id='result' style='display: block; border: 1px solid #ff4d4d; background-color: #ffe6e6;'><p style='color: red;'>Код ответа: $http_code. Произошла ошибка при запросе.</p></div>";
            } else {
             //   echo "<div id='result' style='display: block; border: 1px solid #2c7c2c; background-color: #e6ffe6;'><p style='color: green;'>Ответ сервера: " . htmlspecialchars($server_output) . "</p></div>";
          //  }
					 // Декодируем JSON и получаем значение "text"
            $response_data = json_decode($server_output, true);
            $text = $response_data['text'] ?? 'Текст не найден';

            echo "<div id='result' style='display: block; border: 1px solid #2c7c2c; background-color: #e6ffe6;'><p style='color: green;'>" . htmlspecialchars($text) . "</p></div>";
        }
			}
	 curl_close($ch);
			//printt(['FINAL', $jsonData]);
}
?>
	
	<?php
	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );


	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
