<?php

/**
 * Fio gateway main class based on woocommerce
 *
 * @since 1.0.0
 *
 */
class WC_Gateway_Fio extends WC_Payment_Gateway {

    /**
     * Logging enabled?
     *
     * @var bool
     */
    public $logging;


    function __construct() {
        $this->id = 'fio';
        $this->method_title = __('FIO', 'woocommerce-gateway-fio');
        $this->method_description = __('FIO works by letting clients pay FIO to your FIO wallet for orders in you shop.', 'woocommerce-gateway-fio');
        $this->has_fields = true;
        $this->icon = WC_FIO_PLUGIN_URL . ('/assets/img/pay_with_fio.png');
        $this->order_button_text = "Waiting for payment";


        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Get setting values.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->fio_address = $this->get_option('fio_address');
        $this->match_amount = 'yes' === $this->get_option('match_amount');
        $this->logging = 'yes' === $this->get_option('logging');
        $this->prices_in_fio = $this->get_option('prices_in_fio');


        // Hooks.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('wp_enqueue_scripts', array( $this, 'payment_scripts' ));

    }



    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        $user = wp_get_current_user();
        $fio_amount = WC_Fio_Currency::get_fio_amount($this->get_order_total(), strtoupper(get_woocommerce_currency()));

        $fio_ref = wp_create_nonce("3h62h6u26h42h6i2462h6u4h624");
        $fio_ref = hexdec($fio_ref) % 100000;
        $fio_ref_dec = $fio_ref / 10000000;

        $fio_amount = round($fio_amount, 2) + $fio_ref_dec;

        //Todo: Lock amount for 5 minutes
        WC()->session->set('fio_amount', $fio_amount);

        if ( $user->ID ) {
            $user_email = get_user_meta($user->ID, 'billing_email', true);
            $user_email = $user_email ? $user_email : $user->user_email;
        } else {
            $user_email = '';
        }

        //Start wrapper
        echo '<div id="fio-form"
			data-email="' . esc_attr($user_email) . '"
			data-amount="' . esc_attr($this->get_order_total()) . '"
			data-currency="' . esc_attr(strtolower(get_woocommerce_currency())) . '"
			data-fio-address="' . esc_attr($this->fio_address) . '"
			data-fio-amount="' . esc_attr($fio_amount) . '"
			">';
			// data-fio-ref="' . esc_attr($fio_ref) . '"

        //Info box
        echo '<div id="fio-description">';
        if ( $this->description ) {
            //echo apply_filters( 'wc_fio_description', wpautop( wp_kses_post( $this->description ) ) );
        }
        echo '</div>';

        echo '<div class="alert alert-danger">Transaction amount needs to match all the decimals in the amount above. Any transaction with different amount will be discarded!</div>';

        //QRcode
        echo '<div id="fio-qr" style="margin-bottom: 10px;"></div>';


        echo '<div id="fio-payment-desc">';

        echo '<div>';

        echo '<div class="fio-payment-desc-row">';
        echo '<label class="fio-label-for">' . __('Amount Fio:', 'woocommerce-gateway-fio') . '</label>';
        echo '<label id="fio-amount-wrapper" class="fio-label fio-amount" data-clipboard-text="' . esc_attr($fio_amount) . '">' . esc_attr($fio_amount) . '</label>';
        echo '</div>';

        echo '<div class="fio-payment-desc-row">';
        echo '<label class="fio-label-for">' . __('Address:', 'woocommerce-gateway-fio') . '</label>';
        echo '<label id="fio-address-wrapper" class="fio-label fio-address" data-clipboard-text="' . esc_attr($this->fio_address) . '">' . esc_attr($this->fio_address) . '</label>';
        echo '</div>';

        // echo '<div class="fio-payment-desc-row">';
        // echo '<label class="fio-label-for">' . __('Reference:', 'woocommerce-gateway-fio') . '</label>';
        // echo '<label id="fio-ref-wrapper" class="fio-label fio-ref" data-clipboard-text="' . esc_attr($fio_ref) . '">' . esc_attr($fio_ref) . '</label>';
        // echo '</div>';

        echo '</div>';

        echo '</div>';


        //fioProcess
        echo '<div id="fio-process"></div>';

    }

    /**
     * payment_scripts function.
     *
     * Outputs scripts used for stripe payment
     *
     * @access public
     */
    public function payment_scripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script('woocommerce_fio_qrcode', plugins_url('assets/js/qrcode' . $suffix . '.js', WC_FIO_MAIN_FILE), array( 'jquery' ), WC_FIO_VERSION, true);
        wp_enqueue_script('jquery-initialize', plugins_url('assets/js/jquery.initialize' . $suffix . '.js', WC_FIO_MAIN_FILE), array( 'jquery' ), WC_FIO_VERSION, true);
        wp_enqueue_script('clipboard', plugins_url('assets/js/clipboard' . $suffix . '.js', WC_FIO_MAIN_FILE), array( 'jquery' ), WC_FIO_VERSION, true);
        wp_enqueue_script('nanobar', plugins_url('assets/js/nanobar' . $suffix . '.js', WC_FIO_MAIN_FILE), array( 'jquery' ), WC_FIO_VERSION, true);
        wp_enqueue_script('woocommerce_fio_js', plugins_url('assets/js/fio-checkout' . $suffix . '.js', WC_FIO_MAIN_FILE), array(
            'jquery',
            'woocommerce_fio_qrcode',
            'jquery-initialize',
            'clipboard',
            'nanobar'
        ), WC_FIO_VERSION, true);
        wp_enqueue_style('woocommerce_fio_css', plugins_url('assets/css/fio-checkout.css', WC_FIO_MAIN_FILE), array(), WC_FIO_VERSION);


        //Add js variables
        $fio_params = array(
            'wc_ajax_url' => WC()->ajax_url(),
            'nounce' => wp_create_nonce("woocommerce-fio"),
            'store' => get_bloginfo()
        );

        wp_localize_script('woocommerce_fio_js', 'wc_fio_params', apply_filters('wc_fio_params', $fio_params));

    }

    public function validate_fields() {
        $fio_payment = json_decode(WC()->session->get('fio_payment'));
        if ( empty($fio_payment) ) {
            wc_add_notice(__('A FIO payment has not been registered to this checkout. Please contact our support department.', 'woocommerce-gateway-fio'), 'error');
            return false;
        }
        return true;
    }

    /**
     * Process Payment.
     *
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment( $order_id ) {

        global $woocommerce;
        $order = new WC_Order($order_id);

        //Get the FIO transaction
        $fio_payment = json_decode(WC()->session->get('fio_payment'));


        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', __('Awaiting FIO payment', 'woocommerce'));
        update_post_meta($order_id, 'fio_payment_hash', $fio_payment->id);
        update_post_meta($order_id, 'fio_payment_ref', $fio_payment->action->data->memo);
        update_post_meta($order_id, 'fio_payment_amount', $fio_payment->amount);
        update_post_meta($order_id, 'fio_payment_fee', 0);
        update_post_meta($order_id, 'fio_payment_height', 0);
        update_post_meta($order_id, 'fio_payment_recipient', $fio_payment->action->data->to);

        // Reduce stock levels
        $order->reduce_order_stock();

        //Mark as paid
        $order->payment_complete();

        // Remove cart
        $woocommerce->cart->empty_cart();
        WC()->session->set('fio_payment', false);
        //Lock amount for 5 minutes
        WC()->session->set('fio_amount', false);


        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url()
        );
    }

    public function hex2str( $hex ) {
        $str = '';
        for ( $i = 0; $i < strlen($hex); $i += 2 ) $str .= chr(hexdec(substr($hex, $i, 2)));
        return $str;
    }

    /**
     * Get fio amount to pay
     *
     * @param float $total Amount due.
     * @param string $currency Accepted currency.
     *
     * @return float|int
     */
    public function get_fio_amount( $total, $currency = '' ) {
        if ( !$currency ) {
            $currency = get_woocommerce_currency();
        }
        /*Todo: Add filter for supported currencys. Also, could add to tri-exchange if currency outside polo currency*/
        $supported_currencys = array();

        switch ( strtoupper($currency) ) {
            // Zero decimal currencies.
            case 'BIF' :
            case 'CLP' :
            case 'DJF' :
            case 'GNF' :
            case 'JPY' :
            case 'KMF' :
            case 'KRW' :
            case 'MGA' :
            case 'PYG' :
            case 'RWF' :
            case 'VND' :
            case 'VUV' :
            case 'XAF' :
            case 'XOF' :
            case 'XPF' :
                $total = absint($total);
                break;
            default :
                $total = round($total, 2) * 100; // In cents.
                break;
        }

        return $total;
    }

    /**
     * Init settings for gateways.
     */
    public function init_settings() {
        parent::init_settings();
        $this->enabled = !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = include('wc-gateway-fio-settings.php');

        wc_enqueue_js("
			jQuery( function( $ ) {
				
			});
		");
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {
        if ( 'yes' === $this->enabled && $this->fio_address ) {
            return true;
        }

        return false;
    }

}
