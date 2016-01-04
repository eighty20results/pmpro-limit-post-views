<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 */
namespace E20R\BLUR_PROTECTED_CONTENT;

use E20R\BLUR_PROTECTED_CONTENT as BPC;

class blur_protected_content
{
    private static $_this;
    private $options = array();
    private $elements = array();
    private $a_idx = 0;
    private $filters = array();
    private $modules = array();

    /**
     * bpp constructor.
     *
     * @since 0.1
     */
    public function __construct()
    {

        if (isset(self::$_this)) {
            wp_die(sprintf(__("%s is a singleton class and you are not allowed to create a second instance", "e20rblurppc"), get_class($this)));
        }

        self::$_this = $this;

        $this->load_modules();

        // Don't print warnings about bad/incomplete HTML
        libxml_use_internal_errors(true);

        add_filter('get_e20rbpc_class_instance', array($this, 'get_instance'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));
        add_action('wp_loaded', array($this, 'init'));
//        add_action('admin_notices', array($this, 'admin_notice'));
    }

    /**
     * Load any 3rd party module(s)
     * @since 0.8.1
     */
    public function load_modules()
    {
        $modules = get_option('e20rbpc_modules', array());

        foreach( $modules as $class => $path ) {

            e20rbpc_write_log("Attempting to load: {$class} at: {$path}");

            if (!empty($path) && !empty($class)) {

                $success = include_once $path;

                if (!$success) {

                    e20rbpc_write_log("Error: Unable to load {$class} from {$path}");
                    continue;
                }

                $tmp = preg_split("/\//", $class);
                $class_name = $tmp[(count($tmp)-1)];

                $this->modules[] = apply_filters("get_{$class_name}_class_instance", null);
            }
        }

    }

    /**
     * A access this class using the singleton pattern
     *
     * @return bpp - Blur Protected Posts object
     *
     * @since 0.1
     */
    public static function get_instance()
    {
        return self::$_this;
    }

    public function module_error()
    { ?>
        <div class="error notice">
        <p><?php _e('Unable to load one of the content protection modules', 'e20rbpc'); ?></p>
        </div><?php
    }

    /**
     * Load all required filters (and remove any we'd rather not run right now).
     *
     * @since 0.4
     */
    public function init()
    {
        $this->options = get_option('e20rbpc_settings',
            array(
                'paragraphs' => apply_filters('e20rbpc_settings_paragraphs', 2),
                'ctapage' => apply_filters('e20rbpc_settings_ctapage', null),
            )
        );

        $this->clear_filters();

        // Use our own excerpt & content filters
        add_filter('excerpt_length', array($this, 'set_excerpt_length'), 999);
        add_filter('wp_trim_excerpt', array($this, 'remove_more_text'), 999);
        add_filter('the_content', array($this, 'encode_content'), 999);
        add_filter('get_the_excerpt', array($this, 'encode_excerpt'), 999);
        add_filter('the_excerpt', array($this, 'encode_excerpt'), 999);
    }

    /**
     * Preserve existing content & excerpt filters. Then run excerpt & content filters removal hooks
     *
     * @since 0.8
     */
    private function clear_filters()
    {

        // preserve existing filter(s).
        global $wp_filter;

        $this->filters['the_content'] = isset($wp_filter['the_content']) ? $wp_filter['the_content'] : null;
        $this->filters['get_the_excerpt'] = isset($wp_filter['get_the_excerpt']) ? $wp_filter['get_the_excerpt'] : null;
        $this->filters['the_excerpt'] = isset($wp_filter['the_excerpt']) ? $wp_filter['the_excerpt'] : null;
        $this->filters['wp_trim_excerpt'] = isset($wp_filter['wp_trim_excerpt']) ? $wp_filter['wp_trim_excerpt'] : null;
        $this->filters['excerpt_length'] = isset($wp_filter['excerpt_length']) ? $wp_filter['excerpt_length'] : null;

        // Run actions to clear content & excerpt filters
        do_action('e20rbpc_remove_excerpt_filters');
        do_action('e20rbpc_remove_content_filters');
    }

    /**
     * Extend excerpt length to the full content size.
     *
     * @param $length - ignored
     * @return int - Returns # of words in content.
     *
     * @since 0.3
     */
    public function set_excerpt_length($length)
    {

        global $post;
        $words = preg_split('/ /', $post->post_content);

        return count($words) + 100;
    }

    /**
     * Remove any trace of the ellipsis for 'more' in excerpts.
     *
     * @param $more - The current text used to indicate more content
     * @return string - Empty string.
     * @since 0.1
     */
    public function remove_more_text($more)
    {
        return '';
    }

    /**
     * Special function for handling excerpts
     *
     * @param $content - The content
     * @return mixed - Processed excerpt - blurred post->post_content, or combination of $post->post_excerpt & blurred $post->post_content)
     *
     * @since 0.7
     */
    public function encode_excerpt($content)
    {
        e20rbpc_write_log("Processing as an excerpt");
        return $this->encode_content($content);
    }

    /**
     * Encode (hide) text while preserving sentence/paragraph look. Will also preserve key HTML tags for SEO purposes.
     *
     * @param $content - The content for the page.
     * @return mixed - Teaser text plus hidden and encrypted content with Call To Action overlay.
     *
     * @since 0.1
     */
    public function encode_content($content)
    {
        global $post;

        $pmpro_loaded = true;

        $this->clear_filters();

        global $post, $current_user;

        $has_access = apply_filters("e20rbpc_has_content_access_filter", false);

        if (false === $has_access) {

            // Inspired by http://stackoverflow.com/questions/24805636/wordpress-excerpt-by-second-paragraph
            // With gratitude to Clément Malet and Pieter Goosen

            $content = $this->reapply_filters($post->post_content);

            $rt = null;
            $ct_array = explode(PHP_EOL, $content);

            foreach ($ct_array as $line) {

                if (0 == preg_match('/^\s+$/', $line)) {

                    $rt[] = $line;
                }
            }

            unset($ct_array);

            $bt = $rt;
            $rt_to_add = array();
            $bt_to_add = array();

            if (!empty($post->post_excerpt)) {

                e20rbpc_write_log("Using the post excerpt for the visible content");
                $rt = explode(PHP_EOL, $post->post_excerpt);
            }

            $start = ($this->options['paragraphs']);

            // Process content that should remain visible.
            for ($i = 0; $i < $this->options['paragraphs']; ++$i) {

                if (0 != preg_match('/\[.*\]/', $rt[$i], $m)) {

                    $rt[$i] = preg_replace("/\[.*\]/", '', $rt[$i]);
                }

                if (isset($rt[$i]) && !empty($rt[$i])) {

                    $rt_to_add[$i] = '<p>' . $rt[$i];
                }
            }

            e20rbpc_write_log("Requested {$this->options['paragraphs']} paragraphs w/standard content. Got: " . count($rt_to_add));

            //Make remaining content mostly unreadable.
            $regular_text = implode('</p>', $rt_to_add) . '</p>';

            $regular_text = $this->reapply_filters($regular_text);

            e20rbpc_write_log("Making remaining content unreadable, starting at {$start}");

            $i = 0;

            // Process content that should be hidden
            for ($i = $start; $i < count($bt); $i++) {

                if (isset($bt[$i]) && !empty($bt[$i])) {

                    $bt[$i] = $this->process_text($bt[$i]);

                    if ($bt[$i] == '') {
                        continue;
                    }

                    $bt_to_add[$i] = $bt[$i];
                }
            }

            $blurred_text = implode(PHP_EOL, $bt_to_add) . PHP_EOL;
            $blurred_text = str_replace(']]>', ']]&gt;', $blurred_text);

            $blurred_text = $this->reapply_filters($blurred_text);

            // Build the structure of the visible and blurred content.
            $regular_text = '<div class="e20r-blur-content-wrapper clear-after"><div class="e20r-visible-content">' . PHP_EOL . $regular_text . PHP_EOL . '</div>' . PHP_EOL;
            $regular_text .= '<div class="e20r-blurred-content-overlay">' . $this->load_overlay() . '</div>' . PHP_EOL;
            $regular_text .= '<!--googleoff: index-->' . PHP_EOL . '<div class="e20r-blurred-content">' . PHP_EOL;

            $blurred_text .= PHP_EOL . '</div>' . PHP_EOL . '</div><!--googleon: index-->';

            $content = $regular_text . $blurred_text . PHP_EOL;
        }

        $this->reapply_filters($content);
        $this->reset_filters();

        return $content;
    }

    /**
     * Apply the_content filter without entering perma-loop
     *
     * @param $content - content supplied
     * @return mixed|void - Filtered content
     *
     * @since 0.7.2
     */
    private function reapply_filters($content)
    {

        remove_filter('the_content', array($this, 'encode_content'), 999);
        remove_filter('get_the_excerpt', array($this, 'encode_excerpt'), 999);
        remove_filter('the_excerpt', array($this, 'encode_excerpt'), 999);

        $content = apply_filters('the_content', trim($content));

        add_filter('the_content', array($this, 'encode_content'), 999);
        add_filter('get_the_excerpt', array($this, 'encode_excerpt'), 999);
        add_filter('the_excerpt', array($this, 'encode_excerpt'), 999);

        return $content;
    }

    /**
     * Encode the supplied text using random ASCII characters.
     *
     * @param $paragraph - Text from paragraph (w/o extra CR)
     * @return mixed|string - Returns SEO friendly, encoded text/paragraph.
     *
     * @since 0.3
     */
    private function process_text($paragraph)
    {

        if (empty($paragraph) || 0 != preg_match('/^\s+$/', $paragraph)) {
            e20rbpc_write_log("Skipping line, it's empty");
            return $paragraph;
        }

        $paragraph = str_replace(PHP_EOL, '', $paragraph);

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML($paragraph, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $preserve = array(
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'img',
            'blockquote',
            'a'
        );

        foreach ($preserve as $e) {

            $r = null;
            $nodes = $doc->getElementsByTagName($e);

            foreach ($nodes as $elem) {

                $this->elements = array_merge(array("element_" . $this->a_idx => $doc->saveHTML($elem)), $this->elements);

                $r = $doc->createElement('e20r');
                $r->nodeValue = "element_" . $this->a_idx++;

                $elem->parentNode->replaceChild($r, $elem);
            }
        }

        $paragraph = $doc->saveHTML();
        $paragraph = str_replace('&Acirc;&nbsp;', ' ', $paragraph);

        preg_match_all("/\<e20r\>(.*)\<\/e20r\>/", $paragraph, $to_replace, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);
        $wds = preg_split('/\s+/', strip_tags($paragraph), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

        $new_paragraph = '';

        // $paragraph = strip_tags($paragraph);

        foreach ($wds as $match) {

            $word = $match[0];
            $pos = $match[1];

            // Not a replaced tag in the text.
            $word = $this->randomize_text($word);
            $new_paragraph .= $word . " ";
        }

        $paragraph = '<p>' . $new_paragraph . '</p>';

        if (!empty($to_replace[0])) {

            // Have something to do...
            $key = $to_replace[1][0][0];
            $start = $to_replace[0][0][1];
            $paragraph = substr_replace($paragraph, $this->elements[$key], $start, 0);
        }

        unset($doc);

        $paragraph = ucfirst($paragraph);
        return $paragraph;

    }

    /**
     * Text encryption
     *
     * @param $text - The text (line/paragraph/word) to encrypt
     * @return mixed|string|void - The encrypted text.
     *
     * @since 0.3
     */
    private function randomize_text($text)
    {
        $possible = "abcdefghijklmnopqrstuvwxyz";
        $skip = array(
            ':', ';', '.', ',', '\\', '(', ')',
            '[', ']', '{', '}', "'", '>', '<',
            '"', '_', '-', '=', '+', '|', '?'
        );

        $word = '';

        for ($i = 0; strlen($text) > $i; $i++) {

            $char = substr($word, $i, 1);

            if (in_array($char, $skip, true)) {
                $word .= $char;
            } else {
                $rand = rand(0, strlen($possible) - 1);
                $word .= substr($possible, $rand, 1);

            }
        }

        return esc_html($word);
    }

    /**
     * Load the specified CTA page (from settings in /wp-admin)
     *
     * @return string - Overlay HTML to return
     *
     * @since 0.3
     */
    private function load_overlay()
    {

        add_filter('pmpro_levels_array', array($this, 'set_levels'), 99);
        $options = get_option('e20rbpc_settings');

        if (empty($options['ctapage']) || $options['ctapage'] == 0) {

            $levels_page = get_option('pmpro_levels_page_id');
        } else {
            $levels_page = $options['ctapage'];
        }

        e20rbpc_write_log("CTA page ID: {$levels_page}");
        $lvlpage = get_post($levels_page);

        ob_start();
        ?>
        <div class="e20r-blur-call-to-action clear-after">
            <div class="e20r-blur-header"><h2
                    class="e20r-blur-cta-h1"><?php echo apply_filters('e20rbpc-cta-headline-2', __("Unlock this content", "e20rbpc")); ?></h2><span
                    class="e20r-blur-cta-login"><?php echo apply_filters('e20rbpc-cta-login',
                        sprintf(
                            "<a href=\"%s\" title=\"%s\">%s</a>",
                            wp_login_url(get_permalink()),
                            __("Or you can log in to view this content", "e20rbpc"),
                            __("Login", "e20rbpc")
                        )); ?></span></div>
            <h3 class="e20r-blur-cta-h3"><?php echo apply_filters('e20rbpc-cta-headline-3', __("Click the checkout button and get access today", "e20rbpc")); ?></h3>
            <?php echo $this->fix_autop(wpautop(do_shortcode($lvlpage->post_content))); ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Attempt to fix wpautop() function output.
     *
     * @param $content - The content to attempt to fix.
     * @return mixed|string - The (hopefully) fixed content.
     *
     * @since 0.3
     */
    private function fix_autop($content)
    {

        $html = trim($content);

        if ($html === '') {
            return '';
        }

        $blocktags = 'address|article|aside|audio|blockquote|canvas|caption|center|col|del|dd|div|dl|fieldset|figcaption|figure|footer|form|frame|frameset|h1|h2|h3|h4|h5|h6|header|hgroup|iframe|ins|li|nav|noframes|noscript|object|ol|output|pre|script|section|table|tbody|td|tfoot|thead|th|tr|ul|video';
        $html = preg_replace('~<p>\s*<(' . $blocktags . ')\b~i', '<$1', $html);
        $html = preg_replace('~</(' . $blocktags . ')>\s*</p>~i', '</$1>', $html);

        return $html;
    }

    /**
     * Using action(s) to reset all of the filter(s) we removed on init.
     *
     * @since 0.8
     */
    private function reset_filters()
    {
        do_action('e20rbpc_add_excerpt_filters');
        do_action('e20rbpc_add_content_filters');
    }

    /**
     * Return array of memberships that protect the current content.
     *
     * @return mixed - The membership levels that protect the current post/page/content.
     */
    public function set_levels()
    {
        global $post;
        global $current_user;

        $reqd_levels = array();

        $level_info = \pmpro_has_membership_access($post->ID, $current_user->ID, true);
        $reqd = $level_info[1];

        foreach ($reqd as $level_id) {

            $reqd_levels[$level_id] = pmpro_getLevel($level_id);
        }

        return $reqd_levels;

    }

    /**
     * Load admin page styles
     */
    public function enqueue_admin() {

            e20rbpc_write_log("Loading styles for admin pages");
            wp_enqueue_style(
                'e20r-bpc-admin',
                E20R_BPC_PLUGIN_URL . '/css/admin.css',
                null,
                E20R_BPC_VER
            );
    }

    /**
     * Load CSS & JS libraries
     *
     * @since 0.4
     */
    public function enqueue()
    {
        // Load plugin style sheet
        if (file_exists(E20R_BPC_PLUGIN_DIR . '/css/e20r-blur-protected-content.min.css')) {
            wp_enqueue_style(
                'e20r-blur-protected-content',
                E20R_BPC_PLUGIN_URL . '/css/e20r-blur-protected-content.min.css',
                null,
                E20R_BPC_VER
            );
        } else {
            wp_enqueue_style(
                'e20r-blur-protected-content',
                E20R_BPC_PLUGIN_URL . '/css/e20r-blur-protected-content.css',
                null,
                E20R_BPC_VER
            );
        }

        // Load Debug or non-debug version(s) of the JS file(s).
        if (false === WP_DEBUG &&
            file_exists(E20R_BPC_PLUGIN_DIR . '/js/lib/scrollToFixed/jquery-scrolltofixed-min.js')
        ) {
            e20rbpc_write_log("Loading scrollToFixed jQuery plugin for production");
            wp_enqueue_script(
                'jquery-scrolltofixed',
                E20R_BPC_PLUGIN_URL . '/js/lib/scrollToFixed/jquery-scrolltofixed-min.js',
                array('jquery'),
                '1.0.6',
                true
            );

        } else if (true === WP_DEBUG &&
            file_exists(E20R_BPC_PLUGIN_DIR . '/js/lib/scrollToFixed/jquery-scrolltofixed.js')
        ) {
            e20rbpc_write_log("Loading scrollToFixed jQuery plugin for test/debug");
            wp_enqueue_script(
                'jquery-scrolltofixed',
                E20R_BPC_PLUGIN_URL . '/js/lib/scrollToFixed/jquery-scrolltofixed.js',
                array('jquery'),
                '1.0.6',
                true
            );
        }

        // Load Debug or non-debug version(s) of the JS file(s).
        if (false === WP_DEBUG &&
            file_exists(E20R_BPC_PLUGIN_DIR . '/js/e20r-blur-protected-content.min.js')
        ) {
            e20rbpc_write_log("Loading Blur PMPro Content Javascript for production");
            wp_enqueue_script(
                'e20r-blur-protected-content',
                E20R_BPC_PLUGIN_URL . '/js/e20r-blur-protected-content.min.js',
                array('jquery', 'jquery-scrolltofixed'),
                E20R_BPC_VER,
                true
            );
        } else if (true === WP_DEBUG &&
            file_exists(E20R_BPC_PLUGIN_DIR . '/js/e20r-blur-protected-content.js')
        ) {
            e20rbpc_write_log("Loading Blur PMPro Content Javascript for test/debug");
            wp_enqueue_script(
                'e20r-blur-protected-content',
                E20R_BPC_PLUGIN_URL . '/js/e20r-blur-protected-content.js',
                array('jquery', 'jquery-scrolltofixed'),
                E20R_BPC_VER,
                true
            );
        } else {
            wp_enqueue_script(
                'e20r-blur-protected-content',
                E20R_BPC_PLUGIN_URL . '/js/e20r-blur-protected-content.js',
                array('jquery', 'jquery-scrolltofixed'),
                E20R_BPC_VER,
                true
            );
        }

        if (file_exists(get_template_directory() . '/e20r-style/blur-protected-content.css')) {
            wp_enqueue_style(
                'e20r-blur-protected-content-user',
                get_template_directory_uri() . '/e20r-style/blur-protected-content.css',
                array('e20r-blur-protected-content'),
                E20R_BPC_VER
            );
        }

        //Allow loading of CSS from user's template directory
        if (file_exists(get_stylesheet_directory() . '/e20r-style/blur-protected-content.css')) {
            wp_enqueue_style(
                'e20r-blur-protected-content-user',
                get_stylesheet_directory_uri() . '/e20r-style/blur-protected-content.css',
                array('e20r-blur-protected-content'),
                E20R_BPC_VER
            );
        }
    }

    /**
     * Remove <br> html tags, replace them with \n instead.
     *
     * @param $text - The text to strip <br*> tag(s) from.
     * @return mixed - The text w/o the <br*> tags and using \n instead.
     *
     * @since 0.3
     */
    private function br2nl($text)
    {
        $text = preg_replace("/\<br[^\>]*\>/", '\n', $text);
        $text = preg_replace("/\<\/br[^\>]*\>/", '\n', $text);

        return $text;
    }

    /**
     * Imprecise function for detecting whether the $string contains html/xml tags (or anything wrapped in < & >)
     *
     * @param $string - String to test for HTML content
     * @return bool - True if content contains tags
     *
     * @since 0.7.0
     */
    private function is_html($string)
    {
        return preg_match("/<[^<]+>/", $string, $m) != 0;
    }

    /**
     * Test whether the content in post_id requires membership to access
     *
     * @param null $post_id - Post ID to check
     * @return bool - True if post is being protected by PMPro
     *
     * @since 0.1
     */
    private function is_protected($post_id = null)
    {

        global $current_user;

        if (is_null($post_id)) {

            global $post;
            $post_id = $post->ID;
        }

        e20rbpc_write_log("Checking whether {$post_id} is protected by PMPro");
        $level_info = \pmpro_has_membership_access($post_id, $current_user->ID, true);

        return isset($level_info[1]);
    }

    /**
     * Displays the 2nd function in the current stack trace (i.e. the one that called the one that called "me"
     *
     * @access private
     *
     * @since v0.7.1
     */
    private function who_called_me()
    {

        $trace = debug_backtrace();
        $caller = $trace[2];

        $trace = "Called by {$caller['function']}()";
        if (isset($caller['class']))
            $trace .= " in {$caller['class']}()";

        if (isset($caller['args']))
            $trace .= " with args: " . print_r($caller['args'], true);

        return $trace;
    }
}