// Admin JavaScript

jQuery(document).ready(function($) {
	// API Key validation
	$('#apc_openai_api_key').on('change', function() {
		var apiKey = $(this).val();
		if (apiKey.length > 0) {
			$.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'apc_validate_api_key',
					api_key: apiKey
				},
				success: function(response) {
					if (response.success) {
						console.log('API Key is valid');
					}
				}
			});
		}
	});

	// Load conversations list
	if ($('#apc-conversations-list').length) {
		loadAllConversations();
	}

	function loadAllConversations() {
		$.ajax({
			url: apcAdmin.restUrl + '/admin/conversations',
			method: 'GET',
			headers: {
				'X-WP-Nonce': apcAdmin.restNonce
			},
			success: function(response) {
				if (response.success && response.conversations.length > 0) {
					displayConversations(response.conversations);
				} else {
					$('#apc-conversations-list').html('<p>No conversations found</p>');
				}
			},
			error: function(xhr) {
				console.error('Failed to load conversations', xhr);
				var errorMsg = 'Failed to load conversations';
				if (xhr.status === 403) {
					errorMsg = 'You do not have permission to view conversations';
				}
				$('#apc-conversations-list').html('<p style="color: red;">' + errorMsg + '</p>');
			}
		});
	}

	function displayConversations(conversations) {
		var html = '<table>';
		html += '<thead><tr>';
		html += '<th>ID</th>';
		html += '<th>User</th>';
		html += '<th>Status</th>';
		html += '<th>Agent</th>';
		html += '<th>Last Updated</th>';
		html += '</tr></thead><tbody>';
		
		$.each(conversations, function(i, conv) {
			html += '<tr class="conv-row" data-id="' + conv.id + '">';
			html += '<td>' + conv.id + '</td>';
			html += '<td>' + (conv.user_name || 'Unknown') + '<br><small>' + (conv.user_email || '') + '</small></td>';
			html += '<td><span class="apc-status-badge ' + conv.status + '">' + conv.status + '</span></td>';
			html += '<td>' + (conv.agent_name || '-') + '</td>';
			html += '<td>' + formatDate(conv.updated_at) + '</td>';
			html += '</tr>';
		});
		
		html += '</tbody></table>';
		$('#apc-conversations-list').html(html);

		// Click handler to view conversation details
		$('.conv-row').on('click', function() {
			var convId = $(this).data('id');
			loadConversationDetails(convId);
			$('.conv-row').removeClass('selected');
			$(this).addClass('selected');
		});
	}

	function loadConversationDetails(conversationId) {
		$('#apc-conversation-details').html('<p>Loading conversation details...</p>');

		$.ajax({
			url: apcAdmin.restUrl + '/admin/conversations/' + conversationId,
			method: 'GET',
			headers: {
				'X-WP-Nonce': apcAdmin.restNonce
			},
			success: function(response) {
				if (response.success) {
					displayConversationDetails(response.conversation, response.messages);
				} else {
					$('#apc-conversation-details').html('<p>Failed to load conversation details</p>');
				}
			},
			error: function() {
				$('#apc-conversation-details').html('<p style="color: red;">Error loading conversation details</p>');
			}
		});
	}

	function displayConversationDetails(conversation, messages) {
		var html = '<div class="apc-conversation-info">';
		html += '<h3>Conversation #' + conversation.id + '</h3>';
		html += '<p><strong>User:</strong> ' + (conversation.user_name || 'Unknown') + ' (' + (conversation.user_email || '') + ')</p>';
		html += '<p><strong>Status:</strong> <span class="apc-status-badge ' + conversation.status + '">' + formatStatus(conversation.status) + '</span></p>';
		html += '<p><strong>Agent:</strong> ' + (conversation.agent_name || 'None assigned') + '</p>';
		html += '<p><strong>Created:</strong> ' + formatDate(conversation.created_at) + '</p>';
		html += '<p><strong>Last Updated:</strong> ' + formatDate(conversation.updated_at) + '</p>';
		html += '</div>';

		html += '<div class="apc-messages-list">';
		html += '<h4>💬 Messages (' + messages.length + ')</h4>';

		if (messages.length > 0) {
			$.each(messages, function(i, msg) {
				var roleIcon = getRoleIcon(msg.role);
				var content = msg.content;
				
				// Clean up agent-only prefix for display
				if (content && content.startsWith('[AGENT-ONLY]')) {
					content = content.substring('[AGENT-ONLY]'.length);
				}
				
				html += '<div class="apc-message-item ' + msg.role + '">';
				html += '<div class="apc-message-header">';
				html += '<span>' + roleIcon + ' ' + formatRole(msg.role) + '</span>';
				html += '<span>' + formatDateTime(msg.created_at) + '</span>';
				html += '</div>';
				html += '<div class="apc-message-content">' + escapeHtml(content) + '</div>';
				html += '</div>';
			});
		} else {
			html += '<p>No messages in this conversation yet.</p>';
		}

		html += '</div>';

		$('#apc-conversation-details').html(html);
	}

	function formatStatus(status) {
		var statusMap = {
			'ai': 'AI',
			'human_requested': 'Agent Requested',
			'human_active': 'Agent Active',
			'closed': 'Closed'
		};
		return statusMap[status] || status;
	}

	function formatRole(role) {
		var roleMap = {
			'user': 'User',
			'assistant': 'AI Assistant',
			'agent': 'Support Agent',
			'system': 'System'
		};
		return roleMap[role] || role;
	}

	function getRoleIcon(role) {
		var iconMap = {
			'user': '👤',
			'assistant': '🤖',
			'agent': '👨‍💼',
			'system': 'ℹ️'
		};
		return iconMap[role] || '💬';
	}

	function formatDate(dateString) {
		if (!dateString) return '-';
		var date = new Date(dateString.replace(' ', 'T'));
		return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
	}

	function formatDateTime(dateString) {
		if (!dateString) return '-';
		var date = new Date(dateString.replace(' ', 'T'));
		var now = new Date();
		var diff = now - date;
		var minutes = Math.floor(diff / 60000);
		var hours = Math.floor(diff / 3600000);
		var days = Math.floor(diff / 86400000);

		if (minutes < 1) return 'Just now';
		if (minutes < 60) return minutes + 'm ago';
		if (hours < 24) return hours + 'h ago';
		if (days < 7) return days + 'd ago';
		
		return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// **AGENT STATUS TOGGLE**
	var $statusToggle = $('#apc-agent-status-toggle');
	var $statusText = $('#apc-status-text');

	// Load current agent status
	function loadAgentStatus() {
		$.ajax({
			url: apcAdmin.restUrl + '/agent/status',
			method: 'GET',
			headers: {
				'X-WP-Nonce': apcAdmin.restNonce
			},
			success: function(response) {
				if (response.is_online) {
					$statusToggle.prop('checked', true);
					$statusText.text('Online').css('color', '#28a745');
				} else {
					$statusToggle.prop('checked', false);
					$statusText.text('Offline').css('color', '#dc3545');
				}
			},
			error: function(xhr) {
				console.error('Failed to load agent status', xhr);
			}
		});
	}

	// Toggle agent status
	$statusToggle.on('change', function() {
		var isOnline = $(this).is(':checked');
		var self = this;
		
		console.log('Toggling agent status to:', isOnline);
		
		$.ajax({
			url: apcAdmin.restUrl + '/agent/status',
			method: 'POST',
			headers: {
				'X-WP-Nonce': apcAdmin.restNonce,
				'Content-Type': 'application/json'
			},
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify({
				is_online: isOnline
			}),
			success: function(response) {
				console.log('Agent status response:', response);
				console.log('Response type:', typeof response);
				console.log('is_online value:', response.is_online);
				
				// Update text and color based on what was sent (not the response)
				if (isOnline) {
					$statusText.text('Online').css('color', '#28a745');
					$statusToggle.prop('checked', true);
					console.log('✓ Status is now Online');
				} else {
					$statusText.text('Offline').css('color', '#dc3545');
					$statusToggle.prop('checked', false);
					console.log('✓ Status is now Offline');
				}
			},
			error: function(xhr, status, error) {
				console.error('Failed to update agent status:', status, error);
				console.error('Response text:', xhr.responseText);
				console.error('Status code:', xhr.status);
				
				// Revert toggle on error
				$statusToggle.prop('checked', !isOnline);
				
				// Show error message  
				alert('Failed to update status (Error ' + xhr.status + '). Please try again.');
			}
		});
	});

	// Load status on page load if on support page
	if ($statusToggle.length) {
		loadAgentStatus();
	}

	// **DEBUG FUNCTIONS**
	window.apcDebug = {
		checkAgentStatus: function() {
			console.log('[APC Debug] Checking agent status...');
			$.ajax({
				url: apcAdmin.restUrl + '/agent/status',
				method: 'GET',
				headers: {
					'X-WP-Nonce': apcAdmin.restNonce
				},
				success: function(response) {
					console.log('[APC Debug] Current Agent Status:', response);
				},
				error: function(xhr) {
					console.error('[APC Debug] Error:', xhr.status, xhr.responseText);
				}
			});
		},
		
		checkAllAgentsOnline: function() {
			console.log('[APC Debug] Checking if any agents are online...');
			$.ajax({
				url: apcAdmin.restUrl + '/agents/online',
				method: 'GET',
				success: function(response) {
					console.log('[APC Debug] Agents Online Response:', response);
					console.log('[APC Debug] agents_available:', response.agents_available);
				},
				error: function(xhr) {
					console.error('[APC Debug] Error:', xhr.status, xhr.responseText);
				}
			});
		},
		
		getDebugInfo: function() {
			console.log('[APC Debug] Fetching full debug info...');
			$.ajax({
				url: apcAdmin.restUrl + '/debug/agent-status',
				method: 'GET',
				success: function(response) {
					console.log('[APC Debug] Full Debug Info:', response);
					console.log('[APC Debug] Table exists:', response.table_exists);
					console.log('[APC Debug] Current user:', response.current_user_login, '(ID: ' + response.current_user_id + ')');
					console.log('[APC Debug] Current user status:', response.current_user_status);
					console.log('[APC Debug] Current user raw DB row:', response.current_user_raw);
					console.log('[APC Debug] All agents:', response.all_agents);
					console.log('[APC Debug] Any agents online:', response.any_agents_online);
					
					// Print formatted table
					console.table(response.all_agents);
				},
				error: function(xhr) {
					console.error('[APC Debug] Error:', xhr.status, xhr.responseText);
				}
			});
		},
		
		setAgentStatus: function(isOnline) {
			console.log('[APC Debug] Setting agent status to:', isOnline);
			$.ajax({
				url: apcAdmin.restUrl + '/agent/status',
				method: 'POST',
				headers: {
					'X-WP-Nonce': apcAdmin.restNonce,
					'Content-Type': 'application/json'
				},
				contentType: 'application/json',
				dataType: 'json',
				data: JSON.stringify({ is_online: isOnline }),
				success: function(response) {
					console.log('[APC Debug] Status set successfully:', response);
					// Verify it was saved
					setTimeout(function() {
						window.apcDebug.getDebugInfo();
					}, 1000);
				},
				error: function(xhr) {
					console.error('[APC Debug] Error:', xhr.status, xhr.responseText);
				}
			});
		},
		
		showInstructions: function() {
			console.log('%cAPC Debug Helper Instructions', 'font-size: 14px; font-weight: bold; background: #007bff; color: white; padding: 5px 10px;');
			console.log('Use these functions in the browser console:');
			console.log('  apcDebug.getDebugInfo()        - Get full debug info about agent status');
			console.log('  apcDebug.checkAgentStatus()    - Check current agent\'s status');
			console.log('  apcDebug.checkAllAgentsOnline() - Check if any agents are online');
			console.log('  apcDebug.setAgentStatus(true)  - Set current agent to ONLINE');
			console.log('  apcDebug.setAgentStatus(false) - Set current agent to OFFLINE');
			console.log('');
			console.log('Endpoints to test directly:');
			console.log('  GET  ' + apcAdmin.restUrl + '/debug/agent-status');
			console.log('  GET  ' + apcAdmin.restUrl + '/agents/online');
			console.log('  GET  ' + apcAdmin.restUrl + '/agent/status');
			console.log('  POST ' + apcAdmin.restUrl + '/agent/status (with body {"is_online": true/false})');
		}
	};

	// Show help on page load
	console.log('%c[APC] Debug Helper Loaded', 'color: #007bff; font-weight: bold;');
	console.log('Type: apcDebug.showInstructions() for help');
});
