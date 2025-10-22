jQuery(function($){
    var gw = (typeof bm_gwp_vars !== 'undefined' && bm_gwp_vars.gateway_id) ? bm_gwp_vars.gateway_id : 'manual_bangladeshi_bank_payment';
    var nonce_field_name = 'bm_gwp_process_payment_nonce'; // The nonce field name from PHP

    // When payment method is selected, show/hide payment details
    function toggleBmGwpFields() {
        var selected = $('input[name="payment_method"]:checked').val();
        if ( selected === gw ) {
            $('#bm_gwp-payment-details-wrapper').show();
        } else {
            $('#bm_gwp-payment-details-wrapper').hide();
        }
    }

    // Initial toggle on load
    toggleBmGwpFields();

    // On change of payment method (works with other gateways)
    $(document).on('change', 'input[name="payment_method"]', function(){
        toggleBmGwpFields();
    });

    // Client-side validation to prevent submission if Transaction ID is missing.
    $(document).on('checkout_place_order', function(){
        var selected = $('input[name="payment_method"]:checked').val();
        if ( selected === gw ) {
            var tx = $('#bm_gwp-transaction-id-input').val(); 
            if ( typeof tx === 'undefined' || tx.trim() === '' ) {
                // Show standard alert and prevent form submission
                window.alert(bm_gwp_vars && bm_gwp_vars.notice_text ? bm_gwp_vars.notice_text : 'Please enter the Transaction ID.');
                return false;
            }
        }
        return true;
    });

    // This is required because WooCommerce does not reliably serialize hidden custom payment gateway fields.
    $( 'body' ).on( 'checkout_form_data', function(e, data) {
        if ( $('input[name="payment_method"]:checked').val() === gw ) {
            
            // Get the nonce value from the input field name
            var nonce_value = $( 'input[name="' + nonce_field_name + '"]' ).val();
            
            // Add custom data fields to the AJAX request payload
            data.bm_gwp_transaction_id = $('#bm_gwp-transaction-id-input').val();
            
            // Force the nonce into the data, matching the name expected by PHP's $_POST
            data[nonce_field_name] = nonce_value; 
        }
    });
});