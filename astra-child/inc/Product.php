<?php

defined('ABSPATH') || exit;

class Product{
	private $glob_id; #5983(ha)
	private $order_manager;
	private $product;
	
	public $index; #6002-1
	public $id; #6002
	public $number; #0
	public $slugs = array(
        'slugs' => [],
        'typ' => 1 # Default value is 1. 2 is for -a. 0 is for -profi 
	);
	public $isdemo;
	
	public function __construct($index, $isdemo, Order $order_manager){
		$this->index = $index; #6002-1
		$this->number = (int)explode('-', $index)[1]-1;
		$this->id = array_values($order_manager->get_order()->get_items())[$this->number]->get_product_id();
		printt($this->id);
		$this->isdemo = $isdemo;
		$this->order_manager = $order_manager;
		$this->glob_id = array_values((array)$order_manager->get_order()->get_items())[$this->number]->get_product_id();
		$this->product = wc_get_product($this->glob_id);
		
		if (!$isdemo){
			$slugs = $this->get_slugs();
		}
	}
	
	public function get_product_id(){
		return $this->glob_id;
	}
	
	public function get_product(){
		return $this->product;
	}
	
	public function get_slugs() {
		$product_slugs_list = array();
		$profi_flag = 1; // Default value is 1. 2 is for -a. 0 is for -profi 

		// Iterate through each item in the order.

			if ( $this->product ) { // Check if the product exists.
				$product_slug = $this->product->get_slug(); // Get the product slug.
				$base_slug = str_replace(array('-profi', '-a'), '', $product_slug); // Remove suffixes.

				// Determine flag value based on suffix.
				if ( substr( $product_slug, -6 ) === '-profi' ) {
					$profi_flag = 0;
				} elseif ( substr( $product_slug, -2 ) === '-a' ) {
					$profi_flag = 2;
				} else {
					$profi_flag = 1;
				}

				// Convert slug to uppercase and add to the list.
				$product_slugs_list[] = strtoupper($base_slug);
			}
			
		// Return array with slugs as list and flag.
		$this->slugs = array(
			'slugs' => $product_slugs_list,
			'typ' => $profi_flag
		);
		return;
	}
}