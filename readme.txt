=== AI Door ===
Contributors: flowboxx
Tags: ai, llms.txt, agent card, chatgpt, mcp
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

When someone asks an AI assistant such as ChatGPT about businesses like yours, it can read what you offer here. Free, set up in 5 minutes.

== Description ==

AI Door writes a short, readable description of your business for AI assistants. You answer three questions. The plugin does the rest.

What you get, free and without an account:

* An agent card at `/.well-known/agent-card.json` and `/.well-known/agent.json`
* A server card at `/.well-known/mcp/server-card.json`
* A plain text file at `/llms.txt` with your offers and your pages
* A small read-only endpoint through which an assistant can ask "who are you" and "what do you offer"
* A block and a shortcode, "Ask an AI about us", for any page

Good to know:

* The free part works on its own. Nothing is locked behind an account.
* There is no AI inside this plugin.
* The plugin sends no data anywhere and has no tracking.
* Nobody can promise that an AI assistant will mention your business. This plugin makes your information readable, nothing more.
* Your e-mail address is stored in your own WordPress only. It is not published.
* Nothing is written to your file system. If a real `llms.txt` file exists in your web root, your server delivers that one first.
* Your website needs https.

= For developers =

The cards follow the A2A agent card format and the MCP server card format. The endpoint speaks MCP (streamable HTTP, protocol 2025-06-18) with the tools `about_business` and `list_services`. Filter `ai_door_interface_url` changes the endpoint address in the cards.

== External services ==

This version connects to no external service and sends no data.

A hosted inbox for inquiries from AI assistants is planned as a separate, paid service by flowboxx (https://flowboxx.de/). Price on request. It will only be switched on after you connect it yourself and confirm your e-mail address, and this readme will describe exactly what is sent before that is released.

== Installation ==

1. Install and activate the plugin.
2. Go to Settings, AI Door. Enter your company name, what you offer and an e-mail address.
3. Press "Save and switch on". The addresses are listed on the same page.
4. Optional: add the block "Ask an AI about us" to a page.

== Frequently Asked Questions ==

= Do I need an account? =
No.

= Does the plugin use AI? =
No.

= Will ChatGPT now recommend my business? =
Nobody can promise that. The plugin only makes your information easy to read.

= Where do I see the addresses? =
On the settings page. Press "Check my addresses" to test them.

= Does it work with Multisite? =
Use it per single site. Network activation is not supported in this version.

== Changelog ==

= 0.1.0 =
* First version.
