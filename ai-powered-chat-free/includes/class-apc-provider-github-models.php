<?php
/**
 * GitHub Models Provider Implementation
 * Uses GitHub Models API (Copilot Models)
 *
 * @package    AI_Powered_Chat
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Provider_GitHub_Models implements APC_AI_Provider_Interface {
	private $github_token;
	private $model;
	private $temperature;
	private $max_tokens;
	private $api_base = 'https://models.inference.ai.azure.com/chat/completions';

	public function __construct() {
		$this->github_token = get_option( 'apc_provider_github_models_token' );
		$this->model = get_option( 'apc_provider_github_models_model', 'gpt-4o' );
		$this->temperature = (float) get_option( 'apc_provider_github_models_temperature', 1.0 );
		$this->max_tokens = (int) get_option( 'apc_provider_github_models_max_tokens', 500 );
	}

	public function get_name() {
		return __( 'GitHub Copilot Models', 'apc-free' );
	}

	public function get_id() {
		return 'github-models';
	}

	public function get_models() {
		return array(
			'gpt-4o'              => 'GPT-4o (Latest, Recommended)',
			'gpt-4o-mini'         => 'GPT-4o Mini (Fast)',
			'gpt-5'               => 'GPT-5 (Most Capable)',
			'gpt-5-mini'          => 'GPT-5 Mini',
			'o1'                  => 'O1 (Reasoning)',
			'o3'                  => 'O3 (Advanced Reasoning)',
			'o3-mini'             => 'O3 Mini (Fast Reasoning)',
			'claude-opus'         => 'Claude 3 Opus',
			'claude-sonnet'       => 'Claude 3 Sonnet',
			'deepseek-r1'         => 'DeepSeek-R1 (Reasoning)',
			'grok-3'              => 'Grok-3 (xAI)',
			'grok-3-mini'         => 'Grok-3 Mini',
		);
	}

	public function validate_credentials() {
		return $this->test_connection();
	}

	public function test_connection() {
		if ( empty( $this->github_token ) ) {
			return false;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->github_token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'    => $this->model,
				'messages' => array(
					array(
						'role'    => 'user',
						'content' => 'ping',
					),
				),
				'max_tokens' => 10,
			) ),
			'timeout' => 10,
		);

		$response = wp_remote_post( $this->api_base, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status = wp_remote_retrieve_response_code( $response );
		
		// GitHub Models API returns 200 for success
		if ( 200 === $status ) {
			return true;
		}

		// Check for rate limit or auth errors
		if ( in_array( $status, array( 401, 403 ), true ) ) {
			return false;
		}

		return false;
	}

	public function get_response( $messages, $system_prompt = null ) {
		if ( empty( $this->github_token ) ) {
			return new WP_Error( 'missing_token', __( 'GitHub token is not configured', 'apc-free' ) );
		}

		if ( $system_prompt ) {
			array_unshift( $messages, array(
				'role'    => 'system',
				'content' => $system_prompt,
			) );
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->github_token,
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

		$status = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'GitHub Models API Status: ' . $status );
			error_log( 'GitHub Models API Response: ' . print_r( $body, true ) );
		}

		// Handle authentication errors
		if ( 401 === $status || 403 === $status ) {
			return new WP_Error( 
				'auth_error', 
				__( 'GitHub token is invalid or expired. Please update it in Settings.', 'apc-free' ),
				$body 
			);
		}

		// Handle rate limiting
		if ( 429 === $status ) {
			return new WP_Error( 
				'rate_limit', 
				__( 'Rate limit reached. Please try again later.', 'apc-free' ),
				$body 
			);
		}

		// Check for successful response
		if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 
				'api_error', 
				__( 'Unexpected API response', 'apc-free' ), 
				$body 
			);
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
				'id'      => 'apc_provider_github_models_token',
				'label'   => __( 'GitHub Personal Access Token', 'apc-free' ),
				'type'    => 'password',
				'default' => '',
			'help'    => sprintf(
				/* translators: %s: GitHub settings URL link */
					__( 'Create a token at %s<br/>Leave it empty if using GitHub token from environment variable.', 'apc-free' ),
					'<a href="https://github.com/settings/tokens" target="_blank">github.com/settings/tokens</a>'
				),
			),
			array(
				'id'      => 'apc_provider_github_models_model',
				'label'   => __( 'Model', 'apc-free' ),
				'type'    => 'select',
				'default' => 'gpt-4o',
				'options' => $this->get_models(),
			),
			array(
				'id'      => 'apc_provider_github_models_temperature',
				'label'   => __( 'Temperature', 'apc-free' ),
				'type'    => 'number',
				'default' => 1.0,
				'min'     => 0,
				'max'     => 2,
				'step'    => 0.1,
				'help'    => __( '0-2: Lower = focused, Higher = creative', 'apc-free' ),
			),
			array(
				'id'      => 'apc_provider_github_models_max_tokens',
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
