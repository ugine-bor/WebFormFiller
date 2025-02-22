<?php

function printt($variable) {
    echo "<script>console.log('" . json_encode($variable) . "');</script>";
}

class Order {
    private $user;

    public function __construct() {
        $this->user = wp_get_current_user();
    }

    public function isUserLoggedIn() {
        return is_user_logged_in();
    }

    public function checkDependencies() {
        return class_exists('WooCommerce') && class_exists('UM');
    }

    public function getOrders() {
        $args = [
            'customer_id' => $this->user->ID,
            'post_status' => array_keys(wc_get_order_statuses()),
        ];
        return wc_get_orders($args);
    }

    public static function addButton($label, $url, $params, $style) {
        $querystring = http_build_query($params);
        $fullurl = "$url/?$querystring";
        return "<a href=\"$fullurl\" class=\"$style\">$label</a>";
    }

    public function displayOrders() {
        $orders = $this->getOrders();

        if (empty($orders)) {
            echo '<p>У вас нет заказов.</p>';
            return;
        }

        foreach ($orders as $order) {
            $this->displayOrder($order);
        }
    }

    private function displayOrder($order) {
        $order_id = $order->get_id();
        $order_items = $order->get_items();
        $order_name = $this->getOrderItemsNames($order_items);
        $order_status_en = $order->get_status();
        $order_status = wc_get_order_status_name($order_status_en);
        $order_payment_url = $order->get_checkout_payment_url();
        $order_date = $order->get_date_created()->date_i18n('d.m.Y H:i:s');
        $status_class = $this->getStatusClass($order_status);

        echo "<div class='order'>
            <div class='order-summary'>
                <p><strong>ID заказа:</strong> " . esc_html($order_id) . "</p>
                <p><strong>Дата:</strong> " . esc_html($order_date) . "</p>
                <div class='order-status'>
                    <strong>Состояние:</strong>
                    <span class='order-status-label " . esc_attr($status_class) . "'>" . esc_html($order_status) . "</span>
                </div>
                <div class='order-actions'>
                    " . self::addButton('Подробнее о заказе', '/wordpress/order_detail', ['id' => $order_id], 'btn-show-entries') . "
                    <a href='#' class='btn-show-entries toggle-order-items' data-order-id='" . esc_attr($order_id) . "'>Показать антраги</a>
                    " . ($order_status_en === 'pending' ? "<a href='" . esc_url($order_payment_url) . "' class='btn-show-entries'>Оплатить</a>" : "") . "
                </div>
            </div>
            <div class='order-items' id='items-" . esc_attr($order_id) . "' style='display:none;'>";
        foreach ($order_items as $item) {
            $product_id = $item->get_product_id();
            $product_tags = get_the_terms($product_id, 'product_tag');
            if ($product_tags && !is_wp_error($product_tags)) {
                foreach ($product_tags as $tag) {
                    $tag_name = $tag->name;
                    if (substr($tag_name, -1) === '0') {
                        $display_tag_name = rtrim($tag_name, '0');
                        echo "<div class='product-tag'>
                            <div>" . strtoupper(esc_html($display_tag_name)) . "</div>
                            " . ($order_status === 'Обработка' || $order_status === 'Выполнен' ? self::addButton('Заполнить', '/zapoln', ['antr' => strtoupper(esc_html($display_tag_name)), 'ord' => $order_id, 'dem' => 0], 'btn-show-entries') : "") . "
                        </div>";
                        break;
                    }
                }
            } else {
                echo '<p>' . esc_html($item->get_name()) . '</p>';
            }
        }
        echo "</div>
        </div>";
    }

    private function getOrderItemsNames($order_items) {
        $order_name = '';
        foreach ($order_items as $item) {
            $order_name .= ($order_name ? ', ' : '') . $item->get_name();
        }
        return $order_name;
    }

    private function getStatusClass($order_status) {
        $status_classes = [
            'Обработка' => 'processing-p',
            'Ожидается оплата' => 'on-hold-p',
            'Выполнен' => 'completed-p',
            'Отменён' => 'cancelled-p',
            'Возвращён' => 'refunded-p',
            'Не удался' => 'failed-p',
        ];
        return $status_classes[$order_status] ?? 'unknown-status';
    }
}

get_header();

$orderManager = new Order();

if (!$orderManager->checkDependencies()) {
    echo '<p>WooCommerce или Ultimate Member не активированы.</p>';
    get_footer();
    exit;
}

if (!$orderManager->isUserLoggedIn()) {
    echo '<p>Пожалуйста, войдите в систему, чтобы увидеть свои заказы.</p>';
    get_footer();
    exit;
}
printt('done searchign');

?>

<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/orders.css'; ?>">
<?php printt('done cssing'); ?>

<div id="<?php echo esc_attr($orderManager->user->ID); ?>_container" class="order-container">
    <?php $orderManager->displayOrders(); ?>
</div>

<?php printt('done displayin'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-order-items').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var orderId = this.getAttribute('data-order-id');
            var itemsDiv = document.getElementById('items-' + orderId);
            itemsDiv.style.display = itemsDiv.style.display === 'none' ? 'block' : 'none';
        });
    });
});
</script>
