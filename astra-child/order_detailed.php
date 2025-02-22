<?php
/* Template Name: order_detailed */

//if (!is_user_logged_in()) {
//    wp_redirect(wp_login_url());
//    exit;
//}

get_header();

$user_id = get_current_user_id();
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id) {
    $order = wc_get_order($order_id);

    if ($order && $order->get_user_id() === $user_id) {
        $billing_address = $order->get_formatted_billing_address() ? $order->get_formatted_billing_address() : 'Не указано';
        $shipping_address = $order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : 'не указано';
        $billing_email = $order->get_billing_email() ? $order->get_billing_email() : 'Не указано';
        $billing_phone = $order->get_billing_phone() ? $order->get_billing_phone() : 'Не указано';
        $billing_first_name = $order->get_billing_first_name() ? $order->get_billing_first_name() : 'Не указано';
        $billing_last_name = $order->get_billing_last_name() ? $order->get_billing_last_name() : ' ';
        ?>

        <div class="order-details-container">
            <h2>Заказ №<?php echo $order->get_order_number(); ?></h2>
            <h3>Товары в заказе</h3>
            <table class="order-details-table">
                <thead>
                    <tr>
                        <th>Продукт</th>
                        <th>Количество</th>
                        <th>Цена</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($order->get_items() as $item_id => $item) {
                        $product = $item->get_product();
                        ?>
                        <tr>
                            <td><?php echo $product->get_name(); ?></td>
                            <td><?php echo $item->get_quantity(); ?></td>
                            <td><?php echo wc_price($item->get_total()); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <h3>Детали оплаты</h3>
            <p><strong>Имя:</strong> <?php echo $billing_first_name . ' ' . $billing_last_name; ?></p>
            <p><strong>Email:</strong> <?php echo $billing_email; ?></p>
            <p><strong>Телефон:</strong> <?php echo $billing_phone; ?></p>
            <p><strong>Адрес:</strong> <?php echo $billing_address; ?></p>

            <?php if ($shipping_address !== 'Не указано') : ?>
                <h3>Детали доставки</h3>
                <p><strong>Адрес:</strong> <?php echo $shipping_address; ?></p>
            <?php endif; ?>

            <h3>Детали заказа</h3>
            <p><strong>Дата:</strong> <?php echo wc_format_datetime($order->get_date_created()); ?></p>
            <p><strong>Итого:</strong> <?php echo $order->get_formatted_order_total(); ?></p>
            <p><strong>Метод оплаты:</strong> <?php echo $order->get_payment_method_title(); ?></p>

            <a href="<?php echo wc_get_account_endpoint_url('orders'); ?>" class="button-primary">Назад к заказам</a>
        </div>
        <?php
    } else {
        echo '<p>У вас нет прав для просмотра этого заказа.</p>';
    }
} else {
    echo '<p>Неверный ID заказа.</p>';
}

get_footer();