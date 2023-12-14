jQuery(document).ready(function ($) {
	displayPaymentMethod();
	$(document).on("click", "#virt-pagseguro-payment-methods li label", function (e) {
		$("#virt-pagseguro-payment-methods li").removeClass("active");
		$(this).parent().addClass("active");
		$('#virt-pagseguro-payment-methods input[name="payment_mode"]').removeAttr(
			"checked"
		);
		$(this).find('input[name="payment_mode"]').prop("checked", true);
		displayPaymentMethod();
	});

	$(document).on("updated_checkout", function () {
		displayPaymentMethod();
	});

	$(document).on("click", "#virt-pagseguro-use-other-card", function () {
		if ($(this).prop("checked")) {
			$("#virt-pagseguro-credit-card-form .form-row").removeClass("card-loaded");
			$(".card-in-use").hide("fast");
			$("#pagseguro-payment-virt_pagseguro_credit").removeClass("card-loaded");
		} else {
			$("#virt-pagseguro-credit-card-form .form-row").addClass("card-loaded");
			$(".card-in-use").show("fast");
			$("#virt-pagseguro-card-installments-field").removeClass("card-loaded");
			$("#pagseguro-payment-virt_pagseguro_credit").addClass("card-loaded");
		}
	});

	$(document).on("keyup", "#virt-pagseguro-card-expiry", function () {
		var v = $(this).val().replace(/\D/g, "");

		v = v.replace(/(\d{2})(\d)/, "$1 / $2");

		$(this).val(v);
	});

	$(document).on("click", "#place_order", function () {
		if (
			encriptation
			&& ( $("#credit-card").prop("checked")
				|| ( is_separated ) && $('#payment_method_virt_pagseguro_credit').prop('checked') )
			&& !$("#virt-pagseguro-card-number-field").hasClass("card-loaded")
		) {
			var expire = $("#virt-pagseguro-card-expiry").val().split(" / ");
			var card = PagSeguro.encryptCard({
				publicKey: encriptation.pub_key,
				holder: $("#virt-pagseguro-card-holder-name").val(),
				number: $("#virt-pagseguro-card-number").val().replace(/ /g, ""),
				expMonth: expire[0],
				expYear: expire[1],
				securityCode: $("#virt-pagseguro-card-cvc").val(),
			});
			$("#virt_pagseguro_encrypted_card").val(card.encryptedCard);
		}
	});

	$(document).on('focusout', '#virt-pagseguro-card-expiry', function() {
		if ( $(this).val().length == 7 ) {
			var v = $(this).val().replace(/\D/g, "");

			let century = new Date().getFullYear().toString().substring(0, 2);
			v = v.replace(/(\d{2})(\d)/, "$1 / " + century + "$2");

			$(this).val(v);
		}
	});

	$(document).on('keyup', '#virt-pagseguro-card-number', function() {
		if ( $(this).val().length > 0 ) {
			let flag = getCardFlag( $(this).val() );

			if ( flag ) {
				$(this).removeClass();
				$(this).addClass('input-text');
				$(this).addClass('wc-credit-card-form-card-number');
				$(this).addClass( flag );
			} else {
				$(this).removeClass();
				$(this).addClass('input-text');
				$(this).addClass('wc-credit-card-form-card-number');
			}
		}
	});

	$(document).on("click", "#place_order", function () {
		if ( $('#payment_method_virt_pagseguro:checked').length > 0
			&& $('#virt-pagseguro-payment .payment-methods input[name="payment_mode"]:checked').length == 0
			&& $('#virt-pagseguro-payment #virt-pagseguro-payment-methods input[name="payment_mode"]:checked').length == 0) {
			alert('PagSeguro: Selecione um método de pagamento!');
			return false;
		}
	});
});

function displayPaymentMethod() {
	jQuery(".virt-pagseguro-method-form").hide();
	var method = jQuery(
		'#virt-pagseguro-payment-methods input[name="payment_mode"]:checked'
	).val();

	var active_id = jQuery(
		'#virt-pagseguro-payment-methods input[name="payment_mode"]:checked'
	).attr('id');
	if ( ! method ) {
		jQuery( '#virt-pagseguro-payment-methods li:first-child' ).addClass('active');
		jQuery( '#virt-pagseguro-payment-methods li:first-child input[type="radio"]').prop('checked', true);
		method    = jQuery( '#virt-pagseguro-payment-methods li:first-child input[type="radio"]').val();
		active_id = jQuery( '#virt-pagseguro-payment-methods li:first-child input[type="radio"]').attr('id');
	}

	jQuery( '#virt-pagseguro-payment-methods li' ).removeClass('active');
	jQuery( '#virt-pagseguro-payment-methods #' + active_id ).parent().parent().addClass('active');
	if (method == "credit") {
		jQuery("#virt-pagseguro-credit-card-form").show();
	} else if (method == "ticket") {
		jQuery("#virt-pagseguro-banking-ticket-form").show();
	} else if (method == 'pix'){
		jQuery("#virt-pagseguro-banking-pix-form").show();
	}
}

cards = [
    {
      type: 'maestro',
      patterns: [5018, 502, 503, 506, 56, 58, 639, 6220, 67]
    },
	{
      type: 'forbrugsforeningen',
      patterns: [600]
    }, {
      type: 'dankort',
      patterns: [5019]
    },
	{
      type: 'visa',
      patterns: [4]
    },
	{
      type: 'mastercard',
      patterns: [51, 52, 53, 54, 55, 22, 23, 24, 25, 26, 27]
    },
	{
      type: 'amex',
      patterns: [34, 37]
    },
	{
      type: 'dinersclub',
      patterns: [30, 36, 38, 39]
    },
	{
      type: 'discover',
      patterns: [60, 64, 65, 622]
    },
	{
      type: 'unionpay',
      patterns: [62, 88]
    },
	{
      type: 'jcb',
      patterns: [35]
    }
];

getCardFlag = function(num) {
    var card, p, pattern, _i, _j, _len, _len1, _ref;
    num = (num + '').replace(/\D/g, '');
    for (_i = 0, _len = cards.length; _i < _len; _i++) {
      card = cards[_i];
      _ref = card.patterns;
      for (_j = 0, _len1 = _ref.length; _j < _len1; _j++) {
        pattern = _ref[_j];
        p = pattern + '';
        if (num.substr(0, p.length) === p) {
          return card.type;
        }
      }
    }
};
