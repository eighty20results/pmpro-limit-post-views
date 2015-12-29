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
        // remove_filter('get_the_excerpt', 'pmpro_membership_get_excerpt_filter_start', 1);
        // remove_filter('get_the_excerpt', 'pmpro_membership_get_excerpt_filter_end', 100);
        //add_filter('get_the_excerpt', array($this, 'obfuscate_content'), 5);

        remove_filter('the_content', 'pmpro_membership_content_filter', 5);

        add_filter('the_content', array($this, 'obfuscate_content'), 5, 2);
        add_filter('get_the_excerpt', array($this, 'obfuscate_content'));
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

            // $raw_content = $content;

            // $content = get_the_content('');
            $content = strip_shortcodes($content);
            $raw_content = $content;

            ini_set('xdebug.var_display_max_data', -1);
            ini_set('xdebug.var_display_max_depth', 10);

            $pattern = "/(\\<a.*\\<\\/a\\>)|(\\<img.*\\>)|(\\<h[1-6]\\>.*\\<\\/h[1-6]\\>)|(\\<blockquote.*\\/blockquote\\>)|(\\<em.*\\/em\\>)|\\<strong.*\\/strong\\>/i";
            $has_html = preg_match_all($pattern, $content, $matches);
            $elements = array();

            if ($has_html && !empty($content)) {

                if (strlen($content) != strlen($post->post_content)) {
                    $content = $post->post_content;
                }

                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

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
                        e20rbpp_write_log($innerHtml);
                        $nodes->item($a_idx)->nodeValue = "!!{$e}_element_{$a_idx}!!";
                    }

                    $elements = array_merge($elements, $prepared);
                }

                $content = $dom->saveHTML();
                $content = str_replace('&Acirc;&nbsp;', ' ', $content);
                $content = wpautop($content);


                // e20rbpp_write_log($content);

                if (!empty($post->post_excerpt)) {

                    $regular_text =(false !== strrpos($post->post_excerpt, '<p>', -strlen($post->post_excerpt)) ? $post->post_excerpt : "<p>$post->post_excerpt</p>");

                } else {

                    $regular_text = (false !== strrpos($content, '<p>', -strlen($content)) ? $content : "<p>$content</p>");;
                }

                $blurred_text = (false !== strrpos($content, '<p>', -strlen($content)) ? $content : "<p>$content</p>");

                $e = explode('</p>', $regular_text);
                $e_to_add = array();

                for ($i = 0; $i < $this->options['paragraphs']; ++$i) {

                    if (isset($e[$i]) && !empty($e[$i])) {

                        $e_to_add[$i] = $e[$i];
                    }
                }

                e20rbpp_write_log("Requested {$this->options['paragraphs']} paragraphs unblurred. Got: " . count($e_to_add));

                $regular_text = implode('</p>', $e_to_add);

                $regular_text = $this->fix_autop($regular_text);

                //reset the variables
                $e_to_add = array();
                $e = null;

                e20rbpp_write_log("The content of the blurred text variable:");

                $e = explode('</p>', $blurred_text) . "</p>";
                e20rbpp_write_log($e);

                // Obfuscate the rest of the content.
                $remainder = ( count($e) >= $this->options['paragraphs']) ? ((count($e) - $this->options['paragraphs']) - 1) : $this->options['paragraphs'];
                $start = ($this->options['paragraphs']);

                e20rbpp_write_log("Blurring content: {$remainder} paragraphs, starting at {$start}");
                $i = 0;

                for ( $i = $start; $i < $remainder ; ++$i) {

                    $words = array();

                    if ( isset($e[$i]) && !empty($e[$i]) ) {

                        foreach (explode(" ", $e[$i]) as $word) {

                            $org = $word;

                            $word = preg_replace_callback(
                                "/\\!\\!\\(.*\\)\\!\\!/",
                                function ($matches) use ($elements) {

                                    e20rbpp_write_log("Found match for: {$matches[0]}");
                                    // print var_dump($matches);
                                    return $elements[$matches[0]];
                                },
                                $word
                            );

                            $rand = (false !== strpos($org, "!!") ? $word : $this->randomize_text($word));

                            if (false === strpos($org, "!!") && strlen($rand) != strlen($org)) {

                                if (1 == preg_match('/(\\<p\\>)/', $org)) {
                                    $rand = '<p>' . $rand;
                                }

                                if (1 == preg_match('/(\\<\\/p\\>)/', $org)) {
                                    $rand = $rand . '</p>';
                                }
                            }

                            $words[] = $rand;
                        }
                    }

                    // print var_dump($words);
                    $e[$i] = implode(" ", $words) . ".";
                    $e_to_add[$i] = ucfirst($e[$i]);

                }



                $blurred_text = implode('</p>', $e_to_add);
                $blurred_text = str_replace(']]>', ']]&gt;', $blurred_text);

                //$content_end = '';
                //$content_end = ' <a href="' . esc_url(get_permalink()) . '">' . '&nbsp;&raquo;&nbsp;' . sprintf(__('Read more: %s &nbsp;&raquo;', 'e20rblurppc'), get_the_title()) . '</a>';
                //$content_more = apply_filters('excerpt_more', ' ' . $content_end);

                // $content .= $content_end;
                $regular_text .= '<div class=""><!--googleoff: index-->';
                $blurred_text .= '</div><!--googleon: index-->';

                $content = $regular_text . $blurred_text;
                $content = wpautop($content);

                // print var_dump($content);
            }
        }

        return $content;
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
    private function get_the_excerpt($content)
    {

        if (null !== $this->options['word_count']) {
            $excerpt = wp_trim_words($content, $this->options['word_count']);
        }

        return $excerpt;

    }
}