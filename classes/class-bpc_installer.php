<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 1/3/16
 * Time: 6:27 PM
 */

namespace E20R\BLUR_PROTECTED_CONTENT\MODULES;

class bpc_installer
{
    private static $_this;

    public function __construct() {

        if(isset(self::$_this)) {
            wp_die(
                sprintf(
                    __("Please use the get_%s_class_instance filter to access this class", "e20rbpc"),
                    get_class(static::$_this)
                )
            );
        }

        self::$_this = $this;

        apply_filters('get_bpc_installer_class_instance', array($this, 'get_instance'));
    }

    public function get_instance() {
        return self::$_this;
    }

    public function display_addons() {

    }

    public function install_addon() {

    }
}