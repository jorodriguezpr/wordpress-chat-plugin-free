<?php
/**
 * Anthropic Claude Provider Implementation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Provider_Claude implements APC_AI_Provider_Interface {
	private $api_key;
	private $model;
	private $temperature;
	private $max_tokens;
	private $api_base = 'https://api.anthropic.com/v1/messages';

	public function __construct() {
		$this->api_key = get_option( 'apc_provider_claude_api_key' );
		$this->model = get_option( 'apc_provider_claude_model', 'claude-sonnet-4-6' );
		$this->temperature = get_option( 'apc_provider_claude_temperature', 1.0 );
		$this->max_tokens = get_option( 'apc_provider_claude_max_tokens', 500 );
	}

	public function get_name() {
		return __( 'Anthropic Claude', 'apc-free' );
	}

	public function get_id() {
		return 'claude';
	}

	public function get_models() {
		return array(
			'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Fastest)',
			'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (Recommended)',
			'claude-opus-4-6'           => 'Claude Opus 4.6 (Most Capable)',
		);
	}

	public function validate_credentials() {
		return $this->test_connection();
	}

	public function test_connection() {
		if ( empty( $this->api_key ) ) {
			return false;
		}

		$args = array(
			'headers' => array(
				'x-api-key'       => $this->api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type'    => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'       => $this->model,
				'max_tokens'  => 100,
				'messages'    => array(
					array(
						'role'    => 'user',
						'content' => 'Hello',
					),
				),
			) ),
			'timeout' => 10,
		);

		$response = wp_remote_post( $this->api_base, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status = wp_remote_retrieve_response_code( $response );
		return 200 === $status;
	}

	public function get_response( $messages, $system_prompt = null ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'Claude API key is not configured', 'apc-free' ) );
		}

		$args = array(
			'headers' => array(
				'x-api-key'       => $this->api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type'    => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'       => $this->model,
				'max_tokens'  => $this->max_tokens,
				'temperature' => $this->temperature,
				'system'      => $system_prompt ?: __( 'You are a helpful assistant.', 'apc-free' ),
				'messages'    => $messages,
			) ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $this->api_base, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['content'][0]['text'] ) ) {
			return new WP_Error( 'api_error', __( 'Unexpected API response', 'apc-free' ), $body );
		}

		return array(
			'content' => $body['content'][0]['text'],
			'role'    => 'assistant',
			'tokens'  => $body['usage']['output_tokens'] ?? 0,
		);
	}

	public function get_config_fields() {
		return array(
			array(
				'id'      => 'apc_provider_claude_api_key',
				'label'   => __( 'Claude API Key', 'apc-free' ),
				'type'    => 'password',
				'default' => '',
				'help'    => __( 'Get your key at console.anthropic.com', 'apc-free' ),
			),
			array(
				'id'      => 'apc_provider_claude_model',
				'label'   => __( 'Model', 'apc-free' ),
				'type'    => 'select',
				'default' => 'claude-sonnet-4-6',
				'options' => $this->get_models(),
			),
			array(
				'id'      => 'apc_provider_claude_temperature',
				'label'   => __( 'Temperature', 'apc-free' ),
				'type'    => 'number',
				'default' => 1.0,
				'min'     => 0,
				'max'     => 1,
				'step'    => 0.1,
				'help'    => __( '0-1: Lower = focused, Higher = creative', 'apc-free' ),
			),
			array(
				'id'      => 'apc_provider_claude_max_tokens',
				'label'   => __( 'Max Tokens', 'apc-free' ),
				'type'    => 'number',
				'default' => 500,
				'min'     => 1,
				'max'     => 4096,
				'help'    => __( 'Maximum response length', 'apc-free' ),
			),
		);
	}
}
