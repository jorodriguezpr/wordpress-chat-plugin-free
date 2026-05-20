<?php
/**
 * REST API - Handles API endpoints for chat functionality
 *
 * @package    AI_Powered_Chat
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_REST_API {
	private $namespace = 'apc/v1';
	private $ai_handler;

	public function __construct() {
		try {
			$this->ai_handler = new APC_AI_Handler();
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		} catch ( Exception $e ) {
			// Handle gracefully during deactivation
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'APC Free - REST API initialization error: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Debug test endpoint
		register_rest_route( $this->namespace, '/test', array(
			'methods'             => 'GET',
			'callback'            => function() { return array('status' => 'REST API Working!'); },
			'permission_callback' => '__return_true',
		) );

		// Debug guest ID endpoint
		register_rest_route( $this->namespace, '/debug/guest-id', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'debug_guest_id' ),
			'permission_callback' => '__return_true',
			'args' => array(
				'apc_guest_session_id' => array(
					'required' => false,
					'type' => 'string',
				),
			),
		) );

		// Debug conversation endpoint
		register_rest_route( $this->namespace, '/debug/conversation/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => function( $request ) {
				global $wpdb;
				$conversation_id = $request->get_param( 'id' );
				$user_id = get_current_user_id();
				$conversation = APC_Database::get_conversation( $conversation_id );
				
				// Direct database query
				$direct_query = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}apc_conversations WHERE id = %d",
						$conversation_id
					),
					ARRAY_A
				);
				
				// Get all conversations
				$all_convos = $wpdb->get_results(
					"SELECT id, user_id, status, created_at FROM {$wpdb->prefix}apc_conversations ORDER BY id DESC LIMIT 5",
					ARRAY_A
				);
				
				return array(
					'conversation_id' => $conversation_id,
					'current_user_id' => $user_id,
					'conversation_data' => $conversation,
					'direct_query' => $direct_query,
					'conversation_exists' => !empty($conversation),
					'user_id_match' => $conversation ? ($conversation['user_id'] == $user_id) : false,
					'recent_conversations' => $all_convos,
					'table_name' => $wpdb->prefix . 'apc_conversations'
				);
			},
			'permission_callback' => '__return_true',
		) );

		// Create conversation
		register_rest_route( $this->namespace, '/conversations', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_conversation' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'apc_guest_session_id' => array(
					'required' => false,
					'type'    => 'string',
				),
			),
		) );

		// Get conversations
		register_rest_route( $this->namespace, '/conversations', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_conversations' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'apc_guest_session_id' => array(
					'required' => false,
					'type'    => 'string',
				),
			),
		) );

		// Send message
		register_rest_route( $this->namespace, '/messages', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'send_message' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'conversation_id' => array(
					'required' => true,
					'type'    => 'integer',
				),
				'message'         => array(
					'required' => true,
					'type'    => 'string',
				),
				'apc_guest_session_id' => array(
					'required' => false,
					'type'    => 'string',
				),
			),
		) );

		// Delete conversation
		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_conversation' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// **HUMAN SUPPORT ROUTES**

		// Request human support
		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)/request-human', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'request_human_support' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Get conversation status
		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)/status', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_conversation_status' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Close conversation (User)
		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)/close', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'close_user_conversation' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'apc_guest_session_id' => array(
					'required' => false,
					'type'    => 'string',
				),
			),
		) );

		// Poll for new messages
		register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)/poll', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'poll_messages' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'since_id' => array(
					'required' => false,
					'type'    => 'integer',
					'default' => 0,
				),
				'apc_guest_session_id' => array(
					'required' => false,
					'type'    => 'string',
				),
				'_t' => array(
					'required' => false,
					'type'    => 'integer',
					'description' => 'Cache buster timestamp',
				),
			),
		) );

		// **ADMIN ONLY ROUTES**

		// Get pending support requests
		register_rest_route( $this->namespace, '/support/pending', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_pending_requests' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Assign agent to conversation
		register_rest_route( $this->namespace, '/support/(?P<id>\d+)/assign', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'assign_agent' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Send agent message
		register_rest_route( $this->namespace, '/support/(?P<id>\d+)/message', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'send_agent_message' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
			'args'                => array(
				'message' => array(
					'required' => true,
					'type'    => 'string',
				),
			),
		) );

		// Close conversation
		register_rest_route( $this->namespace, '/support/(?P<id>\d+)/close', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'close_conversation' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Get agent's active conversations
		register_rest_route( $this->namespace, '/support/my-conversations', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_agent_conversations' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Get all conversations (for admin conversation viewer)
		register_rest_route( $this->namespace, '/admin/conversations', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_all_conversations' ),
			'permission_callback' => array( $this, 'check_conversation_viewer_permission' ),
		) );

		// Get conversation details with messages (for admin conversation viewer)
		register_rest_route( $this->namespace, '/admin/conversations/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_conversation_details' ),
			'permission_callback' => array( $this, 'check_conversation_viewer_permission' ),
		) );

		// **CONVERSATIONAL FORMS ROUTES**

		// Get all forms
		register_rest_route( $this->namespace, '/forms', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_forms' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Create form
		register_rest_route( $this->namespace, '/forms', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_form' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Update form
		register_rest_route( $this->namespace, '/forms/(?P<id>\d+)', array(
			'methods'             => 'PUT',
			'callback'            => array( $this, 'update_form' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Delete form
		register_rest_route( $this->namespace, '/forms/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_form' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Get form with fields
		register_rest_route( $this->namespace, '/forms/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_form_with_fields' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Add field to form
		register_rest_route( $this->namespace, '/forms/(?P<id>\d+)/fields', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'add_form_field' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Update field
		register_rest_route( $this->namespace, '/forms/fields/(?P<id>\d+)', array(
			'methods'             => 'PUT',
			'callback'            => array( $this, 'update_form_field' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Delete field
		register_rest_route( $this->namespace, '/forms/fields/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_form_field' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Submit form
		register_rest_route( $this->namespace, '/forms/(?P<id>\d+)/submit', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'submit_form' ),
			'permission_callback' => '__return_true',
		) );

		// Get form submissions
		register_rest_route( $this->namespace, '/forms/(?P<id>\d+)/submissions', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_form_submissions' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Get submission responses with field details
		register_rest_route( $this->namespace, '/forms/submissions/(?P<id>\d+)/responses', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_submission_responses' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Get form by trigger command
		register_rest_route( $this->namespace, '/forms/trigger', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_form_by_trigger' ),
			'permission_callback' => '__return_true',
		) );

		// Check workflow triggers
		register_rest_route( $this->namespace, '/forms/workflow-check', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'check_workflow_triggers' ),
			'permission_callback' => '__return_true',
		) );

		// Get form with fields by ID
		register_rest_route( $this->namespace, '/forms/(?P<id>\d+)/with-fields', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_form_with_fields' ),
			'permission_callback' => '__return_true',
		) );

		// Predefined Responses endpoints
		register_rest_route( $this->namespace, '/predefined-responses', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_all_responses' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		register_rest_route( $this->namespace, '/predefined-responses/match', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'match_response' ),
			'permission_callback' => '__return_true',
		) );

		// **AGENT STATUS ROUTES**

		// Set agent online status (agents need to be logged in)
		register_rest_route( $this->namespace, '/agent/status', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'set_agent_status' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'is_online' => array(
					'required' => true,
					'type'    => 'boolean',
				),
			),
		) );

		// Get agent status (agents need to be logged in)
		register_rest_route( $this->namespace, '/agent/status', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_my_agent_status' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Check if any agents are online (public endpoint for chat bubble)
		register_rest_route( $this->namespace, '/agents/online', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'check_agents_online' ),
			'permission_callback' => '__return_true',
		) );

		// Get all online agents
		register_rest_route( $this->namespace, '/agents/online-list', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_online_agents' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Debug: Get agent status info
		register_rest_route( $this->namespace, '/debug/agent-status', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'debug_agent_status' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get current user ID or generate guest ID
	 * For guests, we use a unique ID stored in session
	 */
	private function get_user_id_or_guest_id( $request = null ) {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}

		$guest_session_id = '';
		
		// If request object provided, use it to get the session ID
		if ( $request ) {
			$guest_session_id = $request->get_param( 'apc_guest_session_id' );
			error_log( '[APC] get_user_id_or_guest_id: got session ID from request param: ' . ($guest_session_id ?: 'empty') );
		}
		
		// If still not found, try direct $_POST or $_GET
		if ( empty( $guest_session_id ) ) {
			if ( isset( $_POST['apc_guest_session_id'] ) ) {
				$guest_session_id = sanitize_text_field( wp_unslash( $_POST['apc_guest_session_id'] ) );
				error_log( '[APC] get_user_id_or_guest_id: got session ID from POST' );
			} elseif ( isset( $_GET['apc_guest_session_id'] ) ) {
				$guest_session_id = sanitize_text_field( wp_unslash( $_GET['apc_guest_session_id'] ) );
				error_log( '[APC] get_user_id_or_guest_id: got session ID from GET' );
			}
		}
		
		// If no guest session ID from frontend, fall back to IP+UA hash for consistency
		if ( empty( $guest_session_id ) ) {
			$guest_identifier = md5( $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] );
			$guest_session_id = 'fallback_' . $guest_identifier;
			error_log( '[APC] get_user_id_or_guest_id: No session ID provided, using IP+UA fallback' );
		} else {
			error_log( '[APC] get_user_id_or_guest_id: Using guest session ID: ' . $guest_session_id );
		}
		
		// Convert session ID to numeric guest user ID
		$guest_id = -abs( crc32( $guest_session_id ) );
		
		// Store in transient for tracking (expires in 7 days)
		set_transient( 'apc_guest_session_' . $guest_id, true, 604800 );
		
		error_log( '[APC] FINAL GUEST ID: ' . $guest_id . ' (from session: ' . $guest_session_id . ')' );
		
		return $guest_id;
	}

	/**
	 * Debug endpoint to check guest ID calculation
	 */
	public function debug_guest_id( WP_REST_Request $request ) {
		$guest_session_from_param = $request->get_param( 'apc_guest_session_id' );
		$calculated_user_id = $this->get_user_id_or_guest_id( $request );
		
		global $wpdb;
		$conversations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_id, status, created_at FROM {$wpdb->prefix}apc_conversations WHERE user_id = %d",
				$calculated_user_id
			),
			ARRAY_A
		);
		
		return array(
			'guest_session_id_from_param' => $guest_session_from_param,
			'calculated_user_id' => $calculated_user_id,
			'is_logged_in' => is_user_logged_in(),
			'conversations_for_this_user_id' => $conversations,
			'request_params' => $request->get_params(),
		);
	}

	/**
	 * Check permissions - allows logged-in users or guests if guest chat is enabled
	 */
	public function check_permission() {
		$allow_guest = get_option( 'apc_allow_guest_chat', 0 );
		
		// Always allow logged-in users
		if ( is_user_logged_in() ) {
			return true;
		}
		
		// Allow guests only if the setting is enabled
		if ( $allow_guest ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Permission check for administrators
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Permission check for conversation viewers
	 */
	public function check_conversation_viewer_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Create new conversation
	 */
	public function create_conversation( WP_REST_Request $request ) {
		$user_id = $this->get_user_id_or_guest_id( $request );
		$title = $request->get_param( 'title' ) ?: '';
		$guest_session_id = $request->get_param( 'apc_guest_session_id' );

		error_log( '[APC] create_conversation called:' );
		error_log( '  - user_id: ' . $user_id );
		error_log( '  - title: ' . $title );
		error_log( '  - guest_session_id: ' . ($guest_session_id ?: 'NOT PROVIDED') );
		error_log( '  - logged_in: ' . (is_user_logged_in() ? 'yes' : 'no') );

		$conversation_id = APC_Database::create_conversation( $user_id, $title );

		if ( ! $conversation_id ) {
			return new WP_Error( 'db_error', __( 'Failed to create conversation', 'apc-free' ), array( 'status' => 500 ) );
		}

		error_log( '[APC] Conversation created: id=' . $conversation_id . ', user_id=' . $user_id );

		// Verify the conversation was saved correctly
		$saved_conversation = APC_Database::get_conversation( $conversation_id );
		if ( $saved_conversation ) {
			error_log( '[APC] Verified conversation in DB - user_id: ' . $saved_conversation['user_id'] . ', status: ' . $saved_conversation['status'] );
		} else {
			error_log( '[APC] WARNING: Could not retrieve conversation ' . $conversation_id . ' after creation!' );
		}

		// Check if AI is enabled
		$ai_enabled = get_option( 'apc_ai_enabled', 1 );
		
		// If AI is disabled, set conversation status to human_requested
		if ( ! $ai_enabled ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'apc_conversations',
				array( 
					'status' => 'human_requested',
					'human_requested_at' => current_time( 'mysql' )
				),
				array( 'id' => $conversation_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		return new WP_REST_Response( array(
			'success'           => true,
			'conversation_id'   => $conversation_id,
			'conversation_date' => current_time( 'mysql' ),
			'ai_enabled'        => (bool) $ai_enabled,
		), 201 );
	}

	/**
	 * Get user conversations
	 */
	public function get_conversations( WP_REST_Request $request ) {
		$user_id = $this->get_user_id_or_guest_id( $request );
		$conversations = APC_Database::get_conversations( $user_id );

		return new WP_REST_Response( array(
			'success'         => true,
			'conversations'   => $conversations,
		), 200 );
	}

	/**
	 * Send message and get AI response (or just save if human support active)
	 */
	public function send_message( WP_REST_Request $request ) {
		$user_id = $this->get_user_id_or_guest_id( $request );
		$conversation_id = $request->get_param( 'conversation_id' );
		$user_message = $request->get_param( 'message' );
		$guest_session_id = $request->get_param( 'apc_guest_session_id' );

		// Debug logging
		error_log( '[APC] send_message called:' );
		error_log( '  - user_id: ' . $user_id );
		error_log( '  - conversation_id: ' . $conversation_id );
		error_log( '  - message: ' . substr( $user_message, 0, 50 ) );
		error_log( '  - guest_session_id: ' . ($guest_session_id ?: 'NOT PROVIDED') );
		error_log( '  - logged_in: ' . (is_user_logged_in() ? 'yes' : 'no') );

		// Verify conversation belongs to user - get full conversation with status
		global $wpdb;
		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, status FROM {$wpdb->prefix}apc_conversations WHERE id = %d AND user_id = %d",
				$conversation_id,
				$user_id
			),
			ARRAY_A
		);

		if ( ! $conversation ) {
			// Debug: check what exists in the database
			$all_for_user = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, user_id, status FROM {$wpdb->prefix}apc_conversations WHERE user_id = %d LIMIT 10",
					$user_id
				),
				ARRAY_A
			);
			error_log( '[APC] Conversations for user ' . $user_id . ': ' . wp_json_encode( $all_for_user ) );
			
			$all_convos = $wpdb->get_results(
				"SELECT id, user_id, status, created_at FROM {$wpdb->prefix}apc_conversations ORDER BY id DESC LIMIT 5",
				ARRAY_A
			);
			error_log( '[APC] Recent conversations (all users): ' . wp_json_encode( $all_convos ) );
			
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		// Don't allow sending messages to closed conversations
		if ( $conversation['status'] === 'closed' ) {
			return new WP_Error( 'conversation_closed', __( 'This conversation has been closed. Please start a new conversation.', 'apc-free' ), array( 'status' => 410 ) );
		}

		// Add user message
		APC_Database::add_message( $conversation_id, 'user', $user_message );

		// Check for predefined responses FIRST (works for both AI and Human modes)
		$matched_response = APC_Database::find_matching_response( $user_message );
		if ( $matched_response ) {
			$answer = $matched_response['answer'];
			
			// Append page link if provided
			if ( ! empty( $matched_response['page_link'] ) ) {
				/* translators: %s: URL to learn more */
				$answer .= "\n\n" . sprintf( __( 'Learn more: %s', 'apc-free' ), $matched_response['page_link'] );
			}
			
			// Save the predefined response as assistant message
			APC_Database::add_message( $conversation_id, 'assistant', $answer );
			
			return new WP_REST_Response( array(
				'success'            => true,
				'message'            => $answer,
				'tokens_used'        => 0,
				'is_predefined'      => true,
				'predefined_id'      => $matched_response['id'],
			), 200 );
		}

		// If human support is active, do NOT send to AI - just save the message
		if ( $conversation['status'] === 'human_requested' || $conversation['status'] === 'human_active' ) {
			return new WP_REST_Response( array(
				'success'  => true,
				'message'  => __( 'Message sent to agent', 'apc-free' ),
				'is_human' => true,
				'status'   => $conversation['status'],
			), 200 );
		}

		// Get conversation history (skip permission check - already verified by REST API)
		$messages = APC_Database::get_messages( $conversation_id, 0, true );

		// Convert messages for AI provider compatibility
		// AI providers expect: system, assistant, user (not 'agent' or custom roles)
		// Convert 'agent' Ã¢â€ â€™ 'assistant' since both are responses to the user
		$ai_messages = array();
		foreach ( $messages as $msg ) {
			$ai_messages[] = array(
				'role'    => ( $msg['role'] === 'agent' ) ? 'assistant' : $msg['role'],
				'content' => $msg['content'],
			);
		}

		// Get AI response
		$system_prompt = get_option( 'apc_system_prompt', __( 'You are a helpful assistant.', 'apc-free' ) );
		$response = $this->ai_handler->get_response( $ai_messages, $system_prompt );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Save assistant message
		$tokens = $response['tokens'] ?? 0;
		APC_Database::add_message( $conversation_id, 'assistant', $response['content'], $tokens );

		return new WP_REST_Response( array(
			'success'  => true,
			'message'  => $response['content'],
			'tokens'   => $tokens,
		), 200 );
	}

	/**
	 * Delete conversation
	 */
	public function delete_conversation( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$conversation_id = $request->get_param( 'id' );

		$deleted = APC_Database::delete_conversation( $conversation_id, $user_id );

		if ( ! $deleted ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete conversation', 'apc-free' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Conversation deleted', 'apc-free' ),
		), 200 );
	}

	/**
	 * Request human support
	 */
	public function request_human_support( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$conversation_id = $request->get_param( 'id' );

		// Verify conversation belongs to user
		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		// Allow if user owns conversation OR if user_id is 0 (guest/session issue)
		if ( $user_id != 0 && $conversation['user_id'] != $user_id ) {
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		// Request human support
		$success = APC_Database::request_human_support( $conversation_id );

		if ( ! $success ) {
			return new WP_Error( 'request_failed', __( 'Failed to request support', 'apc-free' ), array( 'status' => 500 ) );
		}

		// Add system message
		APC_Database::add_message( $conversation_id, 'system', __( 'Support request sent. A human agent will join shortly.', 'apc-free' ) );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Support requested', 'apc-free' ),
			'status'  => 'human_requested',
		), 200 );
	}

	/**
	 * Get conversation status
	 */
	public function get_conversation_status( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$conversation_id = $request->get_param( 'id' );

		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation || $conversation['user_id'] != $user_id ) {
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( array(
			'success'    => true,
			'status'     => $conversation['status'],
			'agent_name' => $conversation['agent_name'] ?? null,
		), 200 );
	}

	/**
	 * Poll for new messages
	 */
	public function poll_messages( WP_REST_Request $request ) {
		$user_id = $this->get_user_id_or_guest_id( $request );
		$conversation_id = $request->get_param( 'id' );
		$since_id = $request->get_param( 'since_id' );

		error_log( '[APC] poll_messages called:' );
		error_log( '  - user_id: ' . $user_id );
		error_log( '  - conversation_id: ' . $conversation_id );
		error_log( '  - since_id: ' . $since_id );

		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			error_log( '[APC] Conversation not found: ' . $conversation_id );
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		error_log( '[APC] Conversation found - user_id: ' . $conversation['user_id'] );

		// Allow polling if:
		// 1. User owns the conversation
		// 2. User is admin (can see all conversations)
		// 3. User is the assigned agent
		$is_admin = current_user_can( 'manage_options' );
		$is_owner = ( $conversation['user_id'] == $user_id );
		$is_agent = ( ! empty( $conversation['agent_id'] ) && $conversation['agent_id'] == $user_id );

		error_log( '[APC] Permission check - is_admin: ' . ($is_admin ? 'yes' : 'no') . ', is_owner: ' . ($is_owner ? 'yes' : 'no') . ', is_agent: ' . ($is_agent ? 'yes' : 'no') );

		if ( ! $is_admin && ! $is_owner && ! $is_agent ) {
			error_log( '[APC] Access denied - user_id ' . $user_id . ' does not own conversation ' . $conversation_id );
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		// Skip HIPAA permission check since we already verified permissions above
		$new_messages = APC_Database::get_messages( $conversation_id, $since_id, true );

		error_log( '[APC] Returning ' . count($new_messages) . ' messages, status: ' . $conversation['status'] );
		if ( count($new_messages) > 0 ) {
			error_log( '[APC] Sample message: ' . json_encode($new_messages[0]) );
		}

		$response = new WP_REST_Response( array(
			'success'  => true,
			'messages' => $new_messages,
			'status'   => $conversation['status'],
		), 200 );
		
		// Prevent caching of poll responses
		$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );
		
		return $response;
	}

	/**
	 * Get pending support requests (Admin only)
	 */
	public function get_pending_requests( WP_REST_Request $request ) {
		$requests = APC_Database::get_pending_requests();

		return new WP_REST_Response( array(
			'success'  => true,
			'requests' => $requests,
		), 200 );
	}

	/**
	 * Assign agent to conversation (Admin only)
	 */
	public function assign_agent( WP_REST_Request $request ) {
		$conversation_id = $request->get_param( 'id' );
		$agent_id = get_current_user_id();

		error_log( '[APC] assign_agent called - conversation_id: ' . $conversation_id . ', agent_id: ' . $agent_id );

		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			error_log( '[APC] assign_agent - conversation not found: ' . $conversation_id );
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		error_log( '[APC] assign_agent - current conversation status: ' . $conversation['status'] . ', user_id: ' . $conversation['user_id'] );

		$success = APC_Database::assign_agent( $conversation_id, $agent_id );

		if ( ! $success ) {
			error_log( '[APC] assign_agent - failed to assign agent' );
			return new WP_Error( 'assign_failed', __( 'Failed to assign agent', 'apc-free' ), array( 'status' => 500 ) );
		}

		error_log( '[APC] assign_agent - successfully assigned agent, checking updated status...' );
		
		// Verify the update
		$updated_conversation = APC_Database::get_conversation( $conversation_id );
		if ( $updated_conversation ) {
			error_log( '[APC] assign_agent - updated status: ' . $updated_conversation['status'] . ', agent_id: ' . $updated_conversation['agent_id'] );
		}

		// Add system message
		$agent = wp_get_current_user();
		/* translators: %s: agent display name */
		APC_Database::add_message( $conversation_id, 'system', sprintf( __( '%s has joined the conversation', 'apc-free' ), $agent->display_name ) );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Agent assigned', 'apc-free' ),
		), 200 );
	}

	/**
	 * Send agent message (Admin only)
	 */
	public function send_agent_message( WP_REST_Request $request ) {
		$conversation_id = $request->get_param( 'id' );
		$message = $request->get_param( 'message' );
		$agent_id = get_current_user_id();

		error_log( '[APC] send_agent_message called - conversation_id: ' . $conversation_id . ', agent_id: ' . $agent_id );

		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			error_log( '[APC] send_agent_message - conversation not found: ' . $conversation_id );
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		error_log( '[APC] send_agent_message - conversation status: ' . $conversation['status'] . ', agent_id: ' . $conversation['agent_id'] . ', user_id: ' . $conversation['user_id'] );

		// Auto-assign agent if not assigned yet and status is waiting
		if ( empty( $conversation['agent_id'] ) && $conversation['status'] == 'human_requested' ) {
			error_log( '[APC] send_agent_message - auto-assigning agent' );
			$assign_success = APC_Database::assign_agent( $conversation_id, $agent_id );
			if ( $assign_success ) {
				$agent = wp_get_current_user();
				/* translators: %s: agent display name */
				APC_Database::add_message( $conversation_id, 'system', sprintf( __( '%s has joined the conversation', 'apc-free' ), $agent->display_name ) );
				// Refresh conversation data
				$conversation = APC_Database::get_conversation( $conversation_id );
				error_log( '[APC] send_agent_message - after auto-assign, status: ' . $conversation['status'] );
			}
		}

		// Verify agent is assigned to this conversation (use == for loose comparison)
		if ( $conversation['agent_id'] != $agent_id ) {
			error_log( '[APC] send_agent_message - agent not assigned. Expected: ' . $conversation['agent_id'] . ', Got: ' . $agent_id );
			return new WP_Error( 'not_assigned', __( 'You are not assigned to this conversation', 'apc-free' ), array( 'status' => 403 ) );
		}

		// Add agent message
		$message_id = APC_Database::add_message( $conversation_id, 'agent', $message, 0, $agent_id );

		if ( ! $message_id ) {
			error_log( '[APC] send_agent_message - failed to save message' );
			return new WP_Error( 'send_failed', __( 'Failed to send message', 'apc-free' ), array( 'status' => 500 ) );
		}

		error_log( '[APC] send_agent_message - message saved successfully, message_id: ' . $message_id );

		return new WP_REST_Response( array(
			'success'    => true,
			'message_id' => $message_id,
		), 200 );
	}

	/**

		if ( ! $message_id ) {
			return new WP_Error( 'send_failed', __( 'Failed to send message', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success'    => true,
			'message_id' => $message_id,
		), 200 );
	}

	/**
	 * Close conversation (User closes their own conversation)
	 */
	public function close_user_conversation( WP_REST_Request $request ) {
		$user_id = $this->get_user_id_or_guest_id( $request );
		$conversation_id = $request->get_param( 'id' );

		// Verify conversation belongs to user
		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		// Allow user to close their own conversation
		if ( $conversation['user_id'] != $user_id ) {
			return new WP_Error( 'forbidden', __( 'You do not have permission to close this conversation', 'apc-free' ), array( 'status' => 403 ) );
		}

		$success = APC_Database::close_conversation( $conversation_id );

		if ( ! $success ) {
			return new WP_Error( 'close_failed', __( 'Failed to close conversation', 'apc-free' ), array( 'status' => 500 ) );
		}

		// Add system message
		APC_Database::add_message( $conversation_id, 'system', __( 'Conversation closed by user', 'apc-free' ) );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Conversation closed', 'apc-free' ),
		), 200 );
	}

	/**
	 * Close conversation (Admin only)
	 */
	public function close_conversation( WP_REST_Request $request ) {
		$conversation_id = $request->get_param( 'id' );

		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		$success = APC_Database::close_conversation( $conversation_id );

		if ( ! $success ) {
			return new WP_Error( 'close_failed', __( 'Failed to close conversation', 'apc-free' ), array( 'status' => 500 ) );
		}

		// Add system message
		APC_Database::add_message( $conversation_id, 'system', __( 'Conversation closed by agent', 'apc-free' ) );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Conversation closed', 'apc-free' ),
		), 200 );
	}

	/**
	 * Get agent's active conversations (Admin only)
	 */
	public function get_agent_conversations( WP_REST_Request $request ) {
		$agent_id = get_current_user_id();
		$conversations = APC_Database::get_agent_conversations( $agent_id );

		return new WP_REST_Response( array(
			'success'       => true,
			'conversations' => $conversations,
		), 200 );
	}

	/**
	 * Get all conversations (Admin conversation viewer)
	 */
	public function get_all_conversations( WP_REST_Request $request ) {
		$conversations = APC_Database::get_all_conversations();

		return new WP_REST_Response( array(
			'success'       => true,
			'conversations' => $conversations,
		), 200 );
	}

	/**
	 * Get conversation details with messages (Admin conversation viewer)
	 */
	public function get_conversation_details( WP_REST_Request $request ) {
		$conversation_id = $request->get_param( 'id' );

		$conversation = APC_Database::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			return new WP_Error( 'not_found', __( 'Conversation not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		// Skip permission check - admin endpoint already verified permissions
		$messages = APC_Database::get_messages( $conversation_id, 0, true );

		return new WP_REST_Response( array(
			'success'      => true,
			'conversation' => $conversation,
			'messages'     => $messages,
		), 200 );
	}

	/**
	 * Get all forms
	 */
	public function get_forms( WP_REST_Request $request ) {
		$forms = APC_Forms::get_all_forms();

		return new WP_REST_Response( array(
			'success' => true,
			'forms'   => $forms,
		), 200 );
	}

	/**
	 * Create form
	 */
	public function create_form( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Form name is required', 'apc-free' ), array( 'status' => 400 ) );
		}

		$form_id = APC_Forms::create_form( $data );

		if ( ! $form_id ) {
			return new WP_Error( 'create_failed', __( 'Failed to create form', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'form_id' => $form_id,
			'message' => __( 'Form created successfully', 'apc-free' ),
		), 201 );
	}

	/**
	 * Update form
	 */
	public function update_form( WP_REST_Request $request ) {
		$form_id = $request->get_param( 'id' );
		$data = $request->get_json_params();

		$form = APC_Forms::get_form( $form_id );

		if ( ! $form ) {
			return new WP_Error( 'not_found', __( 'Form not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		$result = APC_Forms::update_form( $form_id, $data );

		if ( ! $result ) {
			return new WP_Error( 'update_failed', __( 'Failed to update form', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Form updated successfully', 'apc-free' ),
		), 200 );
	}

	/**
	 * Delete form
	 */
	public function delete_form( WP_REST_Request $request ) {
		$form_id = $request->get_param( 'id' );

		$form = APC_Forms::get_form( $form_id );

		if ( ! $form ) {
			return new WP_Error( 'not_found', __( 'Form not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		$result = APC_Forms::delete_form( $form_id );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete form', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Form deleted successfully', 'apc-free' ),
		), 200 );
	}

	/**
	 * Get form with fields
	 */
	public function get_form_with_fields( WP_REST_Request $request ) {
		$form_id = $request->get_param( 'id' );

		$form = APC_Forms::get_form( $form_id );

		if ( ! $form || ! $form['is_active'] ) {
			return new WP_Error( 'not_found', __( 'Form not found or inactive', 'apc-free' ), array( 'status' => 404 ) );
		}

		$fields = APC_Forms::get_form_fields( $form_id );

		return new WP_REST_Response( array(
			'success' => true,
			'form'    => $form,
			'fields'  => $fields,
		), 200 );
	}

	/**
	 * Add field to form
	 */
	public function add_form_field( WP_REST_Request $request ) {
		$form_id = $request->get_param( 'id' );
		$data = $request->get_json_params();

		$form = APC_Forms::get_form( $form_id );

		if ( ! $form ) {
			return new WP_Error( 'not_found', __( 'Form not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		if ( empty( $data['field_type'] ) || empty( $data['field_name'] ) || empty( $data['field_label'] ) ) {
			return new WP_Error( 'missing_fields', __( 'Required fields are missing', 'apc-free' ), array( 'status' => 400 ) );
		}

		$field_id = APC_Forms::add_field( $form_id, $data );

		if ( ! $field_id ) {
			return new WP_Error( 'add_failed', __( 'Failed to add field', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success'  => true,
			'field_id' => $field_id,
			'message'  => __( 'Field added successfully', 'apc-free' ),
		), 201 );
	}

	/**
	 * Update form field
	 */
	public function update_form_field( WP_REST_Request $request ) {
		$field_id = $request->get_param( 'id' );
		$data = $request->get_json_params();

		$result = APC_Forms::update_field( $field_id, $data );

		if ( ! $result ) {
			return new WP_Error( 'update_failed', __( 'Failed to update field', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Field updated successfully', 'apc-free' ),
		), 200 );
	}

	/**
	 * Delete form field
	 */
	public function delete_form_field( WP_REST_Request $request ) {
		$field_id = $request->get_param( 'id' );

		$result = APC_Forms::delete_field( $field_id );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete field', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Field deleted successfully', 'apc-free' ),
		), 200 );
	}

	/**
	 * Submit form
	 */
	public function submit_form( WP_REST_Request $request ) {
		$form_id = $request->get_param( 'id' );
		$data = $request->get_json_params();

		$form = APC_Forms::get_form( $form_id );

		if ( ! $form || ! $form['is_active'] ) {
			return new WP_Error( 'not_found', __( 'Form not found or inactive', 'apc-free' ), array( 'status' => 404 ) );
		}

		$conversation_id = $data['conversation_id'] ?? null;
		$responses = $data['responses'] ?? array();

		if ( empty( $responses ) ) {
			return new WP_Error( 'no_responses', __( 'No form responses provided', 'apc-free' ), array( 'status' => 400 ) );
		}

		$submission_id = APC_Forms::save_submission( $form_id, $conversation_id, $responses );

		if ( ! $submission_id ) {
			return new WP_Error( 'submit_failed', __( 'Failed to save submission', 'apc-free' ), array( 'status' => 500 ) );
		}

		// Send notification to agent if conversation exists
		if ( $conversation_id ) {
			// Get form fields to display labels
			$fields = APC_Forms::get_form_fields( $form_id );
			$field_map = array();
			foreach ( $fields as $field ) {
				$field_map[ $field['id'] ] = $field['field_label'];
			}

			// Build notification with submitted data (agent-only)
			$notification = '[AGENT-ONLY]' . sprintf(
				/* translators: 1: form name, 2: submission ID */
				__( '✓ Form "%1$s" completed (Submission #%2$d)', 'apc-free' ),
				$form['name'],
				$submission_id
			);

			// Add submitted data
			$notification .= "\n\n";
			foreach ( $responses as $field_id => $value ) {
				$label = $field_map[ $field_id ] ?? __( 'Field', 'apc-free' );
				$display_value = is_array( $value ) ? implode( ', ', $value ) : $value;
				$notification .= sprintf( "%s: %s\n", $label, $display_value );
			}

			APC_Database::add_message( $conversation_id, 'system', $notification );
		}

		return new WP_REST_Response( array(
			'success'       => true,
			'submission_id' => $submission_id,
			'message'       => __( 'Form submitted successfully', 'apc-free' ),
		), 200 );
	}

	/**
	 * Get form submissions
	 */
	public function get_form_submissions( WP_REST_Request $request ) {
		$form_id = $request->get_param( 'id' );

		$form = APC_Forms::get_form( $form_id );

		if ( ! $form ) {
			return new WP_Error( 'not_found', __( 'Form not found', 'apc-free' ), array( 'status' => 404 ) );
		}

		$submissions = APC_Forms::get_submissions( $form_id );

		return new WP_REST_Response( array(
			'success'     => true,
			'submissions' => $submissions,
		), 200 );
	}

	/**
	 * Get submission responses with field details
	 */
	public function get_submission_responses( WP_REST_Request $request ) {
		$submission_id = $request->get_param( 'id' );

		$responses = APC_Forms::get_submission_responses( $submission_id );

		return new WP_REST_Response( array(
			'success'   => true,
			'responses' => $responses,
		), 200 );
	}

	/**
	 * Get form by trigger command
	 */
	public function get_form_by_trigger( WP_REST_Request $request ) {
		$command = $request->get_param( 'command' );

		if ( empty( $command ) ) {
			return new WP_Error( 'missing_command', __( 'Command is required', 'apc-free' ), array( 'status' => 400 ) );
		}

		$form = APC_Forms::get_form_by_command( $command );

		if ( ! $form || ! $form['is_active'] ) {
			return new WP_Error( 'not_found', __( 'Form not found or inactive', 'apc-free' ), array( 'status' => 404 ) );
		}

		$fields = APC_Forms::get_form_fields( $form['id'] );

		return new WP_REST_Response( array(
			'success' => true,
			'form'    => $form,
			'fields'  => $fields,
		), 200 );
	}

	/**
	 * Check workflow triggers
	 */
	public function check_workflow_triggers( WP_REST_Request $request ) {
		$message = $request->get_param( 'message' );

		if ( empty( $message ) ) {
			return new WP_REST_Response( array( 'success' => false ), 200 );
		}

		// Get all active workflow forms
		global $wpdb;
		$forms = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}apc_forms 
			WHERE trigger_type = 'workflow' 
			AND is_active = 1 
			AND workflow_keywords IS NOT NULL",
			ARRAY_A
		);

		// Check each form's keywords against the message
		foreach ( $forms as $form ) {
			$keywords = explode( ',', $form['workflow_keywords'] );
			$message_lower = strtolower( $message );

			foreach ( $keywords as $keyword ) {
				$keyword = trim( strtolower( $keyword ) );
				if ( strpos( $message_lower, $keyword ) !== false ) {
					// Found a match!
					return new WP_REST_Response( array(
						'success' => true,
						'form'    => $form,
					), 200 );
				}
			}
		}

		return new WP_REST_Response( array( 'success' => false ), 200 );
	}

	/**
	 * Get all predefined responses (Admin only)
	 */
	public function get_all_responses( WP_REST_Request $request ) {
		$responses = APC_Database::get_all_predefined_responses();

		return new WP_REST_Response( array(
			'success'   => true,
			'responses' => $responses,
		), 200 );
	}

	/**
	 * Match predefined response by message
	 */
	public function match_response( WP_REST_Request $request ) {
		$message = $request->get_param( 'message' );

		if ( empty( $message ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'matched' => false,
			), 200 );
		}

		$matched_response = APC_Database::find_matching_response( $message );

		if ( $matched_response ) {
			// Prepare the answer
			$answer = $matched_response['answer'];
			
			// Append page link if available
			if ( ! empty( $matched_response['page_link'] ) ) {
				/* translators: %s: URL to learn more */
				$answer .= "\n\n" . sprintf( __( 'Learn more: %s', 'apc-free' ), $matched_response['page_link'] );
			}

			return new WP_REST_Response( array(
				'success'  => true,
				'matched'  => true,
				'answer'   => $answer,
				'question' => $matched_response['question'],
			), 200 );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'matched' => false,
		), 200 );
	}

	/**
	 * Set agent online/offline status
	 */
	public function set_agent_status( WP_REST_Request $request ) {
		$agent_id = get_current_user_id();
		
		// Get parameter from JSON body or query param
		$is_online = $request->get_param( 'is_online' );
		
		// If not found, try getting from JSON body directly
		if ( $is_online === null ) {
			$json_params = $request->get_json_params();
			$is_online = $json_params['is_online'] ?? false;
		}
		
		// Convert to boolean
		$is_online = (bool) $is_online;

		$result = APC_Database::set_agent_status( $agent_id, $is_online );

		if ( ! $result ) {
			return new WP_Error( 'status_update_failed', __( 'Failed to update agent status', 'apc-free' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success'   => true,
			'is_online' => $is_online,
			'message'   => $is_online ? __( 'You are now online', 'apc-free' ) : __( 'You are now offline', 'apc-free' ),
		), 200 );
	}

	/**
	 * Get current agent's status
	 */
	public function get_my_agent_status( WP_REST_Request $request ) {
		$agent_id = get_current_user_id();
		$is_online = APC_Database::get_agent_status( $agent_id );

		return new WP_REST_Response( array(
			'agent_id'  => $agent_id,
			'is_online' => $is_online,
		), 200 );
	}

	/**
	 * Check if any agents are online (public endpoint)
	 */
	public function check_agents_online( WP_REST_Request $request ) {
		$any_online = APC_Database::is_any_agent_online();

		return new WP_REST_Response( array(
			'agents_available' => $any_online,
		), 200 );
	}

	/**
	 * Get list of online agents
	 */
	public function get_online_agents( WP_REST_Request $request ) {
		$agents = APC_Database::get_online_agents();

		return new WP_REST_Response( array(
			'success' => true,
			'agents'  => $agents,
			'count'   => count( $agents ),
		), 200 );
	}

	/**
	 * Debug: Get agent status debug info
	 */
	public function debug_agent_status( WP_REST_Request $request ) {
		global $wpdb;
		$current_user = wp_get_current_user();
		$current_user_id = get_current_user_id();

		// Check if table exists
		$table_name = $wpdb->prefix . 'apc_agent_status';
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;

		// Try to get current user's status
		$user_status = null;
		$user_status_raw = null;
		if ( $current_user_id ) {
			$user_status = APC_Database::get_agent_status( $current_user_id );
			$user_status_raw = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}apc_agent_status WHERE agent_id = %d LIMIT 1",
					$current_user_id
				),
				ARRAY_A
			);
		}

		// Get all agents with status
		$all_agents = $wpdb->get_results(
			"SELECT agent_id, is_online, updated_at FROM {$wpdb->prefix}apc_agent_status ORDER BY agent_id DESC",
			ARRAY_A
		);

		// Check if any are online
		$any_online = APC_Database::is_any_agent_online();

		return new WP_REST_Response( array(
			'table_exists'        => $table_exists,
			'table_name'          => $table_name,
			'current_user_id'     => $current_user_id,
			'current_user_login'  => $current_user ? $current_user->user_login : 'Not logged in',
			'current_user_status' => $user_status,
			'current_user_raw'    => $user_status_raw,
			'all_agents'          => $all_agents ? $all_agents : array(),
			'any_agents_online'   => $any_online,
			'total_agents'        => count( $all_agents ? $all_agents : array() ),
		), 200 );
	}
}
