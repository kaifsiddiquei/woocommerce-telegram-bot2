<?php

class WC_Telegram_Bot_API {
    
    private $bot_token;
    
    public function __construct() {
        $settings = get_option('wc_telegram_bot_settings', array());
        $this->bot_token = isset($settings['bot_token']) ? $settings['bot_token'] : '';
    }
    
    public function send_message($chat_id, $message, $media_url = '', $button_text = '', $button_url = '') {
        if (empty($this->bot_token)) {
            return false;
        }
        
        $api_url = "https://api.telegram.org/bot{$this->bot_token}/";
        
        // Prepare inline keyboard if button is provided
        $reply_markup = '';
        if (!empty($button_text) && !empty($button_url)) {
            $keyboard = array(
                'inline_keyboard' => array(
                    array(
                        array(
                            'text' => $button_text,
                            'url' => $button_url
                        )
                    )
                )
            );
            $reply_markup = json_encode($keyboard);
        }
        
        // Send media with caption if media URL is provided
        if (!empty($media_url)) {
            return $this->send_media_message($api_url, $chat_id, $message, $media_url, $reply_markup);
        } else {
            return $this->send_text_message($api_url, $chat_id, $message, $reply_markup);
        }
    }
    
    private function send_text_message($api_url, $chat_id, $message, $reply_markup = '') {
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        );
        
        if (!empty($reply_markup)) {
            $data['reply_markup'] = $reply_markup;
        }
        
        return $this->make_request($api_url . 'sendMessage', $data);
    }
    
    private function send_media_message($api_url, $chat_id, $caption, $media_url, $reply_markup = '') {
        // Determine media type
        $media_type = $this->get_media_type($media_url);
        
        $data = array(
            'chat_id' => $chat_id,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        );
        
        if (!empty($reply_markup)) {
            $data['reply_markup'] = $reply_markup;
        }
        
        // Send appropriate media type
        switch ($media_type) {
            case 'video':
                $data['video'] = $media_url;
                $endpoint = 'sendVideo';
                break;
            case 'animation':
                $data['animation'] = $media_url;
                $endpoint = 'sendAnimation';
                break;
            case 'photo':
            default:
                $data['photo'] = $media_url;
                $endpoint = 'sendPhoto';
                break;
        }
        
        return $this->make_request($api_url . $endpoint, $data);
    }
    
    private function get_media_type($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        if (in_array($extension, array('mp4', 'avi', 'mov'))) {
            return 'video';
        } elseif (in_array($extension, array('gif'))) {
            return 'animation';
        } else {
            return 'photo';
        }
    }
    
    private function make_request($url, $data) {
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Telegram API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        return isset($result['ok']) && $result['ok'];
    }
    
    public function test_connection() {
        if (empty($this->bot_token)) {
            return array('success' => false, 'message' => 'Bot token is required');
        }
        
        $api_url = "https://api.telegram.org/bot{$this->bot_token}/getMe";
        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['ok']) && $result['ok']) {
            return array('success' => true, 'message' => 'Bot connected successfully!', 'bot_info' => $result['result']);
        } else {
            return array('success' => false, 'message' => 'Invalid bot token');
        }
    }
}
