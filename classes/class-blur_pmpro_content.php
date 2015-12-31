<?php
namespace E20R\BLUR_PMPRO_CONTENT;

use E20R\BLUR_PMPRO_CONTENT as BlurPPC;

class blur_pmpro_content
{

    private static $_this;
    private $options = array();

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

        add_filter('get_e20rbppc_class_instance', array($this, 'get_instance'));
        // add_filter('e20r_blurppc_overlay_class_filter', 'e20r_blurppc_overlay_class');

        remove_all_filters('get_the_excerpt');
        remove_all_filters('the_content');

        add_filter('excerpt_length', array($this, 'set_excerpt_length'), 999);
        add_filter('wp_trim_excerpt', array($this, 'remove_more_text'), 999);
        add_filter('the_content', array($this, 'obfuscate_content'), 999, 2);
        add_filter('get_the_excerpt', array($this, 'obfuscate_content'), 999);
        add_action('wp_enqueue_scripts', array($this, 'load_css'));
    }

    /**
     * @return bpp - Blur Protected Posts object
     *
     * @since 0.1
     */
    public static function get_instance()
    {
        return self::$_this;
    }

    /**
     * @return int|mixed|void
     *
     * @since 0.1
     */
    private function get_excerpt_size()
    {
        return $this->word_count;
    }

    public function set_excerpt_length($length) {

        global $post;
        $words = preg_split('/ /', $post->post_content);

        return count($words);
    }

    public function load_css() {

        if ( file_exists( E20R_BLUR_PMPRO_PLUGIN_DIR . '/css/e20r-blur-pmpro-content.min.css')) {
            wp_enqueue_style('e20r-blur-pmpro-content', E20R_BLUR_PMPRO_PLUGIN_URL . '/css/e20r-blur-pmpro-content.min.css', null, E20R_BLUR_PMPRO_VER);
        } else {
            wp_enqueue_style('e20r-blur-pmpro-content', E20R_BLUR_PMPRO_PLUGIN_URL . '/css/e20r-blur-pmpro-content.css', null, E20R_BLUR_PMPRO_VER);
        }

        if ( file_exists( E20R_BLUR_PMPRO_PLUGIN_DIR . '/js/e20r-blur-pmpro-content.min.js')) {
            wp_enqueue_script('e20r-blur-pmpro-content', E20R_BLUR_PMPRO_PLUGIN_URL . '/js/e20r-blur-pmpro-content.min.js', array('jquery'), E20R_BLUR_PMPRO_VER, true);
        }
    }

    public function remove_more_text($more) {
        return '';
    }

    /**
     * @param $content
     * @return mixed
     *
     * @since 0.1
     */
    public function obfuscate_content($content, $skipcheck = false)
    {
        global $post;

        if (!function_exists('pmpro_has_membership_access')) {

            return $content;
        }

        global $post, $current_user;

        $this->options = get_option('e20r_blurppc_settings',
            array(
                'word_count' => 150,
                'paragraphs' => apply_filters('e20r_blurppc_paragraphs_in_excerpt', 2)
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

        if ($hasaccess) {
            //all good, return content
            return $content;
        } else {

            // From: http://stackoverflow.com/questions/24805636/wordpress-excerpt-by-second-paragraph
            // With gratitude to Clément Malet and Pieter Goosen

            $old_content = $content;
            $content = $post->post_content;
            $content = strip_shortcodes($content);
            $raw_content = $content;
            // $content = apply_filters('the_content', $content);
            $content = wpautop($content);
            $content = $this->fix_autop($content);
            // $content = str_replace('...', '', $content);

            ini_set('xdebug.var_display_max_data', -1);
            ini_set('xdebug.var_display_max_depth', 10);

            // var_dump($content);

            $pattern = "/(\\<a.*\\<\\/a\\>)|(\\<img.*\\>)|(\\<h[1-6]\\>.*\\<\\/h[1-6]\\>)|(\\<blockquote.*\\/blockquote\\>)|(\\<em.*\\/em\\>)|\\<strong.*\\/strong\\>/i";
            $has_html = preg_match_all($pattern, $content, $matches);
            $elements = array();

            if ($has_html && !empty($content)) {

                if (!empty($post->post_excerpt)) {

                    $regular_text = $this->fix_autop(wpautop($post->post_excerpt));
                    $blurred_text = $content;

                } else {

                    $blurred_text = $content;
                    $regular_text = $content;
                }

                $blurred_text = (false !== strrpos($blurred_text, '<p>', -strlen($blurred_text)) ? $blurred_text : "<p>$blurred_text</p>");
                $regular_text = (false !== strrpos($regular_text, '<p>', -strlen($regular_text)) ? $regular_text : "<p>$regular_text</p>");;

                $bt = explode('</p>', $blurred_text);
                $rt = explode('</p>', $regular_text);

                $rt_to_add = array();

                for ($i = 0; $i < $this->options['paragraphs']; ++$i) {

                    if (isset($rt[$i]) && !empty($rt[$i])) {

                        $rt_to_add[$i] = $rt[$i];
                    }
                }

                e20rbpp_write_log("Requested {$this->options['paragraphs']} paragraphs w/standard content. Got: " . count($rt_to_add));
                $regular_text = $this->fix_autop($regular_text);
                $regular_text = implode('</p>', $rt_to_add);

                //reset the variables
                $bt_to_add = array();

                e20rbpp_write_log("The content of the blurred text variable:");
                e20rbpp_write_log($bt);

                // Obfuscate the rest of the content.
                $remainder = ( count($bt) >= $this->options['paragraphs']) ? ((count($bt) - $this->options['paragraphs'])) : $this->options['paragraphs'];
                $start = ($this->options['paragraphs']);

                e20rbpp_write_log("Blurring content: {$remainder} paragraphs, starting at {$start}");
                $i = 0;

                for ( $i = $start; $i < $remainder ; ++$i) {

                    $words = array();

                    if ( isset($bt[$i]) && !empty($bt[$i]) ) {

                        $bt_to_add[$i] = $bt[$i];
                    }
                }
/*

                 foreach (explode(" ", $bt[$i]) as $word) {

                            $org = $word;

                            $word = preg_replace_callback(
                                '/!!(.*)!!/',
                                function ($matches) use ($elements) {

                                    e20rbpp_write_log("Found match for: {$matches[0]}");
                                    e20rbpp_write_log("Returning: {$elements[$matches[0]]}");
                                    // print var_dump($matches);
                                    return $elements[$matches[0]];
                                },
                                $word
                            );

                            $rand = (false !== strpos($bt[$i], "!!") ? $word : $this->randomize_text($word));

                            if (false === strpos($bt[$i], "!!") && strlen($rand) != strlen($org)) {

                                if (1 == preg_match('/(\\<p\\>)/', $org)) {
                                    $rand = '<p>' . $rand;
                                }

                                if (1 == preg_match('/(\\<\\/p\\>)/', $org)) {
                                    $rand = $rand . '</p>';
                                }
                            }

                            $words[] = $rand;
                        }

                        $bt[$i] = implode(" ", $words) . ".";
*/

                $blurred_text = implode('</p>', $bt_to_add);
                $blurred_text = str_replace(']]>', ']]&gt;', $blurred_text);

                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadHTML($blurred_text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                // $non_html = $dom->textContent;

                $elements = array();
                $preserve = array(
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                    'img',
                    'blockquote',
                    'a'
                );

                foreach ($preserve as $e) {

                    e20rbpp_write_log("Processing element type: {$e}");

                    $nodes = $dom->getElementsByTagName($e);
                    $prepared = array();

                    foreach ($nodes as $a_idx => $elem) {

                        $prepared["!!{$e}_element_{$a_idx}!!"] = $dom->saveHTML($elem);
                        // $nodes->item($a_idx)->parentNode->removeChild($elem);
                        $innerHtml = $this->innerHTML($nodes->item($a_idx)->cloneNode(), true, false);
                        e20rbpp_write_log("Inner HTML: ");
                        e20rbpp_write_log($innerHtml);
                        $nodes->item($a_idx)->nodeValue = "!!{$e}_element_{$a_idx}!!";
                    }

                    $elements = array_merge($elements, $prepared);
                }

                $content = $dom->saveHTML();
                $content = str_replace('&Acirc;&nbsp;', ' ', $content);
                // $content = wpautop($content);

                //$content_end = '';
                //$content_end = ' <a href="' . esc_url(get_permalink()) . '">' . '&nbsp;&raquo;&nbsp;' . sprintf(__('Read more: %s &nbsp;&raquo;', 'e20rblurppc'), get_the_title()) . '</a>';
                //$content_more = apply_filters('excerpt_more', ' ' . $content_end);

                // $content .= $content_end;
                $regular_text = '<div class="e20r-visible-content">' . $regular_text . '</div>';
                $regular_text .= '<div class="e20r-blurred-content-overlay">' .  $this->load_overlay() .'<!--googleoff: index--><div class="e20r-blurred-content">';
                $blurred_text .= '</div><!--googleon: index--></div>';

                $content = $regular_text . $blurred_text;
                // $content = wpautop($content);

                // print var_dump($content);
            }

        }

        return $content;
    }
    public function set_levels() {

        global $post;
        global $current_user;

        $level_info = pmpro_has_membership_access($post->ID, $current_user->ID, true);
        $reqd = $level_info[1];

        foreach( $reqd as $level_id ) {

            $reqd_levels[$level_id] = pmpro_getLevel($level_id);
        }

        return $reqd_levels;

    }

    private function load_overlay() {

        add_filter('pmpro_levels_array', array($this, 'set_levels'), 99 );

        $levels_page = get_option( 'pmpro_levels_page_id' );
        $lvlpage = get_post($levels_page);

        e20rbpp_write_log("Levels page is on ID: {$levels_page}");

        ob_start();
        ?>
        <div class="e20r-blur-call-to-action">
            <h2 class="e20r-blur-cta-h1"><?php echo apply_filters('e20rbpc-cta-headline-2', __("Unlock the content", "e20rbpc"));?></h2>
            <h3 class="e20r-blur-cta-h3"><?php echo apply_filters('e20rbpc-cta-headline-3', __("Click the checkout button and get access today", "e20rbpc")); ?></h3>
            <?php echo do_shortcode($lvlpage->post_content);?>
        </div>
        <?php

        return ob_get_clean();
    }

    private function innerHTML( \DOMNode $node, $trim = true, $decode = true) {

        $innerHTML = '';

        foreach ($node->childNodes as $inner_node) {

            $temp_container = new \DOMDocument();
            $temp_container->appendChild($temp_container->importNode($inner_node, true));

            $innerHTML .= ($trim ? trim($temp_container->saveHTML()) : $temp_container->saveHTML());
        }

        return ($decode ? html_entity_decode($innerHTML) : $innerHTML);
    }

    private function fix_autop($content) {

        $html = trim($content);

        if ( $html === '' ) {
            return '';
        }

        $blocktags = 'address|article|aside|audio|blockquote|canvas|caption|center|col|del|dd|div|dl|fieldset|figcaption|figure|footer|form|frame|frameset|h1|h2|h3|h4|h5|h6|header|hgroup|iframe|ins|li|nav|noframes|noscript|object|ol|output|pre|script|section|table|tbody|td|tfoot|thead|th|tr|ul|video';
        $html = preg_replace('~<p>\s*<('.$blocktags.')\b~i', '<$1', $html);
        $html = preg_replace('~</('.$blocktags.')>\s*</p>~i', '</$1>', $html);

        return $html;
    }

    private function randomize_text($text)
    {

        $possible = "abcdefghijklmnopqrstuvwxyz";
        $word = '';

        for ($i = 0; strlen($text) > $i; $i++) {
            $rand = rand(0, strlen($possible) - 1);
            $word .= substr($possible, $rand, 1);
        }
        return $word;
    }

    /**
     * @param $content
     * @return mixed
     *
     * @visibility private
     * @since 0.1
     */
/*    private function get_the_excerpt($content)
    {

        if (null !== $this->options['word_count']) {
            $excerpt = wp_trim_words($content, $this->options['word_count']);
        }

        return $excerpt;
    }
*/
}