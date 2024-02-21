<?php
/**
 * Handle block to payment with ticket card.
 *
 * @package Virtuaria_PagSeguro/Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register ticket card block.
 */
final class Virtuaria_PagSeguro_Ticket_Block extends Virtuaria_PagSeguro_Abstract_Block {
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'virt_pagseguro_ticket';
}
