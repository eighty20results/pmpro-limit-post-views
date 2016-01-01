<?php
namespace E20R\BLUR_PMPRO_CONTENT;

use E20R\BLUR_PMPRO_CONTENT as BlurPPC;

class blur_pmpro_content
{

    private static $_this;
    private $options = array();
    private $elements = array();
    private $a_idx = 0;

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

        add_filter('get_e20rbpc_class_instance', array($this, 'get_instance'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Load all required filters (and remove any we'd rather not run right now).
     */
    public function init() {

        // Strip all excerpt & content filters
        remove_all_filters('get_the_excerpt');
        remove_all_filters('the_content');

        // Use our own excerpt & content filters
        add_filter('excerpt_length', array($this, 'set_excerpt_length'), 999);
        add_filter('wp_trim_excerpt', array($this, 'remove_more_text'), 999);
        add_filter('the_content', array($this, 'encode_content'), 999, 2);
        add_filter('get_the_excerpt', array($this, 'encode_content'), 999);
    }

    /**
     * Function to access this class using the singleton pattern
     *
     * @return bpp - Blur Protected Posts object
     *
     * @since 0.1
     */
    public static function get_instance()
    {
        return self::$_this;
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

        return count($words);
    }

    /**
     * Load CSS & JS libraries
     */
    public function enqueue()
    {

        if (file_exists(E20R_BLUR_PMPRO_PLUGIN_DIR . '/css/e20r-blur-pmpro-content.min.css')) {
            wp_enqueue_style(
                'e20r-blur-pmpro-content',
                E20R_BLUR_PMPRO_PLUGIN_URL . '/css/e20r-blur-pmpro-content.min.css',
                null,
                E20R_BLUR_PMPRO_VER
            );
        } else {
            wp_enqueue_style(
                'e20r-blur-pmpro-content',
                E20R_BLUR_PMPRO_PLUGIN_URL .
                '/css/e20r-blur-pmpro-content.css',
                null,
                E20R_BLUR_PMPRO_VER
            );
        }

        if (false === WP_DEBUG &&
            file_exists(E20R_BLUR_PMPRO_PLUGIN_URL . '/js/lib/scrollToFixed/jquery-scrolltofixed-min.js')
        ) {

            wp_enqueue_script(
                'jquery.scrolltofixed',
                E20R_BLUR_PMPRO_PLUGIN_URL . '/js/lib/scrollToFixed/jquery-scrolltofixed-min.js',
                array('jquery'),
                '1.0.6',
                true
            );

        } else if (true === WP_DEBUG &&
            file_exists(E20R_BLUR_PMPRO_PLUGIN_URL . '/js/lib/scrollToFixed/jquery-scrolltofixed.js')
        ) {

            wp_enqueue_script(
                'jquery.scrolltofixed',
                E20R_BLUR_PMPRO_PLUGIN_URL . '/js/lib/scrollToFixed/jquery-scrolltofixed.js',
                array('jquery'),
                '1.0.6',
                true
            );
        }

        if (false === WP_DEBUG &&
            file_exists(E20R_BLUR_PMPRO_PLUGIN_DIR . '/js/e20r-blur-pmpro-content.min.js')
        ) {
            wp_enqueue_script(
                'e20r-blur-pmpro-content',
                E20R_BLUR_PMPRO_PLUGIN_URL . '/js/e20r-blur-pmpro-content.min.js',
                array('jquery', 'jquery.scrolltofixed'),
                E20R_BLUR_PMPRO_VER,
                true
            );
        } else if (true === WP_DEBUG &&
            file_exists(E20R_BLUR_PMPRO_PLUGIN_DIR . '/js/e20r-blur-pmpro-content.js')
        ) {
            wp_enqueue_script(
                'e20r-blur-pmpro-content',
                E20R_BLUR_PMPRO_PLUGIN_URL . '/js/e20r-blur-pmpro-content.js',
                array('jquery', 'jquery.scrolltofixed'),
                E20R_BLUR_PMPRO_VER,
                true
            );
        }


    }

    /**
     * @param $more - The current text used to indicate more content
     * @return string - Empty string.
     * @since 0.1
     */
    public function remove_more_text($more)
    {
        return '';
    }

    /**
     *
     * Encode (hide) text while preserving sentence/paragraph look. Will also preserve key HTML tags for SEO purposes.
     *
     * @param $content - The content for the page.
     * @return mixed - Teaser text plus hidden and encrypted content with Call To Action overlay.
     *
     * @since 0.1
     */
    public function encode_content($content, $skipcheck = false)
    {
        global $post;

        if (!function_exists('pmpro_has_membership_access')) {

            return $content;
        }

        global $post, $current_user;

        $this->options = get_option('e20rbpc_settings',
            array(
                'paragraphs' => apply_filters('e20rbpc_settings_paragraphs', 2),
                'ctapage' => apply_filters('e20rbpc_settings_ctapage', null)
            )
        );

        if (false === $skipcheck) {
            $hasaccess = pmpro_has_membership_access(NULL, NULL, true);

            if (is_array($hasaccess)) {
                //returned an array to give us the membership level values
                $post_membership_levels_ids = $hasaccess[1];
                $post_membership_levels_names = $hasaccess[2];
                $hasaccess = $hasaccess[0];
            }
        }

        if (!$hasaccess) {

            // Inspired by http://stackoverflow.com/questions/24805636/wordpress-excerpt-by-second-paragraph
            // With gratitude to Clément Malet and Pieter Goosen

            $unused = $content;

            $content = do_shortcode($post->post_content);
            $raw_content = $content;

//            $content = strip_shortcodes($content);

            $rt = null;
            $ct_array = explode(PHP_EOL, $content);

            foreach ($ct_array as $line) {

                if (0 == preg_match('/^\s+$/', $line)) {
                    $rt[] = $line;
                }
            }

            unset($ct_array);

            ini_set('xdebug.var_display_max_data', -1);
            ini_set('xdebug.var_display_max_depth', 10);

            $pattern = "/(\\<a.*\\<\\/a\\>)|(\\<img.*\\>)|(\\<h[1-6]\\>.*\\<\\/h[1-6]\\>)|(\\<blockquote.*\\/blockquote\\>)|(\\<em.*\\/em\\>)|\\<strong.*\\/strong\\>/i";
            $has_html = preg_match($pattern, $content);

            if ( 0 != $has_html && !empty($content)) {

                $bt = $rt;

                $rt_to_add = array();
                $bt_to_add = array();

                if (!empty($post->post_excerpt)) {

                    $rt = explode(PHP_EOL, $post->post_excerpt);
                }

                // $remainder = (count($bt) >= $this->options['paragraphs']) ? ((count($bt) - $this->options['paragraphs'])) : $this->options['paragraphs'];
                $start = ($this->options['paragraphs']);

                for ($i = 0; $i < $this->options['paragraphs']; ++$i) {

                    if (isset($rt[$i]) && !empty($rt[$i])) {

                        $rt_to_add[$i] = '<p>' . $rt[$i];
                    }
                }

                e20rbpc_write_log("Requested {$this->options['paragraphs']} paragraphs w/standard content. Got: " . count($rt_to_add));

                //Make remaining content mostly unreadable.

                $regular_text = implode('</p>', $rt_to_add) . '</p>';
                $regular_text = $this->br2nl($regular_text);

                e20rbpc_write_log("Making remaining content unreadable, starting at {$start}");
                $i = 0;

                for ($i = $start; $i < count($bt); $i++) {

                    if (isset($bt[$i]) && !empty($bt[$i])) {

                        $bt[$i] = $this->process_text($bt[$i]);
                        $bt_to_add[$i] = $bt[$i];
                    }
                }

                $blurred_text = implode(PHP_EOL, $bt_to_add);
                $blurred_text = str_replace(']]>', ']]&gt;', $blurred_text);

                $regular_text = '<div class="e20r-visible-content">' . PHP_EOL . $regular_text . PHP_EOL . '</div>' . PHP_EOL;
                $regular_text .= '<div class="e20r-blurred-content-overlay">' . $this->load_overlay() . '</div>' . PHP_EOL;
                $regular_text .= '<!--googleoff: index-->'. PHP_EOL . '<div class="e20r-blurred-content">' . PHP_EOL;

                $blurred_text .= PHP_EOL . '<!--googleon: index--></div>';

                $content = $regular_text . $blurred_text . PHP_EOL;
            }
        }

        $content = wpautop(do_shortcode($content));
        return $content;
    }

    /**
     * Encode the supplied text using random ASCII characters.
     *
     * @param $paragraph - Text from paragraph (w/o extra CR)
     * @return mixed|string - Returns SEO friendly, encoded text/paragraph.

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

        preg_match_all("/\<e20r\>(.*)\<\/e20r\>/", $paragraph, $to_replace, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER) ;
        $wds = preg_split('/\s+/', $paragraph, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

        $new_paragraph = '';

        foreach ($wds as $match) {

            $word = $match[0];
            $pos = $match[1];

            // Not a replaced tag in the text.
            $word = $this->randomize_text($word);
            $new_paragraph .= $word . " ";
        }

        $paragraph = '<p>' . $new_paragraph . '</p>';

        if ( !empty($to_replace[0])) {

            // Have something to do...
            // $tag_length = strlen($to_replace[0][0][0]) -1;

            $key = $to_replace[1][0][0];
            $start = $to_replace[0][0][1];
            $paragraph = substr_replace($paragraph, $this->elements[$key], $start, 0);
        }

        unset($doc);

        $paragraph = preg_replace('/\<br[\/|\s+\/]\>/', PHP_EOL, $paragraph);
        $paragraph = ucfirst($paragraph);

        return $paragraph;

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
     * Text encryptor
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

            if ( in_array( $char, $skip, true ) ) {
                $word .= $char;
            } else {
                $rand = rand(0, strlen($possible) - 1);
                $word .= substr($possible, $rand, 1);

            }
        }

        return esc_html($word);
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

        $level_info = \pmpro_has_membership_access($post->ID, $current_user->ID, true);
        $reqd = $level_info[1];

        foreach ($reqd as $level_id) {

            $reqd_levels[$level_id] = pmpro_getLevel($level_id);
        }

        return $reqd_levels;

    }

    /**
     * Load the specified CTA page (from settings in /wp-admin)
     *
     * @return string - Overlay HTML to return
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
        <div class="e20r-blur-call-to-action">
            <h2 class="e20r-blur-cta-h1"><?php echo apply_filters('e20rbpc-cta-headline-2', __("Unlock the content", "e20rbpc")); ?></h2>
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

}