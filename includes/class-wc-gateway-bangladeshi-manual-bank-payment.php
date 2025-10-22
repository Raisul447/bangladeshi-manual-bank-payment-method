<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * WC_Gateway_Manual_Bangladeshi_Bank_Payment Class
 * @extends WC_Payment_Gateway
 */
if ( ! class_exists( 'WC_Gateway_Manual_Bangladeshi_Bank_Payment' ) ) {
    class WC_Gateway_Manual_Bangladeshi_Bank_Payment extends WC_Payment_Gateway {

        // PHP 8+: Declare all custom properties used in the class
        public $instructions;
        public $account_name;
        public $account_number;
        public $account_holder;
        public $branch_name;
        public $routing_number;
        public $payment_description; 
        public $transaction_id_placeholder; 

        /**
         * Constructor
         */
        public function __construct() {
            // GATEWAY ID CHANGED to prevent conflict
            $this->id                 = 'manual_bangladeshi_bank_payment';
            $this->icon               = $this->get_bank_logo_url();
            $this->has_fields         = true; 
            // Text Domain Updated
            $this->method_title       = __( 'Bangladeshi Manual Bank Payment Method', 'bangladeshi-manual-bank-payment-method' );
            $this->method_description = __( 'Accept manually payments via direct bank transfer from customers using Bangladeshi Bank.', 'bangladeshi-manual-bank-payment-method' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Get settings values
            $this->title                      = $this->get_option( 'title' );
            $this->description                = $this->get_option( 'description' );
            $this->instructions               = $this->get_option( 'instructions' );
            $this->account_name               = $this->get_option( 'account_name' );
            $this->account_number             = $this->get_option( 'account_number' );
            $this->account_holder             = $this->get_option( 'account_holder' );
            $this->branch_name                = $this->get_option( 'branch_name' );
            $this->routing_number             = $this->get_option( 'routing_number' );
            $this->payment_description        = $this->get_option( 'payment_description' );
            $this->transaction_id_placeholder = $this->get_option( 'transaction_id_placeholder' );
            
            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            // Add transaction ID to admin order details
            add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_transaction_id_admin_order' ) );
        }
        
        /**
         * Returns the bank logo URL (Default provided)
         */
        private function get_bank_logo_url() {
            $logo_url = $this->get_option( 'bank_logo_url' );
            if ( $logo_url ) {
                return $logo_url;
            }
            return plugin_dir_url( dirname( __FILE__ ) ) . 'assets/bank-logo.png';
        }

        /**
         * Admin Panel Options
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'bangladeshi-manual-bank-payment-method' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Bangladeshi Manual Bank Payment', 'bangladeshi-manual-bank-payment-method' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'description' => __( 'It will display as a title which the user sees during checkout.', 'bangladeshi-manual-bank-payment-method' ),
                    'default'     => __( 'Bangladeshi Manual Bank Payment', 'bangladeshi-manual-bank-payment-method' ),
                    'desc_tip'    => true,
                    'custom_attributes' => array(
                    'rows' => 4,
                    'cols' => 50,
                    ),
                ),
                
                'bank_settings_title' => array(
                    'title'       => __( 'Bank Account Details', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'title',
                    'description' => __( 'Enter the details of the bank account where customers should send payments.', 'bangladeshi-manual-bank-payment-method' ),
                ),
                'account_name' => array(
                    'title'       => __( 'Bank Account Name', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'default'     => 'Example Bank PLC',
                    'placeholder' => 'Name of the Bank',
                    'desc_tip'    => true,
                ),
                'account_number' => array(
                    'title'       => __( 'Account Number', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'default'     => '1234567891011',
                ),
                'account_holder' => array(
                    'title'       => __( 'Account Holder Name', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'default'     => 'Your Account Name',
                ),
                'branch_name' => array(
                    'title'       => __( 'Branch Name', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'default'     => 'Dhaka Main Branch',
                ),
                'routing_number' => array(
                    'title'       => __( 'Routing Number', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'default'     => '987654321',
                ),
                
                // Transaction ID Placeholder
                'transaction_id_placeholder' => array(
                    'title'       => __( 'Transaction ID Placeholder', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'description' => __( 'The text displayed inside the Transaction ID input field.', 'bangladeshi-manual-bank-payment-method' ),
                    'default'     => __( 'Enter your transaction/reference number', 'bangladeshi-manual-bank-payment-method' ),
                    'desc_tip'    => true,
                ),
                
                'bank_logo_url' => array(
                    'title'       => __( 'Bank Logo URL', 'bangladeshi-manual-bank-payment-method' ),
                    'type'        => 'text',
                    'description' => __( 'Enter the full URL of the bank logo (e.g., https://domain.com/asstes/bank_logo.png) for the icon on the checkout page. The image should be small (e.g., 45x30px) for best display.', 'bangladeshi-manual-bank-payment-method' ),
                    'desc_tip'    => true, 
                    'default'     => '',
                    'custom_attributes' => array(
                    'style' => 'width: 400px; max-width: 100%;', 
                    ),
                ),
            );
        }
        
        /**
         * Bank Details display on Checkout Page (Transaction ID Input Only)
         */
        public function payment_fields() {
            
            $output = '<div id="bm_gwp-payment-details-wrapper">';    
            $output .= '<div id="bm_gwp-payment-details-inner" class="bm_gwp-details-box">';
            $output .= '<p class="bm_gwp-bank-name"><strong>' . esc_html( $this->account_name ) . '</strong></p>';
            $output .= '<ul class="bm_gwp-account-list">';
            $output .= '<li class="bm_gwp-list-item"><strong>' . esc_html( __( 'Account Number:', 'bangladeshi-manual-bank-payment-method' ) ) . '</strong> <span>' . esc_html( $this->account_number ) . '</span></li>';
            $output .= '<li class="bm_gwp-list-item"><strong>' . esc_html( __( 'Account Holder Name:', 'bangladeshi-manual-bank-payment-method' ) ) . '</strong> <span>' . esc_html( $this->account_holder ) . '</span></li>';
            $output .= '<li class="bm_gwp-list-item"><strong>' . esc_html( __( 'Branch Name:', 'bangladeshi-manual-bank-payment-method' ) ) . '</strong> <span>' . esc_html( $this->branch_name ) . '</span></li>';
            $output .= '<li class="bm_gwp-list-item"><strong>' . esc_html( __( 'Routing Number:', 'bangladeshi-manual-bank-payment-method' ) ) . '</strong> <span>' . esc_html( $this->routing_number ) . '</span></li>';
            $output .= '</ul>';
            
            $output .= '</div>'; // End of bm_gwp-payment-details-inner

            // Transaction ID Input Field (Required)
            $output .= '<div class="form-row bm_gwp-transaction-id-field">';
            $field_id = 'bm_gwp-transaction-id-input';
            $output .= '<label for="' . esc_attr( $field_id ) . '">' . esc_html( __( 'Transaction ID (Required)', 'bangladeshi-manual-bank-payment-method' ) ) . ' <span class="required">*</span></label>';
            
            $placeholder = esc_attr( $this->transaction_id_placeholder );

            // Input Field Name
            $output .= '<input id="' . esc_attr( $field_id ) . '" class="input-text bm_gwp-input-field" type="text" name="bm_gwp_transaction_id" placeholder="' . $placeholder . '" required />';
            
            // Nonce is now added outside the $output string (below echo wp_kses)

            $output .= '</div>';
            
            $output .= '<div class="bm_gwp-help-text">' . esc_html( __( 'Please pay the total amount through NPSB and provide the Transaction ID to confirm your order.', 'bangladeshi-manual-bank-payment-method' ) ) . '</div>';

            $output .= '</div>'; // End of wrapper

            // Allowed tags to ensure form fields work and output is escaped.
            $allowed_html = array(
                'div' => array(
                    'id' => array(),
                    'class' => array(),
                ),
                'p' => array(
                    'class' => array(),
                ),
                'strong' => array(),
                'ul' => array(
                    'class' => array(),
                ),
                'li' => array(
                    'class' => array(),
                ),
                'span' => array(
                    'class' => array(),
                ),
                'label' => array(
                    'for' => array(),
                    'class' => array(),
                ),
                'input' => array(
                    'id' => array(),
                    'class' => array(),
                    'type' => array(),
                    'name' => array(),
                    'placeholder' => array(),
                    'required' => array(),
                    'value' => array(), 
                ),
                'a' => array(
                    'href' => array(),
                ),
            );
            
            $allowed_html = array_merge( $allowed_html, wp_kses_allowed_html( 'post' ) );

            echo wp_kses( $output, $allowed_html );

            // Echo the nonce field directly to ensure it renders correctly for the AJAX request.
            wp_nonce_field( 'bm_gwp_process_payment', 'bm_gwp_process_payment_nonce' ); 
        }

        /**
         * Process the payment and save the transaction ID.
         */
        public function process_payment( $order_id ) {
            
            // Nonce Field and Name Checked
            if ( empty( $_POST['bm_gwp_process_payment_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bm_gwp_process_payment_nonce'] ), 'bm_gwp_process_payment' ) ) {
                // Returns fail result for AJAX
                wc_add_notice( __( 'Security check failed. Please try again.', 'bangladeshi-manual-bank-payment-method' ), 'error' );
                return array( 'result' => 'fail' );
            }
            
            $order = wc_get_order( $order_id );
            
            // Transaction ID POST
            if ( empty( $_POST['bm_gwp_transaction_id'] ) ) {
                // translators: Error message shown when the transaction ID field is empty.
                $error_message = __( 'Please enter the Transaction ID to place your order.', 'bangladeshi-manual-bank-payment-method' );
                wc_add_notice( $error_message, 'error' ); 
                return array( 'result' => 'fail' );
            }
            
            // Transaction ID POST Name
            $transaction_id = sanitize_text_field( wp_unslash( $_POST['bm_gwp_transaction_id'] ) );
            // Meta Data Key
            $order->update_meta_data( '_bm_gwp_transaction_id', $transaction_id );
            
            // Order Status: On Hold
            $order->payment_complete();
            
            // Placing the translators comment immediately above the sprintf call (now line 269 or near).
            // translators: %s: Transaction ID provided by the customer.
            $note = sprintf( __( 'Awaiting manual confirmation of bank transfer with Transaction ID: %s', 'bangladeshi-manual-bank-payment-method' ), $transaction_id );
            
            $order->update_status( 'on-hold', $note );
            
            $order->save();
            
            WC()->cart->empty_cart();
            
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
        
        public function thankyou_page( $order_id ) {
            // No custom thank you logic needed for simple transaction ID
        }
        
        /**
         * Display Transaction ID in Dashboard Order Details
         */
        public function display_transaction_id_admin_order( $order ) {
            // Meta Data Key
            $transaction_id = $order->get_meta( '_bm_gwp_transaction_id', true );
            
            if ( $transaction_id ) {
                echo '<div class="bm_gwp-admin-transaction-id-box">';
                echo '<h3>' . esc_html( __( 'Manual Bank Payment Details', 'bangladeshi-manual-bank-payment-method' ) ) . '</h3>';
                
                if ( $transaction_id ) {
                    echo '<p><strong>' . esc_html( __( 'Transaction ID:', 'bangladeshi-manual-bank-payment-method' ) ) . '</strong> ' . esc_html( $transaction_id ) . '</p>';
                }
                
                echo '</div>';
            }
        }
    }
}