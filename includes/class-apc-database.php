<?php
/**
 * Database - Manages chat history and messages (Free Version - No HIPAA/Encryption)
 *
 * @package    AI_Powered_Chat_Free
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Database {
	/**
	 * Create database tables on plugin activation (Free version - no audit log table)
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Chat conversations table
		$conversations_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_conversations (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT NOT NULL,
				title VARCHAR(255) NOT NULL,
				status ENUM('ai', 'human_requested', 'human_active', 'closed') DEFAULT 'ai',
				agent_id BIGINT UNSIGNED NULL,
				ip_address VARCHAR(45) NULL,
				human_requested_at DATETIME NULL,
				human_joined_at DATETIME NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY status (status),
				KEY agent_id (agent_id),
				KEY ip_address (ip_address)
			) $charset_collate;
		";

		// Chat messages table
		$messages_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_messages (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				conversation_id BIGINT UNSIGNED NOT NULL,
				role ENUM('user', 'assistant', 'agent', 'system') NOT NULL,
				sender_id BIGINT UNSIGNED NULL,
				content LONGTEXT NOT NULL,
				tokens_used INT DEFAULT 0,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY conversation_id (conversation_id),
				KEY sender_id (sender_id),
				FOREIGN KEY (conversation_id) REFERENCES {$wpdb->prefix}apc_conversations(id) ON DELETE CASCADE
			) $charset_collate;
		";

		// Conversational Forms tables
		$forms_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_forms (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				description TEXT NULL,
				trigger_type ENUM('command','link','workflow') DEFAULT 'command',
				trigger_command VARCHAR(100) NULL,
				trigger_button_text VARCHAR(100) NULL,
				workflow_keywords TEXT NULL,
				workflow_conditions TEXT NULL,
				is_active TINYINT(1) DEFAULT 1,
				email_notification TINYINT(1) DEFAULT 0,
				email_recipients TEXT NULL,
				created_by BIGINT UNSIGNED NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY trigger_command (trigger_command),
				KEY trigger_type (trigger_type),
				KEY is_active (is_active)
			) $charset_collate;
		";

		$form_fields_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_form_fields (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				form_id BIGINT UNSIGNED NOT NULL,
				field_order INT NOT NULL DEFAULT 0,
				field_type ENUM('text', 'email', 'number', 'textarea', 'select', 'checkbox', 'radio') NOT NULL,
				field_name VARCHAR(100) NOT NULL,
				field_label TEXT NOT NULL,
				field_placeholder VARCHAR(255) NULL,
				field_options LONGTEXT NULL,
				is_required TINYINT(1) DEFAULT 0,
				conditional_logic LONGTEXT NULL,
				validation_rules LONGTEXT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY form_id (form_id),
				KEY field_order (field_order),
				FOREIGN KEY (form_id) REFERENCES {$wpdb->prefix}apc_forms(id) ON DELETE CASCADE
			) $charset_collate;
		";

		$form_submissions_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_form_submissions (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				form_id BIGINT UNSIGNED NOT NULL,
				conversation_id BIGINT UNSIGNED NULL,
				user_id BIGINT UNSIGNED NULL,
				submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY form_id (form_id),
				KEY conversation_id (conversation_id),
				KEY user_id (user_id),
				FOREIGN KEY (form_id) REFERENCES {$wpdb->prefix}apc_forms(id) ON DELETE CASCADE,
				FOREIGN KEY (conversation_id) REFERENCES {$wpdb->prefix}apc_conversations(id) ON DELETE SET NULL
			) $charset_collate;
		";

		$form_responses_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_form_responses (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				submission_id BIGINT UNSIGNED NOT NULL,
				field_id BIGINT UNSIGNED NOT NULL,
				field_value LONGTEXT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY submission_id (submission_id),
				KEY field_id (field_id),
				FOREIGN KEY (submission_id) REFERENCES {$wpdb->prefix}apc_form_submissions(id) ON DELETE CASCADE,
				FOREIGN KEY (field_id) REFERENCES {$wpdb->prefix}apc_form_fields(id) ON DELETE CASCADE
			) $charset_collate;
		";

		// Predefined Responses table
		$predefined_responses_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_predefined_responses (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				question VARCHAR(500) NOT NULL,
				answer LONGTEXT NOT NULL,
				keywords TEXT NULL,
				page_link VARCHAR(500) NULL,
				priority INT DEFAULT 0,
				is_active TINYINT(1) DEFAULT 1,
				created_by BIGINT UNSIGNED NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY is_active (is_active),
				KEY priority (priority),
				FULLTEXT KEY keywords_fulltext (keywords, question)
			) $charset_collate;
		";

		// Agent Status table
		$agent_status_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apc_agent_status (
				agent_id BIGINT UNSIGNED NOT NULL,
				is_online TINYINT(1) DEFAULT 0,
				last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (agent_id)
			) $charset_collate;
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $conversations_table );
		dbDelta( $messages_table );
		dbDelta( $forms_table );
		dbDelta( $form_fields_table );
		dbDelta( $form_submissions_table );
		dbDelta( $form_responses_table );
		dbDelta( $predefined_responses_table );
		dbDelta( $agent_status_table );
		
		// Run migration to add missing columns
		self::migrate_schema();
	}

	/**
	 * Migrate database schema - add missing columns to existing tables
	 */
	public static function migrate_schema() {
		global $wpdb;
		$conversations_table = $wpdb->prefix . 'apc_conversations';
		$messages_table = $wpdb->prefix . 'apc_messages';
		
		// === CONVERSATIONS TABLE MIGRATIONS ===
		
		// Check if status column exists
		$status_exists = $wpdb->get_results( "SHOW COLUMNS FROM $conversations_table LIKE 'status'" );
		if ( empty( $status_exists ) ) {
			$wpdb->query( "ALTER TABLE $conversations_table ADD COLUMN status ENUM('ai', 'human_requested', 'human_active', 'closed') DEFAULT 'ai' AFTER title" );
		}
		
		// Check if agent_id column exists
		$agent_id_exists = $wpdb->get_results( "SHOW COLUMNS FROM $conversations_table LIKE 'agent_id'" );
		if ( empty( $agent_id_exists ) ) {
			$wpdb->query( "ALTER TABLE $conversations_table ADD COLUMN agent_id BIGINT UNSIGNED NULL AFTER status" );
			$wpdb->query( "ALTER TABLE $conversations_table ADD KEY agent_id (agent_id)" );
		}
		
		// Check if human_requested_at column exists
		$human_requested_at_exists = $wpdb->get_results( "SHOW COLUMNS FROM $conversations_table LIKE 'human_requested_at'" );
		if ( empty( $human_requested_at_exists ) ) {
			$wpdb->query( "ALTER TABLE $conversations_table ADD COLUMN human_requested_at DATETIME NULL AFTER agent_id" );
		}
		
		// Check if human_joined_at column exists
		$human_joined_at_exists = $wpdb->get_results( "SHOW COLUMNS FROM $conversations_table LIKE 'human_joined_at'" );
		if ( empty( $human_joined_at_exists ) ) {
			$wpdb->query( "ALTER TABLE $conversations_table ADD COLUMN human_joined_at DATETIME NULL AFTER human_requested_at" );
		}
		
		// === MESSAGES TABLE MIGRATIONS ===
		
		// Check if sender_id column exists
		$sender_id_exists = $wpdb->get_results( "SHOW COLUMNS FROM $messages_table LIKE 'sender_id'" );
		if ( empty( $sender_id_exists ) ) {
			$wpdb->query( "ALTER TABLE $messages_table ADD COLUMN sender_id BIGINT UNSIGNED NULL AFTER role" );
			$wpdb->query( "ALTER TABLE $messages_table ADD KEY sender_id (sender_id)" );
		}
		
		// Check if role column has all needed values (user, assistant, agent, system)
		$role_info = $wpdb->get_results( "SHOW COLUMNS FROM $messages_table LIKE 'role'" );
		if ( ! empty( $role_info ) ) {
			$type = $role_info[0]->Type;
			// If 'agent' or 'system' not in ENUM, update it
			if ( strpos( $type, "'agent'" ) === false || strpos( $type, "'system'" ) === false ) {
				$wpdb->query( "ALTER TABLE $messages_table MODIFY COLUMN role ENUM('user', 'assistant', 'agent', 'system') NOT NULL" );
			}
		}
	}

	/**
	 * Create a new conversation
	 *
	 * @param int $user_id
	 * @param string $title
	 * @return int|false
	 */
	public static function create_conversation( $user_id, $title = '', $ip_address = null ) {
		global $wpdb;

		if ( empty( $title ) ) {
			$title = __( 'New Conversation', 'apc-free' ) . ' - ' . current_time( 'M d, Y h:i A' );
		}

		// Get IP address if not provided
		if ( empty( $ip_address ) ) {
			$ip_address = self::get_client_ip();
		}

		$result = $wpdb->insert(
			$wpdb->prefix . 'apc_conversations',
			array(
				'user_id'    => $user_id,
				'title'      => $title,
				'ip_address' => $ip_address,
			),
			array( '%d', '%s', '%s' )
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}
		return false;
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				// Handle multiple IPs (proxy chain)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Add message to conversation (Free version - no encryption)
	 *
	 * @param int $conversation_id
	 * @param string $role user|assistant|agent|system
	 * @param string $content
	 * @param int $tokens_used
	 * @param int $sender_id Optional sender ID (for agents)
	 * @return int|false
	 */
	public static function add_message( $conversation_id, $role, $content, $tokens_used = 0, $sender_id = null ) {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'apc_messages',
			array(
				'conversation_id' => $conversation_id,
				'role'            => $role,
				'content'         => $content,
				'tokens_used'     => $tokens_used,
				'sender_id'       => $sender_id,
			),
			array( '%d', '%s', '%s', '%d', '%d' )
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}
		return false;
	}

	/**
	 * Get conversation messages (Free version - no encryption/access control)
	 *
	 * @param int $conversation_id
	 * @param int $since_id Get messages after this ID (for polling)
	 * @param bool $skip_permission_check For compatibility with full version
	 * @return array
	 */
	public static function get_messages( $conversation_id, $since_id = 0, $skip_permission_check = false ) {
		global $wpdb;

		if ( $since_id > 0 ) {
			$query = $wpdb->prepare(
				"SELECT id, role, content, sender_id, created_at FROM {$wpdb->prefix}apc_messages WHERE conversation_id = %d AND id > %d ORDER BY created_at ASC",
				$conversation_id,
				$since_id
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT id, role, content, sender_id, created_at FROM {$wpdb->prefix}apc_messages WHERE conversation_id = %d ORDER BY created_at ASC",
				$conversation_id
			);
		}

		$results = $wpdb->get_results( $query, ARRAY_A );
		return $results ?: array();
	}

	/**
	 * Get user conversations
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function get_conversations( $user_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, created_at, updated_at FROM {$wpdb->prefix}apc_conversations WHERE user_id = %d ORDER BY updated_at DESC",
				$user_id
			),
			ARRAY_A
		);

		return $results ?: array();
	}

	/**
	 * Get all conversations (for admin - Free version, no access control needed)
	 *
	 * @return array
	 */
	public static function get_all_conversations() {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT c.id, c.user_id, c.title, c.status, c.ip_address, c.created_at, c.updated_at,
					u.display_name as user_name, u.user_email,
					a.display_name as agent_name
			FROM {$wpdb->prefix}apc_conversations c
			LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
			LEFT JOIN {$wpdb->users} a ON c.agent_id = a.ID
			ORDER BY c.updated_at DESC",
			ARRAY_A
		);

		// Process results to handle guest users
		if ( $results ) {
			foreach ( $results as &$row ) {
				// If user_id is negative, it's a guest
				if ( $row['user_id'] < 0 ) {
					$guest_id = abs( $row['user_id'] );
					$row['user_name'] = sprintf( __( 'Guest #%s', 'apc-free' ), substr( $guest_id, -6 ) );
					$row['user_email'] = $row['ip_address'] ? sprintf( __( 'IP: %s', 'apc-free' ), $row['ip_address'] ) : __( '(Guest User)', 'apc-free' );
				}
			}
		}

		return $results ?: array();
	}

	/**
	 * Delete conversation
	 *
	 * @param int $conversation_id
	 * @param int $user_id
	 * @return bool
	 */
	public static function delete_conversation( $conversation_id, $user_id ) {
		global $wpdb;

		// Verify ownership
		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}apc_conversations WHERE id = %d AND user_id = %d",
				$conversation_id,
				$user_id
			)
		);

		if ( ! $conversation ) {
			return false;
		}

		// Delete conversation (messages will cascade)
		$result = $wpdb->delete(
			$wpdb->prefix . 'apc_conversations',
			array( 'id' => $conversation_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Request human support for a conversation
	 *
	 * @param int $conversation_id
	 * @return bool
	 */
	public static function request_human_support( $conversation_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'apc_conversations',
			array(
				'status'             => 'human_requested',
				'human_requested_at' => current_time( 'mysql' ),
			),
			array( 'id' => $conversation_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Assign agent to conversation
	 *
	 * @param int $conversation_id
	 * @param int $agent_id
	 * @return bool
	 */
	public static function assign_agent( $conversation_id, $agent_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'apc_conversations',
			array(
				'status'          => 'human_active',
				'agent_id'        => $agent_id,
				'human_joined_at' => current_time( 'mysql' ),
			),
			array( 'id' => $conversation_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get pending support requests
	 *
	 * @return array
	 */
	public static function get_pending_requests() {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT c.id as conversation_id, c.user_id, c.title, c.status, c.human_requested_at as requested_time, c.created_at, c.updated_at,
					u.display_name as user_name, u.user_email,
					(SELECT content FROM {$wpdb->prefix}apc_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
			FROM {$wpdb->prefix}apc_conversations c
			LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
			WHERE c.status = 'human_requested'
			AND EXISTS (
				SELECT 1 FROM {$wpdb->prefix}apc_messages m 
				WHERE m.conversation_id = c.id 
				AND m.role = 'user'
			)
			ORDER BY c.human_requested_at ASC",
			ARRAY_A
		);

		return $results ?: array();
	}

	/**
	 * Get conversation details with status
	 *
	 * @param int $conversation_id
	 * @return array|null
	 */
	public static function get_conversation( $conversation_id ) {
		global $wpdb;

		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*, u.display_name as user_name, u.user_email,
						a.display_name as agent_name
				FROM {$wpdb->prefix}apc_conversations c
				LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
				LEFT JOIN {$wpdb->users} a ON c.agent_id = a.ID
				WHERE c.id = %d",
				$conversation_id
			),
			ARRAY_A
		);

		// Handle guest users
		if ( $conversation && $conversation['user_id'] < 0 ) {
			$guest_id = abs( $conversation['user_id'] );
			$conversation['user_name'] = sprintf( __( 'Guest #%s', 'apc-free' ), substr( $guest_id, -6 ) );
			$conversation['user_email'] = $conversation['ip_address'] ? sprintf( __( 'IP: %s', 'apc-free' ), $conversation['ip_address'] ) : __( '(Guest User)', 'apc-free' );
		}

		return $conversation;
	}

	/**
	 * Close conversation
	 *
	 * @param int $conversation_id
	 * @return bool
	 */
	public static function close_conversation( $conversation_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'apc_conversations',
			array( 'status' => 'closed' ),
			array( 'id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get agent's active conversations
	 *
	 * @param int $agent_id
	 * @return array
	 */
	public static function get_agent_conversations( $agent_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.id as conversation_id, c.user_id, c.title, c.status, c.human_joined_at as joined_time, c.updated_at,
						u.display_name as user_name, u.user_email,
						(SELECT content FROM {$wpdb->prefix}apc_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
				FROM {$wpdb->prefix}apc_conversations c
				LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
				WHERE c.agent_id = %d AND c.status = 'human_active'
				ORDER BY c.updated_at DESC",
				$agent_id
			),
			ARRAY_A
		);

		return $results ?: array();
	}

	/**
	 * Auto-close stale pending support requests
	 *
	 * @param int $timeout_minutes
	 * @return int
	 */
	public static function auto_close_stale_pending_requests( $timeout_minutes = 5 ) {
		global $wpdb;

		$cutoff_time = gmdate( 'Y-m-d H:i:s', time() - ( $timeout_minutes * 60 ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}apc_conversations 
				WHERE status = 'human_requested' 
				AND human_requested_at < %s",
				$cutoff_time
			),
			ARRAY_A
		);

		$count = 0;
		foreach ( $results as $row ) {
			if ( self::close_conversation( $row['id'] ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Create a predefined response
	 *
	 * @param array $data Response data
	 * @return int|false
	 */
	public static function create_predefined_response( $data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'apc_predefined_responses',
			array(
				'question'    => sanitize_text_field( $data['question'] ),
				'answer'      => wp_kses_post( $data['answer'] ),
				'keywords'    => sanitize_textarea_field( $data['keywords'] ?? '' ),
				'page_link'   => esc_url_raw( $data['page_link'] ?? '' ),
				'priority'    => isset( $data['priority'] ) ? (int) $data['priority'] : 0,
				'is_active'   => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
				'created_by'  => get_current_user_id(),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a predefined response
	 *
	 * @param int   $id Response ID
	 * @param array $data Response data
	 * @return bool
	 */
	public static function update_predefined_response( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$format = array();

		if ( isset( $data['question'] ) ) {
			$update_data['question'] = sanitize_text_field( $data['question'] );
			$format[] = '%s';
		}

		if ( isset( $data['answer'] ) ) {
			$update_data['answer'] = wp_kses_post( $data['answer'] );
			$format[] = '%s';
		}

		if ( isset( $data['keywords'] ) ) {
			$update_data['keywords'] = sanitize_textarea_field( $data['keywords'] );
			$format[] = '%s';
		}

		if ( isset( $data['page_link'] ) ) {
			$update_data['page_link'] = esc_url_raw( $data['page_link'] );
			$format[] = '%s';
		}

		if ( isset( $data['priority'] ) ) {
			$update_data['priority'] = (int) $data['priority'];
			$format[] = '%d';
		}

		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = (int) $data['is_active'];
			$format[] = '%d';
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'apc_predefined_responses',
			$update_data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete a predefined response
	 *
	 * @param int $id Response ID
	 * @return bool
	 */
	public static function delete_predefined_response( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->prefix . 'apc_predefined_responses',
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get predefined response by ID
	 *
	 * @param int $id Response ID
	 * @return array|null
	 */
	public static function get_predefined_response( $id ) {
		global $wpdb;

		$response = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apc_predefined_responses WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		return $response;
	}

	/**
	 * Get all predefined responses
	 *
	 * @param bool $active_only Get only active responses
	 * @return array
	 */
	public static function get_all_predefined_responses( $active_only = false ) {
		global $wpdb;

		$where = $active_only ? 'WHERE is_active = 1' : '';

		$responses = $wpdb->get_results(
			"SELECT r.*, u.display_name as creator_name 
			FROM {$wpdb->prefix}apc_predefined_responses r
			LEFT JOIN {$wpdb->users} u ON r.created_by = u.ID
			$where
			ORDER BY r.priority DESC, r.created_at DESC",
			ARRAY_A
		);

		return $responses ?: array();
	}

	/**
	 * Ensure agent status table exists and create if needed
	 */
	private static function ensure_agent_status_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apc_agent_status';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			// Table doesn't exist, create it
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "
				CREATE TABLE IF NOT EXISTS $table_name (
					agent_id BIGINT UNSIGNED NOT NULL,
					is_online TINYINT(1) DEFAULT 0,
					last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					PRIMARY KEY (agent_id)
				) $charset_collate;
			";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Set agent online/offline status
	 *
	 * @param int $agent_id
	 * @param bool $is_online
	 * @return bool
	 */
	public static function set_agent_status( $agent_id, $is_online ) {
		global $wpdb;

		// Ensure table exists
		self::ensure_agent_status_table();

		$result = $wpdb->replace(
			$wpdb->prefix . 'apc_agent_status',
			array(
				'agent_id'      => $agent_id,
				'is_online'     => $is_online ? 1 : 0,
				'last_activity' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Get agent status
	 *
	 * @param int $agent_id
	 * @return bool
	 */
	public static function get_agent_status( $agent_id ) {
		global $wpdb;

		// Ensure table exists
		self::ensure_agent_status_table();

		$status = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT is_online FROM {$wpdb->prefix}apc_agent_status WHERE agent_id = %d",
				$agent_id
			)
		);

		return (bool) $status;
	}

	/**
	 * Check if any agents are online
	 *
	 * @return bool
	 */
	public static function is_any_agent_online() {
		global $wpdb;

		// Ensure table exists
		self::ensure_agent_status_table();

		$online_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}apc_agent_status WHERE is_online = 1"
		);

		return (int) $online_count > 0;
	}

	/**
	 * Get all online agents
	 *
	 * @return array
	 */
	public static function get_online_agents() {
		global $wpdb;

		// Ensure table exists
		self::ensure_agent_status_table();

		$agents = $wpdb->get_results(
			"SELECT s.agent_id, u.display_name, u.user_email, s.last_activity
			FROM {$wpdb->prefix}apc_agent_status s
			LEFT JOIN {$wpdb->users} u ON s.agent_id = u.ID
			WHERE s.is_online = 1
			ORDER BY s.last_activity DESC",
			ARRAY_A
		);

		return $agents ?: array();
	}

	/**
	 * Auto-set agents offline after inactivity
	 *
	 * @param int $timeout_minutes Minutes of inactivity before auto-offline
	 * @return int Number of agents set offline
	 */
	public static function auto_offline_inactive_agents( $timeout_minutes = 30 ) {
		global $wpdb;

		$cutoff_time = date( 'Y-m-d H:i:s', strtotime( "-{$timeout_minutes} minutes" ) );

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}apc_agent_status 
				SET is_online = 0 
				WHERE is_online = 1 
				AND last_activity < %s",
				$cutoff_time
			)
		);

		return $result !== false ? $result : 0;
	}

	/**
	 * Find matching predefined response by keywords
	 *
	 * @param string $user_message User's message
	 * @return array|null
	 */
	public static function find_matching_response( $user_message ) {
		global $wpdb;

		$user_message = strtolower( trim( $user_message ) );

		// Get all active responses ordered by priority
		$responses = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}apc_predefined_responses 
			WHERE is_active = 1 
			ORDER BY priority DESC, created_at DESC",
			ARRAY_A
		);

		// Check each response for keyword matches
		foreach ( $responses as $response ) {
			// Check exact question match first
			if ( strtolower( $response['question'] ) === $user_message ) {
				return $response;
			}

			// Check keyword matches
			if ( ! empty( $response['keywords'] ) ) {
				$keywords = array_map( 'trim', explode( ',', strtolower( $response['keywords'] ) ) );
				foreach ( $keywords as $keyword ) {
					if ( ! empty( $keyword ) && strpos( $user_message, $keyword ) !== false ) {
						return $response;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Reset database - Drop all plugin tables
	 * WARNING: This will delete ALL conversation history, messages, forms, and responses!
	 *
	 * @return array Array with status and message
	 */
	public static function reset_database() {
		global $wpdb;

		$tables_to_drop = array(
			$wpdb->prefix . 'apc_form_responses',      // Must be dropped first (has FKs to others)
			$wpdb->prefix . 'apc_form_submissions',    // Must be dropped before forms
			$wpdb->prefix . 'apc_form_fields',         // Must be dropped before forms
			$wpdb->prefix . 'apc_forms',
			$wpdb->prefix . 'apc_messages',            // Must be dropped before conversations
			$wpdb->prefix . 'apc_conversations',
			$wpdb->prefix . 'apc_predefined_responses',
			$wpdb->prefix . 'apc_agent_status',
		);

		$dropped_count = 0;
		$errors = array();

		foreach ( $tables_to_drop as $table ) {
			// Safely drop each table if it exists using backticks for identifier
			$drop_query = 'DROP TABLE IF EXISTS `' . str_replace( '`', '``', $table ) . '`';
			$result = $wpdb->query( $drop_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( $result !== false ) {
				$dropped_count++;
			} else {
				$errors[] = $table . ': ' . $wpdb->last_error;
				error_log( 'Error dropping table ' . $table . ': ' . $wpdb->last_error );
			}
		}

		// Recreate tables
		try {
			self::create_tables();
			return array(
				'success' => true,
				'dropped' => $dropped_count,
				'message' => sprintf( __( 'Successfully dropped %d tables and recreated database schema.', 'apc-free' ), $dropped_count ),
			);
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => __( 'Tables dropped but failed to recreate schema: ', 'apc-free' ) . $e->getMessage(),
			);
		}
	}
}
