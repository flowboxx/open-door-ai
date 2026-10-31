# Open Door for AI Assistants (ODAA)

When an AI assistant such as ChatGPT finds your business, it can leave an inquiry for you by e-mail (inquiry service coming soon).

Open Door is a free WordPress plugin. You answer three questions (company name, services, e-mail address). The plugin then serves:

* an agent card at `/.well-known/agent-card.json` and `/.well-known/agent.json`
* an MCP server card at `/.well-known/mcp/server-card.json`
* a plain text file at `/llms.txt`
* a small read-only MCP endpoint ("who are you", "what do you offer")
* a block and a shortcode, "Ask an AI about us"

There is no AI inside the plugin, no tracking, and no data is sent anywhere.

The optional inquiry service (19 EUR per month plus VAT, cancellable monthly) is a separate service by flowboxx and starts soon. It is not part of this plugin.

* Requires WordPress 6.4, PHP 7.4, https
* License: GPLv2 or later
* Page for site owners (German): https://flowboxx.de/open-door/
* Command line tool for developers: https://github.com/flowboxx/agent-card-starter

The WordPress.org description is in `readme.txt`.
