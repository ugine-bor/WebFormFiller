<?php

defined('ABSPATH') || exit;

class Order{
	private $order_id;
	private $order;
	
	public function __construct($order_id){
		$this->order_id = $order_id;
		$this->order = wc_get_order($order_id);
	}
	
	public function get_order_id() {
        return $this->order_id;
    }

    public function get_order() {
        return $this->order;
    }
	
	public function is_order_belong() {
		
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		if ( $order && $order->get_user_id() === $user_id ) {
			return true;
		}

		return false;
	}
}