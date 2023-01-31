<?php
/**
 * Handle API Pagseguro.
 *
 * @package virtuaria.
 * @since 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition.
 */
class WC_PagSeguro_API {
	/**
	 * Instance from gateway.
	 *
	 * @var WC_Virtuaria_PagSeguro_Gateway
	 */
	private $gateway;

	/**
	 * Endpoint to API.
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * Initialize class.
	 *
	 * @param WC_Pagseguro_Virt_Gateway $gateway the instance from gateway.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;

		if ( 'sandbox' === $this->gateway->environment ) {
			$this->endpoint = 'https://sandbox.api.pagseguro.com/';
		} else {
			$this->endpoint = 'https://api.pagseguro.com/';
		}

		$this->tag      = $this->gateway->id;
		$this->debug_on = 'yes' === $this->gateway->get_option( 'debug' );
	}

	/**
	 * Create new charge.
	 *
	 * @param wc_order $order  the order.
	 * @param array    $posted the data to charge.
	 */
	public function new_charge( $order, $posted ) {
		if ( ( 'credit' === $posted['payment_mode'] && $this->gateway->fee_from > intval( $posted['pagseguro_installments'] ) )
			|| 'ticket' === $posted['payment_mode'] ) {
			$total = $order->get_total();
		} else {
			$total = $this->gateway->get_installment_value(
				$order->get_total(),
				intval( $posted['pagseguro_installments'] )
			);
		}
		$total = number_format( $total, 2, '', '' );

		$data = array(
			'headers' => array(
				'Authorization' => $this->gateway->token,
				'Content-Type'  => 'application/json',
			),
			'body'    => array(
				'reference_id'      => strval( $order->get_id() ),
				'description'       => substr( get_bloginfo( 'name' ), 0, 63 ),
				'amount'            => array(
					'value'    => intval( $total ),
					'currency' => 'BRL',
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
				'payment_method'    => array(
					'type' => 'credit' === $posted['payment_mode'] ? 'CREDIT_CARD' : 'BOLETO',
				),
			),
			'timeout' => 120,
		);

		if ( 'CREDIT_CARD' === $data['body']['payment_method']['type'] ) {
			$validate_card = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $posted['pagseguro_card_validate'] ) ) );
			$exp_month     = substr( $validate_card, 0, 2 );
			$exp_year      = substr( $validate_card, 2 );

			$data['body']['payment_method']['installments']    = intval( $posted['pagseguro_installments'] );
			$data['body']['payment_method']['capture']         = true;
			$data['body']['payment_method']['soft_descriptor'] = $this->gateway->soft_descriptor;

			if ( is_user_logged_in() ) {
				$pagseguro_card_info = get_user_meta( get_current_user_id(), '_pagseguro_credit_info', true );
			}
			if ( isset( $pagseguro_card_info['token'] ) && ! $posted['pagseguro_use_other_card'] && $posted['pagseguro_save_hash_card'] ) {
				$data['body']['payment_method']['card']['id'] = $pagseguro_card_info['token'];
			} else {
				if ( isset( $posted['pagseguro_encrypted_card'] ) && ! empty( $posted['pagseguro_encrypted_card'] ) ) {
					$data['body']['payment_method']['card'] = array(
						'encrypted' => sanitize_text_field( wp_unslash( $posted['pagseguro_encrypted_card'] ) ),
					);
				} else {
					$data['body']['payment_method']['card'] = array(
						'number'        => preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $posted['pagseguro_card_number'] ) ) ),
						'exp_month'     => $exp_month,
						'exp_year'      => $exp_year,
						'security_code' => preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $posted['pagseguro_card_cvc'] ) ) ),
						'holder'        => array(
							'name' => sanitize_text_field( wp_unslash( $posted['pagseguro_holder_name'] ) ),
						),
					);
				}

				if ( $posted['pagseguro_save_hash_card'] ) {
					$data['body']['payment_method']['card']['store'] = true;
				}
			}
		} else {
			$data['body']['payment_method']['boleto'] = array(
				'due_date' => wp_date( 'Y-m-d', strtotime( '+' . intval( $this->gateway->tiket_validate ) . ' day' ) ),
				'holder'   => array(
					'name'    => $order->get_formatted_billing_full_name(),
					'tax_id'  => preg_replace( '/\D/', '', $order->get_meta( '_billing_cpf' ) ),
					'email'   => $order->get_billing_email(),
					'address' => array(
						'street'      => $order->get_billing_address_1(),
						'number'      => $order->get_meta( '_billing_number' ),
						'complement'  => $order->get_billing_address_2(),
						'locality'    => $order->get_meta( '_billing_neighborhood' ),
						'city'        => $order->get_billing_city(),
						'region'      => $order->get_billing_state(),
						'region_code' => $order->get_billing_state(),
						'country'     => $order->get_billing_country(),
						'postal_code' => preg_replace( '/\D/', '', $order->get_billing_postcode() ),
					),
				),
			);
		}

		if ( $this->debug_on ) {
			$to_log = $data;
			if ( 'CREDIT_CARD' === $data['body']['payment_method']['type'] && isset( $data['body']['payment_method']['card']['number'] ) ) {
				$to_log['body']['payment_method']['card']['number']        = preg_replace( '/\d/', 'x', $to_log['body']['payment_method']['card']['number'] );
				$to_log['body']['payment_method']['card']['security_code'] = preg_replace( '/\d/', 'x', $to_log['body']['payment_method']['card']['security_code'] );
			}
			$this->gateway->log->add( $this->tag, 'Enviando novo pedido: ' . wp_json_encode( $to_log ), WC_Log_Levels::INFO );
		}

		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'charges',
			$data
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					'Erro ao criar pedido: ' . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
			return array( 'error' => $request->get_error_message() );
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Resposta do servidor ao tentar criar novo pedido: ' . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$response  = json_decode( wp_remote_retrieve_body( $request ), true );
		$resp_code = intval( wp_remote_retrieve_response_code( $request ) );
		if ( 201 !== $resp_code ) {
			if ( 401 === $resp_code ) {
				return array( 'error' => 'Pagamento não autorizado.' );
			} elseif ( in_array( $resp_code, array( 400, 409 ), true ) ) {
				$msg = $response['error_messages'][0]['description'];
				if ( 'invalid_parameter' === $response['error_messages'][0]['description'] ) {
					$msg = 'Verifique os dados digitados e tente novamente.';
				}
				return array( 'error' => $msg );
			} else {
				return array( 'error' => 'Não foi possível processar a sua compra. Por favor, tente novamente mais tarde.' );
			}
		}

		if ( 'PAID' === $response['status'] ) {
			update_post_meta( $order->get_id(), '_charge_amount', $response['amount']['value'] );
			if ( 'CREDIT_CARD' === $response['payment_method']['type'] ) {
				update_post_meta( $order->get_id(), '_payment_mode', 'CREDIT_CARD' );
				$order->add_order_note(
					sprintf(
						'Pago com %s<br>Parcelas: %dx<br>Total: R$ %s',
						strtoupper( $response['payment_method']['card']['brand'] ),
						$response['payment_method']['installments'],
						number_format( $response['amount']['value'] / 100, 2, ',', '.' )
					)
				);
				if ( isset( $response['payment_method']['card']['id'] ) ) {
					$month = str_pad( sanitize_text_field( wp_unslash( $response['payment_method']['card']['exp_month'] ) ), 2, '0', STR_PAD_LEFT );
					$year  = sanitize_text_field( wp_unslash( $response['payment_method']['card']['exp_year'] ) );
					update_user_meta(
						$order->get_customer_id(),
						'_pagseguro_credit_info',
						array(
							'token'      => sanitize_text_field( wp_unslash( $response['payment_method']['card']['id'] ) ),
							'name'       => sanitize_text_field( wp_unslash( $response['payment_method']['card']['holder']['name'] ) ),
							'card_last'  => sanitize_text_field( wp_unslash( $response['payment_method']['card']['last_digits'] ) ),
							'card_brand' => sanitize_text_field( wp_unslash( $response['payment_method']['card']['brand'] ) ),
							'validate'   => $month . '/' . $year,
						)
					);
				}
				$order->save();
			}
		} elseif ( 'DECLINED' === $response['status'] ) {
			return array( 'error' => 'Não autorizado, ' . $response['payment_response']['message'] . '.' );
		} elseif ( 'WAITING' === $response['status'] && 'BOLETO' === $response['payment_method']['type'] ) {
			update_post_meta( $order->get_id(), '_payment_mode', 'BOLETO' );
			update_post_meta( $order->get_id(), '_formatted_barcode', $response['payment_method']['boleto']['formatted_barcode'] );
			update_post_meta( $order->get_id(), '_pdf_link', $response['links'][0]['href'] );
			$order->add_order_note(
				sprintf(
					'R$ %s no Boleto Bancário',
					number_format( $response['amount']['value'] / 100, 2, ',', '.' )
				)
			);
			$order->save();
		}

		update_post_meta( $order->get_id(), '_charge_id', $response['id'] );

		return 'PAID' === $response['status'];
	}

	/**
	 * Do refund order.
	 *
	 * @param int   $order_id the order id.
	 * @param float $amount   the refund amount.
	 */
	public function refund_order( $order_id, $amount ) {
		$charge = get_post_meta( $order_id, '_charge_id', true );
		if ( ! $charge ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					'Charge code not found',
					WC_Log_Levels::ERROR
				);
			}
			return;
		}
		$data = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->gateway->token,
				'Content-Type'  => 'application/json',
				'x-api-version' => '4.0',
			),
			'body'    => array(
				'amount' => array(
					'value' => preg_replace( '/\D/', '', $amount ),
				),
			),
			'timeout' => 120,
		);

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Reembolso para o pedido ' . $order_id . ' ' . wp_json_encode( $data ) . $this->endpoint . 'charges/' . $charge . '/cancel',
				WC_Log_Levels::INFO
			);
		}

		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'charges/' . $charge . '/cancel',
			$data
		);

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Resposta do reembolso: ' . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		if ( 201 === wp_remote_retrieve_response_code( $request ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get public key using client token.
	 */
	public function get_public_key() {
		$request = wp_remote_get(
			$this->endpoint . 'public-keys/card',
			array(
				'headers' => array(
					'Authorization' => $this->gateway->token,
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					'Falha ao obter chave pública: ' . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Resposta do servidor ao tentar obter chave pública: ' . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $request ) )->public_key;
	}
}
