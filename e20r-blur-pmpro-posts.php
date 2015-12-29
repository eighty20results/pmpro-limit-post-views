<?php

use E20R\BLUR_PMPRO_CONTENT as BPPC;
/*
Plugin Name: E20Ro - Blur PMPro Post Content (Add On)
Plugin URI: http://www.paidmembershipspro.com/wp/blur-pmpro-posts/
Description: Integrates with Paid Memberships Pro to deliver a more SEO friendly way to hide/obfuscate post content for posts protected by PMPro.
Version: .1
Author: Eighty/20 Results
Author URI: http://www.eighty20results.com/thomas-sjolshagen
*/
/*
	The idea
	- Allow the user to specify the number of words to include in the visible portion of the post
    - Obfuscate all text except <h1[-n]> and <img> & <a href> tags
    - Use an overlay to "blur" the remaining content
	- Add overlay on top of the blurred out content and let new users sign up/sign in to the appropriate level to access the page/post.
*/

if ( !function_exists( "\\e20rbpc_autoloader")):

    /**
     * Automatically loads the class on 'new'
     *
     * @param $class_name - Name of the class (autoloader)
     * @since 0.1
     */
    function e20rbpc_autoloader($class_name) {

        if (false === stripos($class_name, 'pmpro_')) {
            return;
        }

        $file_parts = explode('\\', $class_name);

        $base = plugin_dir_path(__FILE__) . 'classes';
        $name = strtolower( $file_parts[count($file_parts)-1]);

        if (file_exists("{$base}/class-{$name}.php")) {
            require_once("{$base}/class-{$name}.php");
        }
    }
endif;

if (!function_exists('e20rbpp_write_log')) {
    function e20rbpp_write_log($msg)
    {

        $uplDir = wp_upload_dir();
        $plugin = plugin_basename(__DIR__);

        $dbgRoot = $uplDir['basedir'] . "/${plugin}";
        // $dbgRoot = "${plugin}/";
        $dbgPath = "${dbgRoot}";

        if (WP_DEBUG === true) {

            if (!file_exists($dbgPath)) {

                error_log("write_log() - Creating root directory for debug logging: ${dbgPath}");

                // Create the debug logging directory
                wp_mkdir_p($dbgPath, 0750);

                if (!is_writable($dbgRoot)) {

                    error_log('write_log() - Debug log directory is not writable. exiting.');

                    return;
                }
            }

            // $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . 'e20r_debug_log-' . date('Y-m-d', current_time('timestamp')) . '.txt';
            $filename = preg_split('/\//', $plugin);
            $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . $filename[0] . '.txt';

            $tid = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . (isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : 80))));

            $dbgMsg = '(' . date('d-m-y H:i:s', current_time('timestamp')) . "-{$tid}) -- " .
                ( is_array($msg) || is_object($msg) ? print_r($msg, true) : $msg) . "\n";

            e20rbpp_add_text($dbgMsg, $dbgFile);
        }
    }
}

if (!function_exists('e20rbpp_add_text')) {

    function e20rbpp_add_text($text, $filename)
    {

        if (!file_exists($filename)) {

            touch($filename);
            chmod($filename, 0640);
        }

        if (filesize($filename) > E20R_MAX_LOG_SIZE) {

            $filename2 = "$filename.old";

            if (file_exists($filename2)) {

                unlink($filename2);
            }

            rename($filename, $filename2);
            touch($filename);
            chmod($filename, 0640);
        }

        if (!is_writable($filename)) {

            error_log("Unable to open debug log file ($filename)");
        }

        if (!$handle = fopen($filename, 'a')) {

            error_log("Unable to open debug log file ($filename)");
        }

        if (fwrite($handle, $text) === false) {

            error_log("Unable to write to debug log file ($filename)");
        }

        fclose($handle);
    }
}
// load any wp-admin "stuff"
require_once(plugin_dir_path(__FILE__) . 'includes/admin.php');

// Configure the class autoloader
spl_autoload_register("\\e20rbpc_autoloader");

// load the Blur_Protected_Posts class and init the plugin.
$bpp = new BPPC\blur_pmpro_content();