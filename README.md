# AI Powered Chat - Free Version

**Note:** This is the FREE version of AI Powered Chat plugin for WordPress. It includes core chat functionality powered by AI providers (OpenAI, Claude, Gemini, GitHub Models) without enterprise compliance features.

## ✨ What's Included in the Free Version

### 🤖 Core AI Chat Features
- ✅ Multi-provider AI support (OpenAI, Claude, Gemini, GitHub Models)
- ✅ Beautiful, responsive chat widget for your website
- ✅ Conversation history stored in database
- ✅ User authentication required
- ✅ Customizable system prompts
- ✅ Adjustable AI temperature and token limits
- ✅ REST API endpoints for custom integrations
- ✅ WordPress hooks for developers

### 👤 Admin Features
- ✅ Settings dashboard for API key configuration
- ✅ Easy provider setup and switching
- ✅ Conversation management interface
- ✅ Customizable welcome messages
- ✅ Chat widget enable/disable toggle
- ✅ Multiple AI provider configurations

### 💬 Frontend Features
- ✅ Floating chat widget
- ✅ Message send with Enter key
- ✅ Typing indicators
- ✅ Auto-scroll to latest messages
- ✅ Mobile responsive design
- ✅ Login prompt for non-logged-in users
- ✅ Conversation persistence

### 📋 Additional Features
- ✅ Conversational Forms (create interactive forms)
- ✅ Predefined Responses (auto-responses library)
- ✅ Quick Actions (shortcut commands)
- ✅ Auto-response system

---

## ⚠️ What's NOT Included (Check Premium Version)

The **Premium HIPAA Compliance Version** includes enterprise-grade security features:

### 🔐 Premium Features
- 🏥 **HIPAA Compliance** - Full HIPAA compliance for healthcare applications
- 🔒 **End-to-End Encryption** - AES-256-GCM encryption for all conversation data
- 📊 **Audit Logging** - Complete audit trail for compliance and security
- 👥 **Role-Based Access Control** - Fine-grained permissions for viewing conversations
- 🔑 **Encryption Key Management** - Secure key rotation and management
- 📋 **Compliance Reporting** - Generate compliance reports

---

## 🚀 Quick Start

### 1. Installation

1. Copy the `ai-powered-chat-free` folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Click "Activate" on "AI Powered Chat - Free"

### 2. Initial Setup

1. Go to **Dashboard → AI Chat → Settings**
2. Click the **AI Providers** tab
3. Choose your preferred AI provider:
   - **OpenAI (ChatGPT)** - [Get API Key](https://platform.openai.com/api-keys)
   - **Claude (Anthropic)** - [Get API Key](https://console.anthropic.com/)
   - **Gemini (Google)** - [Get API Key](https://makersuite.google.com/app/apikey)
   - **GitHub Models** - [Get Token](https://github.com/settings/tokens)

4. Enter your API credentials
5. Select your preferred model and configure settings
6. Click "Save Changes"

### 3. Configure Settings

1. Go to **Dashboard → AI Chat → Settings**
2. Configure:
   - **Active Provider** - Choose which AI provider to use
   - **System Prompt** - Define the AI's behavior
   - **Enable AI Responses** - Toggle AI assistance on/off
   - **Chat Widget** - Enable/disable the chat widget
3. Click "Save Changes"

### 4. Test

Visit your website's frontend. You should see the chat widget in the bottom-right corner!

---

## 🔧 Configuration

### System Prompt

Define how the AI should behave. Example:

```
You are a helpful customer support representative for [Your Company]. 
You provide courteous and professional assistance to customers. 
Always be friendly and helpful. If you cannot answer a question, 
suggest contacting support at support@example.com
```

### Provider Settings

Each provider has unique settings:

**OpenAI:**
- Model: GPT-3.5 Turbo, GPT-4, GPT-4 Turbo, GPT-4o
- Temperature: 0-2 (0=focused, 2=creative)
- Max Tokens: 1-4000

**Claude:**
- Model: Claude 3.5 Sonnet, Claude 3 Opus
- Temperature: 0-1
- Max Tokens: 1-4096

**Gemini:**
- Model: Gemini 1.5 Flash, Gemini 1.5 Pro
- Temperature: 0-2
- Max Tokens: 1-32000

**GitHub Models:**
- Model: GPT-4o, Grok, Llama
- Temperature: 0-2
- Max Tokens: Varies by model

---

## 📱 Widget Customization

### Welcome Message

Go to **Dashboard → AI Chat → Chat Customization** to customize:
- Welcome message shown to users
- Chat widget appearance (via CSS)

---

## 🔌 REST API Endpoints

The free version includes basic REST API endpoints:

### Conversations
- `POST /wp-json/apc/v1/conversations` - Create conversation
- `GET /wp-json/apc/v1/conversations` - Get user's conversations
- `GET /wp-json/apc/v1/conversations/{id}/status` - Get conversation status
- `DELETE /wp-json/apc/v1/conversations/{id}` - Delete conversation

### Messages
- `POST /wp-json/apc/v1/messages` - Send message
- `GET /wp-json/apc/v1/conversations/{id}/poll` - Poll for new messages

### Admin Routes (manage_options capability required)
- `GET /wp-json/apc/v1/admin/conversations` - List all conversations
- `GET /wp-json/apc/v1/admin/conversations/{id}` - Get conversation details

---

## 💾 Database

The plugin creates these tables:

- `wp_apc_conversations` - Conversation records
- `wp_apc_messages` - Message records (NOT encrypted in free version)
- `wp_apc_forms` - Conversational forms
- `wp_apc_form_fields` - Form field definitions
- `wp_apc_form_submissions` - Form submissions
- `wp_apc_form_responses` - Form response values
- `wp_apc_predefined_responses` - Auto-response library
- `wp_apc_agent_status` - Agent status tracking

---

## 🐛 Troubleshooting

### Chat widget not showing?
1. Check that chat is enabled: **Dashboard → AI Chat → Settings → Widget Settings → Enable Chat Widget**
2. Ensure user is logged in (widgets only show for logged-in users)
3. Check browser console for errors (F12 → Console)

### API errors?
1. Verify API key is correct
2. Check API provider status page
3. Ensure API key has correct permissions
4. Check rate limits (API providers have usage limits)

### Messages not saving?
1. Check WordPress permissions
2. Verify database tables were created
3. Check WordPress error log: `/wp-content/debug.log`

---

## 📊 Comparison: Free vs Premium

| Feature | Free | Premium |
|---------|------|---------|
| Multi-provider AI | ✅ | ✅ |
| Conversation storage | ✅ | ✅ |
| REST API | ✅ | ✅ |
| Forms & Auto-responses | ✅ | ✅ |
| **Data Encryption** | ❌ | ✅ |
| **Audit Logging** | ❌ | ✅ |
| **Access Control** | ❌ | ✅ |
| **HIPAA Compliance** | ❌ | ✅ |
| **Compliance Reports** | ❌ | ✅ |
| Support | Community | Premium |

---

## 📝 Requirements

- WordPress 5.0+
- PHP 7.4+
- Active internet connection
- AI provider API key (OpenAI, Claude, Gemini, or GitHub)

---

## 📄 License

GPL v2 or later - See LICENSE file

---

## 🙋 Support

- **Free Version Issues:** Check this README 
- **Premium Version:** Contact support@microrepair.net
- **Bug Reports:** [GitHub Issues](https://github.com/jorodriguezpr/wordpress-chat-plugin-free)

---

## 👨‍💻 Developer Info

### Creating a Custom Provider

Implement `APC_AI_Provider_Interface` in `includes/interface-apc-ai-provider.php`

### WordPress Hooks

Available hooks for customization (check code for details):
- `apc_before_send_message`
- `apc_after_send_message`
- Plus many more...

### Code References

- Admin class: `admin/class-apc-admin-v2.php`
- Database class: `includes/class-apc-database.php`
- REST API: `includes/class-apc-rest-api.php`
- Providers: `includes/class-apc-provider-*.php`

---

## 🎯 Roadmap for Premium

- Mobile apps (iOS/Android)
- Advanced analytics
- Multi-language support
- Custom branding options
- Webhook integrations
- And more...

---

**Ready to upgrade to Premium?** Visit [microrepair.net](https://www.microrepair.net) for the HIPAA Compliance version with end-to-end encryption, audit logging, and access controls!

---

*Free AI Powered Chat - Bringing intelligence to your WordPress site!*
