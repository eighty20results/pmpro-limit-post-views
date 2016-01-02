=== Eighty / 20 Results - Blur PMPro Content (Add-on) ===
Contributors: eighty20results
Tags: paid memberships pro, pmpro, dagbladet, post encryption, hide content, seo friendly
Requires at least: 3.7
Tested up to: 4.4
Stable tag: 0.8

Integrates with Paid Memberships Pro to hide  content (encrypt & blur) with a pretty overlay & call to action when content is protected by a PMPro membership level.

== Description ==

Inspired by a few news outlet sites which adds an overlay for paywall protected content. Admin can configure settings for much of the content should be unencrypted/unblurred (# of paragraphs), and what content (page) should act as the Call-To-Action overlay. The CTA overlay should indicate what prospective members need to do in order to gain access (show the membership levels protecting this content, for instance).

The CTA (list(s) membership levels that are currently protecting the underlying (encrypted) content. The randomizer uses a pseudo-random character generator, so it's not "straight forward" to identify the actual content even if the user is CSS savvy & can "unblur" the page in their browser's development console.

== Installation ==

1. Make sure you have the Paid Memberships Pro plugin installed and activated.
1. Upload the `e20r-blur-pmpro-content` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Configure settings on the Blur Content settings page.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the GitHub issue tracker here: https://github.com/eighty20results/e20r-blur-pmpro-content/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our support site at http://www.eighty20results.com for more documentation and our support forums.

== Changelog ==
= 0.8 =
* Fix: Various documentation improvements & clean up of 'dead' code
* Enh: Use filters & actions to add/remove content & excerpt filters for 3rd party content protection plugins
* Enh: Default filters & actions installed for Paid Memberships Pro
* Enh: Use filter to check for access rights to content in the_content filter

= 0.7.3 =
* Fix: Didn't display unprotected content properly.
* Fix: Update CSS for CTA headline
* Fix: Javascript for scrollToFixed and the plugin didn't load
* Fix: More flexibility when centering the CTA in the content.
* Fix: Load filter management once WP is loaded
* Fix: Too much space between paragraphs in default CTA form
* Fix: Didn't handle PMPro Excerpt and content filters well
* Fix: Didn't return as clean of HTML as we'd like
* Fix: Refactored class
* Fix: Login link didn't always display in 'right' position
* Enh: Allow CTA to scroll to top of page, then stay visible while content scrolls underneath (supports Genesis Themes)
* Enh: Simplify content filter handling Enh: Simplify centering CTA for blurred content
* Enh: Empty but protected page had CTA dropping below content.
* Enh: Add clear fix for CTA
* Enh: Simplify filter handling Enh: Change priority of excerpt & content filtering

= 0.7.2 =
* Fix: Didn't display unprotected content properly.
* Update CSS for CTA headline

= 0.7.1 =
* Fix: Debug logging caused fatal error
* Fix: Didn't include all required files in build

= 0.7 =
* Fix: Path to master & child theme hosted e20r-style CSS files.
* Enh: Add initial styling for default level(s) page in CTA
* Enh: Allow user to load own CSS file (stored in active theme's directory under /e20r-styles)
* Enh: Clean up code (don't leave unused variables, remove xdebug settings, etc)

= .5 =
* Fix: Path to update checker
* Enh: Update tags in readme.txt

= .4 =
* Fix: Paths to build environment
* Enh: Adding build infrastructure for plugin
* Enh: Add plugin update checker
* Enh: Remove unneeded text replace.
* Enh: CTA headline change

= .3 =
* Initial version