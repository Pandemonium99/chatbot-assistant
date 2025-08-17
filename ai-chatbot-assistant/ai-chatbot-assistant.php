<?php
/**
 * Plugin Name:       AI Chatbot Assistant
 * Description:       A customizable chatbot powered by the OpenAI API.
 * Version:           1.0.0
 * Author:            CORRE Technology
 * Author URI:        https://www.corretechnology.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-chatbot-assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * ----------------------------------------------------------------
 * Main Plugin Class
 * ----------------------------------------------------------------
 */
class AI_Chatbot_Assistant
{

    /**
     * Initialize the plugin, set up hooks.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_footer', [$this, 'add_chatbot_widget_html']);
        add_action('wp_ajax_send_chat_message', [$this, 'handle_chat_message']);
        add_action('wp_ajax_nopriv_send_chat_message', [$this, 'handle_chat_message']);
    }

    /**
     * Add the plugin's settings page to the WordPress admin menu.
     */
    public function add_admin_menu()
    {
        add_menu_page('AI Chatbot Assistant', 'AI Chatbot', 'manage_options', 'ai_chatbot_assistant', [$this, 'create_admin_page'], 'dashicons-format-chat', 80);
    }

    /**
     * Render the HTML for the admin settings page.
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h1>AI Chatbot Assistant Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_chatbot_options_group');
                do_settings_sections('ai_chatbot_assistant');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register the settings, sections, and fields for the plugin.
     */
    public function register_settings()
    {
        register_setting('ai_chatbot_options_group', 'ai_chatbot_settings', [$this, 'sanitize_settings']);

        // API Settings Section
        add_settings_section('ai_chatbot_api_section', 'API Configuration', null, 'ai_chatbot_assistant');
        add_settings_field('openai_api_key', 'OpenAI API Key', [$this, 'render_api_key_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');
        add_settings_field('chatbot_instructions', 'Chatbot Instructions', [$this, 'render_instructions_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');
        add_settings_field('max_tokens', 'Response Length Limit (Tokens)', [$this, 'render_max_tokens_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');

        // Appearance Settings Section
        add_settings_section('ai_chatbot_appearance_section', 'Chatbot Appearance', null, 'ai_chatbot_assistant');
        add_settings_field('primary_color', 'Primary Color', [$this, 'render_primary_color_field'], 'ai_chatbot_assistant', 'ai_chatbot_appearance_section');
        add_settings_field('chatbot_title', 'Chatbot Title', [$this, 'render_title_field'], 'ai_chatbot_assistant', 'ai_chatbot_appearance_section');
        add_settings_field('title_color', 'Title Color', [$this, 'render_title_color_field'], 'ai_chatbot_assistant', 'ai_chatbot_appearance_section');

        // Display Settings Section
        add_settings_section('ai_chatbot_display_section', 'Display Settings', null, 'ai_chatbot_assistant');
        add_settings_field('chatbot_position', 'Chatbot Position', [$this, 'render_chatbot_position_field'], 'ai_chatbot_assistant', 'ai_chatbot_display_section');
        add_settings_field('show_on_pages', 'Show Chatbot on', [$this, 'render_show_on_pages_field'], 'ai_chatbot_assistant', 'ai_chatbot_display_section');
    }
    
    /**
     * Sanitize settings before saving.
     */
    public function sanitize_settings($input) {
        $sanitized_input = [];
        if (isset($input['openai_api_key'])) $sanitized_input['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
        if (isset($input['chatbot_instructions'])) $sanitized_input['chatbot_instructions'] = sanitize_textarea_field($input['chatbot_instructions']);
        if (isset($input['max_tokens'])) $sanitized_input['max_tokens'] = absint($input['max_tokens']);
        if (isset($input['primary_color'])) $sanitized_input['primary_color'] = sanitize_hex_color($input['primary_color']);
        if (isset($input['chatbot_title'])) $sanitized_input['chatbot_title'] = sanitize_text_field($input['chatbot_title']);
        if (isset($input['title_color'])) $sanitized_input['title_color'] = sanitize_hex_color($input['title_color']);
        if (isset($input['chatbot_position'])) $sanitized_input['chatbot_position'] = sanitize_key($input['chatbot_position']);
        $sanitized_input['show_on_pages'] = isset($input['show_on_pages']) ? array_map('absint', $input['show_on_pages']) : [];
        return $sanitized_input;
    }

    public function render_api_key_field() {
        $options = get_option('ai_chatbot_settings');
        printf('<input type="password" name="ai_chatbot_settings[openai_api_key]" value="%s" class="regular-text" />', isset($options['openai_api_key']) ? esc_attr($options['openai_api_key']) : '');
        echo '<p class="description">Enter your secret API key for the OpenAI API.</p>';
    }
    
    public function render_instructions_field() {
        $options = get_option('ai_chatbot_settings');
        printf('<textarea name="ai_chatbot_settings[chatbot_instructions]" rows="8" class="large-text">%s</textarea>', isset($options['chatbot_instructions']) ? esc_textarea($options['chatbot_instructions']) : 'You are a helpful assistant.');
        echo '<p class="description">Define the behavior and personality of your chatbot.</p>';
    }

    public function render_max_tokens_field() {
        $options = get_option('ai_chatbot_settings');
        printf('<input type="number" name="ai_chatbot_settings[max_tokens]" value="%s" class="small-text" />', isset($options['max_tokens']) ? esc_attr($options['max_tokens']) : '150');
        echo '<p class="description">Set the maximum length of the AI response. 100 tokens is about 75 words. Leave blank for default.</p>';
    }

    public function render_primary_color_field() {
        $options = get_option('ai_chatbot_settings');
        $color = isset($options['primary_color']) ? esc_attr($options['primary_color']) : '#0073aa';
        echo '<input type="text" name="ai_chatbot_settings[primary_color]" value="' . $color . '" class="ai-chatbot-color-picker" />';
    }
    
    public function render_title_field() {
        $options = get_option('ai_chatbot_settings');
        printf('<input type="text" name="ai_chatbot_settings[chatbot_title]" value="%s" class="regular-text" />', isset($options['chatbot_title']) ? esc_attr($options['chatbot_title']) : 'Chat with us!');
    }

    public function render_title_color_field() {
        $options = get_option('ai_chatbot_settings');
        $color = isset($options['title_color']) ? esc_attr($options['title_color']) : '#ffffff';
        echo '<input type="text" name="ai_chatbot_settings[title_color]" value="' . $color . '" class="ai-chatbot-color-picker" />';
    }

    public function render_chatbot_position_field() {
        $options = get_option('ai_chatbot_settings');
        $position = isset($options['chatbot_position']) ? $options['chatbot_position'] : 'right';
        ?>
        <select name="ai_chatbot_settings[chatbot_position]">
            <option value="right" <?php selected($position, 'right'); ?>>Right Side</option>
            <option value="left" <?php selected($position, 'left'); ?>>Left Side</option>
        </select>
        <?php
    }

    public function render_show_on_pages_field() {
        $options = get_option('ai_chatbot_settings');
        $pages = get_pages();
        $selected_pages = $options['show_on_pages'] ?? [];
        echo '<label><input type="checkbox" class="show-on-all" ' . (empty($selected_pages) ? 'checked' : '') . '> Show on all pages</label><br><br>';
        echo '<div id="pages-checklist" style="' . (empty($selected_pages) ? 'display:none;' : '') . '">';
        foreach ($pages as $page) {
            $checked = in_array($page->ID, $selected_pages) ? 'checked' : '';
            echo '<label><input type="checkbox" name="ai_chatbot_settings[show_on_pages][]" value="' . $page->ID . '" ' . $checked . '> ' . esc_html($page->post_title) . '</label><br>';
        }
        echo '</div><p class="description">Uncheck "Show on all pages" to select specific pages.</p>';
    }
    
    public function enqueue_frontend_assets() {
        $options = get_option('ai_chatbot_settings');
        $selected_pages = $options['show_on_pages'] ?? [];
        if (empty($selected_pages) || is_page($selected_pages)) {
            wp_enqueue_style('ai-chatbot-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], '1.2.0');
            wp_enqueue_script('ai-chatbot-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], '1.2.0', true);
            wp_localize_script('ai-chatbot-script', 'chatbot_params', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('chatbot_nonce')]);
            
            $primary_color = !empty($options['primary_color']) ? $options['primary_color'] : '#0073aa';
            $title_color = !empty($options['title_color']) ? $options['title_color'] : '#ffffff';
            $custom_css = ":root { --chatbot-primary-color: {$primary_color}; --chatbot-title-color: {$title_color}; }";
            wp_add_inline_style('ai-chatbot-style', $custom_css);
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_ai_chatbot_assistant' != $hook) return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('ai-chatbot-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['wp-color-picker', 'jquery'], false, true);
    }

    public function add_chatbot_widget_html() {
        $options = get_option('ai_chatbot_settings');
        $selected_pages = $options['show_on_pages'] ?? [];
        if (empty($selected_pages) || is_page($selected_pages)) {
            $title = $options['chatbot_title'] ?? 'Chat with us!';
            $position_class = ($options['chatbot_position'] ?? 'right') === 'left' ? 'chatbot-position-left' : '';
            ?>
            <div id="ai-chatbot-container" class="<?php echo $position_class; ?>">
                <div id="ai-chatbot-bubble"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg></div>
                <div id="ai-chatbot-widget" class="hidden">
                    <div id="ai-chatbot-header">
                        <h3><?php echo esc_html($title); ?></h3>
                        <button id="ai-chatbot-close">&times;</button>
                    </div>
                    <div id="ai-chatbot-messages"></div>
                    <div id="ai-chatbot-input-container">
                        <input type="text" id="ai-chatbot-input" placeholder="Type your message...">
                        <button id="ai-chatbot-send"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg></button>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    public function handle_chat_message() {
        check_ajax_referer('chatbot_nonce', 'nonce');
        $options = get_option('ai_chatbot_settings');
        $api_key = $options['openai_api_key'] ?? '';
        $instructions = $options['chatbot_instructions'] ?? 'You are a helpful assistant.';
        $max_tokens = !empty($options['max_tokens']) ? absint($options['max_tokens']) : 150;
        $message_history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : [];

        if (empty($api_key)) { wp_send_json_error(['message' => 'API key is not configured.']); return; }
        if (empty($message_history)) { wp_send_json_error(['message' => 'No message provided.']); return; }

        $api_messages = [['role' => 'system', 'content' => $instructions]];
        foreach ($message_history as $msg) $api_messages[] = ['role' => $msg['role'], 'content' => $msg['content']];

        $api_body = ['model' => 'gpt-3.5-turbo', 'messages' => $api_messages, 'max_tokens' => $max_tokens];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json'],
            'body'    => json_encode($api_body), 'timeout' => 30,
        ]);

        if (is_wp_error($response)) { wp_send_json_error(['message' => 'Failed to connect to API.']); return; }
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($response_body['choices'][0]['message']['content'])) {
            wp_send_json_success(['reply' => $response_body['choices'][0]['message']['content']]);
        } else {
            $error_message = $response_body['error']['message'] ?? 'An unknown API error occurred.';
            wp_send_json_error(['message' => $error_message]);
        }
    }
    
    public static function uninstall() {
        delete_option('ai_chatbot_settings');
    }
}

register_uninstall_hook(__FILE__, ['AI_Chatbot_Assistant', 'uninstall']);
new AI_Chatbot_Assistant();