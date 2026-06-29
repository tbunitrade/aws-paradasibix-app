<?php
/**
 * Plugin Name: ParadiseBox Custom Fixes
 * Description: Custom WooCommerce and Elementor fixes for Paradise in a Box.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Clean product rating: no review-count link, add small average like (4.7)
add_filter('woocommerce_product_get_rating_html', function ($html, $rating, $count) {
    if ($rating <= 0) {
        return '';
    }

    $avg = number_format((float) $rating, 1, '.', '');
    $width_pct = max(0, min(100, ($rating / 5) * 100));

    $stars  = '<div class="star-rating" role="img" aria-label="' .
              esc_attr(sprintf(__('Rated %s out of 5', 'woocommerce'), $avg)) . '">';
    $stars .= '<span style="width:' . esc_attr($width_pct) . '%"></span>';
    $stars .= '</div>';

    return $stars . '<span class="rating-number"> (' . esc_html($avg) . ')</span>';
}, 10, 3);

// Move BOS4W ".bos4w-display-wrap" BEFORE Elementor widget .elementor-element-96ab053
add_action( 'wp_enqueue_scripts', function () {
    if ( ! function_exists('is_product') || ! is_product() ) {
        return;
    }

    wp_enqueue_script('jquery');

    $script = <<<JS
    (function($){
      function moveBox(){
        var \$box = $('.bos4w-display-wrap').first();
        var \$target = $('.elementor-element-96ab053').first();

        if (\$box.length && \$target.length) {
          if (!\$box.next().is(\$target)) {
            \$target.before(\$box);
          }
          return true;
        }

        return false;
      }

      function tryMove(tries){
        if (moveBox()) return;
        if (tries <= 0) return;
        setTimeout(function(){ tryMove(tries-1); }, 150);
      }

      $(function(){
        tryMove(60);

        var root = document.querySelector('.elementor') || document.body;
        if (!root) return;

        var obs = new MutationObserver(function(){ moveBox(); });
        obs.observe(root, {childList:true, subtree:true});
      });
    })(jQuery);
    JS;

    wp_add_inline_script('jquery', $script);
}, 20);

add_action('wp_head', function () {
    if ( ! function_exists('is_product') || ! is_product() ) {
        return;
    }

    echo '<style>.bos4w-display-wrap{margin:0 0 16px 0; width:100%;}</style>';
});

// ACF Vimeo video embed shortcode: [acf_video_embed]
// Reads ACF field "vimeo_video_link" from current post/blog and renders Vimeo iframe via oEmbed.
add_shortcode('acf_video_embed', function () {
    if ( ! is_singular() ) {
        return '';
    }

    $post_id = get_the_ID();

    if ( ! $post_id ) {
        return '';
    }

    $url = '';

    if ( function_exists('get_field') ) {
        $url = get_field('vimeo_video_link', $post_id);
    }

    if ( ! $url ) {
        $url = get_post_meta($post_id, 'vimeo_video_link', true);
    }

    $url = trim((string) $url);

    if ( ! $url ) {
        return '';
    }

    $embed = wp_oembed_get($url);

    if ( ! $embed ) {
        return '<p>Video unavailable.</p>';
    }

    return '<div class="acf-video-embed">' . $embed . '</div>';
});

add_filter('wpcf7_validate_email*', 'pb_block_bad_cf7_email', 20, 2);
add_filter('wpcf7_validate_email', 'pb_block_bad_cf7_email', 20, 2);

function pb_block_bad_cf7_email($result, $tag) {
    $blocked_emails = array(
        'paradiseinabox@tkbuddy.com',
    );

    $name = $tag->name;

    if (!$name) {
        return $result;
    }

    $submission = WPCF7_Submission::get_instance();

    if (!$submission) {
        return $result;
    }

    $posted_data = $submission->get_posted_data();

    if (empty($posted_data[$name])) {
        return $result;
    }

    $email = strtolower(trim($posted_data[$name]));

    if (in_array($email, $blocked_emails, true)) {
        $result->invalidate($tag, 'This email address is not allowed.');
    }

    return $result;
}


add_filter('wpcf7_validate_email*', 'pb_block_bad_cf7_email_domain', 20, 2);
add_filter('wpcf7_validate_email', 'pb_block_bad_cf7_email_domain', 20, 2);

function pb_block_bad_cf7_email_domain($result, $tag) {
    $blocked_domains = array(
        'tkbuddy.com',
    );

    $name = $tag->name;

    if (!$name) {
        return $result;
    }

    $submission = WPCF7_Submission::get_instance();

    if (!$submission) {
        return $result;
    }

    $posted_data = $submission->get_posted_data();

    if (empty($posted_data[$name])) {
        return $result;
    }

    $email = strtolower(trim($posted_data[$name]));

    if (!is_email($email)) {
        return $result;
    }

    $domain = substr(strrchr($email, '@'), 1);

    if (in_array($domain, $blocked_domains, true)) {
        $result->invalidate($tag, 'This email domain is not allowed.');
    }

    return $result;
}


// Newsletter popup control: show Elementor popup #161 only for new visitors.
add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }

    if (is_user_logged_in()) {
        return;
    }

    $popup_id = 161;
    ?>
    <script>
    (function () {
        const POPUP_ID = <?php echo (int) $popup_id; ?>;
        const COOKIE_NAME = 'pb_newsletter_popup_done';
        const SESSION_NAME = 'pb_newsletter_popup_seen_this_session';

        function getCookie(name) {
            return document.cookie
                .split('; ')
                .find(row => row.startsWith(name + '='));
        }

        function setCookie(name, value, days) {
            const maxAge = days * 24 * 60 * 60;

            document.cookie = name + '=' + encodeURIComponent(value)
                + '; Max-Age=' + maxAge
                + '; Path=/'
                + '; SameSite=Lax'
                + (location.protocol === 'https:' ? '; Secure' : '');
        }

        function shouldSkipPopup() {
            if (document.body && document.body.classList.contains('logged-in')) {
                return true;
            }

            if (getCookie(COOKIE_NAME)) {
                return true;
            }

            if (sessionStorage.getItem(SESSION_NAME)) {
                return true;
            }

            return false;
        }

        function markClosed() {
            setCookie(COOKIE_NAME, 'closed', 14);
            sessionStorage.setItem(SESSION_NAME, '1');
        }

        function markSubmitted() {
            setCookie(COOKIE_NAME, 'submitted', 365);
            sessionStorage.setItem(SESSION_NAME, '1');
        }

        function openPopup() {
            if (shouldSkipPopup()) {
                return;
            }

            if (
                window.elementorProFrontend &&
                elementorProFrontend.modules &&
                elementorProFrontend.modules.popup
            ) {
                sessionStorage.setItem(SESSION_NAME, '1');
                elementorProFrontend.modules.popup.showPopup({ id: POPUP_ID });
            }
        }

        window.addEventListener('load', function () {
            setTimeout(openPopup, 5000);
        });

        document.addEventListener('click', function (event) {
            if (
                event.target.closest('.dialog-close-button') ||
                event.target.closest('.dialog-lightbox-close-button') ||
                event.target.closest('.elementor-popup-modal .dialog-close-button') ||
                event.target.closest('.elementor-popup-modal .dialog-lightbox-close-button')
            ) {
                markClosed();
            }
        });

        if (window.jQuery) {
            jQuery(window).on('elementor-pro/forms/new-record-success', function () {
                markSubmitted();
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (!document.body) {
                return;
            }

            const observer = new MutationObserver(function () {
                const successMessage = document.querySelector(
                    '.elementor-message-success, .elementor-form .elementor-message-success'
                );

                if (successMessage) {
                    markSubmitted();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    })();
    </script>
    <?php
}, 100);

