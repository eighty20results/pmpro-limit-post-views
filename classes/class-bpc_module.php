<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 1/3/16
 * Time: 11:51 AM
 */

namespace E20R\BLUR_PROTECTED_CONTENT\MODULES;

use E20R\BLUR_PROTECTED_CONTENT AS BPC;

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
        self::$module = get_class(get_parent_class($this));

        if (isset(self::$_this)) {
            wp_die(
                sprintf(
                    __("Please use the get_bpc_%s_class_instance filter to access this class", "e20rbpc"),
                    self::$module
                )
            );
        }

        e20rbpc_write_log("Base module class loading: " . $this->module);

        self::$_this = $this;
        $tmp = preg_split("/\//", $self::module);
        $class_name = $tmp[(count($tmp)-1)];

        add_filter("get_bpc_" .$class_name . "_class_instance", array($this, 'get_instance'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Default plugin activation function (adds the module to the modules option)
     * @since 0.8.1
     */
    public static function activate()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "activate-plugin_{$plugin}" );

        self::$module = get_called_class();
        e20rbpc_write_log("Running activate() for " . self::$module . " module class");

        $modules = get_option('e20rbpc_modules', array());

        if (!in_array(self::$module, $modules)) {

            e20rbpc_write_log("Adding module");
            $reflector = new \ReflectionClass(self::$module);
            $file = $reflector->getFileName();

            $modules[self::$module] = $file;
            update_option('e20rbpc_modules', $modules, true);
        }

        e20rbpc_write_log("class::activate() - Content of module array: ");
        e20rbpc_write_log($modules);
    }

    /**
     * Default plugin deactivation function (removes the module from the modules option)
     * @since 0.8.1
     */
    public static function deactivate()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "deactivate-plugin_{$plugin}" );

        self::$module = get_called_class();

        e20rbpc_write_log("Running deactivate (via parent) for " . self::$module . " module class");

        $modules = get_option('e20rbpc_modules', array());

        if (in_array(self::$module, $modules)) {

            e20rbpc_write_log("Removing module");
            unset($modules[self::$module]);
            update_option('e20rbpc_modules', $modules, true);
        }

        e20rbpc_write_log("clas::deactivate() - Content of module array: ");
        e20rbpc_write_log($modules);

    }

    /**
     * Default init function - manages module specific excerpt & content filter functions
     * @since 0.8.1
     */
    public function init()
    {

        e20rbpc_write_log("Running init() for base module class");
        add_action('e20rbpc_remove_excerpt_filters', array($this, "remove_" . $this->module . "_excerpt_filters"));
        add_action('e20rbpc_remove_content_filters', array($this, "remove_" . $this->module . "_content_filters"));
        add_action('e20rbpc_add_excerpt_filters', array($this, "add_" . $this->module . "_excerpt_filters"));
        add_action('e20rbpc_add_content_filters', array($this, "add_" . $this->module . "_content_filters"));

    }
}