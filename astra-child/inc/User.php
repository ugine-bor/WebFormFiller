<?php

defined('ABSPATH') || exit;

class User{
	private $id;
	
	public function __construct($id){
		$this->id = $id;
	}
	
	public function get_user_id(){
		return $this->id;
	}
}