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
class WC_Virtuaria_PagSeguro_API {
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
	 * Timetou to comunication with API.
	 *
	 * @var int
	 */
	private const TIMEOUT = 25;

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
		if ( 'credit' === $posted['payment_mode']
			&& $this->gateway->fee_from <= intval( $posted['pagseguro_installments'] ) ) {
			$total = $this->gateway->get_installment_value(
				$order->get_total(),
				intval( $posted['pagseguro_installments'] )
			);
		} else {
			$total = $order->get_total();
		}
		$total = number_format( $total, 2, '', '' );

		$phone = $order->get_billing_phone();
		$phone = explode( ' ', $phone );

		$data = array(
			'headers' => array(
				'Authorization' => $this->gateway->token,
				'Content-Type'  => 'application/json',
			),
			'body'    => array(
				'reference_id'      => strval( $order->get_id() ),
				'customer'          => array(
					'name'   => $order->get_formatted_billing_full_name(),
					'email'  => $order->get_billing_email(),
					'tax_id' => preg_replace( '/\D/', '', $order->get_meta( '_billing_cpf' ) ),
					'phone'  => array(
						'country' => '55',
						'area'    => preg_replace( '/\D/', '', $phone[0] ),
						'number'  => preg_replace( '/\D/', '', $phone[1] ),
						'type'    => 'CELLPHONE',
					),
				),
				'items'             => array(),
				'shipping'          => array(
					'address' => array(
						'street'      => $order->get_billing_address_1(),
						'number'      => $order->get_meta( '_billing_number' ),
						'complement'  => $order->get_billing_address_2(),
						'locality'    => $order->get_meta( '_billing_neighborhood' ),
						'city'        => $order->get_billing_city(),
						'region_code' => $order->get_billing_state(),
						'country'     => 'BRA',
						'postal_code' => preg_replace( '/\D/', '', $order->get_billing_postcode() ),
					),
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
			),
			'timeout' => self::TIMEOUT,
		);

		if ( ! $order->get_billing_address_2() ) {
			unset( $data['body']['shipping']['address']['complement'] );
		}

		if ( ! $order->get_meta( '_billing_neighborhood' ) ) {
			return array( 'error' => __( 'o campo <b>Bairro</b> é obrigatório!', 'virtuaria-pagseguro' ) );
		}

		if ( ! $order->get_meta( '_billing_cpf' ) || 2 == $order->get_meta( '_billing_persontype' ) ) {
			$data['body']['customer']['tax_id'] = preg_replace( '/\D/', '', $order->get_meta( '_billing_cnpj' ) );
		}

		foreach ( $order->get_items() as $item ) {
			$data['body']['items'][] = array(
				'name'        => $item->get_name(),
				'quantity'    => $item->get_quantity(),
				'unit_amount' => number_format( $item->get_total(), 2, '', '' ),
			);
		}

		if ( 'pix' === $posted['payment_mode'] ) {
			$expiration = new DateTime(
				wp_date(
					'Y-m-d H:i:s',
					strtotime( '+' . $this->gateway->pix_validate . ' seconds' )
				),
				new DateTimeZone( 'America/Sao_Paulo' )
			);

			$data['body']['qr_codes'][] = array(
				'amount'          => array(
					'value' => $total,
				),
				'expiration_date' => $expiration->format( 'c' ),
			);
		} else {
			$data['body']['charges'][] = array(
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
			);

			if ( 'CREDIT_CARD' === $data['body']['charges'][0]['payment_method']['type'] ) {
				$data['body']['charges'][0]['payment_method']['installments']    = intval( $posted['pagseguro_installments'] );
				$data['body']['charges'][0]['payment_method']['capture']         = true;
				$data['body']['charges'][0]['payment_method']['soft_descriptor'] = $this->gateway->soft_descriptor;

				if ( is_user_logged_in() ) {
					$pagseguro_card_info = get_user_meta( get_current_user_id(), '_pagseguro_credit_info_store_' . get_current_blog_id(), true );
				}

				if ( isset( $pagseguro_card_info['token'] ) && ! $posted['pagseguro_use_other_card'] && $posted['pagseguro_save_hash_card'] ) {
					$data['body']['charges'][0]['payment_method']['card']['id'] = $pagseguro_card_info['token'];
				} else {
					if ( isset( $posted['pagseguro_encrypted_card'] ) && ! empty( $posted['pagseguro_encrypted_card'] ) ) {
						$data['body']['charges'][0]['payment_method']['card'] = array(
							'encrypted' => sanitize_text_field( wp_unslash( $posted['pagseguro_encrypted_card'] ) ),
						);
					} else {
						if ( $this->debug_on ) {
							$this->gateway->log->add(
								$this->tag,
								'Não foi possível encriptar o cartão de crédito.',
								WC_Log_Levels::ERROR
							);
						}

						return array( 'error' => 'Dados do cartão inválidos, verifique os dados informados e tente novamente.' );
					}

					if ( $posted['pagseguro_save_hash_card'] ) {
						$data['body']['charges'][0]['payment_method']['card']['store'] = true;
					}
				}
			} else {
				$data['body']['charges'][0]['payment_method']['boleto'] = array(
					'due_date' => wp_date( 'Y-m-d', strtotime( '+' . intval( $this->gateway->ticket_validate ) . ' day' ) ),
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

				if ( ! $order->get_billing_address_2() ) {
					unset( $data['body']['charges'][0]['payment_method']['boleto']['holder']['address']['complement'] );
				}
			}
		}

		if ( $this->debug_on ) {
			$to_log = $data;
			if ( 'CREDIT_CARD' === $data['body']['charges'][0]['payment_method']['type'] && isset( $data['body']['charges'][0]['payment_method']['card']['number'] ) ) {
				$to_log['body']['charges'][0]['payment_method']['card']['number']        = preg_replace( '/\d/', 'x', $to_log['body']['charges'][0]['payment_method']['card']['number'] );
				$to_log['body']['charges'][0]['payment_method']['card']['security_code'] = preg_replace( '/\d/', 'x', $to_log['body']['charges'][0]['payment_method']['card']['security_code'] );
			}
			$this->gateway->log->add( $this->tag, 'Enviando novo pedido: ' . wp_json_encode( $to_log ), WC_Log_Levels::INFO );
		}

		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'orders',
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
				if ( isset( $response['error_messages'][0]['description'] )
					&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
					update_option( 'virtuaria_pagseguro_not_authorized', true );
				}
				return array( 'error' => 'Pagamento não autorizado.' );
			} elseif ( in_array( $resp_code, array( 400, 409 ), true ) ) {
				$msg = $response['error_messages'][0]['description'];
				if ( in_array( $response['error_messages'][0]['description'], array( 'invalid_parameter', 'required_parameter' ), true ) ) {
					$msg = 'Verifique os dados digitados e tente novamente.';
				}
				return array( 'error' => $msg );
			} else {
				return array( 'error' => 'Não foi possível processar a sua compra. Por favor, tente novamente mais tarde.' );
			}
		}

		if ( 'pix' !== $posted['payment_mode'] ) {
			if ( 'PAID' === $response['charges'][0]['status'] ) {
				update_post_meta( $order->get_id(), '_charge_amount', $response['charges'][0]['amount']['value'] );
				if ( 'CREDIT_CARD' === $response['charges'][0]['payment_method']['type'] ) {
					update_post_meta( $order->get_id(), '_payment_mode', 'CREDIT_CARD' );
					$order->add_order_note(
						sprintf(
							'Pago com %s<br>Parcelas: %dx<br>Total: R$ %s',
							strtoupper( $response['charges'][0]['payment_method']['card']['brand'] ),
							$response['charges'][0]['payment_method']['installments'],
							number_format( $response['charges'][0]['amount']['value'] / 100, 2, ',', '.' )
						)
					);
					if ( isset( $response['charges'][0]['payment_method']['card']['id'] ) ) {
						$month = str_pad( sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['exp_month'] ) ), 2, '0', STR_PAD_LEFT );
						$year  = sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['exp_year'] ) );
						update_user_meta(
							$order->get_customer_id(),
							'_pagseguro_credit_info_store_' . get_current_blog_id(),
							array(
								'token'      => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['id'] ) ),
								'name'       => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['holder']['name'] ) ),
								'card_last'  => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['last_digits'] ) ),
								'card_brand' => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['brand'] ) ),
								'validate'   => $month . '/' . $year,
							)
						);
					}
				}
			} elseif ( 'DECLINED' === $response['charges'][0]['status'] ) {
				return array( 'error' => 'Não autorizado, ' . $response['charges'][0]['payment_response']['message'] . '.' );
			} elseif ( 'WAITING' === $response['charges'][0]['status'] && 'BOLETO' === $response['charges'][0]['payment_method']['type'] ) {
				update_post_meta( $order->get_id(), '_payment_mode', 'BOLETO' );
				update_post_meta( $order->get_id(), '_formatted_barcode', $response['charges'][0]['payment_method']['boleto']['formatted_barcode'] );
				update_post_meta( $order->get_id(), '_pdf_link', $response['charges'][0]['links'][0]['href'] );
				$order->add_order_note(
					sprintf(
						'R$ %s no Boleto Bancário',
						number_format( $response['charges'][0]['amount']['value'] / 100, 2, ',', '.' )
					)
				);
			}
			$order->set_transaction_id( $response['id'] );
			$order->save();

			update_post_meta( $order->get_id(), '_charge_id', $response['charges'][0]['id'] );
			return 'PAID' === $response['charges'][0]['status'];
		} else {
			$order->add_meta_data(
				'_payment_mode',
				'PIX',
				true
			);
			$order->add_meta_data(
				'_pagseguro_order_id',
				$response['id'],
				true
			);

			$order->add_meta_data(
				'_pagseguro_qrcode',
				$response['qr_codes'][0]['text'],
				true
			);

			$order->add_meta_data(
				'_qrcode_id',
				$response['qr_codes'][0]['id'],
				true
			);

			$order->add_meta_data(
				'_pagseguro_qrcode_png',
				$response['qr_codes'][0]['links'][0]['href'],
				true
			);

			$order->set_transaction_id( $response['id'] );
			$order->save();
		}
		return false;
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
			'timeout' => self::TIMEOUT,
		);

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Reembolso para o pedido ' . $order_id . ' (' . $charge . ') ' . wp_json_encode( $data ),
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

		$resp_code = wp_remote_retrieve_response_code( $request );
		$response  = json_decode( $request['body'], true );
		if ( 201 === $resp_code ) {
			return true;
		} elseif ( 401 === $resp_code && isset( $response['error_messages'][0]['description'] )
			&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
			update_option( 'virtuaria_pagseguro_not_authorized', true );
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

		$resp_code = wp_remote_retrieve_response_code( $request );
		$response  = json_decode( $request['body'], true );
		if ( 201 === $resp_code ) {
			return true;
		} elseif ( 401 === $resp_code && isset( $response['error_messages'][0]['description'] )
			&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
			update_option( 'virtuaria_pagseguro_not_authorized', true );
		}

		return $response['public_key'];
	}

	/**
	 * Do additional charge to credit card.
	 *
	 * @param wc_order $order  the order.
	 * @param int      $amount the quantity from additional charge.
	 * @param string   $reason the reson from charge.
	 */
	public function additional_charge( $order, $amount, $reason ) {
		if ( $amount <= 0 ) {
			if ( $this->debug_on ) {
				$order->add_order_note( 'PagSeguro: Cobrança Adicional com valor inválido.', 0, true );
				$this->gateway->log->add( $this->tag, 'Valor inválido ou pedido não encontrado para cobrança adicional.', WC_Log_Levels::ERROR );
			}
			return;
		}

		$pagseguro_card_info = get_user_meta( $order->get_customer_id(), '_pagseguro_credit_info_store_' . get_current_blog_id(), true );

		if ( ! $pagseguro_card_info || ! isset( $pagseguro_card_info['token'] ) ) {
			if ( $this->debug_on ) {
				$order->add_order_note( 'PagSeguro: Cobrança Adicional, método de pagamento do cliente ausente.', 0, true );
				$this->gateway->log->add( $this->tag, 'Cobrança Adicional: método de pagamento do cliente ausente', WC_Log_Levels::ERROR );
			}
			return;
		}

		$phone = $order->get_billing_phone();
		$phone = explode( ' ', $phone );

		$data = array(
			'headers' => array(
				'Authorization' => $this->gateway->token,
				'Content-Type'  => 'application/json',
			),
			'body'    => array(
				'reference_id'      => strval( $order->get_id() ),
				'customer'          => array(
					'name'   => $order->get_formatted_billing_full_name(),
					'email'  => $order->get_billing_email(),
					'tax_id' => preg_replace( '/\D/', '', $order->get_meta( '_billing_cpf' ) ),
					'phone'  => array(
						'country' => '55',
						'area'    => preg_replace( '/\D/', '', $phone[0] ),
						'number'  => preg_replace( '/\D/', '', $phone[1] ),
						'type'    => 'CELLPHONE',
					),
				),
				'items'             => array(
					array(
						'name'        => 'Cobrança adicional',
						'quantity'    => 1,
						'unit_amount' => $amount,
					),
				),
				'shipping'          => array(
					'address' => array(
						'street'      => $order->get_billing_address_1(),
						'number'      => $order->get_meta( '_billing_number' ),
						'complement'  => $order->get_billing_address_2(),
						'locality'    => $order->get_meta( '_billing_neighborhood' ),
						'city'        => $order->get_billing_city(),
						'region_code' => $order->get_billing_state(),
						'country'     => 'BRA',
						'postal_code' => preg_replace( '/\D/', '', $order->get_billing_postcode() ),
					),
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
				'charges'           => array(
					array(
						'reference_id'      => strval( $order->get_id() ),
						'description'       => substr( get_bloginfo( 'name' ), 0, 63 ),
						'amount'            => array(
							'value'    => $amount,
							'currency' => 'BRL',
						),
						'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
						'payment_method'    => array(
							'type'            => 'CREDIT_CARD',
							'installments'    => 1,
							'capture'         => true,
							'soft_descriptor' => $this->soft_descriptor,
							'card'            => array(
								'id' => $pagseguro_card_info['token'],
							),
						),
					),
				),
			),
			'timeout' => self::TIMEOUT,
		);

		if ( ! $order->get_billing_address_2() ) {
			unset( $data['body']['shipping']['address']['complement'] );
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add( $this->tag, 'Enviando cobrança adicional: ' . wp_json_encode( $data ), WC_Log_Levels::INFO );
		}
		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'orders',
			$data
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					'Erro na cobrança adicional: ' . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
			$order->add_order_note( 'PagSeguro: Não foi possível criar cobrança adicional.', 0, true );
			return;
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Resposta da cobrança adicional: ' . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$response  = json_decode( wp_remote_retrieve_body( $request ), true );
		$resp_code = intval( wp_remote_retrieve_response_code( $request ) );
		$note_resp = '';
		if ( 201 !== $resp_code ) {
			if ( 401 === $resp_code ) {
				if ( isset( $response['error_messages'][0]['description'] )
					&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
					update_option( 'virtuaria_pagseguro_not_authorized', true );
				}
				$note_resp = __( 'Pagamento não autorizado.', 'virtuaria-pagseguro' );
			} elseif ( in_array( $resp_code, array( 400, 409 ), true ) ) {
				$msg = $response['error_messages'][0]['description'];
				if ( 'invalid_parameter' === $response['error_messages'][0]['description'] ) {
					$msg = 'Verifique os dados enviados e tente novamente.';
				}
				$note_resp = $msg;
			} else {
				$note_resp = __( 'Não foi possível processar a sua cobrança. Por favor, tente novamente mais tarde.', 'virtuaria-pagseguro' );
			}
		}

		if ( $note_resp ) {
			$order->add_order_note( 'PagSeguro: ' . $note_resp, 0, true );
			return;
		}

		if ( $reason ) {
			$reason = '<br>Motivo: ' . $reason . '.';
		}
		$order->add_order_note(
			'PagSeguro: Nova cobrança enviada R$' . number_format( $amount / 100, 2, ',', '.' ) . '.' . $reason,
			0,
			true
		);

		if ( 'PAID' === $response['charges'][0]['status'] ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: amount value */
					__( 'PagSeguro: Cobrança recebida R$ %s', 'virtuaria-pagseguro' ),
					number_format( $response['charges'][0]['amount']['value'] / 100, 2, ',', '.' )
				)
			);
		} elseif ( 'DECLINED' === $response['charges'][0]['status'] ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: payment response */
					__( 'PagSeguro: Não autorizado, %s.', 'virtuaria-pagseguro' ),
					$response['charges'][0]['payment_response']['message']
				)
			);
		}
	}

	/**
	 * Simulate PIX payment.
	 *
	 * @param int $qrcode_id the qrcode id.
	 * @return boolean
	 */
	public function simulate_payment( $qrcode_id ) {
		$result = wp_remote_post(
			'https://sandbox.api.pagseguro.com/pix/pay/' . $qrcode_id,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->gateway->token,
					'Content-Type'  => 'application/json',
				),
			)
		);

		$resp_code = wp_remote_retrieve_response_code( $result );
		$response  = json_decode( $result['body'], true );

		if ( $this->debug_on ) {
			if ( 200 === $resp_code ) {
				$this->gateway->log->add( $this->tag, 'Simulação de Pagamento Pix efetuada com sucesso.', WC_Log_Levels::INFO );
			} else {
				$this->gateway->log->add(
					$this->tag,
					'Simulação de pagamento Pix:' . $result['body'],
					WC_Log_Levels::ERROR
				);
			}
		}

		if ( 401 === $resp_code && isset( $response['error_messages'][0]['description'] )
			&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
			update_option( 'virtuaria_pagseguro_not_authorized', true );
		}

		return 200 === $resp_code;
	}
}
