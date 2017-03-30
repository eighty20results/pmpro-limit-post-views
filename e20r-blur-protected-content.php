<?php
use E20R\BLUR_PROTECTED_CONTENT as BPPC;

/*
Plugin Name: Eighty/20 Results - Blur Protected Content
Plugin URI: https://www.eighty20results.com/plugins/e20r-blur-protected-content/
Description: Integrates with a content protection plugin to deliver a more SEO friendly way to protect the content.
Version: 0.8.5
Author: Thomas Sjolshagen (Eighty/20 Results)
Author URI: http://www.eighty20results.com/thomas-sjolshagen
*/
/*
	The idea:
	- Allow the admin to specify the number of paragraphs to include in the visible portion of the post
    - Make all text unreadable except first N paragraphs plus <h1[-n]>, <img> & <a href> tags
    - Use an overlay to "blur" the unreadable content
	- Add a call-to-action overlay on top of the unreadable.
    - CTA is starting point for sign up/sign in to the preferred membership level.

   License: MIT
 */
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\');

define('E20R_BPC_VER', '0.8.5');
define('E20R_BPC_PLUGIN_URL', plugins_url('', __FILE__));
define('E20R_BPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
// define('E20R\BLUR_PROTECTED_CONTENT\NS\E20R_MAX_LOG_SIZE', 1024 * 2014 * 3); // In MB

if (!function_exists("\\e20rbpc_autoloader")):

    /**
     * Automatically loads the class on 'new'
     *
     * @param $class_name - Name of the class (autoloader)
     * @since 0.1
     */
    function e20rbpc_autoloader($class_name)
    {

        if (false === stripos($class_name, 'blur_') && (false === stripos($class_name, 'dbg') && (false === stripos($class_name, 'bpc_')) ) ) {
            return;
        }

        $file_parts = explode('\\', $class_name);

        $base = plugin_dir_path(__FILE__) . 'classes';
        $module = plugin_dir_path(__FILE__) . 'modules';
        $tools = "{$base}/tools";

        $name = strtolower($file_parts[count($file_parts) - 1]);

        if (file_exists("{$base}/class-{$name}.php")) {
            require_once("{$base}/class-{$name}.php");
        }

        if (file_exists("{$tools}/class-{$name}.php")) {
            require_once("{$tools}/class-{$name}.php");
        }

        if (file_exists("{$module}/{$name}.php")) {
            require_once("{$module}/{$name}.php");
        }
    }
endif;

if ( ! class_exists('\\PucFactory') ) {
	// Load the update checker for this plugin
	require_once( E20R_BPC_PLUGIN_DIR . '/classes/plugin-update/plugin-update-checker.php' );
}

if ( ! class_exists( 'E20R\\BLUR_PROTECTED_CONTENT\\MODULES\\BPC_Module' ) ) {
	include_once( E20R_BPC_PLUGIN_DIR . 'classes/class-bpc_module.php' );
}

$myUpdateChecker = \PucFactory::buildUpdateChecker(
    'https://eighty20results.com/protected-content/e20r-blur-protected-content/metadata.json',
    __FILE__,
	'e20r-blur-protected-content'
);

// Load wp-admin "stuff"
require_once(E20R_BPC_PLUGIN_DIR . '/includes/admin.php');

// Configure the class autoloader
spl_autoload_register("\\e20rbpc_autoloader");

// load the Blur_Protected_Posts class and init the plugin.
$bpp = new BPPC\blur_protected_content();