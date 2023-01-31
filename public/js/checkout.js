jQuery(document).ready(function ($) {
	displayPaymentMethod();
	$(document).on("click", "#pagseguro-payment-methods li label", function (e) {
		$("#pagseguro-payment-methods li").removeClass("active");
		$(this).parent().addClass("active");
		$('#pagseguro-payment-methods input[name="payment_mode"]').removeAttr(
			"checked"
		);
		$(this).find('input[name="payment_mode"]').prop("checked", true);
		displayPaymentMethod();
	});

	$(document).on("updated_checkout", function () {
		displayPaymentMethod();
	});

	$(document).on("click", "#pagseguro-use-other-card", function () {
		if ($(this).prop("checked")) {
			$("#pagseguro-credit-card-form .form-row").removeClass("card-loaded");
			$(".card-in-use").hide("fast");
			$("#pagseguro-payment").removeClass("card-loaded");
		} else {
			$("#pagseguro-credit-card-form .form-row").addClass("card-loaded");
			$(".card-in-use").show("fast");
			$("#pagseguro-card-installments-field").removeClass("card-loaded");
			$("#pagseguro-payment").addClass("card-loaded");
		}
	});

	$(document).on("keyup", "#pagseguro-card-expiry", function () {
		var v = $(this).val().replace(/\D/g, "");

		v = v.replace(/(\d{2})(\d)/, "$1 / $2");

		$(this).val(v);
	});

	$(document).on("click", "#place_order", function () {
		if (
			encriptation &&
			$("#credit-card").prop("checked") &&
			!$("#pagseguro-card-number-field").hasClass("card-loaded")
		) {
			var expire = $("#pagseguro-card-expiry").val().split(" / ");
			var card = PagSeguro.encryptCard({
				publicKey: encriptation.pub_key,
				holder: $("#pagseguro-card-holder-name").val(),
				number: $("#pagseguro-card-number").val().replace(/ /g, ""),
				expMonth: expire[0],
				expYear: expire[1],
				securityCode: $("#pagseguro-card-cvc").val(),
			});
			$("#pagseguro_encrypted_card").val(card.encryptedCard);
		}
	});
});

function displayPaymentMethod() {
	jQuery(".pagseguro-method-form").hide();
	var method = jQuery(
		'#pagseguro-payment-methods input[name="payment_mode"]:checked'
	).val();

	var active_id = jQuery(
		'#pagseguro-payment-methods input[name="payment_mode"]:checked'
	).attr('id');
	if ( ! method ) {
		jQuery( '#pagseguro-payment-methods li:first-child' ).addClass('active');
		jQuery( '#pagseguro-payment-methods li:first-child input[type="radio"]').prop('checked', true);
		method    = jQuery( '#pagseguro-payment-methods li:first-child input[type="radio"]').val();
		active_id = jQuery( '#pagseguro-payment-methods li:first-child input[type="radio"]').attr('id');
	}

	jQuery( '#pagseguro-payment-methods li' ).removeClass('active');
	jQuery( '#pagseguro-payment-methods #' + active_id ).parent().parent().addClass('active');
	if (method == "credit") {
		jQuery("#pagseguro-credit-card-form").show();
	} else if (method == "ticket") {
		jQuery("#pagseguro-banking-ticket-form").show();
	} else {
		jQuery("#pagseguro-banking-pix-form").show();
	}
}
