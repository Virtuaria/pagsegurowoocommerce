jQuery(document).ready(function($) {
    $(document).on("click", "#place_order", async function (e) {
        let is_credit_separated = $('.payment_method_virt_pagseguro_credit input[name="payment_method"]:checked').length == 1;
        let is_credit_unified   = $('#virt-pagseguro-payment #credit-card:checked').length == 1;
        if ( ! is_credit_separated && ! is_credit_unified ) {
            return true;
        }

        if ( $(this).attr('virt_pagseguro_3ds_authorized') === 'yes' ) {
            console.log( 'PagSeguro: payment already authorized' );
            $(this).removeAttr('virt_pagseguro_3ds_authorized');
            return true;
        } else {
            e.preventDefault();
        }
        
        if ( ! auth_3ds.session && 'yes' === auth_3ds.allow_sell ) {
           return true;
        }
       
        PagSeguro.setUp({
            session: auth_3ds.session,
            env: auth_3ds.environment,
        });

        if ( $('#virt_pagseguro_encrypted_card').length == 0 && ! auth_3ds.card_id ) {
            alert('PagSeguro: Cartão inválido!');
            return false;
        }
       
        var checkoutFormData = $('form.woocommerce-checkout').serializeArray();
        // Convert the form data to an object
        var checkoutFormDataObj = {};
        $.each(checkoutFormData, function(i, field) {
            checkoutFormDataObj[field.name] = field.value;
        });
     
        let request = {
            data: {
                customer: {
                    name: checkoutFormDataObj['billing_first_name'] + ' ' + checkoutFormDataObj['billing_last_name'],
                    email: checkoutFormDataObj['billing_email'],
                    phones: [
                        {
                            country: '55',
                            area: checkoutFormDataObj['billing_phone'].replace(/\D/g, '').substring(0, 2),
                            number: checkoutFormDataObj['billing_phone'].replace(/\D/g, '').substring(2),
                            type: 'MOBILE'
                        }
                    ]
                },
                paymentMethod: {
                    type: 'CREDIT_CARD',
                    installments: $('#virt-pagseguro-card-installments').val(),
                    card: {
                    }
                },
                amount: {
                    value: auth_3ds.order_total,
                    currency: 'BRL'
                },
                billingAddress: {
                    street: checkoutFormDataObj['billing_address_1'].replace(/\s+/g, ' '),
                    number: checkoutFormDataObj['billing_number'].replace(/\s+/g, ' '),
                    complement: checkoutFormDataObj['billing_neighborhood'].replace(/\s+/g, ' '),
                    regionCode: checkoutFormDataObj['billing_state'].replace(/\s+/g, ' '),
                    country: 'BRA',
                    city: checkoutFormDataObj['billing_city'].replace(/\s+/g, ' '),
                    postalCode: checkoutFormDataObj['billing_postcode'].replace(/\D+/g, '')
                },
                dataOnly: false
            }
        }

        if ( $('#virt_pagseguro_encrypted_card').length > 0
            && '' != $('#virt_pagseguro_encrypted_card').val() ) {
            request.data.paymentMethod.card.encrypted = $('#virt_pagseguro_encrypted_card').val();
        } else {
            request.data.paymentMethod.card.id = auth_3ds.card_id;
        }
        
        $('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table').block({
            message: 'Processando Autenticação 3DS, por favor aguarde...', 
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            },
            css: {border: 0}
        });
        
        PagSeguro.authenticate3DS(request).then( result => {
            console.log('PagBank: ' + result);
            switch (result.status) {
                case 'CHANGE_PAYMENT_METHOD':
                    alert('Pagamento negado pelo PagBank. Escolha outro método de pagamento ou cartão.');
                    return false;
                case 'AUTH_FLOW_COMPLETED':
                    if (result.authenticationStatus === 'AUTHENTICATED') {
                        $('#virt_pagseguro_auth_3ds').val(result.id);
                        console.log('PagBank: 3DS Autenticado ou Sem desafio');
                        $('#place_order').attr('virt_pagseguro_3ds_authorized', 'yes');
                        $('#place_order').attr('disabled', false);
                        $('#place_order').trigger('click');
                        return true;
                    }
                    alert( 'PagSeguro: Não foi possível autenticar o cartão. Tente novamente.' );
                    return false;
                case 'AUTH_NOT_SUPPORTED':
                    if (auth_3ds.allow_sell === 'yes') {
                        console.log('PagBank: 3DS não suportado pelo cartão. Continuando sem 3DS.');
                        $('#place_order').attr('virt_pagseguro_3ds_authorized', 'yes');
                        $('#place_order').attr('disabled', false);
                        $('#place_order').trigger('click');
                        return true;
                    }
                    alert('Seu cartão não suporta autenticação 3D. Escolha outro método de pagamento ou cartão.');
                    return false;
                case 'REQUIRE_CHALLENGE':
                    console.log('PagBank: REQUIRE_CHALLENGE - O desafio está sendo exibido pelo banco.');
                    break;
            }
        }).catch((err) => {
            if(err instanceof PagSeguro.PagSeguroError ) {
                console.log('PagBank: ' + err.detail);
                return false;
            }
        }).finally(() => {
            $('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table').unblock();  
        })
        
        return false;
    });
});