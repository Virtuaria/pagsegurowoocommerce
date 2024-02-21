<?php
/**
 * Handle block to payment with pix card.
 *
 * @package Virtuaria_PagSeguro/Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register pix card block.
 */
final class Virtuaria_PagSeguro_Pix_Block extends Virtuaria_PagSeguro_Abstract_Block {
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'virt_pagseguro_pix';
}
