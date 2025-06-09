<?php

class WC_Telegram_Bot_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_test_telegram_connection', array($this, 'test_telegram_connection'));
        add_action('wp_ajax_add_telegram_group', array($this, 'add_telegram_group'));
        add_action('wp_ajax_delete_telegram_group', array($this, 'delete_telegram_group'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Telegram Bot Settings',
            'Telegram Bot',
            'manage_options',
            'wc-telegram-bot',
            array($this, 'admin_page'),
            'dashicons-email-alt',
            30
        );
        
        add_submenu_page(
            'wc-telegram-bot',
            'Bot Settings',
            'Settings',
            'manage_options',
            'wc-telegram-bot',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'wc-telegram-bot',
            'Telegram Groups',
            'Groups',
            'manage_options',
            'wc-telegram-groups',
            array($this, 'groups_page')
        );
        
        add_submenu_page(
            'wc-telegram-bot',
            'Message Logs',
            'Logs',
            'manage_options',
            'wc-telegram-logs',
            array($this, 'logs_page')
        );
    }
    
    public function register_settings() {
        register_setting('wc_telegram_bot_settings', 'wc_telegram_bot_settings');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wc-telegram') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('wc-telegram-bot-admin', WC_TELEGRAM_BOT_PLUGIN_URL . 'assets/admin.js', array('jquery'), WC_TELEGRAM_BOT_VERSION);
            wp_localize_script('wc-telegram-bot-admin', 'wc_telegram_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_telegram_nonce')
            ));
            wp_enqueue_style('wc-telegram-bot-admin', WC_TELEGRAM_BOT_PLUGIN_URL . 'assets/admin.css', array(), WC_TELEGRAM_BOT_VERSION);
        }
    }
    
    public function admin_page() {
        $settings = get_option('wc_telegram_bot_settings', array());
        ?>
        <div class="wrap">
            <h1>Telegram Bot Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('wc_telegram_bot_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Bot Token</th>
                        <td>
                            <input type="text" name="wc_telegram_bot_settings[bot_token]" value="<?php echo esc_attr($settings['bot_token'] ?? ''); ?>" class="regular-text" />
                            <button type="button" id="test-connection" class="button">Test Connection</button>
                            <p class="description">Get your bot token from @BotFather on Telegram</p>
                            <div id="connection-result"></div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Default Message Template</th>
                        <td>
                            <textarea name="wc_telegram_bot_settings[default_message_template]" rows="10" cols="50" class="large-text"><?php echo esc_textarea($settings['default_message_template'] ?? ''); ?></textarea>
                            <p class="description">
                                Available placeholders: {product_name}, {quantity}, {order_total}, {stock_quantity}, {order_status}, {order_id}, {customer_name}, {product_price}, {order_date}
                                <br>Use HTML formatting: &lt;b&gt;bold&lt;/b&gt;, &lt;i&gt;italic&lt;/i&gt;, emojis are supported
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Enable Media</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wc_telegram_bot_settings[enable_media]" value="1" <?php checked($settings['enable_media'] ?? true); ?> />
                                Send product images/videos with messages
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Button Text</th>
                        <td>
                            <input type="text" name="wc_telegram_bot_settings[button_text]" value="<?php echo esc_attr($settings['button_text'] ?? 'Shop Now'); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Default Button URL</th>
                        <td>
                            <input type="url" name="wc_telegram_bot_settings[button_url]" value="<?php echo esc_attr($settings['button_url'] ?? get_site_url()); ?>" class="regular-text" />
                            <p class="description">Default URL for the button (product URL will override this)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function groups_page() {
        $groups = WC_Telegram_Bot_Database::get_groups();
        $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        ?>
        <div class="wrap">
            <h1>Telegram Groups</h1>
            
            <div class="add-group-form">
                <h2>Add New Group</h2>
                <form id="add-group-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Group Name</th>
                            <td><input type="text" name="group_name" required class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Chat ID</th>
                            <td>
                                <input type="text" name="chat_id" required class="regular-text" />
                                <p class="description">Add your bot to the group and use @userinfobot to get the chat ID</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Categories</th>
                            <td>
                                <select name="categories[]" multiple class="regular-text">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Select categories for this group (leave empty for all products)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Custom Message Template</th>
                            <td>
                                <textarea name="message_template" rows="8" cols="50" class="large-text" placeholder="Leave empty to use default template"></textarea>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary">Add Group</button>
                </form>
            </div>
            
            <h2>Existing Groups</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Chat ID</th>
                        <th>Categories</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?php echo esc_html($group->group_name); ?></td>
                            <td><?php echo esc_html($group->chat_id); ?></td>
                            <td>
                                <?php 
                                $group_categories = maybe_unserialize($group->categories);
                                if (empty($group_categories)) {
                                    echo 'All Categories';
                                } else {
                                    $cat_names = array();
                                    foreach ($group_categories as $cat_id) {
                                        $term = get_term($cat_id);
                                        if ($term) $cat_names[] = $term->name;
                                    }
                                    echo implode(', ', $cat_names);
                                }
                                ?>
                            </td>
                            <td>
                                <button class="button delete-group" data-id="<?php echo $group->id; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function logs_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_bot_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sent_at DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>Message Logs</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Chat ID</th>
                        <th>Status</th>
                        <th>Sent At</th>
                        <th>Message Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><a href="<?php echo admin_url('post.php?post=' . $log->order_id . '&action=edit'); ?>">#<?php echo $log->order_id; ?></a></td>
                            <td><?php echo esc_html($log->chat_id); ?></td>
                            <td>
                                <span class="status-<?php echo $log->status; ?>">
                                    <?php echo ucfirst($log->status); ?>
                                </span>
                            </td>
                            <td><?php echo $log->sent_at; ?></td>
                            <td><?php echo esc_html(substr($log->message_text, 0, 100)) . '...'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function test_telegram_connection() {
        check_ajax_referer('wc_telegram_nonce', 'nonce');
        
        $telegram_api = new WC_Telegram_Bot_API();
        $result = $telegram_api->test_connection();
        
        wp_send_json($result);
    }
    
    public function add_telegram_group() {
        check_ajax_referer('wc_telegram_nonce', 'nonce');
        
        $data = array(
            'group_name' => sanitize_text_field($_POST['group_name']),
            'chat_id' => sanitize_text_field($_POST['chat_id']),
            'categories' => isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array(),
            'message_template' => wp_kses_post($_POST['message_template'])
        );
        
        $result = WC_Telegram_Bot_Database::add_group($data);
        
        if ($result) {
            wp_send_json_success('Group added successfully');
        } else {
            wp_send_json_error('Failed to add group');
        }
    }
    
    public function delete_telegram_group() {
        check_ajax_referer('wc_telegram_nonce', 'nonce');
        
        $group_id = intval($_POST['group_id']);
        $result = WC_Telegram_Bot_Database::delete_group($group_id);
        
        if ($result) {
            wp_send_json_success('Group deleted successfully');
        } else {
            wp_send_json_error('Failed to delete group');
        }
    }
}
