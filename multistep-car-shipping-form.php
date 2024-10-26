<?php
/**
 * Plugin Name: Multistep Car Shipping Form
 * Description: A multi-step car shipping form with Google Maps autocomplete
 * Version: 1.0
 * Author: A2A
 */

if (!defined('ABSPATH')) {
    exit;
}

class MultistepCarShippingForm {
    private $script_handle = 'multistep-car-shipping-form';
    private $style_handle = 'multistep-car-shipping-form-styles';
    private $version;
    private $plugin_path;
    private $plugin_url;

    // Add error handling function
    private function handle_error($error, $context = '') {
        $message = $context ? "[$context] " : '';
        $message .= is_wp_error($error) ? $error->get_error_message() : $error;
        error_log($message);
        return new WP_Error('form_error', $message);
    }

    // Add initialization check
    private function check_requirements() {
        if (!function_exists('wp_enqueue_script')) {
            return $this->handle_error('WordPress core functions not available');
        }
        
        if (!get_option('google_maps_api_key')) {
            return $this->handle_error('Google Maps API key not configured', 'settings');
        }
        
        return true;
    }

    public function __construct() {
        $this->version = '1.0.' . time();
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        // Check requirements before initializing
        $requirements = $this->check_requirements();
        if (is_wp_error($requirements)) {
            add_action('admin_notices', function() use ($requirements) {
                echo '<div class="error"><p>' . esc_html($requirements->get_error_message()) . '</p></div>';
            });
            return;
        }

        // Basic WordPress hooks
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('car_shipping_form', array($this, 'render_form'));

        // AJAX handlers
        add_action('wp_ajax_submit_shipping_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_shipping_form', array($this, 'handle_form_submission'));

        // Register custom post type
        add_action('init', array($this, 'register_shipping_request_post_type'));

        // Add cleanup on deactivation
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
 public function register_scripts() {
        try {
            // Register styles
            wp_register_style(
                $this->style_handle,
                $this->plugin_url . 'css/styles.css',
                array(),
                $this->version
            );

            // Deregister WordPress's version of React
            wp_deregister_script('react');
            wp_deregister_script('react-dom');

            // Register React
            wp_register_script(
                'react',
                'https://unpkg.com/react@18/umd/react.production.min.js',
                array(),
                '18.0.0',
                true
            );

            wp_register_script(
                'react-dom',
                'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',
                array('react'),
                '18.0.0',
                true
            );

            // Register form script
            wp_register_script(
                $this->script_handle,
                $this->plugin_url . 'js/form.js',
                array('react', 'react-dom'),
                $this->version,
                true
            );

            // Localize script with settings
            wp_localize_script($this->script_handle, 'carShippingFormSettings', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_rest'),
                'googleMapsApiKey' => get_option('google_maps_api_key')
            ));

        } catch (Exception $e) {
            $this->handle_error($e->getMessage(), 'script_registration');
        }
    }

    public function render_form() {
        try {
            // Get API key
            $api_key = esc_attr(get_option('google_maps_api_key'));
            
            if (empty($api_key)) {
                return '<div class="error">Error: Google Maps API key is not configured</div>';
            }

            // Add Google Maps initialization code
            wp_add_inline_script('jquery', "
                console.log('Debug: Initializing form scripts');
                
                window.initGoogleMaps = function() {
                    console.log('Debug: Google Maps loaded');
                    window.dispatchEvent(new Event('google-maps-loaded'));
                };

                window.gm_authFailure = function() {
                    console.error('Google Maps authentication failed');
                    const container = document.getElementById('car-shipping-form-container');
                    if (container) {
                        container.innerHTML = '<div class=\"error-message\">Error loading Google Maps. Please try again later.</div>';
                    }
                };
            ", 'before');

            // Register Google Maps script
            wp_register_script(
                'google-maps',
                sprintf(
                    'https://maps.googleapis.com/maps/api/js?key=%s&libraries=places&loading=async',
                    $api_key
                ),
                array('jquery'),
                null,
                true
            );

            // Enqueue all required scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('react');
            wp_enqueue_script('react-dom');
            wp_enqueue_script('google-maps');
            wp_enqueue_script($this->script_handle);
            wp_enqueue_style($this->style_handle);

            // Return container
            return '<div id="car-shipping-form-container" class="car-shipping-form-wrapper">
                <div class="loading-placeholder">Loading form...</div>
            </div>';

        } catch (Exception $e) {
            $error_message = $this->handle_error($e->getMessage(), 'form_render');
            return '<div class="error">' . esc_html($error_message->get_error_message()) . '</div>';
        }
    }

    public function register_shipping_request_post_type() {
        $labels = array(
            'name'                  => 'Shipping Requests',
            'singular_name'         => 'Shipping Request',
            'menu_name'            => 'Shipping Requests',
            'all_items'            => 'All Requests',
            'view_item'            => 'View Request',
            'search_items'         => 'Search Requests',
            'not_found'            => 'No shipping requests found',
            'not_found_in_trash'   => 'No shipping requests found in trash'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'capability_type'     => 'post',
            'capabilities'        => array('create_posts' => false),
            'map_meta_cap'        => true,
            'supports'            => array('title', 'custom-fields'),
            'menu_icon'           => 'dashicons-truck',
            'menu_position'       => 30,
            'hierarchical'        => false,
            'show_in_nav_menus'   => false,
            'has_archive'         => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
        );

        register_post_type('shipping_request', $args);
    }
    
    public function handle_form_submission() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_rest', 'nonce', false)) {
                wp_send_json_error(array(
                    'message' => 'Invalid security token'
                ), 403);
                return;
            }

            // Rate limiting
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $transient_key = 'form_submission_' . md5($ip_address);
            
            if (get_transient($transient_key)) {
                wp_send_json_error(array(
                    'message' => 'Please wait a moment before submitting another request'
                ), 429);
                return;
            }

            // Validate and sanitize form data
            $form_data = $this->validate_and_sanitize_form_data($_POST);
            if (is_wp_error($form_data)) {
                wp_send_json_error(array(
                    'message' => $form_data->get_error_message()
                ), 400);
                return;
            }

            // Create shipping request post
            $post_id = wp_insert_post(array(
                'post_title' => sanitize_text_field($form_data['name']) . ' - ' . current_time('mysql'),
                'post_type' => 'shipping_request',
                'post_status' => 'publish',
                'meta_input' => $form_data
            ));

            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }

            // Set rate limiting
            set_transient($transient_key, true, 60); // 1 minute cooldown

            // Send notification emails
            $email_result = $this->send_notification_emails($form_data);
            
            if (is_wp_error($email_result)) {
                error_log('Email notification failed: ' . $email_result->get_error_message());
            }

            // Return success response
            wp_send_json_success(array(
                'message' => 'Form submitted successfully',
                'id' => $post_id
            ));

        } catch (Exception $e) {
            $this->handle_error($e->getMessage(), 'form_submission');
            wp_send_json_error(array(
                'message' => 'An error occurred while processing your request'
            ), 500);
        }
    }

    private function validate_and_sanitize_form_data($data) {
        $required_fields = array(
            'name' => 'Full Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'pickupLocation' => 'Pickup Location',
            'dropoffLocation' => 'Delivery Location',
            'transportType' => 'Transport Type',
            'manufacturer' => 'Vehicle Make',
            'model' => 'Vehicle Model',
            'year' => 'Vehicle Year',
            'isOperable' => 'Vehicle Operability',
            'availabilityDate' => 'Shipping Timeline'
        );

        $sanitized_data = array();

        // Check required fields
        foreach ($required_fields as $field => $label) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf('Please enter your %s', $label));
            }
        }

        // Validate and sanitize each field
        $sanitized_data['name'] = sanitize_text_field($data['name']);
        
        // Email validation
        $email = sanitize_email($data['email']);
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Please enter a valid email address');
        }
        $sanitized_data['email'] = $email;

        // Phone validation
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) !== 10) {
            return new WP_Error('invalid_phone', 'Please enter a valid 10-digit phone number');
        }
        $sanitized_data['phone'] = $phone;

        // Location fields
        $sanitized_data['pickup_location'] = sanitize_text_field($data['pickupLocation']);
        $sanitized_data['dropoff_location'] = sanitize_text_field($data['dropoffLocation']);
        
        // Transport type validation
        if (!in_array($data['transportType'], array('Open', 'Enclosed'))) {
            return new WP_Error('invalid_transport_type', 'Please select a valid transport type');
        }
        $sanitized_data['transport_type'] = sanitize_text_field($data['transportType']);

        // Vehicle details
        $sanitized_data['manufacturer'] = sanitize_text_field($data['manufacturer']);
        $sanitized_data['model'] = sanitize_text_field($data['model']);
        
        // Year validation
        $year = intval($data['year']);
        if ($year < 1900 || $year > (date('Y') + 1)) {
            return new WP_Error('invalid_year', 'Please select a valid year');
        }
        $sanitized_data['year'] = $year;

        // Operability validation
        if (!in_array($data['isOperable'], array('Yes', 'No'))) {
            return new WP_Error('invalid_operability', 'Please indicate if the vehicle is operable');
        }
        $sanitized_data['is_operable'] = sanitize_text_field($data['isOperable']);

        // Availability validation
        $valid_availability = array('ASAP', 'Within 2 weeks', 'Within 30 days', 'More than 30 days');
        if (!in_array($data['availabilityDate'], $valid_availability)) {
            return new WP_Error('invalid_availability', 'Please select when you need the vehicle shipped');
        }
        $sanitized_data['availability_date'] = sanitize_text_field($data['availabilityDate']);

        return $sanitized_data;
    }

   // Send notifications
    private function send_notification_emails($data) {
        try {
            // Debug log
            error_log('Starting email notification process');
            
            // Check if WP Mail SMTP is active
            if (class_exists('WPMailSMTP\Options')) {
                error_log('WP Mail SMTP is active');
                $mailer = get_option('wp_mail_smtp', array());
                error_log('Current mailer: ' . ($mailer['mail']['mailer'] ?? 'default'));
            }
    
            // Get email settings
            $sales_emails = array_map('trim', explode(',', get_option('sales_team_emails', get_option('admin_email'))));
            $sales_subject = get_option('sales_email_subject', 'New Car Shipping Quote Request');
            $customer_subject = get_option('customer_email_subject', 'Thank you for your car shipping quote request');
    
            // Log email configuration
            error_log('Sending with the following configuration:');
            error_log('From: ' . get_option('admin_email'));
            error_log('Sales emails: ' . implode(', ', $sales_emails));
    
            // Set up email headers with proper encoding
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
                'Reply-To: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            );
    
            // Send to sales team
            $sales_message = $this->create_sales_email_content($data);
            foreach ($sales_emails as $email) {
                if (is_email($email)) {
                    error_log("Attempting to send sales notification to: {$email}");
                    $sent = wp_mail($email, $sales_subject, $sales_message, $headers);
                    error_log("Sales email to {$email} " . ($sent ? 'was sent successfully' : 'failed to send'));
                }
        }

        // Send to customer
        $customer_message = $this->create_customer_email_content($data);
        error_log("Attempting to send customer confirmation to: {$data['email']}");
        $sent = wp_mail($data['email'], $customer_subject, $customer_message, $headers);
        error_log("Customer email " . ($sent ? 'was sent successfully' : 'failed to send'));

        return true;

    } catch (Exception $e) {
        error_log('Email sending error: ' . $e->getMessage());
        error_log('Error trace: ' . $e->getTraceAsString());
        return false;
    }
}

    private function create_sales_email_content($data) {
    $message = '<html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4a90e2; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .section { margin-bottom: 20px; }
            .label { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>New Car Shipping Quote Request</h2>
            </div>
            <div class="content">
                <div class="section">
                    <h3>Customer Details:</h3>
                    <p><span class="label">Name:</span> ' . esc_html($data['name']) . '</p>
                    <p><span class="label">Email:</span> ' . esc_html($data['email']) . '</p>
                    <p><span class="label">Phone:</span> ' . esc_html($this->format_phone_number($data['phone'])) . '</p>
                </div>

                <div class="section">
                    <h3>Shipping Details:</h3>
                    <p><span class="label">Pickup Location:</span> ' . esc_html($data['pickup_location']) . '</p>
                    <p><span class="label">Delivery Location:</span> ' . esc_html($data['dropoff_location']) . '</p>
                    <p><span class="label">Transport Type:</span> ' . esc_html($data['transport_type']) . '</p>
                    <p><span class="label">First Available Date:</span> ' . esc_html($data['availability_date']) . '</p>
                </div>

                <div class="section">
                    <h3>Vehicle Details:</h3>
                    <p><span class="label">Year:</span> ' . esc_html($data['year']) . '</p>
                    <p><span class="label">Make:</span> ' . esc_html($data['manufacturer']) . '</p>
                    <p><span class="label">Model:</span> ' . esc_html($data['model']) . '</p>
                    <p><span class="label">Operable:</span> ' . esc_html($data['is_operable']) . '</p>
                </div>

                <p style="font-size: 0.9em; color: #666;">
                    Request submitted on: ' . current_time('mysql') . '
                </p>
            </div>
        </div>
    </body>
    </html>';

    return $message;
    }

    private function create_customer_email_content($data) {
        $message = '<html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4a90e2; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .section { margin-bottom: 20px; }
                .footer { text-align: center; padding: 20px; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Thank You for Your Car Shipping Request</h2>
                </div>
                <div class="content">
                    <p>Dear ' . esc_html($data['name']) . ',</p>
    
                    <p>Thank you for requesting a car shipping quote. We have received your request and our team will contact you shortly.</p>
    
                    <div class="section">
                        <h3>Your Request Details:</h3>
                        <p>Transport Type: ' . esc_html($data['transport_type']) . '</p>
                        <p>Pickup: ' . esc_html($data['pickup_location']) . '</p>
                        <p>Delivery: ' . esc_html($data['dropoff_location']) . '</p>
                    </div>
    
                    <div class="section">
                        <h3>Vehicle Information:</h3>
                        <p>' . esc_html($data['year'] . ' ' . $data['manufacturer'] . ' ' . $data['model']) . '</p>
                        <p>Operability Status: ' . esc_html($data['is_operable']) . '</p>
                        <p>Requested Timeline: ' . esc_html($data['availability_date']) . '</p>
                    </div>
    
                    <p>If you have any questions, please don\'t hesitate to contact us.</p>
                </div>
                <div class="footer">
                    <p>Best regards,<br>' . esc_html(get_bloginfo('name')) . ' Team</p>
                </div>
            </div>
        </body>
        </html>';
    
        return $message;
    }

    private function send_email($to, $subject, $message) {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        if (!wp_mail($to, $subject, $message, $headers)) {
            throw new Exception("Failed to send email to: {$to}");
        }
    }

    private function replace_template_variables($template, $data) {
        $replacements = array(
            '{name}' => $data['name'],
            '{email}' => $data['email'],
            '{phone}' => $this->format_phone_number($data['phone']),
            '{pickup_location}' => $data['pickup_location'],
            '{dropoff_location}' => $data['dropoff_location'],
            '{transport_type}' => $data['transport_type'],
            '{vehicle_year}' => $data['year'],
            '{vehicle_make}' => $data['manufacturer'],
            '{vehicle_model}' => $data['model'],
            '{is_operable}' => $data['is_operable'],
            '{availability_date}' => $data['availability_date'],
            '{submission_date}' => current_time('mysql')
        );

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    private function format_phone_number($phone) {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Car Shipping Form',
            'Car Shipping',
            'manage_options',
            'car-shipping-form',
            array($this, 'render_admin_page'),
            'dashicons-truck',
            30
        );

        add_submenu_page(
            'car-shipping-form',
            'Settings',
            'Settings',
            'manage_options',
            'car-shipping-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        // API Settings Section
        add_settings_section(
            'car_shipping_api_settings',
            'API Settings',
            array($this, 'render_api_settings_section'),
            'car-shipping-settings'
        );

        // Google Maps API Key
        register_setting('car_shipping_form', 'google_maps_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));

        add_settings_field(
            'google_maps_api_key',
            'Google Maps API Key',
            array($this, 'render_api_key_field'),
            'car-shipping-settings',
            'car_shipping_api_settings'
        );

        // Email Settings
        register_setting('car_shipping_form', 'sales_team_emails', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_email_list'),
            'default' => get_option('admin_email')
        ));

        register_setting('car_shipping_form', 'sales_email_template', array(
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
            'default' => $this->get_default_sales_template()
        ));

        register_setting('car_shipping_form', 'customer_email_template', array(
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
            'default' => $this->get_default_customer_template()
        ));

        // Email Subjects
        register_setting('car_shipping_form', 'sales_email_subject', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'New Car Shipping Quote Request'
        ));

        register_setting('car_shipping_form', 'customer_email_subject', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Thank you for your car shipping quote request'
        ));

        // Cache Settings
        register_setting('car_shipping_form', 'enable_form_cache', array(
            'type' => 'boolean',
            'default' => true
        ));
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        require_once $this->plugin_path . 'templates/admin-settings.php';
    }

    private function get_default_sales_template() {
        return $this->get_template_content('sales-email-template.php');
    }

    private function get_default_customer_template() {
        return $this->get_template_content('customer-email-template.php');
    }

    private function get_template_content($template_name) {
        $template_path = $this->plugin_path . 'templates/' . $template_name;
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        return '';
    }

    public function sanitize_car_data_type($value) {
        $allowed_types = array('api', 'json', 'custom');
        return in_array($value, $allowed_types) ? $value : 'custom';
    }

    private function sanitize_json_data($value) {
        if (empty($value)) {
            return '';
        }
        
        $decoded = json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            add_settings_error(
                'custom_car_data',
                'invalid_json',
                'Invalid JSON format in car data'
            );
            return '';
        }
        
        return $value;
    }

    private function sanitize_email_list($value) {
        $emails = array_map('trim', explode(',', $value));
        $valid_emails = array_filter($emails, 'is_email');
        return implode(', ', $valid_emails);
    }

    public function add_plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=car-shipping-settings'),
            __('Settings', 'car-shipping-form')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    public function deactivate() {
        // Clean up transients and temporary data
        delete_transient('car_shipping_form_cache');
        
        // Optionally remove post type entries
        // Uncomment if you want to remove all shipping requests on deactivation
        /* 
        $posts = get_posts([
            'post_type' => 'shipping_request',
            'numberposts' => -1
        ]);
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
        */
    }
}

// Initialize the plugin
function initialize_car_shipping_form() {
    $plugin = new MultistepCarShippingForm();
    
    // Add settings link on plugin page
    add_filter(
        'plugin_action_links_' . plugin_basename(__FILE__), 
        array($plugin, 'add_plugin_action_links')
    );
}

add_action('plugins_loaded', 'initialize_car_shipping_form');