<?php

class WC_Telegram_Bot_Order_Handler {
    
    public function __construct() {
        // Hook into WooCommerce order events
        add_action('woocommerce_new_order', array($this, 'handle_new_order'));
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
    }
    
    public function handle_new_order($order_id) {
        $this->send_order_notification($order_id, 'new_order');
    }
    
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        // Only send notification for processing status
        if ($new_status === 'processing') {
            $this->send_order_notification($order_id, 'processing');
        }
    }
    
    private function send_order_notification($order_id, $trigger) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Get all active groups
        $groups = WC_Telegram_Bot_Database::get_groups();
        if (empty($groups)) {
            return;
        }
        
        // Get order items
        $items = $order->get_items();
        
        foreach ($items as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }
            
            // Get product categories
            $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
            
            // Find matching groups for this product
            $matching_groups = $this->get_matching_groups($groups, $product_categories);
            
            foreach ($matching_groups as $group) {
                $this->send_group_notification($group, $order, $item, $product);
            }
        }
    }
    
    private function get_matching_groups($groups, $product_categories) {
        $matching_groups = array();
        
        foreach ($groups as $group) {
            $group_categories = maybe_unserialize($group->categories);
            
            // If no categories set for group, send to all
            if (empty($group_categories)) {
                $matching_groups[] = $group;
                continue;
            }
            
            // Check if product categories match group categories
            if (array_intersect($product_categories, $group_categories)) {
                $matching_groups[] = $group;
            }
        }
        
        return $matching_groups;
    }
    
    private function send_group_notification($group, $order, $item, $product) {
        // Get message template
        $template = !empty($group->message_template) ? $group->message_template : $this->get_default_template();
        
        // Replace placeholders
        $message = $this->replace_placeholders($template, $order, $item, $product);
        
        // Get product media
        $media_url = $this->get_product_media($product);
        
        // Get button settings
        $settings = get_option('wc_telegram_bot_settings', array());
        $button_text = isset($settings['button_text']) ? $settings['button_text'] : 'Shop Now';
        $button_url = $product->get_permalink();
        
        // Send message
        $telegram_api = new WC_Telegram_Bot_API();
        $success = $telegram_api->send_message(
            $group->chat_id,
            $message,
            $media_url,
            $button_text,
            $button_url
        );
        
        // Log the message
        WC_Telegram_Bot_Database::log_message(
            $order->get_id(),
            $group->chat_id,
            $message,
            $success ? 'sent' : 'failed'
        );
    }
    
    private function replace_placeholders($template, $order, $item, $product) {
        $placeholders = array(
            '{product_name}' => $product->get_name(),
            '{quantity}' => $item->get_quantity(),
            '{order_total}' => $order->get_formatted_order_total(),
            '{stock_quantity}' => $product->get_stock_quantity() ?: 'N/A',
            '{order_status}' => $order->get_status(),
            '{order_id}' => $order->get_id(),
            '{customer_name}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '{product_price}' => wc_price($product->get_price()),
            '{order_date}' => $order->get_date_created()->format('Y-m-d H:i:s')
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
    
    private function get_product_media($product) {
        $settings = get_option('wc_telegram_bot_settings', array());
        if (!isset($settings['enable_media']) || !$settings['enable_media']) {
            return '';
        }
        
        // Get product featured image
        $image_id = $product->get_image_id();
        if ($image_id) {
            return wp_get_attachment_url($image_id);
        }
        
        return '';
    }
    
    private function get_default_template() {
        $settings = get_option('wc_telegram_bot_settings', array());
        return isset($settings['default_message_template']) ? $settings['default_message_template'] : 
            "⚡⚡ New Order Received ⚡⚡\n\n📦 Product Name: {product_name}\n🔢 Product Quantity: {quantity}\n💰 Order Value: {order_total}\n📊 Stock Quantity: {stock_quantity}\n📋 Status: {order_status}";
    }
}
