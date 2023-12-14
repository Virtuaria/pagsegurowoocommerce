jQuery(document).ready(function ($) {
    $(document).on('click', '#virt-pagseguro-payment .payment-option', function(e) {
		let is_selected = $(this).parent().hasClass('selected');
		e.preventDefault();
		$('#virt-pagseguro-payment .payment-method').removeClass('selected');
		$('#virt-pagseguro-payment .payment-method input[name="payment_mode"]').prop('checked', false);

		if ( ! is_selected ) {
			$(this).parent().toggleClass('selected');
			$(this).find('input[type="radio"]').prop('checked', true);
		}
    });
});
