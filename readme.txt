=== Open Door for AI Assistants ===
Contributors: flowboxx
Tags: ai assistants, chatgpt, inquiries, llms.txt, mcp
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

When an AI assistant such as ChatGPT finds your business, it can leave an inquiry for you, and you get it as an e-mail (optional inquiry service).

== Description ==

Open Door for AI Assistants makes your business readable for AI assistants. You answer three questions. The plugin does the rest.

What makes it different: other plugins stop at making your pages readable. Open Door goes one step further. With the optional inquiry service, an AI assistant can leave an inquiry for a person, and you receive it as an e-mail. The free plugin prepares the door. The inquiry service is a separate, paid service by flowboxx and is not part of this plugin (see "External services").

What you get, free and without an account:

* An agent card at `/.well-known/agent-card.json` and `/.well-known/agent.json`
* A server card at `/.well-known/mcp/server-card.json`
* A plain text file at `/llms.txt` with your offers and your pages
* A small read-only endpoint through which an assistant can ask "who are you" and "what do you offer"
* A block and a shortcode, "Ask an AI about us", for any page

Good to know:

* There is no AI inside this plugin. It writes a short description of your business and serves it. Nothing is generated or decided by a model.
* The free part works on its own. Nothing is locked behind an account.
* The plugin sends no data anywhere and has no tracking.
* Nobody can promise that an AI assistant will mention your business. This plugin makes your information readable, nothing more.
* Your e-mail address is stored in your own WordPress only. It is not published.
* Nothing is written to your file system. If a real `llms.txt` file exists in your web root, your server delivers that one first.
* Your website needs https.

= For developers =

The cards follow the A2A agent card format and the MCP server card format. The endpoint speaks MCP (streamable HTTP, protocol 2025-06-18) with the tools `about_business` and `list_services`. Filter `open_door_interface_url` changes the endpoint address in the cards. The open-source tool agent-card-starter (https://github.com/flowboxx/agent-card-starter) checks the cards offline.

== External services ==

This version connects to no external service and sends no data.

The inquiry service is a separate, optional service by flowboxx (https://flowboxx.de/). It costs 19 EUR per month plus VAT and can be cancelled monthly. It is not included in this plugin and is not switched on by it. It would only be connected after you do so yourself and confirm your e-mail address, and this readme will describe exactly what is sent before that is released.

== Installation ==

1. Install and activate the plugin.
2. Go to Settings, Open Door. Enter your company name, what you offer and an e-mail address.
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

= How do inquiries from AI assistants reach me? =
Through the optional inquiry service by flowboxx. It is not part of this plugin. The free plugin only makes your information readable.

= What does the inquiry service cost? =
19 EUR per month plus VAT, cancellable monthly. The free plugin works without it.

= Does it work with Multisite? =
Use it per single site. Network activation is not supported in this version.

== Screenshots ==

1. Settings page: three answers, then the addresses are listed.
2. The block "Ask an AI about us" in the block editor.
3. The block on a page of your website.

== Changelog ==

= 0.2.0 =
* Renamed to Open Door for AI Assistants (slug open-door-ai). Block is now open-door-ai/ask, shortcode [open_door_ask], settings page under Settings, Open Door. No other change.

= 0.1.0 =
* First version.
