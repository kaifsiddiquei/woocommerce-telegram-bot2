<?php
/**
 * Plugin Name: WooCommerce Telegram Bot
 * Plugin URI: https://yourwebsite.com
 * Description: Send WooCommerce order notifications to Telegram groups with customizable messages and multi-group support
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wc-telegram-bot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_TELEGRAM_BOT_VERSION', '1.0.0');
define('WC_TELEGRAM_BOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_TELEGRAM_BOT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Create plugin directory structure on activation
register_activation_hook(__FILE__, 'wc_telegram_bot_create_directories');

function wc_telegram_bot_create_directories() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = $upload_dir['basedir'] . '/woocommerce-telegram-bot';
    
    if (!file_exists($plugin_dir)) {
        wp_mkdir_p($plugin_dir);
    }
}

class WC_Telegram_Bot {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin files
        $this->load_dependencies();
        
        // Initialize components
        new WC_Telegram_Bot_Admin();
        new WC_Telegram_Bot_Order_Handler();
        new WC_Telegram_Bot_API();
    }
    
    private function load_dependencies() {
        require_once WC_TELEGRAM_BOT_PLUGIN_DIR . 'includes/class-admin.php';
        require_once WC_TELEGRAM_BOT_PLUGIN_DIR . 'includes/class-order-handler.php';
        require_once WC_TELEGRAM_BOT_PLUGIN_DIR . 'includes/class-telegram-api.php';
        require_once WC_TELEGRAM_BOT_PLUGIN_DIR . 'includes/class-database.php';
    }
    
    public function activate() {
        WC_Telegram_Bot_Database::create_tables();
        
        // Set default options
        add_option('wc_telegram_bot_settings', array(
            'bot_token' => '',
            'default_message_template' => "⚡⚡ New Order Received ⚡⚡\n\n📦 Product Name: {product_name}\n🔢 Product Quantity: {quantity}\n💰 Order Value: {order_total}\n📊 Stock Quantity: {stock_quantity}\n📋 Status: {order_status}",
            'enable_media' => true,
            'button_text' => 'Shop Now',
            'button_url' => get_site_url()
        ));
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p><strong>WooCommerce Telegram Bot</strong> requires WooCommerce to be installed and active.</p></div>';
    }
}

new WC_Telegram_Bot();

