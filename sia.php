<?php

/* SIA Payment Gateway Class */

class SIA extends WC_Payment_Gateway {

    // Setup our Gateway's id, description and other values
    function __construct() {

        // The global ID for this Payment method
        $this->id = "sia";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __("SIA", 'sia');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __("SIA Payment Gateway Plug-in for WooCommerce", 'sia');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __("SIA", 'sia');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = SIA_PLUGIN_URL . 'images/migs_icon.jpg';

        // Bool. Can be set to true if you want payment fields to show on the checkout 
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = false;

        // Supports the default credit card form
        //$this->supports = array('default_credit_card_form');
        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'sia_response_handler'));
        // Lets check for SSL
        //add_action('admin_notices', array($this, 'do_ssl_check'));
        // Save settings
        if (is_admin()) {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
        add_action('woocommerce_receipt_sia', array($this, 'sia_receipt_page'));
        //add_action('woocommerce_thankyou_migs', array($this, 'migs_response_handler'));
    }

// End __construct()
    // Build the administration fields for this specific Gateway
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'sia'),
                'label' => __('Enable this payment gateway', 'sia'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'sia'),
                'type' => 'text',
                'desc_tip' => __('Payment title the customer will see during the checkout process.', 'sia'),
                'default' => __('Credit Card', 'sia'),
            ),
            'description' => array(
                'title' => __('Description', 'sia'),
                'type' => 'textarea',
                'desc_tip' => __('Payment description the customer will see during the checkout process.', 'sia'),
                'default' => __('Pay securely using your master card.', 'sia'),
                'css' => 'max-width:350px;'
            ),
            'merchant_id' => array(
                'title' => __('SIA Merchant ID', 'sia'),
                'type' => 'text',
                'desc_tip' => __('This is the Merchant ID when you signed up for an account.', 'sia'),
            ),
            'mac_key' => array(
                'title' => __('SIA MAC Key', 'sia'),
                'type' => 'text',
                'desc_tip' => __('This is MAC Key when you signed up for an account.', 'sia'),
            ),
            'environment' => array(
                'title' => __('SIA Test Mode', 'sia'),
                'label' => __('Enable Test Mode', 'sia'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in test mode.', 'sia'),
                'default' => 'yes',
            )
        );
    }

    //Submit payment and handle response
    public function process_payment($order_id) {
        global $woocommerce;

        //Get this Order's information so that we know
        //who to charge and how much
        $customer_order = new WC_Order($order_id);


        // Redirect to thank you page
        return array(
            'result' => 'success',
            'redirect' => $customer_order->get_checkout_payment_url(true)
        );

        //,
    }

    // Validate fields
    public function validate_fields() {
        return true;
    }

    public function do_ssl_check() {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    public function get_exact_amount($amount) {
        return($amount * 100);
    }

    public function sia_receipt_page($order_id) {
        global $woocommerce;
        $customer_order = new WC_Order($order_id);

        $notify_url = add_query_arg('wc-api', get_class($this), site_url());
        $back_url = $customer_order->get_checkout_payment_url(true);
        $success_url = $this->get_return_url($customer_order);
        $string = "URLMS=" . $notify_url . "&URLDONE=" . $success_url . "&NUMORD=" . $order_id . "&IDNEGOZIO=" . $this->merchant_id . "&IMPORTO=" . $this->get_exact_amount($customer_order->order_total) . "&VALUTA=978&TCONTAB=I&TAUTOR=I&" . $this->mac_key . "";

        $mac = hash("MD5", $string);

        if ($this->environment == 'yes'):
            $url = 'http://atpostest.ssb.it/atpos/pagamenti/main?PAGE=MASTER';
        else:
            $url = 'https://atpos.ssb.it/atpos/pagamenti/main?PAGE=MASTER';
        endif;


        $payment_form = '<form id="sia_frm" action="' . $url . '" method="post">';
        $payment_form .= '<input type="hidden" name="URLMS" value="' . $notify_url . '">';
        $payment_form .= '<input type="hidden" name="URLDONE" value="' . $success_url . '">';
        $payment_form .= '<input type="hidden" name="URLBACK" value="' . $back_url . '">';
        $payment_form .= '<input type="hidden" name="NUMORD" value="' . $order_id . '">';
        $payment_form .= '<input type="hidden" name="IDNEGOZIO" value="' . $this->merchant_id . '">';
        $payment_form .= '<input type="hidden" name="IMPORTO" value="' . $this->get_exact_amount($customer_order->order_total) . '">';
        $payment_form .= '<input type="hidden" name="VALUTA" value="978">';
        $payment_form .= '<input type="hidden" name="TCONTAB" value="I">';
        $payment_form .= ' <input type="hidden" name="TAUTOR" value="I">';
        $payment_form .= '<label><input type="checkbox" name="sia_terms_cond" required="true" /></label><a href="">Terms & conditions</a>';
        $payment_form .= ' <input type="hidden" name="MAC" value="' . $mac . '">';
        $payment_form .= '<input type="submit" name="sia_btn_submit" value="Pay" />';
        $payment_form .= '</form>';

        /* $script = '<script type="text/javascript"> 
          jQuery(document).ready(function(){ jQuery("#migs_frm").on("submit", function(e){e.preventDefault(); var URL = jQuery("#migs_frm").prop("action"); window.location.href = URL; })});
          </script>'; */

        echo $payment_form;
    }

    public function sia_response_handler() {
        global $woocommerce;
        $response = $_REQUEST;

        $order_id = $response['NUMORD'];
        $transaction_id = $response['IDTRANS'];
        $customer_order = new WC_Order($order_id);



        if ($transaction_id) {

            $customer_order->add_order_note(__('SIA payment completed.', 'sia'));

            // Mark order as Paid
            $customer_order->payment_complete();

            // Empty the cart (Very important step)
            $woocommerce->cart->empty_cart();

            // Redirect to thank you page
            wp_redirect($this->get_return_url($customer_order));
            exit;
        } else {
            wc_add_notice('Message: error', 'error');
            // Add note to the order for your reference
            $customer_order->add_order_note('Error: error');
            wp_redirect($customer_order->get_checkout_payment_url(true));
            exit;
        }
    }

    



}
