(function(){
	// Imports
	const { __ }  = wp.i18n;
	const { decodeEntities }  = wp.htmlEntities;
	const { getSetting }  = wc.wcSettings;
	const { registerPaymentMethod }  = wc.wcBlocksRegistry;
	const { RawHTML, createElement, useEffect } = wp.element;

	let settings = getSetting( 'virt_pagseguro_data', {} );
	if ( Object.keys( settings ).length === 0 ) {
		settings = getSetting( 'virt_pagseguro_credit_data', {} );
		if ( Object.keys( settings ).length === 0 ) {
			settings = getSetting( 'virt_pagseguro_pix_data', {} );
			if ( Object.keys( settings ).length === 0 ) {
				settings = getSetting( 'virt_pagseguro_ticket_data', {} );
			}
		}
	}

	const defaultLabel = __(
		'Virtuaria PagSeguro',
		'virtuaria-pagseguro'
	);

	const label = decodeEntities( settings.title ) || defaultLabel;

	const Content = ( props ) => {
		const { eventRegistration, emitResponse } = props;
		const { onPaymentProcessing, onPaymentSetup } = eventRegistration;
		useEffect( () => {
			const unsubscribe = onPaymentSetup( async () => {
				// Here we can do any processing we need, and then emit a response.
				// For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
				let pagbankData = {
					'is_block': 'yes',
				};

				if ( document.getElementsByName('payment_mode').length > 0 ) {
					pagbankData.payment_mode = document.getElementsByName('payment_mode')[0].value;
				}

				if ( document.getElementsByName('virt_pagseguro_credit_nonce').length > 0 ) {
					pagbankData.virt_pagseguro_credit_nonce = document.getElementsByName('virt_pagseguro_credit_nonce')[0].value;
				}
				if ( document.getElementsByName('virt_pagseguro_pix_nonce').length > 0 ) {
					pagbankData.virt_pagseguro_pix_nonce = document.getElementsByName('virt_pagseguro_pix_nonce')[0].value;
				}
				if ( document.getElementsByName('virt_pagseguro_ticket_nonce').length > 0 ) {
					pagbankData.virt_pagseguro_ticket_nonce = document.getElementsByName('virt_pagseguro_ticket_nonce')[0].value;
				}
				if ( document.getElementsByName('new_charge_nonce').length > 0 ) {
					pagbankData.new_charge_nonce = document.getElementsByName('new_charge_nonce')[0].value;
				}

				if ( document.getElementsByName('virt_pagseguro_encrypted_card').length > 0 ) {
					pagbankData.virt_pagseguro_encrypted_card = document.getElementsByName('virt_pagseguro_encrypted_card')[0].value;
				}
				if ( document.getElementsByName('virt_pagseguro_save_hash_card').length > 0 ) {
					pagbankData.virt_pagseguro_save_hash_card = document.getElementsByName('virt_pagseguro_save_hash_card')[0].value;
				}

				let user_other_card = document.getElementsByName('virt_pagseguro_use_other_card');
				if ( user_other_card.length > 0 && user_other_card[0].checked == true ) {
					pagbankData.virt_pagseguro_use_other_card = user_other_card[0].value;
				}

				if ( document.getElementsByName('virt_pagseguro_installments').length > 0 ) {
					pagbankData.virt_pagseguro_installments = document.getElementsByName('virt_pagseguro_installments')[0].value;
				}
				if ( document.getElementsByName('virt_pagseguro_card_cvc').length > 0 ) {
					pagbankData.virt_pagseguro_card_cvc = document.getElementsByName('virt_pagseguro_card_cvc')[0].value;
				}
				if ( document.getElementsByName('virt_pagseguro_card_validate').length > 0 ) {
					pagbankData.virt_pagseguro_card_validate = document.getElementsByName('virt_pagseguro_card_validate')[0].value;
				}
				if ( document.getElementsByName('virt_pagseguro_card_number').length > 0 ) {
					pagbankData.virt_pagseguro_card_number = document.getElementsByName('virt_pagseguro_card_number')[0].value;
				}
				if ( document.getElementsByName('virt_pagseguro_card_holder_name').length > 0 ) {
					pagbankData.virt_pagseguro_card_holder_name = document.getElementsByName('virt_pagseguro_card_holder_name')[0].value;
				}

				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: pagbankData,
					},
				};
	
				// return {
				// 	type: emitResponse.responseTypes.ERROR,
				// 	message: 'There was an error',
				// };
			} );
			// Unsubscribes when this component is unmounted.
			return () => {
				unsubscribe();
			};
		}, [
			emitResponse.responseTypes.ERROR,
			emitResponse.responseTypes.SUCCESS,
			onPaymentProcessing,
		] );
		return RawHTML( {
			children: settings.content
		});
	};

	/**
	 * Virtuaria payment method config object.
	 */
	const Virtuaria = {
		name: settings.method_id,
		label: label,
		content: Object( createElement )( Content ),
		edit: RawHTML( {
			children: settings.content
		}),
		canMakePayment: () => true,
		ariaLabel: label,
		placeOrderButtonLabel: __('Pague com PagBank', 'virtuaria-pagseguro'),
		supports: {
			features: settings.supports || ['products'],
			activePaymentMethod: settings.method_id
		},
	};

	registerPaymentMethod( Virtuaria );
})();