# AI Powered Chat Plugin for WordPress

A modern, AI-powered chat widget plugin for WordPress that integrates with multiple AI providers (OpenAI GPT, Claude, Gemini, GitHub Copilot) to provide intelligent conversational experiences on your website.

**Author:** Jose Rodriguez Arroyo  
**Email:** jrpcone@gmail.com  
**Website:** [https://www.microrepair.net](https://www.microrepair.net)

## Features

✨ **Multi-AI Provider Support** - OpenAI, Claude, Google Gemini, and more!  
✨ **AI-Powered Conversations** - Uses your choice of AI provider  
💾 **Conversation History** - Stores all chat histories in the database  
🔐 **User Authentication** - Chat available only to logged-in users  
🏥 **HIPAA Compliance** - End-to-end encryption, access controls, and audit logging  
🔒 **Data Encryption** - AES-256-GCM encryption for sensitive data  
📊 **Audit Logging** - Complete audit trail for compliance  
👥 **Access Control** - Role-based permissions for viewing conversations  
⚙️ **Customizable Settings** - Admin panel to configure providers, models, and system prompts  
📱 **Responsive Design** - Works seamlessly on desktop and mobile devices  
🎨 **Modern UI** - Beautiful chat widget with smooth animations  
🔌 **REST API** - Full REST API for custom implementations  
🌍 **Multi-language Ready** - Translation-ready plugin (i18n)

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- At least one AI Provider API Key:
  - **OpenAI** (ChatGPT) - https://platform.openai.com/api-keys
  - **Anthropic Claude** - https://console.anthropic.com
  - **Google Gemini** - https://makersuite.google.com/app/apikey

## Installation

### Method 1: From ZIP File
1. Download or extract the plugin files
2. Copy the `wordpress-chat-plugin` folder to `/wp-content/plugins/`
3. Activate the plugin from WordPress Admin > Plugins
4. Add your OpenAI API Key in Settings > AI Chat

### Method 2: Manual Installation
1. Extract the ZIP file
2. Upload the `wordpress-chat-plugin` folder via SFTP to `/wp-content/plugins/`
3. Activate from WordPress admin panel

## Choosing Your AI Provider

The plugin supports multiple AI providers. Choose one or configure all:

### 🤖 **OpenAI (Default)**
- Models: GPT-3.5 Turbo, GPT-4, GPT-4 Turbo, GPT-4o
- Best for: General purpose, fastest responses
- Get API Key: https://platform.openai.com/api-keys

### 🧠 **Anthropic Claude**
- Models: Claude 3 Haiku, Sonnet, Opus
- Best for: High-quality, nuanced responses
- Get API Key: https://console.anthropic.com

### 🔷 **Google Gemini**
- Models: Gemini 1.5 Flash, Pro
- Best for: Fast inference, competitive pricing
- Get API Key: https://makersuite.google.com/app/apikey

**Quick Start Guide:** See [PROVIDER_CHEATSHEET.md](PROVIDER_CHEATSHEET.md) for 5-minute setup per provider.
**Detailed Guide:** See [MULTI_PROVIDER_GUIDE.md](MULTI_PROVIDER_GUIDE.md) for complete documentation.

## Getting Started

### 1. Choose Your AI Provider

For your first setup, pick one of these providers:

**🤖 OpenAI (Most Popular & Easiest)**
- Models: GPT-3.5 Turbo ⭐ (recommended), GPT-4, GPT-4 Turbo
- Speed: Very Fast
- Cost: Low ($0.0001-0.003 per chat)
- Setup Time: 2 minutes

**🧠 Anthropic Claude (Best Quality)**
- Models: Claude 3 Sonnet ⭐ (recommended), Opus
- Speed: Fast
- Cost: Low-Medium ($0.0002-0.0005 per chat)
- Setup Time: 2 minutes

**🔷 Google Gemini (Free Tier Available)**
- Models: Gemini 1.5 Flash ⭐ (recommended), Pro
- Speed: Very Fast
- Cost: Free tier available
- Setup Time: 2 minutes

### 2. Get Your API Key

Choose one provider above and follow these steps:

**For OpenAI:**
1. Visit https://platform.openai.com/api-keys
2. Click "Create new secret key"
3. Copy the key (you'll only see it once!)
4. Keep it safe

**For Claude:**
1. Visit https://console.anthropic.com
2. Go to API keys
3. Create a new key
4. Copy and save it

**For Gemini:**
1. Visit https://makersuite.google.com/app/apikey
2. Click "Create API Key"
3. Copy the key

### 3. Add API Key to WordPress

1. In WordPress Admin, go **Settings > AI Chat**
2. Click the "Configure Providers" tab
3. Scroll to your provider section (OpenAI, Claude, or Gemini)
4. Paste your API key
5. Save Settings

### 4. Activate Your Provider

1. Still in **Settings > AI Chat** (General Settings tab)
2. Under "AI Provider Selection", choose your provider
3. Save Settings

✅ **Done!** Your chat widget is now live.

### 5. Verify It Works

1. Go to your website frontend
2. Make sure you're logged in
3. Find the chat bubble in the bottom-right corner
4. Send a test message
5. You should get a response!

### 6. Customize System Prompt (Optional)

The system prompt defines how the AI assistant will behave. Examples:

**Helpful Customer Support Bot:**
```
You are a helpful customer support representative for our company. 
Be friendly, professional, and always try to resolve customer issues. 
If you don't know something, admit it and suggest contacting support.
```

**Creative Writing Assistant:**
```
You are a creative writing assistant. Help users brainstorm ideas, 
improve their writing, and provide constructive feedback.
```

## Usage

### For Users
- Chat widget appears in the bottom-right corner of your site
- Users must be logged in to use the chat
- Chat history is stored and can be accessed across sessions

### For Developers

#### REST API Endpoints

**Create Conversation:**
```bash
POST /wp-json/apc/v1/conversations
Headers: Authorization: Bearer <token>
```

**Send Message:**
```bash
POST /wp-json/apc/v1/messages
{
  "conversation_id": 1,
  "message": "Hello!"
}
```

**Get Conversations:**
```bash
GET /wp-json/apc/v1/conversations
```

**Delete Conversation:**
```bash
DELETE /wp-json/apc/v1/conversations/{id}
```

## File Structure

```
wordpress-chat-plugin/
├── ai-powered-chat.php          # Main plugin file
├── admin/
│   ├── class-apc-admin.php     # Admin settings page
│   ├── css/admin.css           # Admin styles
│   └── js/admin.js             # Admin scripts
├── public/
│   ├── class-apc-public.php    # Frontend widget
│   ├── css/chat.css            # Chat widget styles
│   └── js/chat.js              # Chat widget scripts
├── includes/
│   ├── class-apc-ai-handler.php    # OpenAI API communication
│   ├── class-apc-database.php      # Database operations
│   └── class-apc-rest-api.php      # REST API endpoints
└── database/                    # Database migration files
```

## Database Structure

### Tables Created

**wp_apc_conversations**
- `id` - Unique conversation ID
- `user_id` - WordPress user ID
- `title` - Conversation title
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**wp_apc_messages**
- `id` - Message ID
- `conversation_id` - Associated conversation
- `role` - 'user' or 'assistant'
- `content` - Message text
- `tokens_used` - Token count for this message
- `created_at` - Timestamp

## Configuration Options

All settings are stored as WordPress options:

| Option | Default | Type |
|--------|---------|------|
| `apc_openai_api_key` | - | string |
| `apc_ai_model` | gpt-3.5-turbo | string |
| `apc_ai_temperature` | 0.7 | float |
| `apc_ai_max_tokens` | 500 | integer |
| `apc_system_prompt` | "You are a helpful assistant." | string |
| `apc_chat_enabled` | 1 | boolean |

## Security Considerations

⚠️ **Important:**
- Never commit your API key to version control
- Store API key securely in WordPress options
- The plugin validates user login before chat access
- Use environment variables for production deployments
- Implement rate limiting for production use
- Add nonce verification for AJAX requests

## Troubleshooting

### Chat widget not appearing?
- Check if "Enable Chat Widget" is checked in settings
- Verify user is logged in
- Check browser console for JavaScript errors

### API errors?
- Verify OpenAI API key is valid
- Check API rate limits on OpenAI dashboard
- Ensure PHP cURL extension is enabled
- Check firewall settings if requests are blocked

### Database errors?
- Run plugin activation again to create tables
- Check database permissions

## HIPAA Compliance 🏥

This plugin includes comprehensive **HIPAA compliance features** to protect sensitive health information:

### Key Features

- **🔒 AES-256-GCM Encryption** - All chat messages and form data are encrypted at rest
- **🔑 Key Management** - Secure key storage and rotation (recommended every 90 days)
- **📊 Audit Logging** - Complete audit trail of all data access
- **👥 Access Control** - Role-based permissions (Administrator, HIPAA Compliance Officer, Chat Reviewer)
- **📅 Data Retention** - Configurable retention policies (6+ years for HIPAA)
- **✅ Compliance Dashboard** - Monitor and manage HIPAA compliance status

### Quick Setup

1. Navigate to **AI Chat > HIPAA Compliance**
2. Click **"Enable Encryption"**
3. Configure data retention policy (minimum 6 years)
4. Assign appropriate roles to authorized users
5. Review audit logs regularly

**📖 Full Documentation:**
- [HIPAA Compliance Guide](HIPAA_COMPLIANCE.md) - Complete implementation details
- [Quick Start Guide](HIPAA_QUICKSTART.md) - 5-minute setup instructions

### Important Notes

⚠️ **Business Associate Agreement (BAA) Required**

To use this plugin with Protected Health Information (PHI), you **must** have a Business Associate Agreement (BAA) with your AI provider:

- **OpenAI** - Available for enterprise customers
- **Anthropic Claude** - Contact sales for BAA
- **Google Gemini** - Available through Google Cloud
- **GitHub Models** - Check enterprise options

⚠️ This plugin provides **technical safeguards**. Full HIPAA compliance also requires:
- Administrative safeguards (policies, training, risk assessment)
- Physical safeguards (facility security, device controls)
- Business Associate Agreements with vendors

### Custom Roles Added

The plugin creates three new WordPress roles:

- **HIPAA Compliance Officer** - View all conversations, export data, view audit logs
- **Chat Reviewer** - View all conversations (read-only)
- **Administrator** - Full access to all HIPAA features

## Technology Stack

- **Backend**: PHP 7.4+, WordPress REST API
- **Frontend**: jQuery, Vanilla JavaScript
- **Database**: MySQL/MariaDB
- **AI**: OpenAI GPT API

## Contributing

To contribute to this plugin:
1. Create a feature branch
2. Make your changes
3. Submit a pull request

## License

GNU General Public License v2.0 or later. See `LICENSE` file for details.

## Support

For issues, questions, or feature requests:
- **Email:** jrpcone@gmail.com
- **Website:** [https://www.microrepair.net](https://www.microrepair.net)

## Author (2026)
- Initial release
- Multi-AI Provider support (OpenAI, Claude, Gemini, GitHub Copilot)
- Conversation history with database storage
- Comprehensive admin settings panel
- Responsive frontend chat widget with floating bubble
- REST API support
- User authentication and permissions
- Developed by Jose Rodriguez Arroyo

## Roadmap

Planned features:
- [ ] Anonymous user support (guest chat)
- [ ] Chat export functionality (CSV, JSON)
- [ ] Advanced analytics dashboard
- [ ] Custom styling options from admin panel
- [ ] Rate limiting and abuse prevention
- [ ] Multi-language translations
- [ ] Conversation search and filtering

## Roadmap

Planned features:
- [ ] Multiple AI providers support (Anthropic Claude, etc.)
- [ ] Chat export functionality
- [ ] Advanced analytics
- [ ] Custom styling options
- [ ] Conversation branching
- [ ] Voice input/output
- [ ] Custom branding in widget

## Author

Created with ❤️ for WordPress developers

---

For the latest updates and documentation, visit the plugin page.
