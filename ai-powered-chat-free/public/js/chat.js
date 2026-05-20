/**
 * Chat Widget JavaScript
 *
 * Handles the frontend chat widget interaction, message sending,
 * and conversation management for the AI Powered Chat plugin.
 *
 * @package    AI_Powered_Chat
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

(function($) {

	var APC_Chat = {
		currentConversationId: null,
		isOpen: false,
		isLoading: false,
		conversationStatus: 'ai',
		lastMessageId: 0,
		pollInterval: null,
		guestSessionId: null,  // NEW: unique ID for guest sessions

		// Helper function to check if user can use chat
		canUseChat: function() {
			return apcChat.isLoggedIn || apcChat.allowGuests;
		},

		// NEW: Generate consistent guest session ID (persists in browser sessionStorage)
		getGuestSessionId: function() {
			if (!this.guestSessionId) {
				// Try to load from sessionStorage first
				if (typeof(Storage) !== "undefined") {
					this.guestSessionId = sessionStorage.getItem('apc_guest_session_id');
				}
				
				// If no existing session ID, generate new one
				if (!this.guestSessionId) {
					// Generate a random ID (this will be consistent within this browser session)
					this.guestSessionId = 'guest_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
					
					// Store in sessionStorage so it persists across page reloads in same session
					if (typeof(Storage) !== "undefined") {
						sessionStorage.setItem('apc_guest_session_id', this.guestSessionId);
					}
					
					console.log('[APC] Generated new guest session ID:', this.guestSessionId);
				} else {
					console.log('[APC] Reusing existing guest session ID:', this.guestSessionId);
				}
			}
			return this.guestSessionId;
		},

		init: function() {
			console.log('APC Chat initializing...', apcChat);
			
			this.cacheDom();
			console.log('Bubble element:', this.$bubble.length, 'Widget element:', this.$widget.length);
			
			// Initialize guest session ID if needed
			if (!apcChat.isLoggedIn && apcChat.allowGuests) {
				var guestId = this.getGuestSessionId();
				console.log('[APC] Guest session ID initialized:', guestId);
			}
			
			this.bindEvents();
			this.checkAgentAvailability();
			
			// Check agent availability every 30 seconds
			var self = this;
			setInterval(function() {
				self.checkAgentAvailability();
			}, 30000);
			
			// Don't create conversation automatically - wait for user to open chat
		},

		cacheDom: function() {
			this.$widget = $('#apc-chat-widget');
			this.$bubble = $('#apc-chat-bubble');
			this.$close = $('#apc-chat-close');
			this.$messages = $('#apc-chat-messages');
			this.$input = $('#apc-chat-input');
			this.$sendBtn = $('#apc-chat-send');
			this.$container = $('#apc-chat-container');
			this.$humanBtn = $('#apc-request-human');
			this.$closeBtn = $('#apc-close-chat');
			this.$statusIndicator = $('#apc-status-indicator');
		},

		bindEvents: function() {
		var self = this;

		// Open chat bubble
		this.$bubble.on('click', function(e) {
			e.preventDefault();
			console.log('Bubble clicked!');
			self.openWidget();
		});

		// Close chat widget
		this.$close.on('click', function(e) {
			e.preventDefault();
			console.log('Close clicked!');
			self.closeWidget();
		});
		
		if (!this.canUseChat()) {
			return;
		}

		// Send message
		this.$sendBtn.on('click', function() {
			self.sendMessage();
		});

		// Send on Enter key
		this.$input.on('keypress', function(e) {
			if (e.which === 13 && !e.shiftKey) {
				e.preventDefault();
				self.sendMessage();
			}
		});

		// Auto-resize textarea
		this.$input.on('input', function() {
			this.style.height = 'auto';
			this.style.height = Math.min(this.scrollHeight, 80) + 'px';
		});

		// Request human support
		this.$humanBtn.on('click', function() {
			self.requestHumanSupport();
		});

		// Close conversation
		this.$closeBtn.on('click', function() {
			self.closeConversation();
		});

		// Quick action buttons
		$(document).on('click', '.apc-quick-action-btn', function() {
			var type = $(this).data('type');
			var command = $(this).data('command');
			var formId = $(this).data('form-id');

			if (type === 'command' && command) {
				// Send command as a message
				self.$input.val(command);
				self.sendMessage();
			} else if (type === 'form' && formId) {
				// Load form directly
				self.loadFormById(formId);
			}
		});
	},

	checkAgentAvailability: function() {
		var self = this;
		
		console.log('[APC] Checking agent availability...');
		console.log('[APC] Debug endpoint available at: ' + apcChat.restUrl + '/debug/agent-status');
		
		$.ajax({
			url: apcChat.restUrl + '/agents/online',
			method: 'GET',
			timeout: 5000,
			success: function(response) {
				console.log('[APC] Agent availability response:', response);
				console.log('[APC] Response type:', typeof response);
				console.log('[APC] agents_available:', response.agents_available);
				
				if (response && response.agents_available === true) {
					console.log('[APC] ✓ Agents AVAILABLE - adding online class to bubble');
					self.$bubble.removeClass('offline').addClass('online');
					self.$bubble.attr('title', 'Chat with us - Agents available');
					console.log('[APC] Bubble classes:', self.$bubble.attr('class'));
				} else if (response && response.agents_available === false) {
					console.log('[APC] ✗ No agents AVAILABLE - adding offline class to bubble');
					self.$bubble.removeClass('online').addClass('offline');
					self.$bubble.attr('title', 'Chat currently offline - No agents available');
					console.log('[APC] Bubble classes:', self.$bubble.attr('class'));
				} else {
					console.warn('[APC] Unexpected response format:', response);
					console.log('[APC] Response keys:', Object.keys(response));
				}
			},
			error: function(xhr, status, error) {
				console.error('[APC] Failed to check agent availability');
				console.error('[APC] Status:', status);
				console.error('[APC] Error:', error);
				console.error('[APC] HTTP Status Code:', xhr.status);
				console.error('[APC] Response:', xhr.responseText);
				
				// Log different error codes
				if (xhr.status === 0) {
					console.error('[APC] Network error - possible CORS issue');
				} else if (xhr.status === 403) {
					console.error('[APC] Permission denied (403)');
				} else if (xhr.status === 404) {
					console.error('[APC] Endpoint not found (404) - URL:', apcChat.restUrl + '/agents/online');
				}
				
				console.log('[APC] Error on availability check, defaulting to ONLINE (fail-safe)');
				self.$bubble.removeClass('offline').addClass('online');
				self.$bubble.attr('title', 'Chat with us');
			}
		});
	},

	openWidget: function() {
		console.log('Opening widget...');
		this.isOpen = true;
		this.$widget.addClass('open');
		this.$bubble.addClass('hidden');
		
		// Create conversation only when user opens chat and only if needed
		var self = this;
		if (this.canUseChat() && !this.currentConversationId) {
			// No active conversation - create one
			this.createNewConversation();
		} else if (this.currentConversationId && this.conversationStatus === 'closed') {
			// Current conversation is closed - clear and create new one
			this.$messages.empty();
			this.currentConversationId = null;
			this.conversationStatus = 'ai';
			this.lastMessageId = 0;
			
			if (this.canUseChat()) {
				this.createNewConversation();
			}
		}
		
		if (this.canUseChat() && this.$input.length) {
			this.$input.focus();
		}
	},

	closeWidget: function() {
		console.log('Closing widget...');
		
		// Auto-close empty conversations in human_requested status
		var self = this;
		if (this.currentConversationId && this.conversationStatus === 'human_requested') {
			// Check if any user messages were sent
			var hasUserMessages = false;
			this.$messages.find('.apc-message-user').each(function() {
				hasUserMessages = true;
				return false; // break loop
			});
			
			// If no user messages, silently close the conversation
			if (!hasUserMessages) {
				var closeData = {};
				
				// Add guest session ID for guests
				if (!apcChat.isLoggedIn && apcChat.allowGuests) {
					closeData.apc_guest_session_id = this.getGuestSessionId();
				}
				
				$.ajax({
					url: apcChat.restUrl + '/conversations/' + this.currentConversationId + '/close',
					method: 'POST',
					headers: {
						'X-WP-Nonce': apcChat.nonce
					},
					xhrFields: {
						withCredentials: true
					},
					contentType: 'application/json',
					data: JSON.stringify(closeData),
					async: false // Make it synchronous to ensure it completes before closing
				});
			}
		}
		
		this.isOpen = false;
		this.$widget.removeClass('open');
		this.$bubble.removeClass('hidden');
		this.stopPolling();
	},

	createNewConversation: function() {
		var self = this;

		// Reset conversation state
		this.conversationStatus = 'ai';
		this.lastMessageId = 0;
		this.$closeBtn.prop('disabled', false).text('Close Chat');

		// Prepare data with guest session ID if guest
		var conversationData = {
			title: ''
		};
		
		// Add guest session ID for guests
		if (!apcChat.isLoggedIn && apcChat.allowGuests) {
			conversationData.apc_guest_session_id = this.getGuestSessionId();
			console.log('[APC] Adding guest session ID to conversation request:', conversationData.apc_guest_session_id);
		}

		$.ajax({
			url: apcChat.restUrl + '/conversations',
			method: 'POST',
			headers: {
				'X-WP-Nonce': apcChat.nonce
			},
			xhrFields: {
				withCredentials: true
			},
			data: JSON.stringify(conversationData),
			contentType: 'application/json',
			success: function(response) {
				console.log('[APC] Create conversation response:', response);
				console.log('[APC] Response type:', typeof response);
				console.log('[APC] Response keys:', Object.keys(response));
				if (response.success) {
					self.currentConversationId = response.conversation_id;
					console.log('[APC] >>> Stored conversation ID in currentConversationId:', self.currentConversationId);
					
					// Show greeting message
					if (response.ai_enabled === false) {
						self.conversationStatus = 'human_requested';
						self.addSystemMessage(apcChat.welcomeMessage || 'Hello! An agent will be with you shortly. Please describe your issue.');
					} else {
						self.addSystemMessage(apcChat.welcomeMessage || 'Hello! How can I help you today?');
					}
					
					self.updateStatusIndicator();
					self.startPolling();
				} else {
					console.error('[APC] Create conversation failed:', response);
					self.addSystemMessage('Error: ' + (response.message || 'Could not start conversation'));
				}
			},
			error: function(xhr, status, error) {
				console.error('[APC] Failed to create conversation:', xhr);
				console.error('[APC] Status:', status);
				console.error('[APC] Error:', error);
				console.error('[APC] Response text:', xhr.responseText);
				self.addSystemMessage('Error: Could not start conversation. (Status: ' + xhr.status + ')');
			}
		});
	},

	requestHumanSupport: function() {
		var self = this;

		if (!this.currentConversationId) {
			return;
		}

		// Disable button during request
		this.$humanBtn.prop('disabled', true).text('Requesting...');

		var requestData = {};
		
		// Add guest session ID for guests
		if (!apcChat.isLoggedIn && apcChat.allowGuests) {
			requestData.apc_guest_session_id = this.getGuestSessionId();
		}

		$.ajax({
			url: apcChat.restUrl + '/conversations/' + this.currentConversationId + '/request-human',
			method: 'POST',
			headers: {
				'X-WP-Nonce': apcChat.nonce
			},
			xhrFields: {
				withCredentials: true
			},
			contentType: 'application/json',
			data: JSON.stringify(requestData),
			success: function(response) {
				if (response.success) {
					self.conversationStatus = 'human_requested';
					self.updateStatusIndicator();
					self.startPolling();
				}
			},
			error: function(xhr) {
				console.error('Failed to request support:', xhr);
				self.addSystemMessage('Error: Could not request human support.');
				self.$humanBtn.prop('disabled', false).text('Talk to Human');
}
});
},

startPolling: function() {
var self = this;

// Poll every 3 seconds
if (this.pollInterval) {
clearInterval(this.pollInterval);
}

this.pollInterval = setInterval(function() {
self.pollMessages();
}, 3000);
},

stopPolling: function() {
if (this.pollInterval) {
clearInterval(this.pollInterval);
this.pollInterval = null;
}
},

closeConversation: function() {
	var self = this;

	if (!this.currentConversationId) {
		return;
	}

	if (!confirm('Are you sure you want to close this conversation?')) {
		return;
	}

	// Disable button during request
	this.$closeBtn.prop('disabled', true).text('Closing...');

	var closeData = {};
	
	// Add guest session ID for guests
	if (!apcChat.isLoggedIn && apcChat.allowGuests) {
		closeData.apc_guest_session_id = this.getGuestSessionId();
	}

	$.ajax({
		url: apcChat.restUrl + '/conversations/' + this.currentConversationId + '/close',
		method: 'POST',
		headers: {
			'X-WP-Nonce': apcChat.nonce
		},
		xhrFields: {
			withCredentials: true
		},
		contentType: 'application/json',
		data: JSON.stringify(closeData),
		success: function(response) {
			if (response.success) {
				self.addSystemMessage('Conversation closed. You can start a new chat anytime.');
				self.conversationStatus = 'closed';
				self.stopPolling();
				
				// Close the widget and clear conversation - don't auto-create new one
				setTimeout(function() {
					self.closeWidget();
					self.$messages.empty();
					self.currentConversationId = null;
					self.lastMessageId = 0;
					// User will create new conversation when they open chat again
				}, 1500);
			}
		},
		error: function(xhr) {
			console.error('Failed to close conversation:', xhr);
			self.addSystemMessage('Error: Could not close conversation.');
			self.$closeBtn.prop('disabled', false).text('Close Chat');
		}
	});
},


pollMessages: function() {
		var self = this;

		if (!this.currentConversationId) {
			return;
		}

		var pollData = {
			since_id: this.lastMessageId,
			_t: Date.now() // Cache buster
		};
		
		// Add guest session ID for guests
		if (!apcChat.isLoggedIn && apcChat.allowGuests) {
			pollData.apc_guest_session_id = this.getGuestSessionId();
			console.log('[APC] POLL: Adding guest session ID to poll request:', pollData.apc_guest_session_id);
		}

		console.log('[APC] POLL: Polling conversation', this.currentConversationId, 'with data:', pollData);

		$.ajax({
			url: apcChat.restUrl + '/conversations/' + this.currentConversationId + '/poll',
			method: 'GET',
			headers: {
				'X-WP-Nonce': apcChat.nonce
			},
			xhrFields: {
				withCredentials: true
			},
			cache: false, // Disable jQuery AJAX cache
			data: pollData,
				success: function(response) {
				console.log('[APC] Poll response:', response);
				if (response.success) {
					console.log('[APC] Current status:', self.conversationStatus, '| New status:', response.status);
					// Update status
					if (response.status !== self.conversationStatus) {
						console.log('[APC] Status changed from', self.conversationStatus, 'to', response.status);

							// When conversation is auto-closed, clean up but don't auto-create new one
							if (response.status === 'closed') {
								self.stopPolling();
								self.addSystemMessage('Your support request has been closed due to inactivity. Feel free to start a new conversation anytime.');
								
								// Clean up but don't create new conversation
								setTimeout(function() {
									if (!self.isOpen) {
										// Only clear if chat is minimized
										self.$messages.empty();
										self.currentConversationId = null;
										self.conversationStatus = 'ai';
										self.lastMessageId = 0;
									}
									// User will create new conversation when they open chat again
								}, 2000);
							}
						}

						// Add new messages
						if (response.messages && response.messages.length > 0) {
							response.messages.forEach(function(msg) {
								// Skip agent-only messages
								if (msg.content && msg.content.startsWith('[AGENT-ONLY]')) {
									self.lastMessageId = Math.max(self.lastMessageId, msg.id);
									return;
								}
								
								if (msg.role === 'agent') {
									self.addMessage(msg.content, 'agent');
								} else if (msg.role === 'system') {
									self.addSystemMessage(msg.content);
								}
								self.lastMessageId = Math.max(self.lastMessageId, msg.id);
							});
						}
					}
				},
				error: function(xhr) {
					console.error('Polling error:', xhr);
				}
			});
		},

		updateStatusIndicator: function() {
			var statusText = '';
			var statusClass = '';

			switch (this.conversationStatus) {
				case 'ai':
					statusText = 'AI Assistant';
					statusClass = 'status-ai';
					this.$humanBtn.show().prop('disabled', false).text('Talk to Human');
		this.$closeBtn.show().prop('disabled', false).text('Close Chat');
		this.stopPolling();
		break;
	case 'human_requested':
		statusText = 'Waiting for agent...';
		statusClass = 'status-waiting';
		this.$humanBtn.hide();
		this.$closeBtn.show().prop('disabled', false).text('Close Chat');
		break;
	case 'human_active':
		statusText = 'Connected to agent';
		statusClass = 'status-active';
		this.$humanBtn.hide();
		this.$closeBtn.show().prop('disabled', false).text('Close Chat');
		break;
	case 'closed':
		statusText = 'Conversation closed';
		statusClass = 'status-closed';
		this.$humanBtn.hide();
		this.$closeBtn.hide();
		break;
}

if (this.$statusIndicator.length) {
	this.$statusIndicator.text(statusText).attr('class', statusClass);
}
},

sendMessage: function() {
	var message = this.$input.val().trim();

	if (!message || this.isLoading) {
		return;
	}

	console.log('[APC] Sending message:', message);
	console.log('[APC] Current conversation ID:', this.currentConversationId);
	console.log('[APC] Is logged in:', apcChat.isLoggedIn);
	console.log('[APC] Allow guests:', apcChat.allowGuests);

	// Check if message is a form trigger command
	if (message.startsWith('/')) {
		this.checkFormTrigger(message);
		return;
	}

	// Check if we're currently in a form flow
	if (this.activeForm) {
		this.handleFormResponse(message);
		return;
	}

	// Add user message to chat UI
	this.addMessage(message, 'user');

	// Clear input
	this.$input.val('').css('height', 'auto');

	// Check for workflow triggers (async, non-blocking)
	this.checkWorkflowTriggers(message);

	// Show loading indicator
	this.showLoadingIndicator();

	this.isLoading = true;
	this.$sendBtn.prop('disabled', true);

			// Send to API
			var self = this;
			var messageData = {
				conversation_id: this.currentConversationId,
				message: message
			};
			
			console.log('[APC] Before adding guest session ID - messageData:', JSON.stringify(messageData));
			console.log('[APC] Condition check: !apcChat.isLoggedIn =', !apcChat.isLoggedIn, ', apcChat.allowGuests =', apcChat.allowGuests);
			
			// Add guest session ID for guests
			if (!apcChat.isLoggedIn && apcChat.allowGuests) {
				messageData.apc_guest_session_id = this.getGuestSessionId();
				console.log('[APC] *** ADDED guest session ID:', messageData.apc_guest_session_id);
			} else {
				console.log('[APC] *** DID NOT ADD guest session ID');
			}
			
			console.log('[APC] Final messageData:', JSON.stringify(messageData));
			
			$.ajax({
				url: apcChat.restUrl + '/messages',
				method: 'POST',
				headers: {
					'X-WP-Nonce': apcChat.nonce
				},
				xhrFields: {
					withCredentials: true
				},
				data: JSON.stringify(messageData),
				contentType: 'application/json',
			success: function(response) {
				console.log('[APC] Send message response:', response);
				self.removeLoadingIndicator();

				if (response.success) {
					// If human support is active, don't add AI message (agent will reply via polling)
					if (response.is_human) {
						// Message sent to human agent - they'll see it and reply
						// No need to add message here, just wait for agent response via polling
					} else {
						// AI response - add it to chat
						self.addMessage(response.message, 'assistant');
					}
				} else {
					console.error('[APC] Send message failed:', response);
					self.addSystemMessage('Error: ' + (response.message || 'Could not get response'));
				}
			},
			error: function(xhr, status, error) {
				self.removeLoadingIndicator();
				console.error('[APC] Error sending message:', xhr);
				console.error('[APC] Status:', status);
				console.error('[APC] Error:', error);
				console.error('[APC] Response text:', xhr.responseText);

				// Handle closed conversation error (410)
				if (xhr.status === 410) {
					self.addSystemMessage('Your previous conversation was closed. Please close and reopen the chat to start a new conversation.');
					
					// Clear state but don't auto-create
					self.$messages.empty();
					self.currentConversationId = null;
					self.conversationStatus = 'closed';
					self.lastMessageId = 0;
					self.stopPolling();
					return;
				}

				var errorMsg = 'Error: Could not send message. Please try again.';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					errorMsg = 'Error: ' + xhr.responseJSON.message;
				}
				self.addSystemMessage(errorMsg);
			},
			complete: function() {
				self.isLoading = false;
				self.$sendBtn.prop('disabled', false);
				self.$input.focus();
			}
		});
	},

addMessage: function(text, role) {
	var messageClass = role;
	if (role === 'assistant' || role === 'agent') {
		messageClass = 'assistant';
	} else if (role === 'user') {
		messageClass = 'user';
	}

	var messageHtml = '<div class="apc-message ' + messageClass + '">';
	messageHtml += '<div class="apc-message-content">' + this.escapeHtml(text) + '</div>';
	
	// Check for form link triggers in agent/assistant messages
	if (messageClass === 'assistant') {
		// Check for auto-start form trigger [auto_form:id]
		var autoFormMatch = text.match(/\[auto_form:(\d+)\]/);
		if (autoFormMatch) {
			var formId = autoFormMatch[1];
			var self = this;
			// Auto-start form after message renders
			setTimeout(function() {
				self.loadFormById(formId);
			}, 500);
		}
		
		// Check for clickable form link triggers [form:id:text]
		var linkMatches = text.match(/\[form:(\d+):(.*?)\]/g);
		if (linkMatches) {
			messageHtml += '<div class="apc-form-triggers">';
			linkMatches.forEach(function(match) {
				var parts = match.match(/\[form:(\d+):(.*?)\]/);
				var formId = parts[1];
				var buttonText = parts[2];
				messageHtml += '<button class="apc-form-trigger-btn" data-form-id="' + formId + '">' + buttonText + '</button>';
			});
			messageHtml += '</div>';
		}
	}
	
	messageHtml += '</div>';

	this.$messages.append(messageHtml);
	
	// Bind form trigger button clicks
	var self = this;
	$('.apc-form-trigger-btn').off('click').on('click', function() {
		var formId = $(this).data('form-id');
		self.loadFormById(formId);
	});
	
	this.scrollToBottom();
},

addSystemMessage: function(text) {
	var messageHtml = '<div class="apc-message system">';
	messageHtml += '<div class="apc-message-content">' + this.escapeHtml(text) + '</div>';
	messageHtml += '</div>';

	this.$messages.append(messageHtml);
	this.scrollToBottom();
},

showLoadingIndicator: function() {
	var loadingHtml = '<div class="apc-message assistant apc-loading">';
	loadingHtml += '<div class="apc-message-content">';
	loadingHtml += '<span class="apc-loading-dot"></span>';
	loadingHtml += '<span class="apc-loading-dot"></span>';
	loadingHtml += '<span class="apc-loading-dot"></span>';
	loadingHtml += '</div>';
	loadingHtml += '</div>';

	this.$messages.append(loadingHtml);
	this.scrollToBottom();
},

removeLoadingIndicator: function() {
	this.$messages.find('.apc-loading').remove();
},

scrollToBottom: function() {
	this.$messages.scrollTop(this.$messages[0].scrollHeight);
},

escapeHtml: function(text) {
	var div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
	},

	// =======================
	// Form Handling Methods
	// =======================

	activeForm: null,
	formFields: [],
	currentFieldIndex: 0,
	formResponses: {},

	checkFormTrigger: function(command) {
		var self = this;

		// Add user message to chat
		this.addMessage(command, 'user');
		this.$input.val('').css('height', 'auto');

		// Show loading
		this.showLoadingIndicator();

		$.ajax({
			url: apcChat.restUrl + '/forms/trigger',
			method: 'GET',
			headers: {
				'X-WP-Nonce': apcChat.nonce
			},
			data: {
				command: command
			},
			success: function(response) {
				self.removeLoadingIndicator();
				
				if (response.success && response.form) {
					self.startForm(response.form, response.fields);
				} else {
					self.addSystemMessage('Command not recognized. Type your message or use a valid command.');
				}
			},
			error: function() {
				self.removeLoadingIndicator();
				self.addSystemMessage('Error loading form.');
			}
		});
	},

	startForm: function(form, fields) {
		this.activeForm = form;
		this.formFields = fields;
		this.currentFieldIndex = 0;
		this.formResponses = {};

		// Show form introduction
		this.addSystemMessage('Starting form: ' + form.name);
		if (form.description) {
			this.addSystemMessage(form.description);
		}

		// Show first field
		this.showNextField();
	},

	showNextField: function() {
		// Check if all fields are completed
		if (this.currentFieldIndex >= this.formFields.length) {
			this.submitForm();
			return;
		}

		var field = this.formFields[this.currentFieldIndex];

		// Check conditional logic
		if (field.conditional_logic) {
			if (!this.evaluateConditional(field.conditional_logic)) {
				// Skip this field
				this.currentFieldIndex++;
				this.showNextField();
				return;
			}
		}

		// Show field question
		var questionText = field.field_label;
		if (field.is_required) {
			questionText += ' *';
		}

		this.addMessage(questionText, 'assistant');

		// For select, checkbox, radio - show options
		if (['select', 'checkbox', 'radio'].includes(field.field_type) && field.field_options && field.field_options.length > 0) {
			var options = field.field_options;
			var optionsHtml = '<div class="apc-form-options">';
			
			options.forEach(function(option, index) {
				optionsHtml += '<button class="apc-option-btn" data-field-id="' + field.id + '" data-value="' + option + '">';
				optionsHtml += (index + 1) + '. ' + option;
				optionsHtml += '</button>';
			});
			
			optionsHtml += '</div>';
			this.$messages.append(optionsHtml);
			this.scrollToBottom();

			// Bind option click events
			var self = this;
			$('.apc-option-btn').off('click').on('click', function() {
				var value = $(this).data('value');
				self.handleFormResponse(value);
			});
		}

		// Show placeholder hint
		if (field.field_placeholder) {
			this.addSystemMessage('Hint: ' + field.field_placeholder);
		}
	},

	handleFormResponse: function(response) {
		var field = this.formFields[this.currentFieldIndex];

		// Add user response to chat
		this.addMessage(response, 'user');
		this.$input.val('').css('height', 'auto');

		// Validate response
		if (!this.validateFieldResponse(field, response)) {
			this.addSystemMessage('Invalid response. Please try again.');
			this.showNextField();
			return;
		}

		// Store response using field ID as key
		this.formResponses[field.id] = response;

		// Move to next field
		this.currentFieldIndex++;
		this.showNextField();
	},

	validateFieldResponse: function(field, value) {
		// Required check
		if (field.is_required && !value) {
			return false;
		}

		// Type-specific validation
		switch (field.field_type) {
			case 'email':
				var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				return emailRegex.test(value);
			case 'number':
				return !isNaN(value);
			default:
				return true;
		}
	},

	evaluateConditional: function(logic) {
		if (!logic || !Array.isArray(logic) || logic.length === 0) return true;

		try {
			var self = this;

			return logic.every(function(rule) {
				var fieldValue = self.formResponses[rule.field];
				var compareValue = rule.value;

				switch (rule.operator) {
					case 'equals':
						return fieldValue == compareValue;
					case 'not_equals':
						return fieldValue != compareValue;
					case 'contains':
						return fieldValue && fieldValue.indexOf(compareValue) !== -1;
					case 'greater_than':
						return parseFloat(fieldValue) > parseFloat(compareValue);
					case 'less_than':
						return parseFloat(fieldValue) < parseFloat(compareValue);
					case 'is_empty':
						return !fieldValue;
					case 'not_empty':
						return !!fieldValue;
					default:
						return true;
				}
			});
		} catch (e) {
			console.error('Error evaluating conditional logic:', e);
			return true;
		}
	},

	submitForm: function() {
		var self = this;

		this.addSystemMessage('Submitting form...');

		$.ajax({
			url: apcChat.restUrl + '/forms/' + this.activeForm.id + '/submit',
			method: 'POST',
			headers: {
				'X-WP-Nonce': apcChat.nonce
			},
			data: JSON.stringify({
				conversation_id: this.currentConversationId,
				responses: this.formResponses
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.success) {
					self.addSystemMessage('✓ Form submitted successfully! Thank you.');
				} else {
					self.addSystemMessage('Error submitting form. Please try again.');
				}
				self.resetForm();
			},
			error: function() {
				self.addSystemMessage('Error submitting form.');
				self.resetForm();
			}
		});
	},

	resetForm: function() {
		this.activeForm = null;
		this.formFields = [];
		this.currentFieldIndex = 0;
		this.formResponses = {};
	},

	loadFormById: function(formId) {
		var self = this;

		this.addSystemMessage('Loading form...');

		$.ajax({
			url: apcChat.restUrl + '/forms/' + formId + '/with-fields',
			method: 'GET',
			headers: {
				'X-WP-Nonce': apcChat.nonce
			},
			success: function(response) {
				if (response.success && response.form) {
					self.startForm(response.form, response.fields);
				} else {
					self.addSystemMessage('Form not available.');
				}
			},
			error: function() {
				self.addSystemMessage('Error loading form.');
			}
		});
	},

	checkWorkflowTriggers: function(message) {
		var self = this;

		// Check if message contains keywords that might trigger a form
		$.ajax({
			url: apcChat.restUrl + '/forms/workflow-check',
			method: 'POST',
			headers: {
				'X-WP-Nonce': apcChat.nonce
			},
			data: JSON.stringify({
				message: message,
				conversation_id: this.currentConversationId
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.success && response.form) {
					// Suggest form to user
					setTimeout(function() {
						self.addSystemMessage('It looks like you might need: ' + response.form.name);
						var buttonHtml = '<div class="apc-form-triggers">';
						buttonHtml += '<button class="apc-form-trigger-btn" data-form-id="' + response.form.id + '">';
						buttonHtml += 'Start ' + response.form.name;
						buttonHtml += '</button>';
						buttonHtml += '</div>';
						self.$messages.append(buttonHtml);
						
						$('.apc-form-trigger-btn').off('click').on('click', function() {
							var formId = $(this).data('form-id');
							self.loadFormById(formId);
						});
						
						self.scrollToBottom();
					}, 1000);
				}
			},
			error: function() {
				// Silently fail - workflow triggers are optional
			}
		});
	}
};

$(document).ready(function() {
	APC_Chat.init();
});

})(jQuery);
