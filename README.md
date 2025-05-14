# Nova AI Brainpool

Nova AI Brainpool is a minimalist AI Chat plugin for WordPress with a retro terminal-style interface, powered by AILinux and Ollama. It brings a simple yet powerful chatbot to your WordPress site—perfect for support, consultations, or interactive content.

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![WordPress Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)](https://wordpress.org/)

## 🌟 Features

- 💻 Terminal-inspired interface (green on black) with modern theme options
- 🧠 AI chat with Ollama backend (Mistral, LLaMA, Zephyr, etc.)
- 🔒 Self-hosted AI - no external APIs required
- 🧪 Built-in knowledge base and web crawler
- 🔧 Admin settings for easy configuration
- 🚀 Fullsite floating chat widget option
- 🎨 Multiple themes (Terminal, Dark, Light)

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- [Ollama](https://ollama.ai/) (For local AI processing)
- Docker (optional, for running Ollama)

## 🚀 Installation

### Manual Installation

1. Download the latest version from GitHub
2. Upload the plugin to your WordPress site under `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Nova AI settings to configure the plugin

### Using Ollama

1. Install [Ollama](https://ollama.ai/) on your server or local machine
2. Pull a model like Zephyr: `ollama pull zephyr`
3. Make sure Ollama is running (API available at `http://localhost:11434`)

## ⚙️ Configuration

### Basic Setup

1. Navigate to "Nova AI" in your WordPress admin menu
2. Configure the API endpoint (default: `http://host.docker.internal:11434/api/generate` for Docker)
3. Select your preferred AI model (Zephyr is recommended)
4. Save settings and test the connection

### AI Settings

- **API Provider**: Choose between Ollama (local) or OpenAI
- **API URL**: URL to your Ollama instance
- **Model**: Select which AI model to use
- **Temperature**: Control creativity (0.0-1.0)
- **Max Tokens**: Maximum length of responses
- **System Prompt**: Set personality and behavior

### Chat Interface

- **Theme Style**: Terminal (default), Dark, or Light
- **Full-Site Chat**: Enable a floating chat button on all pages
- **Position**: Choose where the chat button appears
- **Custom CSS**: Add your own styling

## 📝 Usage

### Shortcode

Add the chat interface to any page or post using the shortcode:

```
[nova_ai_chat]
```

### Shortcode Parameters

Customize the appearance:

```
[nova_ai_chat theme="terminal" width="800px" height="500px" placeholder="Ask me anything..."]
```

### PHP Template Integration

```php
<?php echo do_shortcode('[nova_ai_chat]'); ?>
```

## 🧠 Knowledge Base

Nova AI comes with a built-in knowledge base system:

1. Navigate to "Nova AI > Knowledge Base"
2. Add custom Q&A pairs for your specific content
3. Import knowledge from crawled websites
4. Export your knowledge base as JSON

## 🌐 Web Crawler

The integrated web crawler helps build your knowledge base:

1. Go to "Nova AI > Web Crawler"
2. Enter URLs to crawl (documentation sites, your own content)
3. Set crawl depth and limits
4. Run the crawler and import content to your knowledge base

## 🔧 Development

### Structure

- `/admin/` - Admin interface files
- `/assets/` - CSS, JS, and image files
- `/includes/` - Core functionality

### Local Development

1. Clone this repository
2. Install dependencies: `npm install`
3. Build assets: `npm run build`

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add some amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## 📄 License

Nova AI Brainpool is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

- Created by [derleiti](https://github.com/derleiti)
- Powered by [AILinux](https://ailinux.me/)
- Made possible by [Ollama](https://ollama.ai/) and open-source LLMs

---

📌 **Note**: This plugin requires an AI backend like Ollama to function. It does not include an AI model and is designed to interface with local LLM solutions.
