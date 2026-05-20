<?php
/**
 * Forms Manager - Handles conversational forms operations
 *
 * @package    AI_Powered_Chat
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Forms {

	/**
	 * Create a new form
	 *
	 * @param array $data Form data
	 * @return int|false Form ID or false on failure
	 */
	public static function create_form( $data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'apc_forms',
			array(
				'name'                => sanitize_text_field( $data['name'] ),
				'description'         => sanitize_textarea_field( $data['description'] ?? '' ),
				'trigger_type'        => sanitize_text_field( $data['trigger_type'] ?? 'command' ),
				'trigger_command'     => sanitize_text_field( $data['trigger_command'] ?? '' ),
				'trigger_button_text' => sanitize_text_field( $data['trigger_button_text'] ?? '' ),
				'workflow_keywords'   => sanitize_textarea_field( $data['workflow_keywords'] ?? '' ),
				'email_notification'  => isset( $data['email_notification'] ) ? (int) $data['email_notification'] : 0,
				'email_recipients'    => sanitize_textarea_field( $data['email_recipients'] ?? '' ),
				'is_active'           => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
				'created_by'          => get_current_user_id(),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a form
	 *
	 * @param int   $form_id Form ID
	 * @param array $data    Form data
	 * @return bool
	 */
	public static function update_form( $form_id, $data ) {
		global $wpdb;

		$update_data = array();
		$format = array();

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $data['name'] );
			$format[] = '%s';
		}

		if ( isset( $data['description'] ) ) {
			$update_data['description'] = sanitize_textarea_field( $data['description'] );
			$format[] = '%s';
		}

		if ( isset( $data['trigger_type'] ) ) {
			$update_data['trigger_type'] = sanitize_text_field( $data['trigger_type'] );
			$format[] = '%s';
		}

		if ( isset( $data['trigger_command'] ) ) {
			$update_data['trigger_command'] = sanitize_text_field( $data['trigger_command'] );
			$format[] = '%s';
		}

		if ( isset( $data['trigger_button_text'] ) ) {
			$update_data['trigger_button_text'] = sanitize_text_field( $data['trigger_button_text'] );
			$format[] = '%s';
		}

		if ( isset( $data['workflow_keywords'] ) ) {
			$update_data['workflow_keywords'] = sanitize_textarea_field( $data['workflow_keywords'] );
			$format[] = '%s';
		}

		if ( isset( $data['email_notification'] ) ) {
			$update_data['email_notification'] = (int) $data['email_notification'];
			$format[] = '%d';
		}

		if ( isset( $data['email_recipients'] ) ) {
			$update_data['email_recipients'] = sanitize_textarea_field( $data['email_recipients'] );
			$format[] = '%s';
		}

		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = (int) $data['is_active'];
			$format[] = '%d';
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'apc_forms',
			$update_data,
			array( 'id' => $form_id ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete a form and all its fields
	 *
	 * @param int $form_id Form ID
	 * @return bool
	 */
	public static function delete_form( $form_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->prefix . 'apc_forms',
			array( 'id' => $form_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get form by ID
	 *
	 * @param int $form_id Form ID
	 * @return array|null
	 */
	public static function get_form( $form_id ) {
		global $wpdb;

		$form = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apc_forms WHERE id = %d",
				$form_id
			),
			ARRAY_A
		);

		return $form;
	}

	/**
	 * Get all forms
	 *
	 * @return array
	 */
	public static function get_all_forms() {
		global $wpdb;

		$forms = $wpdb->get_results(
			"SELECT f.*, u.display_name as creator_name 
			FROM {$wpdb->prefix}apc_forms f
			LEFT JOIN {$wpdb->users} u ON f.created_by = u.ID
			ORDER BY f.created_at DESC",
			ARRAY_A
		);

		return $forms ?: array();
	}

	/**
	 * Get form by trigger command
	 *
	 * @param string $command Trigger command
	 * @return array|null
	 */
	public static function get_form_by_command( $command ) {
		global $wpdb;

		$form = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apc_forms WHERE trigger_command = %s AND is_active = 1",
				$command
			),
			ARRAY_A
		);

		return $form;
	}

	/**
	 * Add field to form
	 *
	 * @param int   $form_id Form ID
	 * @param array $data    Field data
	 * @return int|false Field ID or false on failure
	 */
	public static function add_field( $form_id, $data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'apc_form_fields',
			array(
				'form_id'           => $form_id,
				'field_order'       => (int) ( $data['field_order'] ?? 0 ),
				'field_type'        => sanitize_text_field( $data['field_type'] ),
				'field_name'        => sanitize_text_field( $data['field_name'] ),
				'field_label'       => sanitize_text_field( $data['field_label'] ),
				'field_placeholder' => sanitize_text_field( $data['field_placeholder'] ?? '' ),
				'field_options'     => wp_json_encode( $data['field_options'] ?? array() ),
				'is_required'       => isset( $data['is_required'] ) ? (int) $data['is_required'] : 0,
				'conditional_logic' => wp_json_encode( $data['conditional_logic'] ?? array() ),
				'validation_rules'  => wp_json_encode( $data['validation_rules'] ?? array() ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update field
	 *
	 * @param int   $field_id Field ID
	 * @param array $data     Field data
	 * @return bool
	 */
	public static function update_field( $field_id, $data ) {
		global $wpdb;

		$update_data = array();
		$format = array();

		if ( isset( $data['field_order'] ) ) {
			$update_data['field_order'] = (int) $data['field_order'];
			$format[] = '%d';
		}

		if ( isset( $data['field_type'] ) ) {
			$update_data['field_type'] = sanitize_text_field( $data['field_type'] );
			$format[] = '%s';
		}

		if ( isset( $data['field_name'] ) ) {
			$update_data['field_name'] = sanitize_text_field( $data['field_name'] );
			$format[] = '%s';
		}

		if ( isset( $data['field_label'] ) ) {
			$update_data['field_label'] = sanitize_text_field( $data['field_label'] );
			$format[] = '%s';
		}

		if ( isset( $data['field_placeholder'] ) ) {
			$update_data['field_placeholder'] = sanitize_text_field( $data['field_placeholder'] );
			$format[] = '%s';
		}

		if ( isset( $data['field_options'] ) ) {
			$update_data['field_options'] = wp_json_encode( $data['field_options'] );
			$format[] = '%s';
		}

		if ( isset( $data['is_required'] ) ) {
			$update_data['is_required'] = (int) $data['is_required'];
			$format[] = '%d';
		}

		if ( isset( $data['conditional_logic'] ) ) {
			$update_data['conditional_logic'] = wp_json_encode( $data['conditional_logic'] );
			$format[] = '%s';
		}

		if ( isset( $data['validation_rules'] ) ) {
			$update_data['validation_rules'] = wp_json_encode( $data['validation_rules'] );
			$format[] = '%s';
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'apc_form_fields',
			$update_data,
			array( 'id' => $field_id ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete field
	 *
	 * @param int $field_id Field ID
	 * @return bool
	 */
	public static function delete_field( $field_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->prefix . 'apc_form_fields',
			array( 'id' => $field_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get form fields
	 *
	 * @param int $form_id Form ID
	 * @return array
	 */
	public static function get_form_fields( $form_id ) {
		global $wpdb;

		$fields = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apc_form_fields WHERE form_id = %d ORDER BY field_order ASC",
				$form_id
			),
			ARRAY_A
		);

		// Decode JSON fields
		if ( $fields ) {
			foreach ( $fields as &$field ) {
				$field['field_options'] = json_decode( $field['field_options'], true ) ?: array();
				$field['conditional_logic'] = json_decode( $field['conditional_logic'], true ) ?: array();
				$field['validation_rules'] = json_decode( $field['validation_rules'], true ) ?: array();
			}
		}

		return $fields ?: array();
	}

	/**
	 * Save form submission
	 *
	 * @param int   $form_id         Form ID
	 * @param int   $conversation_id Conversation ID
	 * @param array $responses       Field responses
	 * @return int|false Submission ID or false on failure
	 */
	public static function save_submission( $form_id, $conversation_id, $responses ) {
		global $wpdb;

		$user_id = get_current_user_id();

		// Create submission record
		$result = $wpdb->insert(
			$wpdb->prefix . 'apc_form_submissions',
			array(
				'form_id'         => $form_id,
				'conversation_id' => $conversation_id,
				'user_id'         => $user_id ?: null,
			),
			array( '%d', '%d', '%d' )
		);

		if ( ! $result ) {
			return false;
		}

		$submission_id = $wpdb->insert_id;

		// Save individual responses
		foreach ( $responses as $field_id => $value ) {
			$wpdb->insert(
				$wpdb->prefix . 'apc_form_responses',
				array(
					'submission_id' => $submission_id,
					'field_id'      => $field_id,
					'field_value'   => is_array( $value ) ? wp_json_encode( $value ) : $value,
				),
				array( '%d', '%d', '%s' )
			);
		}

		// Send email notification if enabled
		$form = self::get_form( $form_id );
		if ( $form && $form['email_notification'] ) {
			self::send_submission_notification( $form, $submission_id, $responses );
		}

		return $submission_id;
	}

	/**
	 * Get form submissions
	 *
	 * @param int $form_id Form ID
	 * @return array
	 */
	public static function get_submissions( $form_id ) {
		global $wpdb;

		$submissions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, u.display_name as user_name 
				FROM {$wpdb->prefix}apc_form_submissions s
				LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
				WHERE s.form_id = %d
				ORDER BY s.submitted_at DESC",
				$form_id
			),
			ARRAY_A
		);

		return $submissions ?: array();
	}

	/**
	 * Get submission responses
	 *
	 * @param int $submission_id Submission ID
	 * @return array
	 */
	public static function get_submission_responses( $submission_id ) {
		global $wpdb;

		$responses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, f.field_name, f.field_label, f.field_type 
				FROM {$wpdb->prefix}apc_form_responses r
				LEFT JOIN {$wpdb->prefix}apc_form_fields f ON r.field_id = f.id
				WHERE r.submission_id = %d",
				$submission_id
			),
			ARRAY_A
		);

		return $responses ?: array();
	}

	/**
	 * Check if field should be shown based on conditional logic
	 *
	 * @param array $field          Field data
	 * @param array $current_values Current form values
	 * @return bool
	 */
	public static function evaluate_conditional_logic( $field, $current_values ) {
		if ( empty( $field['conditional_logic'] ) || ! is_array( $field['conditional_logic'] ) ) {
			return true; // No conditions, always show
		}

		$logic = $field['conditional_logic'];

		// Check if conditions are met
		if ( isset( $logic['conditions'] ) && is_array( $logic['conditions'] ) ) {
			$logic_type = $logic['logic_type'] ?? 'all'; // 'all' (AND) or 'any' (OR)
			$results = array();

			foreach ( $logic['conditions'] as $condition ) {
				$field_id = $condition['field_id'] ?? null;
				$operator = $condition['operator'] ?? 'equals';
				$value = $condition['value'] ?? '';

				if ( ! $field_id || ! isset( $current_values[ $field_id ] ) ) {
					$results[] = false;
					continue;
				}

				$current_value = $current_values[ $field_id ];

				switch ( $operator ) {
					case 'equals':
						$results[] = $current_value == $value;
						break;
					case 'not_equals':
						$results[] = $current_value != $value;
						break;
					case 'contains':
						$results[] = stripos( $current_value, $value ) !== false;
						break;
					case 'not_contains':
						$results[] = stripos( $current_value, $value ) === false;
						break;
					case 'is_empty':
						$results[] = empty( $current_value );
						break;
					case 'is_not_empty':
						$results[] = ! empty( $current_value );
						break;
					default:
						$results[] = false;
				}
			}

			// Apply logic type
			if ( $logic_type === 'all' ) {
				return ! in_array( false, $results, true );
			} else {
				return in_array( true, $results, true );
			}
		}

		return true;
	}

	/**
	 * Send email notification for form submission
	 *
	 * @param array $form          Form data
	 * @param int   $submission_id Submission ID
	 * @param array $responses     Form responses
	 * @return bool
	 */
	public static function send_submission_notification( $form, $submission_id, $responses ) {
		if ( empty( $form['email_recipients'] ) ) {
			return false;
		}

		$recipients = array_map( 'trim', explode( ',', $form['email_recipients'] ) );
		/* translators: %s: form name */
		$subject = sprintf( __( 'New Form Submission: %s', 'apc-free' ), $form['name'] );
		
		// Build email body
		/* translators: %s: form name */
		$message = sprintf( __( 'A new submission has been received for the form: %s', 'apc-free' ), $form['name'] ) . "\n\n";
		$message .= __( 'Submission Details:', 'apc-free' ) . "\n";
		$message .= str_repeat( '-', 50 ) . "\n\n";

		// Get field responses
		$fields = self::get_form_fields( $form['id'] );
		foreach ( $fields as $field ) {
			if ( isset( $responses[ $field['field_name'] ] ) ) {
				$value = $responses[ $field['field_name'] ];
				$message .= $field['field_label'] . ": " . $value . "\n";
			}
		}

		$message .= "\n" . str_repeat( '-', 50 ) . "\n";
		/* translators: %d: submission ID */
		$message .= sprintf( __( 'Submission ID: %d', 'apc-free' ), $submission_id ) . "\n";
		/* translators: %s: submission timestamp */
		$message .= sprintf( __( 'Submitted at: %s', 'apc-free' ), current_time( 'mysql' ) ) . "\n";

		// Send email
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		
		foreach ( $recipients as $recipient ) {
			wp_mail( $recipient, $subject, $message, $headers );
		}

		return true;
	}

	/**
	 * Get form analytics
	 *
	 * @param int $form_id Form ID
	 * @return array
	 */
	public static function get_form_analytics( $form_id ) {
		global $wpdb;

		// Total submissions
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apc_form_submissions WHERE form_id = %d",
				$form_id
			)
		);

		// Submissions this month
		$this_month = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apc_form_submissions 
				WHERE form_id = %d 
				AND YEAR(submitted_at) = YEAR(CURDATE()) 
				AND MONTH(submitted_at) = MONTH(CURDATE())",
				$form_id
			)
		);

		// Submissions today
		$today = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apc_form_submissions 
				WHERE form_id = %d 
				AND DATE(submitted_at) = CURDATE()",
				$form_id
			)
		);

		// Last 7 days data
		$last_7_days = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(submitted_at) as date, COUNT(*) as count 
				FROM {$wpdb->prefix}apc_form_submissions 
				WHERE form_id = %d 
				AND submitted_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
				GROUP BY DATE(submitted_at)
				ORDER BY date ASC",
				$form_id
			),
			ARRAY_A
		);

		return array(
			'total'       => intval( $total ),
			'this_month'  => intval( $this_month ),
			'today'       => intval( $today ),
			'last_7_days' => $last_7_days,
		);
	}

	/**
	 * Export submissions to CSV
	 *
	 * @param int $form_id Form ID
	 * @return string CSV content
	 */
	public static function export_submissions_csv( $form_id ) {
		$form = self::get_form( $form_id );
		if ( ! $form ) {
			return '';
		}

		$fields = self::get_form_fields( $form_id );
		$submissions = self::get_submissions( $form_id );

		// Build CSV header
		$header = array( 'Submission ID', 'Submitted At', 'User' );
		foreach ( $fields as $field ) {
			$header[] = $field['field_label'];
		}

		// Start CSV
		$csv = array();
		$csv[] = $header;

		// Add data rows
		foreach ( $submissions as $submission ) {
			$responses = self::get_submission_responses( $submission['id'] );
			$response_map = array();
			foreach ( $responses as $response ) {
				$response_map[ $response['field_id'] ] = $response['field_value'];
			}

			$row = array(
				$submission['id'],
				$submission['submitted_at'],
				$submission['user_name'] ?: 'Guest',
			);

			foreach ( $fields as $field ) {
				$row[] = $response_map[ $field['id'] ] ?? '';
			}

			$csv[] = $row;
		}

		// Convert to CSV string
		ob_start();
		$output = fopen( 'php://output', 'w' );
		foreach ( $csv as $row ) {
			fputcsv( $output, $row );
		}
		fclose( $output );
		return ob_get_clean();
	}
}
