<?php
/**
 * Google Gemini Provider Implementation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Provider_Gemini implements APC_AI_Provider_Interface {
	private $api_key;
	private $model;
	private $temperature;
	private $max_tokens;
	private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models';

	public function __construct() {
		$this->api_key = get_option( 'apc_provider_gemini_api_key' );
		$this->model = get_option( 'apc_provider_gemini_model', 'gemini-2.5-flash' );
		$this->temperature = get_option( 'apc_provider_gemini_temperature', 1.0 );
		$this->max_tokens = get_option( 'apc_provider_gemini_max_tokens', 500 );
	}

	public function get_name() {
		return __( 'Google Gemini', 'apc-free' );
	}

	public function get_id() {
		return 'gemini';
	}

	public function get_models() {
		return array(
			'gemini-2.5-flash-lite'  => 'Gemini 2.5 Flash-Lite (Fastest)',
			'gemini-2.5-flash'       => 'Gemini 2.5 Flash (Recommended)',
			'gemini-2.5-pro'         => 'Gemini 2.5 Pro (Advanced)',
			'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro (Most Capable)',
		);
	}

	public function validate_credentials() {
		return $this->test_connection();
	}

	public function test_connection() {
		if ( empty( $this->api_key ) ) {
			return false;
		}

		$url = $this->api_base . '/' . $this->model . ':generateContent?key=' . urlencode( $this->api_key );

		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'contents' => array(
					array(
						'parts' => array(
							array(
								'text' => 'ping',
							),
						),
					),
				),
			) ),
			'timeout' => 10,
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status = wp_remote_retrieve_response_code( $response );
		return 200 === $status;
	}

	public function get_response( $messages, $system_prompt = null ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'Gemini API key is not configured', 'apc-free' ) );
		}

		$url = $this->api_base . '/' . $this->model . ':generateContent?key=' . urlencode( $this->api_key );

		// Convert messages to Gemini format
		$contents = array();

		if ( $system_prompt ) {
			$contents[] = array(
				'role'  => 'user',
				'parts' => array(
					array(
						'text' => '[System: ' . $system_prompt . ']',
					),
				),
			);
		}

		foreach ( $messages as $message ) {
			$contents[] = array(
				'role'  => 'user' === $message['role'] ? 'user' : 'model',
				'parts' => array(
					array(
						'text' => $message['content'],
					),
				),
			);
		}

		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'generationConfig' => array(
					'temperature'     => $this->temperature,
					'maxOutputTokens' => $this->max_tokens,
				),
				'contents'         => $contents,
			) ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return new WP_Error( 'api_error', __( 'Unexpected API response', 'apc-free' ), $body );
		}

		return array(
			'content' => $body['candidates'][0]['content']['parts'][0]['text'],
			'role'    => 'assistant',
			'tokens'  => $body['usageMetadata']['totalTokenCount'] ?? 0,
		);
	}

	public function get_config_fields() {
		return array(
			array(
				'id'      => 'apc_provider_gemini_api_key',
				'label'   => __( 'Gemini API Key', 'apc-free' ),
				'type'    => 'password',
				'default' => '',
				'help'    => __( 'Get your key at makersuite.google.com/app/apikey', 'apc-free' ),
			),
			array(
				'id'      => 'apc_provider_gemini_model',
				'label'   => __( 'Model', 'apc-free' ),
				'type'    => 'select',
				'default' => 'gemini-2.5-flash',
				'options' => $this->get_models(),
			),
			array(
				'id'      => 'apc_provider_gemini_temperature',
				'label'   => __( 'Temperature', 'apc-free' ),
				'type'    => 'number',
				'default' => 0.9,
				'min'     => 0,
				'max'     => 2,
				'step'    => 0.1,
				'help'    => __( '0-2: Lower = focused, Higher = creative', 'apc-free' ),
			),
			array(
				'id'      => 'apc_provider_gemini_max_tokens',
				'label'   => __( 'Max Tokens', 'apc-free' ),
				'type'    => 'number',
				'default' => 500,
				'min'     => 1,
				'max'     => 2048,
				'help'    => __( 'Maximum response length', 'apc-free' ),
			),
		);
	}
}
