=== Mentionable ===
Contributors:      X-team, jonathanbardo, topher1kenobe, shadyvb, westonruter
Tags:              tinyMCE, admin, mention
Requires at least: 3.6
Tested up to:      3.7.1
Stable tag:        trunk
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Mention WordPress content with inline autocomplete inside tinyMCE.

== Description ==

This plugin brings the power of @mention inside tinyMCE. You can choose where the autocompletion gets his information from and on which custom post type this plugin is activated on. You can also create custom template replacement on the front-end based on your need.

**Development of this plugin is done [on GitHub](https://github.com/x-team/mentionable). Pull requests welcome. Please see [issues](https://github.com/x-team/mentionable/issues) reported there before going to the plugin forum.**

== Installation ==

1. Upload `mentionable` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Optional : create a template called mentionable.php in your theme directory and replace the @mention content with custom template.


== Screenshots ==

1. Start typing "@" for the autocomplete to trigger
2. After pressing enter, the plugin replace your input with the right link and content

== Changelog ==

= 0.2.0 =
* Store reference to mentionable content inside post metas
* Add the ability to replace the custom content with a template name mentionable.php
* Add plugin banner
* Add french localization

= 0.1.0 =
First Release
