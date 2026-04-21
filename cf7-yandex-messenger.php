<?php
/**
 * Plugin Name: CF7 to Yandex Messenger
 * Description: Sends Contact Form 7 submissions to a Yandex Messenger chat.
 * Version: 1.0.1
 * Author: Serge Shakhovsky
 * License: GPL-2.0+
 * Text Domain: cf7-yandex-messenger
 * Requires Plugins: contact-form-7
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('CF7YM_VERSION', '1.0.1');
define('CF7YM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF7YM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class CF7_Yandex_Messenger {

    /**
     * Constructor
     */
    public function __construct() {
        // Check if CF7 is active
        add_action('admin_init', array($this, 'check_cf7_dependency'));

        // Add settings page
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Hook into CF7 form submission
        add_action('wpcf7_mail_sent', array($this, 'send_to_yandex_messenger'), 10, 1);

        // Add settings link on plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    /**
     * Check if Contact Form 7 is active
     */
    public function check_cf7_dependency() {
        if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(
                    __('CF7 to Yandex Messenger requires Contact Form 7 to be installed and active. You can download %shere%s.', 'cf7-yandex-messenger'),
                    '<a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">',
                    '</a>'
                );
                echo '</p></div>';
            });

            // Deactivate this plugin
            deactivate_plugins(plugin_basename(__FILE__));
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    /**
     * Add settings page under CF7 menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'wpcf7',
            __('Yandex Messenger', 'cf7-yandex-messenger'),
            __('Yandex Messenger', 'cf7-yandex-messenger'),
            'manage_options',
            'cf7-yandex-messenger',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('cf7ym_settings', 'cf7ym_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));

        register_setting('cf7ym_settings', 'cf7ym_chat_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
    }

    /**
     * Add settings link on plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=cf7-yandex-messenger') . '">' . __('Settings', 'cf7-yandex-messenger') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Render settings page HTML
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cf7-yandex-messenger'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Yandex Messenger Integration', 'cf7-yandex-messenger'); ?></h1>
            <p><?php echo esc_html__('Configure your Yandex Messenger bot to receive Contact Form 7 submissions.', 'cf7-yandex-messenger'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields('cf7ym_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cf7ym_api_key"><?php echo esc_html__('Bot API Key (OAuth Token)', 'cf7-yandex-messenger'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="cf7ym_api_key" 
                                   name="cf7ym_api_key" 
                                   value="<?php echo esc_attr(get_option('cf7ym_api_key')); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php echo esc_attr__('e.g., AQAAAAA...', 'cf7-yandex-messenger'); ?>" />
                            <p class="description">
                                <?php echo esc_html__('Obtain this token from Yandex OAuth.', 'cf7-yandex-messenger'); ?>
                                <a href="https://oauth.yandex.ru/" target="_blank"><?php echo esc_html__('Get token', 'cf7-yandex-messenger'); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cf7ym_chat_id"><?php echo esc_html__('Chat ID', 'cf7-yandex-messenger'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="cf7ym_chat_id" 
                                   name="cf7ym_chat_id" 
                                   value="<?php echo esc_attr(get_option('cf7ym_chat_id')); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php echo esc_attr__('e.g., 0/0/12345678-90ab-cdef-1234-567890abcdef', 'cf7-yandex-messenger'); ?>" />
                            <p class="description">
                                <?php echo esc_html__('The chat ID where messages will be sent.', 'cf7-yandex-messenger'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr />
            <h2><?php echo esc_html__('How It Works', 'cf7-yandex-messenger'); ?></h2>
            <p><?php echo esc_html__('After a Contact Form 7 form is successfully submitted (and email is sent), this plugin will send a formatted message to the configured Yandex Messenger chat.', 'cf7-yandex-messenger'); ?></p>
            <p><?php echo esc_html__('Message format includes:', 'cf7-yandex-messenger'); ?></p>
            <ul>
                <li><?php echo esc_html__('Form title', 'cf7-yandex-messenger'); ?></li>
                <li><?php echo esc_html__('Page URL where form was submitted', 'cf7-yandex-messenger'); ?></li>
                <li><?php echo esc_html__('All submitted fields (key-value pairs)', 'cf7-yandex-messenger'); ?></li>
                <li><?php echo esc_html__('Submission timestamp', 'cf7-yandex-messenger'); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Send formatted form data to Yandex Messenger
     *
     * @param WPCF7_ContactForm $contact_form CF7 form object
     */
    public function send_to_yandex_messenger($contact_form) {
        $api_key = get_option('cf7ym_api_key');
        $chat_id = get_option('cf7ym_chat_id');

        // Don't proceed if settings are missing
        if (empty($api_key) || empty($chat_id)) {
            return;
        }

        // Get submission data
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $posted_data = $submission->get_posted_data();
        $form_title = $contact_form->title();

        // Build message text
        $message = $this->format_message($form_title, $posted_data);
        if (empty($message)) {
            return;
        }

        // Send to Yandex API
        $this->send_api_request($api_key, $chat_id, $message);
    }

    /**
     * Format form data into readable text
     *
     * @param string $form_title
     * @param array  $posted_data
     * @return string
     */
    private function format_message($form_title, $posted_data) {
        $lines = array();

        // Form title and timestamp
        $lines[] = sprintf(__('📬 New submission from: %s', 'cf7-yandex-messenger'), $form_title);
        $lines[] = sprintf(__('📅 Date: %s', 'cf7-yandex-messenger'), current_time('Y-m-d H:i:s'));

        // Get page URL from submission meta
        $submission = WPCF7_Submission::get_instance();
        if ($submission) {
            $page_url = $submission->get_meta('url');
            if (!empty($page_url)) {
                $lines[] = sprintf(__('🌐 Page: %s', 'cf7-yandex-messenger'), esc_url($page_url));
            }
        }

        $lines[] = '────────────────';

        // Add each field (skip internal CF7 fields)
        $skip_fields = array('_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post');
        foreach ($posted_data as $key => $value) {
            if (in_array($key, $skip_fields)) {
                continue;
            }

            // Handle array values (e.g., checkboxes)
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $lines[] = sprintf('<b>%s:</b> %s', esc_html($key), esc_html($value));
        }

        return implode("\n", $lines);
    }

    /**
     * Send HTTP POST request to Yandex Messenger API
     *
     * @param string $api_key OAuth token
     * @param string $chat_id Target chat ID
     * @param string $text    Message text
     * @return bool True on success, false on failure
     */
    private function send_api_request($api_key, $chat_id, $text) {
        $url = 'https://botapi.messenger.yandex.net/bot/v1/messages/sendText/';

        $body = array(
            'chat_id' => $chat_id,
            'text'    => $text
        );

        $headers = array(
            'Authorization' => 'OAuth ' . $api_key,
            'Content-Type'  => 'application/json'
        );

        $args = array(
            'method'      => 'POST',
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers'     => $headers,
            'body'        => wp_json_encode($body),
            'data_format' => 'body'
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $this->log_error('HTTP Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $this->log_error(sprintf(
                'API Error (HTTP %d): %s',
                $response_code,
                $response_body
            ));
            return false;
        }

        return true;
    }

    /**
     * Log error messages to WordPress debug log
     *
     * @param string $message
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('[CF7 to Yandex Messenger] ' . $message);
        }
    }
}

// Initialize the plugin
new CF7_Yandex_Messenger();
