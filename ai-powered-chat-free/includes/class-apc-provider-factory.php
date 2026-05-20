<?php
/**
 * AI Provider Factory - Manages provider instantiation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Provider_Factory {
	private static $providers = array();
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->register_providers();
		}
		return self::$instance;
	}

	/**
	 * Register available providers
	 */
	private function register_providers() {
		self::$providers = array(
			'openai'        => 'APC_Provider_OpenAI',
			'claude'        => 'APC_Provider_Claude',
			'gemini'        => 'APC_Provider_Gemini',
			'github-models' => 'APC_Provider_GitHub_Models',
		);
	}

	/**
	 * Get provider instance
	 *
	 * @param string $provider_id
	 * @return APC_AI_Provider_Interface|WP_Error
	 */
	public function get_provider( $provider_id = null ) {
		if ( is_null( $provider_id ) ) {
			$provider_id = get_option( 'apc_active_provider', 'openai' );
		}

		if ( ! isset( self::$providers[ $provider_id ] ) ) {
			return new WP_Error( 'invalid_provider', __( 'Invalid AI provider', 'apc-free' ) );
		}

		$class = self::$providers[ $provider_id ];

		if ( ! class_exists( $class ) ) {
			return new WP_Error( 'provider_not_found', __( 'Provider class not found', 'apc-free' ) );
		}

		return new $class();
	}

	/**
	 * Get all registered providers
	 *
	 * @return array
	 */
	public function get_providers() {
		$providers = array();

		foreach ( self::$providers as $id => $class ) {
			if ( class_exists( $class ) ) {
				$provider = new $class();
				$providers[ $id ] = array(
					'id'   => $id,
					'name' => $provider->get_name(),
					'class' => $provider,
				);
			}
		}

		return $providers;
	}

	/**
	 * Get provider by ID
	 *
	 * @param string $provider_id
	 * @return APC_AI_Provider_Interface|false
	 */
	public function get_provider_by_id( $provider_id ) {
		$provider = $this->get_provider( $provider_id );
		return is_wp_error( $provider ) ? false : $provider;
	}

	/**
	 * Test all providers
	 *
	 * @return array
	 */
	public function test_all_providers() {
		$results = array();

		foreach ( self::$providers as $id => $class ) {
			if ( class_exists( $class ) ) {
				$provider = new $class();
				$results[ $id ] = array(
					'name'      => $provider->get_name(),
					'connected' => $provider->test_connection(),
				);
			}
		}

		return $results;
	}
}
