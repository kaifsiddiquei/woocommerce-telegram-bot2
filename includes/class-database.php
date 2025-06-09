<?php

class WC_Telegram_Bot_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for Telegram groups
        $table_groups = $wpdb->prefix . 'telegram_bot_groups';
        $sql_groups = "CREATE TABLE $table_groups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            group_name varchar(255) NOT NULL,
            chat_id varchar(255) NOT NULL,
            categories text,
            message_template text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Table for message logs
        $table_logs = $wpdb->prefix . 'telegram_bot_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id mediumint(9) NOT NULL,
            chat_id varchar(255) NOT NULL,
            message_text text,
            status varchar(50),
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_groups);
        dbDelta($sql_logs);
    }
    
    public static function get_groups() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_bot_groups';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1");
    }
    
    public static function add_group($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_bot_groups';
        
        return $wpdb->insert(
            $table_name,
            array(
                'group_name' => sanitize_text_field($data['group_name']),
                'chat_id' => sanitize_text_field($data['chat_id']),
                'categories' => maybe_serialize($data['categories']),
                'message_template' => wp_kses_post($data['message_template'])
            )
        );
    }
    
    public static function update_group($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_bot_groups';
        
        return $wpdb->update(
            $table_name,
            array(
                'group_name' => sanitize_text_field($data['group_name']),
                'chat_id' => sanitize_text_field($data['chat_id']),
                'categories' => maybe_serialize($data['categories']),
                'message_template' => wp_kses_post($data['message_template'])
            ),
            array('id' => $id)
        );
    }
    
    public static function delete_group($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_bot_groups';
        return $wpdb->update($table_name, array('is_active' => 0), array('id' => $id));
    }
    
    public static function log_message($order_id, $chat_id, $message_text, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_bot_logs';
        
        return $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'chat_id' => $chat_id,
                'message_text' => $message_text,
                'status' => $status
            )
        );
    }
}
