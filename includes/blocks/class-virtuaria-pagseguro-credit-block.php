<?php
/**
 * Handle block to payment with credit card.
 *
 * @package Virtuaria_PagSeguro/Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register credit card block.
 */
final class Virtuaria_PagSeguro_Credit_Block extends Virtuaria_PagSeguro_Abstract_Block {
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'virt_pagseguro_credit';
}
