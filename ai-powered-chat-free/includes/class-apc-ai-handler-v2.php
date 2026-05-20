<?php
/**
 * Updated AI Handler - Now uses provider system
 *
 * @package    AI_Powered_Chat
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_AI_Handler {
	private $provider;
	private $factory;

	public function __construct() {
		try {
			$this->factory = APC_Provider_Factory::get_instance();
			$this->provider = $this->factory->get_provider();

			if ( is_wp_error( $this->provider ) ) {
				$this->provider = null;
			}
		} catch ( Exception $e ) {
			// Handle gracefully
			$this->factory = null;
			$this->provider = null;
		}
	}

	/**
	 * Get response from current provider
	 *
	 * @param array $messages Conversation history
	 * @param string $system_prompt System instructions
	 * @return array|WP_Error
	 */
	public function get_response( $messages, $system_prompt = null ) {
		if ( ! $this->provider ) {
			return new WP_Error( 'no_provider', __( 'No AI provider configured', 'apc-free' ) );
		}

		return $this->provider->get_response( $messages, $system_prompt );
	}

	/**
	 * Get current provider name
	 *
	 * @return string
	 */
	public function get_provider_name() {
		if ( ! $this->provider ) {
			return __( 'Unknown', 'apc-free' );
		}
		return $this->provider->get_name();
	}

	/**
	 * Get current provider ID
	 *
	 * @return string
	 */
	public function get_provider_id() {
		if ( ! $this->provider ) {
			return '';
		}
		return $this->provider->get_id();
	}

	/**
	 * Switch provider
	 *
	 * @param string $provider_id
	 * @return bool|WP_Error
	 */
	public function switch_provider( $provider_id ) {
		$provider = $this->factory->get_provider( $provider_id );

		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		update_option( 'apc_active_provider', $provider_id );
		$this->provider = $provider;

		return true;
	}

	/**
	 * Get all available providers
	 *
	 * @return array
	 */
	public function get_available_providers() {
		return $this->factory->get_providers();
	}

	/**
	 * Test current provider
	 *
	 * @return bool
	 */
	public function test_provider_connection() {
		if ( ! $this->provider ) {
			return false;
		}
		return $this->provider->test_connection();
	}

	/**
	 * Get provider's available models
	 *
	 * @return array
	 */
	public function get_models() {
		if ( ! $this->provider ) {
			return array();
		}
		return $this->provider->get_models();
	}

	/**
	 * Get provider config fields
	 *
	 * @return array
	 */
	public function get_config_fields() {
		if ( ! $this->provider ) {
			return array();
		}
		return $this->provider->get_config_fields();
	}

	/**
	 * Validate provider credentials
	 *
	 * @return bool|WP_Error
	 */
	public function validate_credentials() {
		if ( ! $this->provider ) {
			return new WP_Error( 'no_provider', __( 'No provider selected', 'apc-free' ) );
		}
		return $this->provider->validate_credentials();
	}

	/**
	 * Get all provider status
	 *
	 * @return array
	 */
	public function get_all_providers_status() {
		return $this->factory->test_all_providers();
	}
}
