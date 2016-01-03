<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 1/3/16
 * Time: 11:51 AM
 */

namespace E20R\BLUR_PROTECTED_CONTENT\MODULES;
use E20R\BLUR_PROTECTED_CONTENT AS BlurPPC;

/**
 * Class BPC_Module
 * @package E20R\BLUR_PROTECTED_CONTENT\MODULES
 * @since 0.8.1
 */
class BPC_Module
{
    private static $module;
    private static $_this;

    /**
     * BPC_Module constructor.
     * @since 0.8.1
     */
    public function __construct()
    {
        self::$module = get_class($this);

        if (isset(self::$_this)) {
            wp_die(
                sprintf(
                    __("Please use the get_bpc_%s_class_instance filter to access this class", "e20rbpc"),
                    self::$module
                )
            );
        }

        self::$_this = $this;

        add_filter("get_bpc_" . self::$module . "_class_instance", array($this, 'get_instance'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Default init function - manages module specific excerpt & content filter functions
     * @since 0.8.1
     */
    public function init() {

        add_action('e20rbpc_remove_excerpt_filters', array($this, "remove_" . self::$module . "_excerpt_filters"));
        add_action('e20rbpc_remove_content_filters', array($this, "remove_" . self::$module . "_content_filters"));
        add_action('e20rbpc_add_excerpt_filters', array($this, "add_" . self::$module . "_excerpt_filters"));
        add_action('e20rbpc_add_content_filters', array($this, "add_" . self::$module . "_content_filters"));

    }

    /**
     * Default plugin activation function (adds the module to the modules option)
     * @since 0.8.1
     */
    public static function activate() {

        $modules = get_option('e20rbpc_modules', array());

        if (!in_array(self::$module, $modules)) {

            $modules[self::$module] = plugin_dir_path(__FILE__) . basename(__FILE__);
            update_option('e20rbpc_modules', $modules, true);
        }
    }

    /**
     * Default plugin deactivation function (removes the module from the modules option)
     * @since 0.8.1
     */
    public static function deactivate() {

        $class = apply_filters("get_bpc_" . self::$module . "_class_instance", null);
        $cn = get_class($class);

        $modules = get_option('e20rbpc_modules', array());

        if (in_array($cn, $modules)) {

            unset($modules[$cn]);
            update_option('e20rbpc_modules', $modules, true);
        }
    }
}