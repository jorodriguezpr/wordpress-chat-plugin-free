<?php
/**
 * AI Provider Interface - Base for all AI providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface APC_AI_Provider_Interface {
	/**
	 * Get provider name
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Get provider ID
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Validate API credentials
	 *
	 * @return bool|WP_Error
	 */
	public function validate_credentials();

	/**
	 * Send message and get response
	 *
	 * @param array $messages Conversation history
	 * @param string $system_prompt System instructions
	 * @return array|WP_Error
	 */
	public function get_response( $messages, $system_prompt = null );

	/**
	 * Get available models for this provider
	 *
	 * @return array
	 */
	public function get_models();

	/**
	 * Get provider configuration fields
	 *
	 * @return array
	 */
	public function get_config_fields();

	/**
	 * Test API connection
	 *
	 * @return bool
	 */
	public function test_connection();
}
