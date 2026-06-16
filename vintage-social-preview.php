<?php
/**
 * Plugin Name: Vintage Social Preview
 * Description: Automatically generates a vintage social media preview image for every post with Bangla support.
 * Version: 1.2
 * Author: almahmud
 * Author URI: https://ruqyahbd.org/
 */

if (!defined('ABSPATH'))
    exit;

class Vintage_Social_Preview
{

    public function __construct()
    {
        add_action('wp_head', array($this, 'add_og_image_tag'));
        add_action('template_redirect', array($this, 'handle_image_request'));

        // Rank Math Compatibility (Extreme priority)
        add_filter('rank_math/opengraph/facebook/image', array($this, 'override_rank_math_image'), 9999);
        add_filter('rank_math/opengraph/twitter/image', array($this, 'override_rank_math_image'), 9999);
        add_filter('rank_math/opengraph/image', array($this, 'override_rank_math_image'), 9999);
    }

    /**
     * Overrides Rank Math's default social image with our generated one.
     */
    public function override_rank_math_image($image)
    {
        $post_id = get_queried_object_id();

        // Fallback for ID retrieval
        if (!$post_id) {
            global $post;
            $post_id = isset($post->ID) ? $post->ID : 0;
        }

        if ($post_id > 0 && get_post_type($post_id) === 'post' && !has_post_thumbnail($post_id)) {
            return $this->get_generated_image_url($post_id);
        }

        return $image;
    }

    /**
     * Helper to get the generated image URL for a post.
     */
    private function get_generated_image_url($post_id)
    {
        return add_query_arg(array(
            'vsp_gen' => $post_id,
            'v' => get_post_modified_time('U', false, $post_id)
        ), home_url('/'));
    }

    /**
     * Injects Open Graph and Twitter card meta tags into the head.
     * Only runs if Rank Math or other major SEO plugins aren't handling it, 
     * or as a fallback.
     */
    public function add_og_image_tag()
    {
        // If Rank Math is active, it will handle the tags via the filters in __construct.
        // We only output these tags manually if Rank Math is NOT present to avoid duplication.
        if (defined('RANK_MATH_VERSION')) {
            return;
        }

        if (is_singular()) {
            $post_id = get_queried_object_id();
            if (!$post_id || get_post_type($post_id) !== 'post' || has_post_thumbnail($post_id))
                return;

            $image_url = $this->get_generated_image_url($post_id);

            echo '<!-- Vintage Social Preview -->' . "\n";
            echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
            echo '<meta property="og:image:width" content="1200" />' . "\n";
            echo '<meta property="og:image:height" content="630" />' . "\n";
            echo '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        }
    }

    /**
     * Intercepts requests for the generated image.
     */
    public function handle_image_request()
    {
        if (isset($_GET['vsp_gen'])) {
            $post_id = intval($_GET['vsp_gen']);
            if ($post_id > 0) {
                $this->generate_vsp_image($post_id);
            }
        }
    }

    /**
     * Core image generation logic using GD library.
     */
    private function generate_vsp_image($post_id)
    {
        $post = get_post($post_id);
        if (!$post)
            return;

        $title = $post->post_title;
        $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
        $background_path = $assets_dir . 'background.png';
        $font_path = $assets_dir . 'SutonnyMJ.ttf';

        // Load converter
        require_once plugin_dir_path(__FILE__) . 'Unicode2Bijoy.php';

        if (!file_exists($background_path) || !file_exists($font_path)) {
            wp_die('Vintage Social Preview: Assets missing in plugin directory.');
        }

        // Load background
        $im = @imagecreatefrompng($background_path);
        if (!$im) {
            wp_die('Vintage Social Preview: Failed to load background image.');
        }

        // Get actual image dimensions
        $img_width = imagesx($im);
        $img_height = imagesy($im);

        // Define colors
        $text_color = imagecolorallocate($im, 54, 42, 38); // Deep dark sepia

        // Font settings
        $font_size = 62; // Slightly larger for SutonnyMJ
        $angle = 0;
        $max_width = $img_width * 0.85; // 85% of width 

        // Convert title from Unicode to Bijoy ANSI for exact rendering
        $reshaped_title = \mirazmac\Unicode2Bijoy::convert($title);

        // Split text into lines
        $lines = $this->wrap_text_bangla($reshaped_title, $font_size, $font_path, $max_width);

        // Line and block calculation
        $line_height = $font_size * 1.6;
        $total_height = count($lines) * $line_height;

        // Vertical start position (centered block, but slightly higher up)
        $vertical_offset = 30; // Pixels to shift the block up
        $current_y = (($img_height - $total_height) / 2) + $font_size - $vertical_offset;

        foreach ($lines as $line) {
            // Horizontal centering for each line
            $bbox = imagettfbbox($font_size, $angle, $font_path, $line);
            $line_width = abs($bbox[2] - $bbox[0]);
            $current_x = ($img_width - $line_width) / 2;

            imagettftext($im, $font_size, $angle, $current_x, $current_y, $text_color, $font_path, $line);
            $current_y += $line_height;
        }

        // Output image
        header('Content-Type: image/jpeg');
        imagejpeg($im, NULL, 95);
        imagedestroy($im);
        exit;
    }



    /**
     * Simple word wrapping that respects Unicode/Bangla spaces.
     */
    private function wrap_text_bangla($text, $font_size, $font_path, $max_width)
    {
        $words = explode(' ', $text);
        $lines = array();
        $current_line = '';

        foreach ($words as $word) {
            $test_line = $current_line ? $current_line . ' ' . $word : $word;
            $bbox = imagettfbbox($font_size, 0, $font_path, $test_line);
            $width = abs($bbox[2] - $bbox[0]);

            if ($width > $max_width && !empty($current_line)) {
                $lines[] = $current_line;
                $current_line = $word;
            }
            else {
                $current_line = $test_line;
            }
        }

        if (!empty($current_line)) {
            $lines[] = $current_line;
        }

        return $lines;
    }
}

// Initialize the plugin
new Vintage_Social_Preview();
