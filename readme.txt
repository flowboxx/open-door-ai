=== flowboxx Door for AI Assistants ===
Contributors: flowboxx
Tags: ai assistants, chatgpt, inquiries, llms.txt, mcp
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

When an AI assistant such as ChatGPT finds your business, it can leave an inquiry for you, and you get it as an e-mail (optional inquiry service).

== Description ==

flowboxx Door for AI Assistants makes your business readable for AI assistants. You answer three questions. The plugin does the rest.

With the optional inquiry service, an AI assistant can leave an inquiry for a person, and you receive it as an e-mail. The free plugin prepares the door. The inquiry service is a separate, paid service by flowboxx and is not part of this plugin (see "External services").

What you get, free and without an account:

* An agent card at `/.well-known/agent-card.json` and `/.well-known/agent.json`
* A server card at `/.well-known/mcp/server-card.json`
* A plain text file at `/llms.txt` with your offers and your pages
* A small read-only endpoint through which an assistant can ask "who are you" and "what do you offer"
* A block and a shortcode, "Ask an AI about us", for any page

Good to know:

* There is no AI inside this plugin. It writes a short description of your business and serves it. Nothing is generated or decided by a model.
* The free part works on its own. Nothing is locked behind an account.
* Without the inquiry service the plugin sends no data anywhere and has no tracking.
* Nobody can promise that an AI assistant will mention your business. This plugin makes your information readable, nothing more.
* Your e-mail address is stored in your own WordPress only. It is not published.
* Nothing is written to your file system. If a real `llms.txt` file exists in your web root, your server delivers that one first.
* Your website needs https.

= For developers =

The cards follow the A2A agent card format and the MCP server card format. The endpoint speaks MCP (streamable HTTP, protocol 2025-06-18) with the tools `about_business` and `list_services`. Filter `open_door_interface_url` changes the endpoint address in the cards. The open-source tool agent-card-starter (https://github.com/flowboxx/agent-card-starter) checks the cards offline.

== External services ==

The free part of this plugin connects to no external service and sends no data.

The server card (`/.well-known/mcp/server-card.json`) contains the field `$schema` with the address https://static.modelcontextprotocol.io/schemas/v1/server-card.schema.json. This is only a text reference inside the JSON file that your website delivers, so that tools can find the format definition. The plugin itself never requests this address and sends no data to modelcontextprotocol.io.

The inquiry service is a separate, optional service by flowboxx, Oer-Erkenschwick, Germany (https://flowboxx.de/). It costs 19 EUR per month plus VAT and can be cancelled monthly. It is not included in the free part and is not switched on by it. Nothing is sent to the service until you press "Register for the inquiry service" on the settings page. If you do:

* When you register (after ticking the data processing agreement), the plugin sends the address of your website, the e-mail address for inquiries and the version of the agreement to https://agent.flowboxx.de, once. The service sends a message to that address. Inquiries are forwarded only after you confirmed the address and subscribed.
* While the service is registered, the plugin asks https://agent.flowboxx.de/open-door/status from time to time (at most every ten minutes when the page is used) with a secret key to learn whether your subscription is active.
* When an AI assistant leaves an inquiry on your website, the plugin sends the text of the inquiry, the contact the visitor gave and the name of the assistant to https://agent.flowboxx.de. The service stores it for at most 90 days and forwards it to your e-mail address. It is deleted at once when you cancel.
* Payment is handled by Stripe, outside your website, on the Stripe page. Stripe Terms: https://stripe.com/legal/ssa , Stripe Privacy Policy: https://stripe.com/privacy

Terms of use: https://agent.flowboxx.de/nutzungshinweise
Privacy policy: https://agent.flowboxx.de/datenschutz
Data processing agreement: https://agent.flowboxx.de/avv

More about the plugin and the inquiry service: https://flowboxx.de/open-door/en/

== Installation ==

1. Install and activate the plugin.
2. Go to Settings, flowboxx Door. Enter your company name, what you offer and an e-mail address.
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
Through the optional inquiry service by flowboxx. It is not part of the free plugin. The free plugin only makes your information readable.

= What does the inquiry service cost? =
19 EUR per month plus VAT, cancellable monthly. The free plugin works without it.

= Does it work with Multisite? =
Use it per single site. Network activation is not supported in this version.

== Screenshots ==

1. Settings page: three answers, then the addresses are listed.
2. The block "Ask an AI about us" in the block editor.
3. The block on a page of your website.

== Changelog ==

= 0.3.3 =
* Renamed to flowboxx Door for AI Assistants (slug unchanged). Description reworded. External services: server card schema URL and terms links explained.

= 0.3.2 =
* Plugin page added: https://flowboxx.de/open-door/en/ (English), https://flowboxx.de/open-door/ (German). No code change.

= 0.3.1 =
* The inquiry service registration needs a tick for the data processing agreement (text: https://agent.flowboxx.de/avv).

= 0.3.0 =
* Optional inquiry service: register on the settings page, an assistant tool "leave_inquiry" appears while the subscription is active. Nothing is sent before you register.

= 0.2.1 =
* Text: the inquiry service is marked as starting soon. No code change.

= 0.2.0 =
* Renamed (slug open-door-ai). Block is now open-door-ai/ask, shortcode [open_door_ask]. No other change.

= 0.1.0 =
* First version.
