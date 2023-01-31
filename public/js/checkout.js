jQuery(document).ready(function($){
    displayPaymentMethod();
    $(document).on('click', '#pagseguro-payment-methods li label', function(e) {
        $('#pagseguro-payment-methods li').removeClass('active');
        $(this).parent().addClass('active');
        $('#pagseguro-payment-methods input[name="payment_mode"]').removeAttr('checked');
        $(this).find('input[name="payment_mode"]').prop('checked', true);
        displayPaymentMethod();
    });

    $(document).on('updated_checkout', function() {
        displayPaymentMethod();
    });

    $(document).on('click', '#pagseguro-use-other-card', function(){
		if ( $(this).prop('checked') ) {
			$('#pagseguro-credit-card-form .form-row').removeClass('card-loaded');
			$('.card-in-use').hide('fast');
		} else {
			$('#pagseguro-credit-card-form .form-row').addClass('card-loaded');
			$('.card-in-use').show('fast');
			$('#pagseguro-card-installments-field').removeClass('card-loaded');
		}
	});
});

function displayPaymentMethod() {
    jQuery('.pagseguro-method-form').hide();
    if ( jQuery('#pagseguro-payment-methods input[name="payment_mode"]:checked').val() != 'ticket' ) {
        jQuery('#pagseguro-credit-card-form').show();
    } else {
        jQuery('#pagseguro-banking-ticket-form').show();
    }
}