<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The admin packing class.
 *
 * @since      8.0.0
 * @package    Moyasar
 * @subpackage Moyasar/includes/admin
 */
class Moyasar_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    8.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    8.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    8.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        add_filter( 'upload_mimes', array( $this, 'apple_pay_upload_mimes' ) );
        add_filter( 'wp_check_filetype_and_ext', array( $this, 'apple_pay_check_filetype' ), 10, 4 );
        add_action( 'admin_notices', array( $this, 'migration_admin_notice' ) );
        add_action( 'admin_init', array( $this, 'dismiss_migration_notice' ) );
        add_action( 'after_plugin_row_moyasar-payments/moyasar-payments.php', array( $this, 'plugin_row_migration_note' ), 10, 3 );
    }

    /**
     * Display admin notice about configuration migration to v8.
     */
    public function migration_admin_notice() {
        // Only show to users who can manage WooCommerce settings
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // Check if notice should be shown
        if ( 'yes' !== get_option( 'woocommerce_moyasar_v8_show_notice' ) ) {
            return;
        }

        $review_url = wp_nonce_url( add_query_arg( array( 'moyasar_dismiss_v8_notice' => '1', 'moyasar_redirect_to_settings' => '1' ) ), 'moyasar_dismiss_v8_notice_nonce' );
        $dismiss_url  = wp_nonce_url( add_query_arg( 'moyasar_dismiss_v8_notice', '1' ), 'moyasar_dismiss_v8_notice_nonce' );
        ?>
        <div class="notice notice-info is-dismissible" style="position: relative;">
            <p>
                <strong><?php _e( 'Moyasar Payments Upgrade Notice', 'moyasar-payments' ); ?></strong><br>
                <?php _e( 'Moyasar Payments has been upgraded to a new version. Some of your payment configurations have been migrated automatically, but please ensure your API Keys, Webhook Secrets, and active payment methods are correctly configured.', 'moyasar-payments' ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url( $review_url ); ?>" class="button button-primary"><?php _e( 'Review Settings', 'moyasar-payments' ); ?></a>
                <a href="<?php echo esc_url( $dismiss_url ); ?>" class="button button-secondary" style="margin-left: 5px;"><?php _e( 'Dismiss', 'moyasar-payments' ); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Dismiss migration notice.
     */
    public function dismiss_migration_notice() {
        if ( isset( $_GET['moyasar_dismiss_v8_notice'] ) && '1' === $_GET['moyasar_dismiss_v8_notice'] ) {
            if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'moyasar_dismiss_v8_notice_nonce' ) ) {
                if ( current_user_can( 'manage_woocommerce' ) ) {
                    delete_option( 'woocommerce_moyasar_v8_show_notice' );
                    
                    if ( isset( $_GET['moyasar_redirect_to_settings'] ) && '1' === $_GET['moyasar_redirect_to_settings'] ) {
                        $settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=moyasar&from=WCADMIN_PAYMENT_SETTINGS#moyasar_tab_general_settings' );
                        wp_safe_redirect( $settings_url );
                    } else {
                        wp_safe_redirect( remove_query_arg( array( 'moyasar_dismiss_v8_notice', '_wpnonce' ) ) );
                    }
                    exit;
                }
            }
        }
    }

    /**
     * Show a persistent note on the plugins page under the plugin row.
     */
    public function plugin_row_migration_note( $plugin_file, $plugin_data, $status ) {
        // Only show to users who can manage WooCommerce settings
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // Only show if the migration notice option is active
        if ( 'yes' !== get_option( 'woocommerce_moyasar_v8_show_notice' ) ) {
            return;
        }

        $review_url = wp_nonce_url( add_query_arg( array( 'moyasar_dismiss_v8_notice' => '1', 'moyasar_redirect_to_settings' => '1' ) ), 'moyasar_dismiss_v8_notice_nonce' );
        $message = __( 'This is a new version of Moyasar Payments. Some configurations have been migrated automatically. Please ensure your API Keys, Webhook Secrets, and active payment methods are correctly configured.', 'moyasar-payments' );
        ?>
        <tr class="plugin-update-tr active" id="moyasar-payments-migration-note-row" style="background-color: #fcf8e3;">
            <td colspan="4" class="plugin-update colspanchange" style="border-left: 4px solid #ffb900;">
                <div class="inline notice notice-warning notice-alt" style="margin: 5px 20px 15px 20px; padding: 10px; border-left: none;">
                    <p style="margin: 0; font-size: 13px;">
                        <strong><?php _e( 'Configuration Note:', 'moyasar-payments' ); ?></strong> <?php echo esc_html( $message ); ?>
                        <a href="<?php echo esc_url( $review_url ); ?>" style="margin-left: 10px; font-weight: 600; text-decoration: underline;"><?php _e( 'Review Settings', 'moyasar-payments' ); ?></a>
                    </p>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    8.0.0
     */
    /**
     * Add Apple Pay Verification File to Allowed content types
     */
    public function apple_pay_upload_mimes( $mimes ) {
        // Allow the specific filename as a "fake" extension just in case WP checks it that way
        $mimes['apple-developer-merchantid-domain-association'] = 'text/plain';
        // Allow text/plain generically if not present (risky but sometimes needed)
        $mimes['txt'] = 'text/plain';
        return $mimes;
    }

    /**
     * Correct filetype for Apple Pay Verification File
     */
    public function apple_pay_check_filetype( $data, $file, $filename, $mimes ) {
        // Loose check: If filename contains the apple domain association string
        if ( strpos( $filename, 'apple-developer-merchantid-domain-association' ) !== false ) {
            $data['ext']             = 'txt'; // Treat as text extension internally for WP safety
            $data['type']            = 'text/plain';
            // We do NOT enforce proper_filename anymore, let WP/User decide name
        } 
        return $data;
    }

    /**
     * Register the JavaScript and Styles for the admin area.
     *
     * @since    8.0.0
     */
    public function enqueue_scripts() {
        // Enqueue only on WooCommerce Settings page
        if ( ! isset( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] ) {
            return;
        }

        // Check if we are on a Moyasar section
        $section = isset( $_GET['section'] ) ? $_GET['section'] : '';
        if ( strpos( $section, 'moyasar' ) === false ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script( $this->plugin_name . '-admin', MOYASAR_WC_PLUGIN_URL . 'assets/js/moyasar-admin.js', array( 'jquery' ), $this->version, true );
        wp_enqueue_style( $this->plugin_name . '-admin', MOYASAR_WC_PLUGIN_URL . 'assets/css/moyasar-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Add plugin action links.
     *
     * @param  array  $links Original list of plugin links.
     * @param  string $file  Name of current file.
     * @return array  $links Update list of plugin links.
     */
    public function plugin_row_meta( $links, $file ) {
        if ( false !== strpos( $file, 'moyasar-payments.php' ) ) {
            $row_meta = array(
                'docs'    => '<a href="' . esc_url( 'https://docs.moyasar.com/' ) . '" title="' . esc_attr( __( 'View Documentation', 'moyasar-payments' ) ) . '" target="_blank">' . __( 'Docs', 'moyasar-payments' ) . '</a>',
                'support' => '<a href="' . esc_url( 'https://moyasar.com/contact-us' ) . '" title="' . esc_attr( __( 'Open a support request', 'moyasar-payments' ) ) . '" target="_blank">' . __( 'Support', 'moyasar-payments' ) . '</a>',
            );
            return array_merge( $links, $row_meta );
        }
        return (array) $links;
    }

    /**
     * Render Admin Options for the Main Gateway
     * 
     * @param WC_Gateway_Moyasar $gateway Main Moyasar gateway instance.
     */
    /**
     * Render Admin Options for the Main Gateway
     *
     * @param WC_Gateway_Moyasar $gateway Main Moyasar gateway instance.
     */
    public static function render_admin_options( $gateway ) {
        $form_fields = $gateway->get_form_fields();
        ?>
        <div class="moyasar-admin-wrapper">
            <div class="moyasar-header">
                <h2><?php echo esc_html( $gateway->get_method_title() ); ?></h2>
                <div class="moyasar-desc"><?php echo wp_kses_post( $gateway->get_method_description() ); ?></div>
            </div>

            <?php
                $moyasar_config_alerts = Moyasar_Helper::get_configuration_alerts();
                foreach ( $moyasar_config_alerts as $moyasar_alert ) :
            ?>
                <div class="notice notice-error inline moyasar-config-alert" style="margin: 15px 0; padding: 10px 12px;">
                    <p style="margin: 0;"><span class="dashicons dashicons-warning" style="color: #d63638; vertical-align: middle; margin-right: 4px;"></span><?php echo wp_kses_post( $moyasar_alert ); ?></p>
                </div>
            <?php endforeach; ?>

            <nav class="nav-tab-wrapper woo-nav-tab-wrapper moyasar-nav-tabs moyasar-admin-navbar">
                <a href="#moyasar_tab_general_settings" class="nav-tab nav-tab-active">
                    <span class="dashicons dashicons-list-view"></span> <?php _e( 'General Settings', 'moyasar-payments' ); ?>
                </a>
                <a href="#moyasar_tab_advanced_settings" class="nav-tab">
                    <span class="dashicons dashicons-admin-settings"></span> <?php _e( 'Advanced Settings', 'moyasar-payments' ); ?>
                </a>
                <a href="#moyasar_tab_dashboard" class="nav-tab">
                    <span class="dashicons dashicons-dashboard"></span> <?php _e( 'Dashboard', 'moyasar-payments' ); ?>
                    <span style="background-color: #d63638; color: #fff; font-size: 9px; padding: 2px 5px; border-radius: 3px; vertical-align: super; margin-left: 2px;">BETA</span>
                </a>
            </nav>

            <div class="moyasar-settings-container">
                                    
                <!-- Global Enable Section -->
                <div style="background: #fff; border: 1px solid #dcdcde; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; font-size: 15px; font-weight: 600;"><?php _e( 'Enable Moyasar Payments', 'moyasar-payments' ); ?></h3>
                        <p style="margin: 0; color: #646970; font-size: 13px;"><?php _e( 'Activate or deactivate all Moyasar payment methods on your store.', 'moyasar-payments' ); ?></p>
                    </div>
                    <label class="switch" style="transform: scale(1.2);">
                        <input type="checkbox" name="<?php echo esc_attr( $gateway->get_field_key( 'enabled' ) ); ?>" value="yes" <?php checked( 'yes', $gateway->get_option( 'enabled' ) ); ?>>
                        <span class="slider round"></span>
                    </label>
                </div>

                <?php include MOYASAR_WC_PLUGIN_PATH . 'includes/admin/views/tab-dashboard.php'; ?>

                <?php include MOYASAR_WC_PLUGIN_PATH . 'includes/admin/views/tab-general-settings.php'; ?>

                <?php include MOYASAR_WC_PLUGIN_PATH . 'includes/admin/views/tab-advanced-settings.php'; ?>

            </div>
            
            <!-- Feedback Banner -->
            <div class="moyasar-feedback-banner" style="margin-top: 30px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="font-size: 32px; background: #e0f2fe; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #0284c7;">
                        💬
                    </div>
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600; color: #0f172a;"><?php _e( 'We Hear You!', 'moyasar-payments' ); ?></h3>
                        <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.5;"><?php _e( 'Are you enjoying Moyasar Payments? We would love to hear your feedback. Rate us on WordPress.org or contact our support team if you need any help.', 'moyasar-payments' ); ?></p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; flex-shrink: 0;">
                    <a href="https://wordpress.org/support/plugin/moyasar/" target="_blank" class="button button-secondary" style="height: 40px; line-height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 500; font-size: 13px; color: #475569; border-color: #cbd5e1; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                        <span class="dashicons dashicons-sos" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px; display: inline-block;"></span>
                        <?php _e( 'Get Support', 'moyasar-payments' ); ?>
                    </a>
                    <a href="https://wordpress.org/support/plugin/moyasar/reviews/" target="_blank" class="button button-primary" style="height: 40px; line-height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 500; font-size: 13px; background: #0284c7; border-color: #0284c7; color: #fff; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; box-shadow: 0 1px 2px rgba(2,132,199,0.2);">
                        <span class="dashicons dashicons-star-filled" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px; color: #fbbf24; display: inline-block;"></span>
                        <?php _e( 'Rate Us ★★★★★', 'moyasar-payments' ); ?>
                    </a>
                </div>
            </div>
            
            <!-- JS for Tabs -->
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Function to switch tabs
                    function moyasarSwitchTab(tabId) {
                        // Hide all tab content
                        $('.moyasar-tab-content').hide();
                        
                        // Remove active class from all tabs
                        $('.moyasar-admin-navbar .nav-tab').removeClass('nav-tab-active');
                        
                        // Show current tab and add active class to link
                        $('#' + tabId).show();
                        $('.moyasar-admin-navbar .nav-tab[href="#' + tabId + '"]').addClass('nav-tab-active');

                        // Update URL hash without jumping
                        if(history.pushState) {
                            history.pushState(null, null, '#' + tabId);
                        } else {
                            window.location.hash = tabId;
                        }
                    }

                    // Check hash on load
                    var hash = window.location.hash;
                    if (hash && $(hash).length > 0 && hash.indexOf('moyasar_tab_') !== -1) {
                         moyasarSwitchTab(hash.substring(1));
                    } else {
                        // Default to first tab if no hash or invalid
                         moyasarSwitchTab('moyasar_tab_general_settings');
                    }

                    // Click handler
                    $('.moyasar-admin-navbar .nav-tab').on('click', function(e) {
                        e.preventDefault();
                        var tabId = $(this).attr('href').substring(1);
                        moyasarSwitchTab(tabId);
                    });

                    // Inline gateway customization settings row toggle
                    $('.moyasar-toggle-method-settings').on('click', function(e) {
                        e.preventDefault();
                        var mid = $(this).data('method');
                        $('#moyasar_method_settings_' + mid).toggle(200);
                    });
                });
            </script>
        </div>
        <?php
    }

    /**
     * Get form fields for the main Moyasar gateway settings.
     * 
     * Returns an array of form field definitions organized into logical sections:
     * - Core/General settings
     * - API configuration (test/live keys)
     * - Payment method enable/disable toggles
     * - Express checkout features (Apple Pay, Quick Buy)
     * - Payment restrictions (card brands, countries)
     * - Third-party integrations (Apple Pay domain, Samsung Pay)
     * - Webhook configuration
     * - Order status mappings
     * 
     * @since 8.0.0
     * @return array Associative array of field definitions keyed by field ID.
     */
    public static function get_form_fields() {
        $fields = array();

        // ====================================================================
        // SECTION 1: CORE / GENERAL SETTINGS
        // ====================================================================
        
        $fields['enabled'] = array(
            'title'   => __( 'Enable/Disable', 'moyasar-payments' ),
            'type'    => 'checkbox',
            'label'   => __( 'Enable Moyasar Payments', 'moyasar-payments' ),
            'default' => 'yes',
        );

        $fields['title'] = array(
            'title'       => __( 'Title', 'moyasar-payments' ),
            'type'        => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'moyasar-payments' ),
            'default'     => __( 'Moyasar', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['description'] = array(
            'title'       => __( 'Description', 'moyasar-payments' ),
            'type'        => 'textarea',
            'description' => __( 'Payment method description that the customer will see on your checkout.', 'moyasar-payments' ),
            'default'     => __( 'Accept Credit/Debit Cards, Apple Pay, and more.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        // ====================================================================
        // SECTION 2: API CONFIGURATION
        // ====================================================================

        $fields['testmode'] = array(
            'title'       => __( 'Test mode', 'moyasar-payments' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Test Mode', 'moyasar-payments' ),
            'default'     => 'no',
            'description' => __( 'Place the payment gateway in test mode using test API keys.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['debug_mode'] = array(
            'title'       => __( 'Debug Mode', 'moyasar-payments' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Debug Mode', 'moyasar-payments' ),
            'default'     => 'no',
            'description' => __( 'Log detailed payment events for debugging purposes.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        // Test API Keys
        $fields['test_publishable_key'] = array(
            'title'       => __( 'Test Publishable Key', 'moyasar-payments' ),
            'type'        => 'text',
            'description' => __( 'Get your API keys from your Moyasar dashboard. Only values starting with "pk_test_" will be saved.', 'moyasar-payments' ),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => 'pk_test_...',
        );

        $fields['test_secret_key'] = array(
            'title'       => __( 'Test Secret Key', 'moyasar-payments' ),
            'type'        => 'password',
            'description' => __( 'Get your API keys from your Moyasar dashboard. Only values starting with "sk_test_" will be saved.', 'moyasar-payments' ),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => 'sk_test_...',
        );

        // Live API Keys
        $fields['live_publishable_key'] = array(
            'title'       => __( 'Live Publishable Key', 'moyasar-payments' ),
            'type'        => 'text',
            'description' => __( 'Get your API keys from your Moyasar dashboard. Only values starting with "pk_live_" will be saved.', 'moyasar-payments' ),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => 'pk_live_...',
        );

        $fields['live_secret_key'] = array(
            'title'       => __( 'Live Secret Key', 'moyasar-payments' ),
            'type'        => 'password',
            'description' => __( 'Get your API keys from your Moyasar dashboard. Only values starting with "sk_live_" will be saved.', 'moyasar-payments' ),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => 'sk_live_...',
        );

        // ====================================================================
        // SECTION 3: PAYMENT METHOD ENABLE/DISABLE
        // ====================================================================

        $fields['enable_method_moyasar_cc'] = array(
            'title'       => __( 'Credit Card Payment', 'moyasar-payments' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Credit Card Payment Method', 'moyasar-payments' ),
            'default'     => 'yes',
            'description' => __( 'Enable or disable the Credit Card payment method specifically.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['moyasar_cc_title'] = array(
            'title'       => __( 'Credit Card Title', 'moyasar-payments' ),
            'type'        => 'text',
            'default'     => __( 'Card Payment', 'moyasar-payments' ),
            'description' => __( 'Title shown to customer during checkout.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['moyasar_cc_description'] = array(
            'title'       => __( 'Credit Card Description', 'moyasar-payments' ),
            'type'        => 'textarea',
            'default'     => __( 'Pay with your Credit/Debit Card.', 'moyasar-payments' ),
            'description' => __( 'Description shown to customer during checkout.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['cc_enable_tokenization'] = array(
            'title'       => __( 'Enable Saved Cards', 'moyasar-payments' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Payment Method Saving', 'moyasar-payments' ),
            'default'     => 'yes',
            'description' => __( 'If enabled, customers will be able to save their cards to their account for future checkouts.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        // Dynamically add enable/disable fields for other payment methods
        $methods = Moyasar_Helper::get_payment_methods_list();
        $default_descriptions = array(
            'moyasar_apple_pay'   => __( 'Pay with Apple Pay', 'moyasar-payments' ),
            'moyasar_stc_pay'     => __( 'Pay with STC Pay', 'moyasar-payments' ),
            'moyasar_samsung_pay' => __( 'Pay with Samsung Pay', 'moyasar-payments' ),
            'moyasar_invoice'     => __( 'Pay via Invoice (Moyasar Payment Page).', 'moyasar-payments' ),
        );
        $default_titles = array(
            'moyasar_apple_pay'   => __( 'Apple Pay', 'moyasar-payments' ),
            'moyasar_stc_pay'     => __( 'STC Pay', 'moyasar-payments' ),
            'moyasar_samsung_pay' => __( 'Samsung Pay', 'moyasar-payments' ),
            'moyasar_invoice'     => __( 'Invoice', 'moyasar-payments' ),
        );

        foreach ( $methods as $method_id => $method_data ) {
            // Skip Credit Card as it's already defined above
            if ( 'moyasar_cc' === $method_id ) {
                continue;
            }

            $fields['enable_method_' . $method_id] = array(
                'title'       => $method_data['label'],
                'type'        => 'checkbox',
                'label'       => sprintf( __( 'Enable %s', 'moyasar-payments' ), $method_data['label'] ),
                'default'     => 'no',
                'description' => sprintf( __( 'Accept payments via %s.', 'moyasar-payments' ), $method_data['label'] ),
                'desc_tip'    => true,
            );

            $fields[ $method_id . '_title' ] = array(
                'title'       => sprintf( __( '%s Title', 'moyasar-payments' ), $method_data['label'] ),
                'type'        => 'text',
                'default'     => isset( $default_titles[$method_id] ) ? $default_titles[$method_id] : $method_data['label'],
                'description' => __( 'Title shown to customer during checkout.', 'moyasar-payments' ),
                'desc_tip'    => true,
            );

            $fields[ $method_id . '_description' ] = array(
                'title'       => sprintf( __( '%s Description', 'moyasar-payments' ), $method_data['label'] ),
                'type'        => 'textarea',
                'default'     => isset( $default_descriptions[$method_id] ) ? $default_descriptions[$method_id] : sprintf( __( 'Pay with %s', 'moyasar-payments' ), $method_data['label'] ),
                'description' => __( 'Description shown to customer during checkout.', 'moyasar-payments' ),
                'desc_tip'    => true,
            );
        }

        // Apple Pay Checkout Button styling fields
        $fields['apple_pay_checkout_btn_theme'] = array(
            'title'       => __( 'Apple Pay Checkout Button Theme', 'moyasar-payments' ),
            'type'        => 'select',
            'options'     => array(
                'dark'          => __( 'Dark', 'moyasar-payments' ),
                'light'         => __( 'Light', 'moyasar-payments' ),
                'light-outline' => __( 'Light Outline', 'moyasar-payments' ),
            ),
            'default'     => 'dark',
            'description' => __( 'Select the button theme for the checkout page.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['apple_pay_checkout_btn_height'] = array(
            'title'       => __( 'Apple Pay Checkout Button Height', 'moyasar-payments' ),
            'type'        => 'select',
            'default'     => 'medium',
            'options'     => array(
                'small'  => __( 'Small (32px)', 'moyasar-payments' ),
                'medium' => __( 'Medium (44px)', 'moyasar-payments' ),
                'large'  => __( 'Large (55px)', 'moyasar-payments' ),
            ),
            'description' => __( 'Select the height of the Apple Pay button on the checkout page.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['apple_pay_checkout_btn_label'] = array(
            'title'       => __( 'Apple Pay Checkout Button Label', 'moyasar-payments' ),
            'type'        => 'select',
            'default'     => 'plain',
            'options'     => array(
                'plain'     => __( 'Logo Only', 'moyasar-payments' ),
                'buy'       => __( 'Buy with Apple Pay', 'moyasar-payments' ),
                'donate'    => __( 'Donate with Apple Pay', 'moyasar-payments' ),
                'check-out' => __( 'Check out with Apple Pay', 'moyasar-payments' ),
                'book'      => __( 'Book with Apple Pay', 'moyasar-payments' ),
                'subscribe' => __( 'Subscribe with Apple Pay', 'moyasar-payments' ),
            ),
            'description' => __( 'Select the text to display on the Apple Pay button on the checkout page.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        // Samsung Pay Checkout Button styling fields
        $fields['samsung_pay_checkout_btn_height'] = array(
            'title'       => __( 'Samsung Pay Button Height', 'moyasar-payments' ),
            'type'        => 'select',
            'default'     => 'medium',
            'options'     => array(
                'small'  => __( 'Small (32px)', 'moyasar-payments' ),
                'medium' => __( 'Medium (44px)', 'moyasar-payments' ),
                'large'  => __( 'Large (55px)', 'moyasar-payments' ),
            ),
            'description' => __( 'Select the height of the Samsung Pay button on the checkout page.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['samsung_pay_checkout_btn_border_radius'] = array(
            'title'       => __( 'Samsung Pay Button Border Radius', 'moyasar-payments' ),
            'type'        => 'number',
            'default'     => '4',
            'description' => __( 'Set the border radius of the Samsung Pay button (in pixels). Use "0" for square corners (no radius). The recommended range is 0 to 50.', 'moyasar-payments' ),
            'desc_tip'    => true,
            'custom_attributes' => array(
                'min'  => '0',
                'max'  => '100',
                'step' => '1',
            ),
        );

        // ====================================================================
        // SECTION 4: EXPRESS CHECKOUT / PRODUCT PAGE FEATURES
        // ====================================================================

        // Apple Pay on Product Page
        $fields['enable_apple_pay_product_page'] = array(
            'title'       => __( 'Apple Pay', 'moyasar-payments' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Apple Pay on Product Page', 'moyasar-payments' ),
            'default'     => 'yes',
            'description' => __( 'Show the Apple Pay button on individual product pages.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['apple_pay_border_radius'] = array(
            'title'       => __( 'Button Border Radius', 'moyasar-payments' ),
            'type'        => 'number',
            'default'     => '5',
            'description' => __( 'Set the border radius of the Apple Pay button (in pixels). Use "0" for square corners (no radius). The recommended range is 0 to 50.', 'moyasar-payments' ),
            'desc_tip'    => true,
            'custom_attributes' => array(
                'min'  => '0',
                'max'  => '100',
                'step' => '1',
            ),
        );

        // Apple Pay Button Appearance
        $fields['express_btn_theme'] = array(
            'title'       => __( 'Button Theme', 'moyasar-payments' ),
            'type'        => 'select',
            'options'     => array(
                'dark'          => __( 'Dark', 'moyasar-payments' ),
                'light'         => __( 'Light', 'moyasar-payments' ),
                'light-outline' => __( 'Light Outline', 'moyasar-payments' ),
            ),
            'default'     => 'dark',
            'description' => __( 'Select the button theme you would like to show.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['express_btn_height'] = array(
            'title'       => __( 'Button Height', 'moyasar-payments' ),
            'type'        => 'select',
            'default'     => 'medium',
            'options'     => array(
                'small'  => __( 'Small (32px)', 'moyasar-payments' ),
                'medium' => __( 'Medium (44px)', 'moyasar-payments' ),
                'large'  => __( 'Large (55px)', 'moyasar-payments' ),
            ),
            'description' => __( 'Select the height of the Apple Pay button. This setting applies ONLY to the Product Page.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['express_btn_label'] = array(
            'title'       => __( 'Button Label', 'moyasar-payments' ),
            'type'        => 'select',
            'default'     => 'buy',
            'options'     => array(
                'plain'     => __( 'Logo Only', 'moyasar-payments' ),
                'buy'       => __( 'Buy with Apple Pay', 'moyasar-payments' ),
                'donate'    => __( 'Donate with Apple Pay', 'moyasar-payments' ),
                'check-out' => __( 'Check out with Apple Pay', 'moyasar-payments' ),
                'book'      => __( 'Book with Apple Pay', 'moyasar-payments' ),
                'subscribe' => __( 'Subscribe with Apple Pay', 'moyasar-payments' ),
            ),
            'description' => __( 'Text to display on the button.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        // Quick Buy (Credit Card Instant Checkout)
        $fields['enable_instant_checkout'] = array(
            'title'       => __( 'Quick Buy (Credit Card)', 'moyasar-payments' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Quick Buy Button (Beta)', 'moyasar-payments' ),
            'default'     => 'no',
            'description' => __( 'Show a Quick Buy button for Credit Card payments on product pages.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        // Build shipping options list for Quick Buy
        $shipping_options = array( '' => __( 'Select a Shipping Option...', 'moyasar-payments' ) );

        if ( class_exists( 'WC_Shipping_Zones' ) ) {
            // Get all shipping zones
            $zones = WC_Shipping_Zones::get_zones();

            // Add "Rest of the World" zone (zone_id = 0)
            $zones[] = array(
                'zone_id'         => 0,
                'zone_name'       => __( 'Rest of the World', 'moyasar-payments' ),
                'shipping_methods' => WC_Shipping_Zones::get_zone_by( 'zone_id', 0 )->get_shipping_methods(),
            );

            // Loop through zones and their enabled shipping methods
            foreach ( $zones as $zone ) {
                $zone_name = $zone['zone_name'];
                $methods   = isset( $zone['shipping_methods'] ) ? $zone['shipping_methods'] : $zone->get_shipping_methods();

                foreach ( $methods as $method ) {
                    if ( 'yes' === $method->enabled ) {
                        // Format cost display if available
                        $cost_str = '';
                        if ( isset( $method->settings['cost'] ) && $method->settings['cost'] !== '' ) {
                            $cost_str = ' (' . wc_price( $method->settings['cost'] ) . ')';
                        }

                        // Use instance_id as unique identifier for the configured method
                        $value = $method->instance_id;
                        $label = $zone_name . ' - ' . $method->title . $cost_str;

                        $shipping_options[ $value ] = $label;
                    }
                }
            }
        }

        $fields['quick_buy_shipping_enabled'] = array(
            'title'       => __( 'Shipping Options', 'moyasar-payments' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Shipping for Quick Buy', 'moyasar-payments' ),
            'default'     => 'no',
            'description' => __( 'If enabled, the selected shipping method below will be applied.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['quick_buy_shipping_option'] = array(
            'title'       => __( 'Shipping Method', 'moyasar-payments' ),
            'type'        => 'select',
            'options'     => $shipping_options,
            'default'     => '',
            'description' => __( 'Select the shipping method to apply for Quick Buy orders.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        // ====================================================================
        // SECTION 5: PAYMENT RESTRICTIONS
        // ====================================================================

        $fields['allowed_brands'] = array(
            'title'             => __( 'Allowed Brands', 'moyasar-payments' ),
            'type'              => 'multiselect',
            'class'             => 'wc-enhanced-select',
            'css'               => 'width: 400px;',
            'default'           => array( 'visa', 'mastercard', 'mada', 'amex', 'unionpay' ),
            'description'       => __( 'Select the card brands you accept.', 'moyasar-payments' ),
            'options'           => array(
                'visa'       => 'Visa',
                'mastercard' => 'MasterCard',
                'mada'       => 'Mada',
                'amex'       => 'American Express',
                'unionpay'   => 'UnionPay',
            ),
            'desc_tip'          => true,
            'custom_attributes' => array(
                'required' => 'required',
            ),
        );

        $fields['allowed_countries'] = array(
            'title'             => __( 'Allowed Countries', 'moyasar-payments' ),
            'type'              => 'multiselect',
            'class'             => 'wc-enhanced-select',
            'css'               => 'width: 400px;',
            'default'           => array( 'SA' ),
            'description'       => __( 'If restricted, only customers from these countries can use this gateway.', 'moyasar-payments' ),
            'options'           => WC()->countries->get_countries(),
            'desc_tip'          => true,
            'custom_attributes' => array(
                'required' => 'required',
            ),
        );

        $fields['gateway_icon_height'] = array(
            'title'       => __( 'Gateway Icon Height', 'moyasar-payments' ),
            'type'        => 'number',
            'default'     => '',
            'description' => __( 'Set the custom height of payment icons displayed at checkout (in pixels). If left empty, icons will use the default responsive size (max-width: 45px).', 'moyasar-payments' ),
            'desc_tip'    => true,
            'custom_attributes' => array(
                'min'         => '10',
                'max'         => '100',
                'step'        => '1',
                'placeholder' => 'e.g. 24',
            ),
        );

        // ====================================================================
        // SECTION 6: THIRD-PARTY INTEGRATIONS
        // ====================================================================

        // Samsung Pay Service ID
        $fields['samsung_pay_service_id'] = array(
            'title'       => __( 'Samsung Pay Service ID', 'moyasar-payments' ),
            'type'        => 'text',
            'description' => __( 'Your Samsung Pay Service ID from the Samsung Pay Partner Portal.', 'moyasar-payments' ),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => 'dcc0...',
        );

        // ====================================================================
        // SECTION 7: WEBHOOK CONFIGURATION
        // ====================================================================

        $fields['webhook_secret'] = array(
            'type'              => 'password',
            'default'           => '',
            'custom_attributes' => array(
                'readonly' => 'readonly',
            ),
        );

        // ====================================================================
        // SECTION 8: ORDER STATUS CONFIGURATION
        // ====================================================================

        $order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();

        $fields['order_status_success'] = array(
            'title'       => __( 'Success Status', 'moyasar-payments' ),
            'type'        => 'select',
            'options'     => $order_statuses,
            'default'     => 'wc-processing',
            'description' => __( 'Select the order status after a successful payment.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['order_status_failed'] = array(
            'title'       => __( 'Failed Status', 'moyasar-payments' ),
            'type'        => 'select',
            'options'     => $order_statuses,
            'default'     => 'wc-failed',
            'description' => __( 'Select the order status after a failed payment.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['order_status_refunded'] = array(
            'title'       => __( 'Refund Status', 'moyasar-payments' ),
            'type'        => 'select',
            'options'     => $order_statuses,
            'default'     => 'wc-refunded',
            'description' => __( 'Select the order status after a refund.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        $fields['order_status_voided'] = array(
            'title'       => __( 'Void Status', 'moyasar-payments' ),
            'type'        => 'select',
            'options'     => $order_statuses,
            'default'     => 'wc-cancelled',
            'description' => __( 'Select the order status after a void.', 'moyasar-payments' ),
            'desc_tip'    => true,
        );

        return $fields;
    }

    /**
     * Absolute path to the physical .well-known domain association file.
     *
     * @return string
     */
    private static function get_apple_pay_domain_file_path() {
        return ABSPATH . '.well-known/apple-developer-merchantid-domain-association';
    }

    /**
     * Write the domain association file to disk so it is reachable even on
     * hosts where /.well-known/ is served directly by the web server and
     * never reaches WordPress (common with Apache/Nginx ACME/.well-known rules).
     *
     * @param string $content Raw file content.
     * @return bool True on success.
     */
    private static function write_apple_pay_domain_file_to_disk( $content ) {
        $dir = dirname( self::get_apple_pay_domain_file_path() );

        if ( ! wp_mkdir_p( $dir ) ) {
            return false;
        }

        return false !== file_put_contents( self::get_apple_pay_domain_file_path(), $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    }

    /**
     * Remove the physical domain association file from disk, if present.
     */
    private static function delete_apple_pay_domain_file_from_disk() {
        $path = self::get_apple_pay_domain_file_path();
        if ( file_exists( $path ) ) {
            @unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }
    }

    /**
     * Handle the Apple Pay Domain Verification File upload and removal.
     *
     * The Apple domain association file is extension-less, which the WordPress
     * media library often rejects ("This file cannot be processed by the web
     * server"). To make the upload reliable, we read the uploaded file directly,
     * store its content in the main settings option (for dynamic serving via the
     * .well-known rewrite rule), and also write it to disk at
     * /.well-known/apple-developer-merchantid-domain-association so it is served
     * even on hosts that handle .well-known statically, outside of WordPress.
     *
     * @since 8.2.0
     */
    public static function process_apple_pay_domain_file() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $settings = get_option( 'woocommerce_moyasar_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $changed = false;

        // Removal requested.
        if ( isset( $_POST['moyasar_remove_apple_pay_domain_file'] ) && '1' === $_POST['moyasar_remove_apple_pay_domain_file'] ) {
            if ( ! empty( $settings['apple_pay_domain_file_content'] ) ) {
                $settings['apple_pay_domain_file_content'] = '';
                $changed = true;
            }
            self::delete_apple_pay_domain_file_from_disk();
        }

        // New file uploaded.
        if ( isset( $_FILES['moyasar_apple_pay_domain_file'] ) && is_array( $_FILES['moyasar_apple_pay_domain_file'] ) ) {
            $file = $_FILES['moyasar_apple_pay_domain_file'];

            // Only act when a file was actually selected.
            if ( isset( $file['error'] ) && UPLOAD_ERR_NO_FILE !== $file['error'] ) {
                if ( UPLOAD_ERR_OK !== $file['error'] ) {
                    WC_Admin_Settings::add_error( __( 'The Apple Pay Domain Verification File could not be uploaded. Please try again or place the file manually.', 'moyasar-payments' ) );
                } elseif ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
                    WC_Admin_Settings::add_error( __( 'The Apple Pay Domain Verification File upload failed validation.', 'moyasar-payments' ) );
                } elseif ( (int) $file['size'] <= 0 || (int) $file['size'] > 65536 ) {
                    // Apple's association file is a few KB; guard against oversized/empty files.
                    WC_Admin_Settings::add_error( __( 'The Apple Pay Domain Verification File is empty or too large.', 'moyasar-payments' ) );
                } else {
                    $content = file_get_contents( $file['tmp_name'] );
                    if ( false === $content || '' === trim( $content ) ) {
                        WC_Admin_Settings::add_error( __( 'The Apple Pay Domain Verification File appears to be empty.', 'moyasar-payments' ) );
                    } else {
                        $content = trim( $content );
                        $settings['apple_pay_domain_file_content'] = $content;
                        $changed = true;

                        if ( self::write_apple_pay_domain_file_to_disk( $content ) ) {
                            WC_Admin_Settings::add_message( __( 'Apple Pay Domain Verification File uploaded successfully.', 'moyasar-payments' ) );
                        } else {
                            WC_Admin_Settings::add_message( __( 'Apple Pay Domain Verification File saved. It will be served dynamically, but the file could not be written to the /.well-known/ directory on disk (check folder permissions) as an extra safeguard.', 'moyasar-payments' ) );
                        }
                    }
                }
            }
        }

        if ( $changed ) {
            update_option( 'woocommerce_moyasar_settings', $settings );
            // Invalidate the cached remote reachability check so the settings
            // screen reflects the upload/removal immediately.
            delete_transient( Moyasar_Helper::APPLE_PAY_DOMAIN_FILE_REMOTE_CACHE );
            // Ensure the .well-known rewrite rule is active for serving the file.
            flush_rewrite_rules();
        }
    }

    /**
     * Process Admin Options (Save logic)
     */
    public static function process_settings( $gateway ) {
        // Handle the Apple Pay Domain Verification File upload / removal.
        self::process_apple_pay_domain_file();

        // Sync Payment Method Toggles to other Gateways
        $available_methods = Moyasar_Helper::get_payment_methods_list();
        foreach ( $available_methods as $method_id => $method_data ) {

            $key = 'enable_method_' . $method_id;
            $is_enabled = 'yes' === $gateway->get_option( $key ) ? 'yes' : 'no';

            $target_option = 'woocommerce_' . $method_id . '_settings';
            
            $target_settings = get_option( $target_option, array() );
            if ( ! is_array( $target_settings ) ) {
                $target_settings = array();
            }
            
            // Update the 'enabled' key for the sub-gateway
            $target_settings['enabled'] = $is_enabled;
            update_option( $target_option, $target_settings );
        }

        // Dismiss the settings migration notice on settings save
        delete_option( 'woocommerce_moyasar_v8_show_notice' );
    }
}
