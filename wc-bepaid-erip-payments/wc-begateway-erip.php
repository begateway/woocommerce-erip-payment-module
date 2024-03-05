<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/*
Plugin Name: WooCommerce ЕРИП платежи
Plugin URI: https://github.com/begateway/woocommerce-erip-payment-module
Description: Модуль приёма ЕРИП платежей через агрегатора bePaid.by
Version: 4.0.0
Author: bePaid
Author Email: help@bepaid.by

Text Domain: wc-begateway-erip-payment
Domain Path: /languages/

WC requires at least: 3.2.0
WC tested up to: 8.5.2
*/

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class WC_Begateway_Erip
{

    public static $module_id = 'begateway_erip';

    function __construct()
    {
        $this->id = self::$module_id;

        add_action('before_woocommerce_init', array($this, 'woocommerce_begateway_erip_declare_hpos_compatibility'));
        add_action('woocommerce_loaded', array($this, 'woocommerce_loaded'), 40);
        add_action('woocommerce_blocks_loaded', array($this, 'woocommerce_begateway_woocommerce_blocks_support'));
        // Load translation files
        add_action('init', __CLASS__ . '::load_plugin_textdomain', 3);

        // Add statuses for payment complete
        add_filter('woocommerce_valid_order_statuses_for_payment_complete', array(
            $this,
            'add_valid_order_statuses'
        ), 10, 2);


        
        // Add Admin Backend Actions
        add_action('wp_ajax_begateway_cancel_bill', array(
            $this,
            'ajax_begateway_erip_cancel'
        )
        );

        add_action('wp_ajax_begateway_create_bill', array(
            $this,
            'ajax_begateway_erip_create'
        )
        );

        // Add scripts and styles for admin
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }

        // add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 10, 2);

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
            $this,
            'woocommerce_begateway_erip_plugin_links'
        )
        );
    }

    public static function load_plugin_textdomain()
    {
        // Localization
        $plugin_rel_path = apply_filters('woocommerce_begateway_translation_file_rel_path', dirname(plugin_basename(__FILE__)) . '/languages');
        load_plugin_textdomain('wc-begateway-erip-payment', false, $plugin_rel_path);
    }

    public function woocommerce_loaded()
    {
        include_once(__DIR__ . '/includes/vendor/autoload.php');
        include_once(__DIR__ . '/includes/class-wc-gateway-begateway-erip-utils.php');
        include_once(__DIR__ . '/includes/class-wc-gateway-begateway-erip.php');

        WC_Begateway_Erip::register_gateway('WC_Gateway_Begateway_Erip');
    }

    /**
     * Register payment gateway
     *
     * @param string $class_name
     */
    public static function register_gateway($class_name)
    {
        global $gateways;

        if (!$gateways) {
            $gateways = array();
        }

        if (!isset($gateways[$class_name])) {
            // Initialize instance
            if ($gateway = new $class_name) {
                $gateways[] = $class_name;
                if (method_exists($gateway, 'set_version')) {
                    $gateway->set_version(self::_get_plugin_version());
                }

                // Register gateway instance
                add_filter('woocommerce_payment_gateways', function ($methods) use ($gateway) {
                    $methods[] = $gateway;

                    return $methods;
                });
            }
        }
    }

    /**
     * Declare blocks support
     *
     * @param 
     */
    function woocommerce_begateway_woocommerce_blocks_support()
    {

        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once dirname(__FILE__) . '/includes/blocks/class-wc-gateway-begateway-erip-blocks-support.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new WC_BeGateway_Erip_Blocks_Support);
                }
            );
        }
    }

    /**
     * Add links to plugin description
     *
     * @param array $links
     * @return array
     */
    function woocommerce_begateway_plugin_links($links)
    {

        $settings_url = add_query_arg(
            array(
                'page' => 'wc-settings',
                'tab' => 'checkout',
                'section' => 'wc_gateway_begateway_erip',
            ),
            admin_url('admin.php')
        );

        $plugin_links = array(
            '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'wc-begateway-payment') . '</a>',
            '<a href="https://wordpress.org/support/plugin/wc-begateway-erip-payment-payment/">' . esc_html__('Support', 'wc-begateway-erip-payment') . '</a>',
            '<a href="https://wordpress.org/plugins/wc-begateway-erip-payment-payment/#description">' . esc_html__('Docs', 'wc-begateway-erip-payment') . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

    /**
     * Declare HPOS support
     *
     */
    function woocommerce_begateway_erip_declare_hpos_compatibility()
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    /**
     * Allow processing/completed statuses for capture
     *
     * @param array    $statuses
     * @param WC_Order $order
     *
     * @return array
     */
    public function add_valid_order_statuses($statuses, $order)
    {
        if ($this->id == $order->get_payment_method()) {
            $statuses = array_merge($statuses, array(
                'processing',
                'completed')
            );
        }

        return $statuses;
    }

    /**
     * Add meta boxes in admin
     * @return void
     */
    public function add_meta_boxes($screen, $post)
    {
        $order = $order instanceof \WC_Order ? $order : wc_get_order($order->ID);
        if (!($order instanceof \WC_Order)) {
            return;
        }

        if ($this->id !== $order->get_payment_method()) {
            return;
        }

        $screen = WC_Gateway_BeGateway_Utils::get_edit_order_screen_id();
        ;
        $post_types = apply_filters('woocommerce_begateway_erip_admin_meta_box_post_types', array($screen));

        foreach ($post_types as $post_type) {
            add_meta_box(
                'begateway-erip-payment-actions',
                __('ЕРИП действия', 'wc-begateway-erip-payment'),
                [
                    &
                    $this,
                    'meta_box_payment',
                ],
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Inserts the content of the API actions into the meta box
     */
    public function meta_box_payment($object)
    {
        $order = $object instanceof WC_Order ? $object : wc_get_order($object->ID);

        if (!($order instanceof \WC_Order)) {
            return;
        }

        $payment_method = $order->get_payment_method();

        if ($this->id == $payment_method) {

            do_action('woocommerce_begateway_erip_gateway_meta_box_payment_before_content', $order);

            // Get Payment Gateway
            $gateways = WC()->payment_gateways()->get_available_payment_gateways();

            /** @var WC_Payment_Gateway_Begateway_Erip $gateway */
            $gateway = $gateways[$payment_method];

            try {
                wc_get_template(
                    'admin/metabox-order.php',
                    array(
                        'gateway' => $gateway,
                        'order' => $order,
                        'order_id' => $order->get_id(),
                        'order_data' => $gateway->get_invoice_data($order)
                    ),
                    '',
                    dirname(__FILE__) . '/templates/'
                );
            } catch (Exception $e) {
            }
        }
    }

    public function admin_enqueue_scripts($hook)
    {
        wp_register_script(
            'begateway-erip-gateway-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array(
                'jquery'
            )
        );
        wp_enqueue_style('wc-gateway-begateway-erip', plugins_url('/assets/css/style.css', __FILE__), array(), FALSE, 'all');

        // Localize the script
        $translation_array = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'text_wait' => __('Обрабатываем запрос...', 'wc-begateway-erip-payment'),
            'module_id' => self::$module_id
        );
        wp_localize_script('begateway-erip-gateway-admin-js', 'Begateway_Erip_Admin', $translation_array);

        // Enqueued script with localized data
        wp_enqueue_script('begateway-erip-gateway-admin-js');
    }

    /**
     * Action for ERIP bill cancel
     */
    public function ajax_begateway_erip_cancel()
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'begateway')) {
            wp_send_json_error('Invalid nonce');
            die();
        }

        $order_id = (int) sanitize_text_field($_REQUEST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(__('Не верный номер заказа'));
            die();
        }

        // Get Payment Gateway
        $payment_method = $order->get_payment_method();
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();

        /** @var WC_Gateway_Begateway_Erip $gateway */
        $gateway = $gateways[$payment_method];
        $result = $gateway->cancel_bill($order);

        if (!is_wp_error($result)) {
            wp_send_json_success(self::get_admin_notice_message('cancel'));
        } else {
            wp_send_json_error($result->get_error_message());
        }
    }

    public static function get_admin_notice_message($type)
    {
        $messages = [
            'cancel' => __('Счёт в ЕРИП успешно отменён', 'wc-begateway-erip-payment'),
            'create' => __('Счёт в ЕРИП успешно создан', 'wc-begateway-erip-payment')
        ];

        $result = (isset($messages[$type])) ? $messages[$type] : null;
        return $result;
    }

    /**
     * Action for ERIP bill creation
     */
    public function ajax_begateway_erip_create()
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'begateway')) {
            wp_send_json_error('Invalid nonce');
            die();
        }

        $order_id = (int) sanitize_text_field($_REQUEST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(__('Не верный номер заказа'));
            die();
        }

        // Get Payment Gateway
        $payment_method = $order->get_payment_method();
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();

        /** @var WC_Gateway_Begateway_Erip $gateway */
        $gateway = $gateways[$payment_method];
        $result = $gateway->create_bill($order);

        if (!is_wp_error($result)) {
            wp_send_json_success(self::get_admin_notice_message('create'));
        } else {
            wp_send_json_error($result->get_error_message());
        }
    }

    /**
     * Get the pluging version
     * @return string
     */
    public static function _get_plugin_version()
    {
        $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
        return $plugin_data['Version'];
    }

    public static function admin_notice($message, $type = 'success')
    {
        ?>
        <div class="notice notice-<?php echo $type; ?> is-dismissible">
            <p>
                <?php echo $message; ?>
            </p>
        </div>
        <?php
    }
}

new WC_Begateway_Erip();

function admin_notice_erip_message()
{
    $plugin = isset($_GET['plugin']) ? $_GET['plugin'] : false;

    if (!$plugin || $plugin != WC_Begateway_Erip::$module_id) {
        return;
    }

    $message_type = isset($_GET['plugin_message']) ? $_GET['plugin_message'] : null;

    $message = WC_Begateway_Erip::get_admin_notice_message($message_type);

    if (!$message) {
        return;
    }

    WC_Begateway_Erip::admin_notice($message, 'success');
}
add_action('admin_notices', 'admin_notice_erip_message');
