<?php

defined('ABSPATH') || exit;

class Session{
    public function __construct() {
        // Убедимся, что сессия запущена
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public function delete($key) {
        unset($_SESSION[$key]);
    }

    public function clear() {
        session_unset();
    }

    public function destroy() {
        session_destroy();
    }
}