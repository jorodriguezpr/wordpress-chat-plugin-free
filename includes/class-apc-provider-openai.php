<?php
/**
 * OpenAI Provider Implementation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Provider_OpenAI implements APC_AI_Provider_Interface {
	private $api_key;
	private $model;
	private $temperature;
	private $max_tokens;
	private $api_base = 'https://api.openai.com/v1/chat/completions';

	public function __construct() {
		$this->api_key = get_option( 'apc_provider_openai_api_key' );
		$this->model = get_option( 'apc_provider_openai_model', 'gpt-5.4' );
		$this->temperature = get_option( 'apc_provider_openai_temperature', 1.0 );
		$this->max_tokens = get_option( 'apc_provider_openai_max_tokens', 500 );
	}

	public function get_name() {
		return __( 'OpenAI (ChatGPT)', 'apc-free' );
	}

	public function get_id() {
		return 'openai';
	}

	public function get_models() {
		return array(
			'gpt-5.4'       => 'GPT-5.4 (Most Capable)',
			'gpt-5.4-mini'  => 'GPT-5.4 Mini (Fast)',
			'gpt-5.4-nano'  => 'GPT-5.4 Nano (Fastest)',
			'gpt-4o'        => 'GPT-4o (Previous Generation)',
			'gpt-4o-mini'   => 'GPT-4o Mini (Affordable)',
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
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 10,
		);

		$response = wp_remote_get( 'https://api.openai.com/v1/models', $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status = wp_remote_retrieve_response_code( $response );
		return 200 === $status;
	}

	public function get_response( $messages, $system_prompt = null ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'OpenAI API key is not configured', 'apc-free' ) );
		}

		if ( $system_prompt ) {
			array_unshift( $messages, array(
				'role'    => 'system',
				'content' => $system_prompt,
			) );
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'       => $this->model,
				'messages'    => $messages,
				'temperature' => $this->temperature,
				'max_tokens'  => $this->max_tokens,
			) ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $this->api_base, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'api_error', __( 'Unexpected API response', 'apc-free' ), $body );
		}

		return array(
			'content' => $body['choices'][0]['message']['content'],
			'role'    => 'assistant',
			'tokens'  => $body['usage']['total_tokens'] ?? 0,
		);
	}

	public function get_config_fields() {
		return array(
			array(
				'id'      => 'apc_provider_openai_api_key',
				'label'   => __( 'OpenAI API Key', 'apc-free' ),
				'type'    => 'password',
				'default' => '',
				'help'    => __( 'Get your key at platform.openai.com/api-keys', 'apc-free' ),
			),
			array(
				'id'      => 'apc_provider_openai_model',
				'label'   => __( 'Model', 'apc-free' ),
				'type'    => 'select',
				'default' => 'gpt-5.4',
				'options' => $this->get_models(),
			),
			array(
				'id'      => 'apc_provider_openai_temperature',
				'label'   => __( 'Temperature', 'apc-free' ),
				'type'    => 'number',
				'default' => 1.0,
				'min'     => 0,
				'max'     => 2,
				'step'    => 0.1,
				'help'    => __( '0-2: Lower = focused, Higher = creative', 'apc-free' ),
			),
			array(
				'id'      => 'apc_provider_openai_max_tokens',
				'label'   => __( 'Max Tokens', 'apc-free' ),
				'type'    => 'number',
				'default' => 500,
				'min'     => 1,
				'max'     => 4000,
				'help'    => __( 'Maximum response length', 'apc-free' ),
			),
		);
	}
}
