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
final class AI_Chatbot_Assistant
{

    /**
     * The single instance of the class.
     * @var AI_Chatbot_Assistant
     */
    private static $instance = null;

    /**
     * Ensures only one instance of the class is loaded.
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

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
        add_menu_page(
            __('AI Chatbot Assistant', 'ai-chatbot-assistant'),
            __('AI Chatbot', 'ai-chatbot-assistant'),
            'manage_options',
            'ai_chatbot_assistant',
            [$this, 'create_admin_page'],
            'dashicons-format-chat',
            80
        );
    }

    /**
     * Render the HTML for the admin settings page.
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI Chatbot Assistant Settings', 'ai-chatbot-assistant'); ?></h1>
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
        add_settings_section('ai_chatbot_api_section', __('API Configuration', 'ai-chatbot-assistant'), null, 'ai_chatbot_assistant');
        add_settings_field('openai_api_key', __('OpenAI API Key', 'ai-chatbot-assistant'), [$this, 'render_api_key_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');
        add_settings_field('openai_model', __('OpenAI Model', 'ai-chatbot-assistant'), [$this, 'render_model_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');
        add_settings_field('chatbot_instructions', __('Chatbot Instructions', 'ai-chatbot-assistant'), [$this, 'render_instructions_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');
        add_settings_field('important_urls', __('Important URLs', 'ai-chatbot-assistant'), [$this, 'render_important_urls_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');
        add_settings_field('max_tokens', __('Response Length Limit (Tokens)', 'ai-chatbot-assistant'), [$this, 'render_max_tokens_field'], 'ai_chatbot_assistant', 'ai_chatbot_api_section');
        
        // WooCommerce Integration Section
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            add_settings_section('ai_chatbot_woocommerce_section', __('WooCommerce Integration', 'ai-chatbot-assistant'), null, 'ai_chatbot_assistant');
            add_settings_field('enable_woocommerce', __('Enable Product Integration', 'ai-chatbot-assistant'), [$this, 'render_woocommerce_field'], 'ai_chatbot_assistant', 'ai_chatbot_woocommerce_section');
        }


        // Appearance Settings Section
        add_settings_section('ai_chatbot_appearance_section', __('Chatbot Appearance', 'ai-chatbot-assistant'), null, 'ai_chatbot_assistant');
        add_settings_field('primary_color', __('Primary Color', 'ai-chatbot-assistant'), [$this, 'render_primary_color_field'], 'ai_chatbot_assistant', 'ai_chatbot_appearance_section');
        add_settings_field('chatbot_title', __('Chatbot Title', 'ai-chatbot-assistant'), [$this, 'render_title_field'], 'ai_chatbot_assistant', 'ai_chatbot_appearance_section');
        add_settings_field('title_color', __('Title Color', 'ai-chatbot-assistant'), [$this, 'render_title_color_field'], 'ai_chatbot_assistant', 'ai_chatbot_appearance_section');

        // Display Settings Section
        add_settings_section('ai_chatbot_display_section', __('Display Settings', 'ai-chatbot-assistant'), null, 'ai_chatbot_assistant');
        add_settings_field('chatbot_position', __('Chatbot Position', 'ai-chatbot-assistant'), [$this, 'render_chatbot_position_field'], 'ai_chatbot_assistant', 'ai_chatbot_display_section');
        add_settings_field('show_on_pages', __('Show Chatbot on', 'ai-chatbot-assistant'), [$this, 'render_show_on_pages_field'], 'ai_chatbot_assistant', 'ai_chatbot_display_section');
    }
    
    /**
     * Sanitize settings before saving.
     */
    public function sanitize_settings($input) {
        $sanitized_input = [];
        if (isset($input['openai_api_key'])) $sanitized_input['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
        
        if (isset($input['openai_model'])) {
            $allowed_models = ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'];
            if (in_array($input['openai_model'], $allowed_models, true)) {
                $sanitized_input['openai_model'] = $input['openai_model'];
            }
        }

        if (isset($input['chatbot_instructions'])) $sanitized_input['chatbot_instructions'] = sanitize_textarea_field($input['chatbot_instructions']);
        if (isset($input['important_urls'])) $sanitized_input['important_urls'] = sanitize_textarea_field($input['important_urls']);
        if (isset($input['max_tokens'])) $sanitized_input['max_tokens'] = absint($input['max_tokens']);
        if (isset($input['primary_color'])) $sanitized_input['primary_color'] = sanitize_hex_color($input['primary_color']);
        if (isset($input['chatbot_title'])) $sanitized_input['chatbot_title'] = sanitize_text_field($input['chatbot_title']);
        if (isset($input['title_color'])) $sanitized_input['title_color'] = sanitize_hex_color($input['title_color']);
        if (isset($input['chatbot_position'])) $sanitized_input['chatbot_position'] = sanitize_key($input['chatbot_position']);
        $sanitized_input['show_on_pages'] = isset($input['show_on_pages']) && is_array($input['show_on_pages']) ? array_map('absint', $input['show_on_pages']) : [];
        $sanitized_input['enable_woocommerce'] = isset($input['enable_woocommerce']) ? 1 : 0;
        return $sanitized_input;
    }

    public function render_api_key_field() {
        $options = get_option('ai_chatbot_settings');
        printf('<input type="password" name="ai_chatbot_settings[openai_api_key]" value="%s" class="regular-text" />', isset($options['openai_api_key']) ? esc_attr($options['openai_api_key']) : '');
        echo '<p class="description">' . esc_html__('Enter your secret API key for the OpenAI API.', 'ai-chatbot-assistant') . '</p>';
    }
    
    public function render_model_field() {
        $options = get_option('ai_chatbot_settings');
        $current_model = $options['openai_model'] ?? 'gpt-3.5-turbo';
        $models = [
            'gpt-3.5-turbo' => __('GPT-3.5 Turbo (Fast & Cost-Effective)', 'ai-chatbot-assistant'),
            'gpt-4'         => __('GPT-4 (Advanced & Powerful)', 'ai-chatbot-assistant'),
            'gpt-4-turbo'   => __('GPT-4 Turbo (Latest & Optimized)', 'ai-chatbot-assistant'),
            'gpt-4o'        => __('GPT-4o (Omni - Newest Model)', 'ai-chatbot-assistant'),
        ];
        ?>
        <select name="ai_chatbot_settings[openai_model]">
            <?php foreach ($models as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_model, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Select the model to use for the chatbot. Newer models may have different pricing.', 'ai-chatbot-assistant'); ?></p>
        <?php
    }

    public function render_instructions_field() {
        $options = get_option('ai_chatbot_settings');
        $default_instructions = __('You are a helpful assistant.', 'ai-chatbot-assistant');
        printf('<textarea name="ai_chatbot_settings[chatbot_instructions]" rows="8" class="large-text">%s</textarea>', isset($options['chatbot_instructions']) ? esc_textarea($options['chatbot_instructions']) : esc_textarea($default_instructions));
        echo '<p class="description">' . esc_html__('Define the behavior and personality of your chatbot.', 'ai-chatbot-assistant') . '</p>';
    }

    public function render_important_urls_field() {
        $options = get_option('ai_chatbot_settings');
        $urls = isset($options['important_urls']) ? $options['important_urls'] : '';
        echo '<textarea name="ai_chatbot_settings[important_urls]" rows="8" class="large-text">' . esc_textarea($urls) . '</textarea>';
        echo '<p class="description">' . esc_html__('Add one URL per line with instructions. E.g., https://example.com/contact - Use this link for complex issues.', 'ai-chatbot-assistant') . '</p>';
    }

    public function render_woocommerce_field() {
        $options = get_option('ai_chatbot_settings');
        $checked = isset($options['enable_woocommerce']) && $options['enable_woocommerce'] ? 'checked' : '';
        echo '<label><input type="checkbox" name="ai_chatbot_settings[enable_woocommerce]" value="1" ' . esc_attr($checked) . '> ' . esc_html__('Allow the chatbot to access your product list to make recommendations.', 'ai-chatbot-assistant') . '</label>';
    }

    public function render_max_tokens_field() {
        $options = get_option('ai_chatbot_settings');
        printf('<input type="number" name="ai_chatbot_settings[max_tokens]" value="%s" class="small-text" />', isset($options['max_tokens']) ? esc_attr($options['max_tokens']) : '150');
        echo '<p class="description">' . esc_html__('Set the maximum length of the AI response. 100 tokens is about 75 words. Leave blank for default.', 'ai-chatbot-assistant') . '</p>';
    }

    public function render_primary_color_field() {
        $options = get_option('ai_chatbot_settings');
        $color = isset($options['primary_color']) ? $options['primary_color'] : '#0073aa';
        echo '<input type="text" name="ai_chatbot_settings[primary_color]" value="' . esc_attr($color) . '" class="ai-chatbot-color-picker" />';
    }
    
    public function render_title_field() {
        $options = get_option('ai_chatbot_settings');
        printf('<input type="text" name="ai_chatbot_settings[chatbot_title]" value="%s" class="regular-text" />', isset($options['chatbot_title']) ? esc_attr($options['chatbot_title']) : esc_attr__('Chat with us!', 'ai-chatbot-assistant'));
    }

    public function render_title_color_field() {
        $options = get_option('ai_chatbot_settings');
        $color = isset($options['title_color']) ? $options['title_color'] : '#ffffff';
        echo '<input type="text" name="ai_chatbot_settings[title_color]" value="' . esc_attr($color) . '" class="ai-chatbot-color-picker" />';
    }

    public function render_chatbot_position_field() {
        $options = get_option('ai_chatbot_settings');
        $position = isset($options['chatbot_position']) ? $options['chatbot_position'] : 'right';
        ?>
        <select name="ai_chatbot_settings[chatbot_position]">
            <option value="right" <?php selected($position, 'right'); ?>><?php esc_html_e('Right Side', 'ai-chatbot-assistant'); ?></option>
            <option value="left" <?php selected($position, 'left'); ?>><?php esc_html_e('Left Side', 'ai-chatbot-assistant'); ?></option>
        </select>
        <?php
    }

    public function render_show_on_pages_field() {
        $options = get_option('ai_chatbot_settings');
        $pages = get_pages();
        $selected_pages = $options['show_on_pages'] ?? [];
        echo '<label><input type="checkbox" class="show-on-all" ' . checked(empty($selected_pages), true, false) . '> ' . esc_html__('Show on all pages', 'ai-chatbot-assistant') . '</label><br><br>';
        echo '<div id="pages-checklist" style="' . (empty($selected_pages) ? 'display:none;' : '') . '">';
        foreach ($pages as $page) {
            $checked = in_array($page->ID, $selected_pages) ? 'checked' : '';
            echo '<label><input type="checkbox" name="ai_chatbot_settings[show_on_pages][]" value="' . esc_attr($page->ID) . '" ' . esc_attr($checked) . '> ' . esc_html($page->post_title) . '</label><br>';
        }
        echo '</div><p class="description">' . esc_html__('Uncheck "Show on all pages" to select specific pages.', 'ai-chatbot-assistant') . '</p>';
    }
    
    public function enqueue_frontend_assets() {
        $options = get_option('ai_chatbot_settings');
        $selected_pages = $options['show_on_pages'] ?? [];
        if (empty($selected_pages) || is_page($selected_pages)) {
            wp_enqueue_style('ai-chatbot-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], '1.2.1');
            wp_enqueue_script('ai-chatbot-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], '1.2.2', true);
            wp_localize_script('ai-chatbot-script', 'chatbot_params', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('chatbot_nonce')]);
            
            $primary_color = !empty($options['primary_color']) ? sanitize_hex_color($options['primary_color']) : '#0073aa';
            $title_color = !empty($options['title_color']) ? sanitize_hex_color($options['title_color']) : '#ffffff';
            $custom_css = ":root { --chatbot-primary-color: {$primary_color}; --chatbot-title-color: {$title_color}; }";
            wp_add_inline_style('ai-chatbot-style', $custom_css);
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_ai_chatbot_assistant' != $hook) return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('ai-chatbot-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['wp-color-picker', 'jquery'], '1.0.1', true);
    }

    public function add_chatbot_widget_html() {
        $options = get_option('ai_chatbot_settings');
        $selected_pages = $options['show_on_pages'] ?? [];
        if (empty($selected_pages) || is_page($selected_pages)) {
            $title = !empty($options['chatbot_title']) ? $options['chatbot_title'] : __('Chat with us!', 'ai-chatbot-assistant');
            $position_class = ($options['chatbot_position'] ?? 'right') === 'left' ? 'chatbot-position-left' : '';
            ?>
            <div id="ai-chatbot-container" class="<?php echo esc_attr($position_class); ?>">
                <div id="ai-chatbot-bubble" role="button" tabindex="0" aria-label="<?php esc_attr_e('Open Chat', 'ai-chatbot-assistant'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg></div>
                <div id="ai-chatbot-widget" class="hidden" role="dialog" aria-labelledby="ai-chatbot-header-title">
                    <div id="ai-chatbot-header">
                        <h3 id="ai-chatbot-header-title"><?php echo esc_html($title); ?></h3>
                        <button id="ai-chatbot-close" aria-label="<?php esc_attr_e('Close Chat', 'ai-chatbot-assistant'); ?>">&times;</button>
                    </div>
                    <div id="ai-chatbot-messages" role="log" aria-live="polite"></div>
                    <div id="ai-chatbot-input-container">
                        <input type="text" id="ai-chatbot-input" placeholder="<?php esc_attr_e('Type your message...', 'ai-chatbot-assistant'); ?>">
                        <button id="ai-chatbot-send" aria-label="<?php esc_attr_e('Send Message', 'ai-chatbot-assistant'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg></button>
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
        $model = $options['openai_model'] ?? 'gpt-3.5-turbo';
        $instructions = $options['chatbot_instructions'] ?? __('You are a helpful assistant.', 'ai-chatbot-assistant');
        $important_urls = $options['important_urls'] ?? '';
        $enable_woocommerce = $options['enable_woocommerce'] ?? 0;
        $max_tokens = !empty($options['max_tokens']) ? absint($options['max_tokens']) : 150;
        
        $context = '';

        if (!empty($important_urls)) {
            $context .= __("IMPORTANT PAGES:\n", 'ai-chatbot-assistant') . $important_urls . "\n\n";
        }

        if ($enable_woocommerce && is_plugin_active('woocommerce/woocommerce.php')) {
            $products_context = __("AVAILABLE PRODUCTS:\n", 'ai-chatbot-assistant');
            $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish'];
            $products = new WP_Query($args);
            if ($products->have_posts()) {
                while ($products->have_posts()) {
                    $products->the_post();
                    global $product;
                    $short_description = $product->get_short_description();
                    $clean_description = wp_strip_all_tags($short_description);
                    $clean_description = mb_substr($clean_description, 0, 250); // Limit length to manage token count

                    $products_context .= get_the_permalink() . ' - ' . get_the_title() . ' - Description: ' . $clean_description . "\n";
                }
            }
            wp_reset_postdata();
            $context .= $products_context . "\n\n";
        }

        if (!empty($context)) {
            $instructions = __("CONTEXT: Here is a list of important pages and/or products on this website. When a user asks for something that matches, provide the link in your response. Format links as standard URLs (e.g., https://example.com).\n\n", 'ai-chatbot-assistant') . $context . "INSTRUCTIONS:\n" . $instructions;
        }

        if ( ! isset( $_POST['history'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid message history.', 'ai-chatbot-assistant' ) ] );
			return;
		}
        $history_json = sanitize_textarea_field( wp_unslash( $_POST['history'] ) );
        $message_history = json_decode($history_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($message_history)) {
            wp_send_json_error(['message' => __('Invalid JSON in message history.', 'ai-chatbot-assistant')]);
            return;
        }

        if (empty($api_key)) { wp_send_json_error(['message' => __('API key is not configured.', 'ai-chatbot-assistant')]); return; }
        if (empty($message_history)) { wp_send_json_error(['message' => __('No message provided.', 'ai-chatbot-assistant')]); return; }

        $api_messages = [['role' => 'system', 'content' => $instructions]];
        foreach ($message_history as $msg) {
            if (isset($msg['role'], $msg['content']) && is_string($msg['role']) && is_string($msg['content'])) {
                 $api_messages[] = ['role' => sanitize_text_field($msg['role']), 'content' => sanitize_textarea_field($msg['content'])];
            }
        }

        $api_body = ['model' => $model, 'messages' => $api_messages, 'max_tokens' => $max_tokens];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json'],
            'body'    => wp_json_encode($api_body), 'timeout' => 30,
        ]);

        if (is_wp_error($response)) { wp_send_json_error(['message' => __('Failed to connect to API.', 'ai-chatbot-assistant')]); return; }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if($response_code !== 200) {
            $error_message = $response_body['error']['message'] ?? __('An unknown API error occurred.', 'ai-chatbot-assistant');
            wp_send_json_error(['message' => $error_message]);
            return;
        }

        if (isset($response_body['choices'][0]['message']['content'])) {
            wp_send_json_success(['reply' => sanitize_textarea_field($response_body['choices'][0]['message']['content'])]);
        } else {
            $error_message = $response_body['error']['message'] ?? __('An unknown API error occurred.', 'ai-chatbot-assistant');
            wp_send_json_error(['message' => $error_message]);
        }
    }
    
    /**
     * Runs on plugin uninstallation.
     */
    public static function uninstall() {
        delete_option('ai_chatbot_settings');
    }
}

/**
 * Begins execution of the plugin.
 */
function ai_chatbot_assistant_run() {
    return AI_Chatbot_Assistant::instance();
}
ai_chatbot_assistant_run();

register_uninstall_hook(__FILE__, ['AI_Chatbot_Assistant', 'uninstall']);
