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

    $(document).on('click', '#place_order', function() {
        if ( encriptation && $('#credit-card').prop('checked') && ! $('#pagseguro-card-number-field').hasClass('card-loaded') ) {
            var expire = $('#pagseguro-card-expiry').val().split(' / ');
            var card = PagSeguro.encryptCard({
                publicKey: encriptation.pub_key,
                holder: $('#pagseguro-card-holder-name').val(),
                number: $('#pagseguro-card-number').val().replace(/ /g, ''),
                expMonth: expire[0],
                expYear: expire[1],
                securityCode: $('#pagseguro-card-cvc').val()
            });
            $('#pagseguro_encrypted_card').val(card.encryptedCard);
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