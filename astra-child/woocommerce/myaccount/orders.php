<?php

class OrderManager {
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

        foreach ($orders as $wc_order) {
            $order = new Order($wc_order);
            $order->display();
        }
    }
}


class Order {
    private $wc_order;
    private $products = [];

    public function __construct($wc_order) {
        $this->wc_order = $wc_order;
        $this->initProducts();
    }

    private function initProducts() {
        $order_id = $this->getId();
        $index = 1;
        foreach ($this->wc_order->get_items() as $item) {
            $unique_id = "{$order_id}-{$index}";
            $this->products[] = new Product($item, $unique_id);
            $index++;
        }
    }

    public function getId() {
        return $this->wc_order->get_id();
    }

    public function getStatus() {
        return wc_get_order_status_name($this->wc_order->get_status());
    }

    public function getStatusText() {
        $status_descriptions = [
            'Выполнен' => 'Ваш заказ успешно выполнен.',
            'Ожидается оплата' => 'Ваш заказ ожидает оплаты. Попробуйте оплатить повторно.',
            'Отменён' => 'Ваш заказ был отменён.',
            'Обработка' => 'Ваш заказ принят в работу.',
            'Возвращён' => 'Ваш заказ был возвращён.',
            'Не удался' => 'Произошла ошибка при обработке вашего заказа.',
        ];
        return $status_descriptions[$this->getStatus()] ?? 'Статус заказа неизвестен.';
    }

    public function getDate() {
        return $this->wc_order->get_date_created()->date_i18n('d.m.Y H:i:s');
    }

    public function getPaymentUrl() {
        return $this->wc_order->get_checkout_payment_url();
    }

	public function display() {
		$order_id = $this->getId();
		$status = $this->getStatus();
		$status_text = $this->getStatusText();
		$date = $this->getDate();
		$payment_url = $this->getPaymentUrl();
		$products_html = $this->displayProducts();

		// Карта классов статусов
		$status_class_map = [
			'Обработка' => 'processing-p',
			'Ожидается оплата' => 'on-hold-p',
			'Выполнен' => 'completed-p',
			'Отменён' => 'cancelled-p',
			'Возвращён' => 'refunded-p',
			'Не удался' => 'failed-p',
		];
		$status_class = $status_class_map[$status] ?? 'unknown-status';

		// Кнопка оплаты, если статус "Ожидается оплата"
		$payment_button = ($status === 'Ожидается оплата') 
			? "<a href='" . esc_url($payment_url) . "' class='btn-show-entries'>" . esc_html__('Оплатить', 'woocommerce') . "</a>" 
			: '';

		// Генерация HTML
		echo "
		<div class='order'>
			<div class='order-summary'>
				<p><strong>ID заказа:</strong> " . esc_html($order_id) . "</p>
				<p><strong>Дата:</strong> " . esc_html($date) . "</p>
				<div class='order-status'>
					<strong>Состояние:</strong>
					<span class='order-status-label {$status_class}' data-tooltip='" . esc_html($status_text) . "'>
						" . esc_html($status) . "
					</span>
					<span class='question-mark' title='" . esc_html($status_text) . "'>?</span>
				</div>
				<div class='order-actions'>
					" . OrderManager::addButton('Подробнее о заказе', '/wordpress/order_detail', ['id' => $order_id], 'btn-show-entries') . "
					<a href='#' class='btn-show-entries toggle-order-items' data-order-id='" . esc_attr($order_id) . "'>Показать антраги</a>
					{$payment_button}
				</div>
			</div>
			<div class='order-items' id='items-" . esc_attr($order_id) . "' style='display:none;'>
				{$products_html}
			</div>
		</div>";
	}


    private function displayProducts() {
        $output = '';
        foreach ($this->products as $product) {
            $output .= '<div class="product">' . $product->displayTagActions($this->getId(), $this->getStatus()) . '</div>';
        }
        return $output;
    }
}



class Product {
    private $item;
	private $id;

    public function __construct($item,$id) {
        $this->item = $item;
		$this->id = $id;
    }

    public function getName() {
        return $this->item->get_name();
    }

    public function getProductId() {
        return $this->item->get_product_id();
    }

    public function getTags() {
        $product_id = $this->getProductId();
        $tags = get_the_terms($product_id, 'product_tag');
        return is_wp_error($tags) || !$tags ? [] : $tags;
    }

public function displayTagActions($order_id, $order_status) {
    $output = '';
    foreach ($this->getTags() as $tag) {
        $tag_name = $tag->name;
        if (substr($tag_name, -1) === '0') {
            $display_tag_name = rtrim($tag_name, '0');
            $output .= '<div class="product-tag">';
            $output .= '<div>' . strtoupper(esc_html($display_tag_name)) . '</div>';
            if (in_array($order_status, ['Обработка', 'Выполнен'], true)) {
                $output .= OrderManager::addButton(
                    'Заполнить',
                    '/zapoln',
                    ['antr' => strtoupper($display_tag_name), 'ord' => $this->id, 'dem' => 0],
                    'btn-show-entries'
                );
            }
            $output .= '</div>';
        }
    }
    return $output;
}

}

get_header();

$orderManager = new OrderManager();

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
?>
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/orders.css'; ?>">
<div id="order-container" class="order-container">
    <?php $orderManager->displayOrders(); ?>
</div>

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

