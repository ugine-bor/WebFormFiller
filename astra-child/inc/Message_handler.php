<?php

defined('ABSPATH') || exit;

class Message_handler{
	public $message;
	
	public function __contruct(){
		
	}
	
	public static function updateMessage($message) {
		global $dynamicMessage;
		$dynamicMessage = $message;

		return $message;
	}
}