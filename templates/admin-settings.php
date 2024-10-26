<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'car_shipping_messages',
            'car_shipping_message',
            __('Settings Saved', 'multistep-car-shipping-form'),
            'updated'
        );
    }
    settings_errors('car_shipping_messages');
    ?>

    <div class="nav-tab-wrapper">
        <a href="#api-settings" class="nav-tab nav-tab-active" data-tab="api-settings">API Settings</a>
        <a href="#email-settings" class="nav-tab" data-tab="email-settings">Email Settings</a>
        <a href="#submissions" class="nav-tab" data-tab="submissions">Recent Submissions</a>
    </div>

    <!-- API Settings Tab -->
<div id="api-settings" class="tab-content active">
    <form method="post" action="options.php">
      <?php settings_fields('car_shipping_form'); ?>
        <table class="form-table">
            <!-- Google Maps API Key -->
            <tr>
                <th scope="row">
                    <label for="google_maps_api_key"><?php _e('Google Maps API Key', 'multistep-car-shipping-form'); ?></label>
                </th>
                <td>
                    <input type="text"
                           id="google_maps_api_key"
                           name="google_maps_api_key"
                           value="<?php echo esc_attr(get_option('google_maps_api_key')); ?>"
                           class="regular-text"
                    />
                    <p class="description">
                        <?php _e('Enter your Google Maps API key. Required for location autocomplete.', 'multistep-car-shipping-form'); ?>
                        <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
                            <?php _e('Get an API key', 'multistep-car-shipping-form'); ?>
                        </a>
                    </p>
                    <button type="button" class="button button-secondary test-api" data-api="google">
                        <?php _e('Test Google Maps API Key', 'multistep-car-shipping-form'); ?>
                    </button>
                    <span class="api-test-result"></span>
                </td>
            </tr>

            <!-- Car Data API Settings -->
            <tr>
                <th scope="row">
                    <label for="car_data_api_type"><?php _e('Car Data Source', 'multistep-car-shipping-form'); ?></label>
                </th>
                <td>
                    <select id="car_data_api_type" name="car_data_api_type" class="regular-text">
                        <option value="api" <?php selected(get_option('car_data_api_type'), 'api'); ?>>External API</option>
                        <option value="json" <?php selected(get_option('car_data_api_type'), 'json'); ?>>JSON URL/File</option>
                        <option value="custom" <?php selected(get_option('car_data_api_type'), 'custom'); ?>>Custom Data</option>
                    </select>
                </td>
            </tr>

            <!-- API Key or URL field -->
            <tr class="car-data-setting api-setting json-setting">
                <th scope="row">
                    <label for="car_data_source"><?php _e('Car Data API Key/URL', 'multistep-car-shipping-form'); ?></label>
                </th>
                <td>
                    <input type="text"
                           id="car_data_source"
                           name="car_data_source"
                           value="<?php echo esc_attr(get_option('car_data_source')); ?>"
                           class="regular-text"
                    />
                    <p class="description api-description">
                        <?php _e('Enter your car data API key.', 'multistep-car-shipping-form'); ?>
                    </p>
                    <p class="description json-description" style="display:none;">
                        <?php _e('Enter the URL or path to your JSON file containing car data.', 'multistep-car-shipping-form'); ?>
                    </p>
                </td>
            </tr>

            <!-- Custom Car Data -->
            <tr class="car-data-setting custom-setting" style="display:none;">
                <th scope="row">
                    <label for="custom_car_data"><?php _e('Custom Car Data', 'multistep-car-shipping-form'); ?></label>
                </th>
                <td>
                    <textarea id="custom_car_data"
                              name="custom_car_data"
                              class="large-text code"
                              rows="10"
                              placeholder='{
    "manufacturers": [
        {
            "name": "Toyota",
            "models": ["Camry", "Corolla", "RAV4"]
        },
        {
            "name": "Honda",
            "models": ["Civic", "Accord", "CR-V"]
        }
    ]
}'
                    ><?php echo esc_textarea(get_option('custom_car_data')); ?></textarea>
                    <p class="description">
                        <?php _e('Enter your custom car data in JSON format.', 'multistep-car-shipping-form'); ?>
                    </p>
                    <button type="button" class="button button-secondary validate-json">
                        <?php _e('Validate JSON', 'multistep-car-shipping-form'); ?>
                    </button>
                    <span class="json-validation-result"></span>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>

    <!-- Email Settings Tab -->
    <div id="email-settings" class="tab-content" style="display: none;">
        <form method="post" action="options.php">
            <?php settings_fields('car_shipping_form_email'); ?>
            
            <!-- Sales Team Email Settings -->
            <h3><?php _e('Sales Team Email Settings', 'multistep-car-shipping-form'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sales_team_emails"><?php _e('Sales Team Email Addresses', 'multistep-car-shipping-form'); ?></label>
                    </th>
                    <td>
                        <textarea id="sales_team_emails"
                                  name="sales_team_emails"
                                  class="large-text code"
                                  rows="3"
                                  placeholder="Enter email addresses, separated by commas"
                        ><?php echo esc_textarea(get_option('sales_team_emails')); ?></textarea>
                        <p class="description">
                            <?php _e('Enter the email addresses of your sales team members, separated by commas.', 'multistep-car-shipping-form'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sales_email_subject"><?php _e('Sales Email Subject', 'multistep-car-shipping-form'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="sales_email_subject"
                               name="sales_email_subject"
                               value="<?php echo esc_attr(get_option('sales_email_subject', 'New Car Shipping Quote Request')); ?>"
                               class="regular-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sales_email_template"><?php _e('Sales Email Template', 'multistep-car-shipping-form'); ?></label>
                    </th>
                    <td>
                        <textarea id="sales_email_template"
                                  name="sales_email_template"
                                  class="large-text code email-template"
                                  rows="15"
                        ><?php echo esc_textarea(get_option('sales_email_template')); ?></textarea>
                        <p class="description">
                            <?php _e('Available variables: {name}, {email}, {phone}, {pickup_location}, {dropoff_location}, {transport_type}, {vehicle_year}, {vehicle_make}, {vehicle_model}, {is_operable}, {availability_date}', 'multistep-car-shipping-form'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Customer Email Settings -->
            <h3><?php _e('Customer Email Settings', 'multistep-car-shipping-form'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="customer_email_subject"><?php _e('Customer Email Subject', 'multistep-car-shipping-form'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="customer_email_subject"
                               name="customer_email_subject"
                               value="<?php echo esc_attr(get_option('customer_email_subject', 'Thank you for your car shipping quote request')); ?>"
                               class="regular-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="customer_email_template"><?php _e('Customer Email Template', 'multistep-car-shipping-form'); ?></label>
                    </th>
                    <td>
                        <textarea id="customer_email_template"
                                  name="customer_email_template"
                                  class="large-text code email-template"
                                  rows="15"
                        ><?php echo esc_textarea(get_option('customer_email_template')); ?></textarea>
                        <p class="description">
                            <?php _e('This email will be sent to customers after they submit the form.', 'multistep-car-shipping-form'); ?>
                            <br>
                            <?php _e('Available variables: {name}, {email}, {phone}, {pickup_location}, {dropoff_location}, {transport_type}, {vehicle_year}, {vehicle_make}, {vehicle_model}, {is_operable}, {availability_date}', 'multistep-car-shipping-form'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Email Testing -->
            <h3><?php _e('Email Testing', 'multistep-car-shipping-form'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Test Email Settings', 'multistep-car-shipping-form'); ?></label>
                    </th>
                    <td>
                        <button type="button" class="button button-secondary" id="test-email">
                            <?php _e('Send Test Email', 'multistep-car-shipping-form'); ?>
                        </button>
                        <span id="test-email-result"></span>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <!-- Submissions Tab -->
    <div id="submissions" class="tab-content" style="display: none;">
        <div class="tablenav top">
            <div class="alignleft actions">
                <button type="button" class="button action" id="export-submissions">
                    <?php _e('Export to CSV', 'multistep-car-shipping-form'); ?>
                </button>
            </div>
        </div>

        <?php
        $recent_submissions = get_posts(array(
            'post_type' => 'shipping_request',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if ($recent_submissions) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th><?php _e('Date', 'multistep-car-shipping-form'); ?></th>
                    <th><?php _e('Name', 'multistep-car-shipping-form'); ?></th>
                    <th><?php _e('Email', 'multistep-car-shipping-form'); ?></th>
                    <th><?php _e('Phone', 'multistep-car-shipping-form'); ?></th>
                    <th><?php _e('Transport', 'multistep-car-shipping-form'); ?></th>
                    <th><?php _e('Vehicle', 'multistep-car-shipping-form'); ?></th>
                    <th><?php _e('Route', 'multistep-car-shipping-form'); ?></th>
                    <th><?php _e('Availability', 'multistep-car-shipping-form'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_submissions as $submission) :
                    $meta = get_post_meta($submission->ID); ?>
                    <tr>
                        <td><?php echo get_the_date('Y-m-d H:i:s', $submission); ?></td>
                        <td><?php echo esc_html($meta['name'][0] ?? ''); ?></td>
                        <td><?php echo esc_html($meta['email'][0] ?? ''); ?></td>
                        <td><?php echo esc_html($meta['phone'][0] ?? ''); ?></td>
                        <td>
                            <?php 
                            echo esc_html($meta['transport_type'][0] ?? '');
                            echo $meta['is_operable'][0] === 'No' ? ' (Non-op)' : '';
                            ?>
                        </td>
                        <td>
                            <?php
                            echo esc_html($meta['year'][0] ?? '');
                            echo ' ';
                            echo esc_html($meta['manufacturer'][0] ?? '');
                            echo ' ';
                            echo esc_html($meta['model'][0] ?? '');
                            ?>
                        </td>
                        <td>
                            <?php
                            echo esc_html($meta['pickup_location'][0] ?? '');
                            echo ' → ';
                            echo esc_html($meta['dropoff_location'][0] ?? '');
                            ?>
                        </td>
                        <td><?php echo esc_html($meta['availability_date'][0] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No submissions yet.', 'multistep-car-shipping-form'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tab-content:not(.active) {
    display: none;
}

.form-table th {
    width: 200px;
}

.api-test-result,
#test-email-result {
    display: inline-block;
    margin-left: 10px;
    padding: 5px 10px;
    border-radius: 3px;
}

.api-test-result.success,
#test-email-result.success {
    color: #46b450;
    background: #edfaef;
    border: 1px solid #46b450;
}

.api-test-result.error,
#test-email-result.error {
    color: #dc3232;
    background: #fbeaea;
    border: 1px solid #dc3232;
}

.template-variables {
    margin: 10px 0;
    padding: 10px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.template-variables .button {
    margin: 2px;
    font-size: 12px;
}

.char-counter {
    text-align: right;
    color: #666;
    font-size: 12px;
    margin-top: 5px;
}

/* Responsive adjustments */
@media screen and (max-width: 782px) {
    .form-table td {
        padding: 15px 10px;
    }
    
    .form-table textarea {
        width: 100%;
    }
    
    .template-variables {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 5px;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab Navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').hide();
        $('#' + tabId).show();

        // Save active tab
        localStorage.setItem('activeShippingFormTab', tabId);
    });

    // Restore active tab
    const activeTab = localStorage.getItem('activeShippingFormTab');
    if (activeTab) {
        $(`.nav-tab[data-tab="${activeTab}"]`).click();
    }

    // Test Google Maps API Key
    $('.test-api').on('click', function() {
        const $button = $(this);
        const $result = $button.next('.api-test-result');
        const apiKey = $('#google_maps_api_key').val();

        if (!apiKey) {
            $result.html('Please enter an API key').removeClass('success').addClass('error');
            return;
        }

        $button.prop('disabled', true);
        $result.html('Testing...').removeClass('success error');

        $.get(`https://maps.googleapis.com/maps/api/geocode/json?address=test&key=${apiKey}`)
            .done(function(response) {
                if (response.status === 'OK' || response.status === 'ZERO_RESULTS') {
                    $result.html('API key is valid').removeClass('error').addClass('success');
                } else {
                    $result.html('API key is invalid').removeClass('success').addClass('error');
                }
            })
            .fail(function() {
                $result.html('API test failed').removeClass('success').addClass('error');
            })
            .always(function() {
                $button.prop('disabled', false);
            });
    });

    // Test Email
    $('#test-email').on('click', function() {
        const $button = $(this);
        const $result = $('#test-email-result');
        
        $button.prop('disabled', true);
        $result.html('Sending test email...').removeClass('success error');

        $.post(ajaxurl, {
            action: 'test_shipping_form_email',
            nonce: '<?php echo wp_create_nonce('test_email_nonce'); ?>'
        })
        .done(function(response) {
            if (response.success) {
                $result.html('Test email sent successfully').addClass('success');
            } else {
                $result.html('Failed to send test email').addClass('error');
            }
        })
        .fail(function() {
            $result.html('Failed to send test email').addClass('error');
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });

    // Export to CSV
    $('#export-submissions').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true);
        
        window.location.href = ajaxurl + '?action=export_shipping_submissions&nonce=<?php echo wp_create_nonce('export_submissions_nonce'); ?>';
        
        setTimeout(function() {
            $button.prop('disabled', false);
        }, 2000);
    });

    // Template variable insertion
    function insertVariable(templateId, variable) {
        const textarea = document.getElementById(templateId);
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end);
        
        textarea.value = before + variable + after;
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        
        // Trigger change for character counter
        $(textarea).trigger('input');
    }

    // Add variable buttons for email templates
    const variables = [
        '{name}', '{email}', '{phone}', 
        '{pickup_location}', '{dropoff_location}', '{transport_type}',
        '{vehicle_year}', '{vehicle_make}', '{vehicle_model}',
        '{is_operable}', '{availability_date}', '{submission_date}'
    ];

    ['sales_email_template', 'customer_email_template'].forEach(templateId => {
        const $container = $('<div class="template-variables"></div>');
        $container.append('<p class="description">Click to insert variable:</p>');
        
        variables.forEach(variable => {
            const $button = $('<button type="button" class="button button-small"></button>')
                .text(variable)
                .click(function(e) {
                    e.preventDefault();
                    insertVariable(templateId, variable);
                });
            $container.append($button);
        });

        $(`#${templateId}`).before($container);
    });

    // Character counter for templates
    $('.email-template').each(function() {
        const $textarea = $(this);
        const $counter = $('<div class="char-counter">Characters: 0</div>');
        
        $textarea.after($counter);
        
        $textarea.on('input', function() {
            $counter.text('Characters: ' + $textarea.val().length);
        }).trigger('input');
    });
    
    // Car Data Source Toggle
$('#car_data_api_type').on('change', function() {
    const selectedType = $(this).val();
    $('.car-data-setting').hide();
    
    switch(selectedType) {
        case 'api':
            $('.api-setting').show();
            $('.api-description').show();
            $('.json-description').hide();
            break;
        case 'json':
            $('.json-setting').show();
            $('.api-description').hide();
            $('.json-description').show();
            break;
        case 'custom':
            $('.custom-setting').show();
            break;
    }
});

    // JSON Validation
$('.validate-json').on('click', function() {
    const jsonString = $('#custom_car_data').val();
    const $result = $('.json-validation-result');
    
    try {
        JSON.parse(jsonString);
        $result.html('Valid JSON format').removeClass('error').addClass('success');
    } catch (e) {
        $result.html('Invalid JSON format: ' + e.message).removeClass('success').addClass('error');
    }
});

    // Initialize car data source display
$('#car_data_api_type').trigger('change');
    
    
});
</script>