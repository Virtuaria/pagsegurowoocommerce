jQuery(document).ready(function ($) {
	$(document).on("click", "#place_order", function () {
		if ( $('#payment_method_virt_pagseguro:checked').length > 0
			&& $('#pagseguro-payment .payment-methods input[name="payment_mode"]:checked').length == 0) {
			alert('PagSeguro: Selecione um método de pagamento!');
			return false;
		}
	});

    $(document).on('click', '#pagseguro-payment .payment-option', function(e) {
		let is_selected = $(this).parent().hasClass('selected');
		e.preventDefault();
		$('#pagseguro-payment .payment-method').removeClass('selected');
		$('#pagseguro-payment .payment-method input[name="payment_mode"]').prop('checked', false);

		if ( ! is_selected ) {
			$(this).parent().toggleClass('selected');
			$(this).find('input[type="radio"]').prop('checked', true);
		}
    });
});
