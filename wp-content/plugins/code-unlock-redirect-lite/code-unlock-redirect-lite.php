<?php
/**
 * Plugin Name: Code Unlock Redirect Lite (Stateless Fixed)
 * Description: Protects posts and blogs with unique unlock codes. Visitors can view content only after entering the correct code — no cookies or sessions are stored.
 * Version: 1.6
 * Author: Custom Secure
 */

if (!defined('ABSPATH')) exit;

/**
 * ---------------------------------------------------------
 * 1️⃣ Add Unlock Code Meta Box in Post Editor (Posts + Blogs)
 * ---------------------------------------------------------
 */
add_action('add_meta_boxes', function () {
    $screens = ['post', 'blogs'];
    foreach ($screens as $screen) {
        if (post_type_exists($screen)) {
            add_meta_box(
                'cul_unlock_code_box',
                __('Unlock Code', 'cul-unlock'),
                function ($post) {
                    $value = get_post_meta($post->ID, '_cul_unlock_code', true);
                    echo '<label for="cul_unlock_code">' . esc_html__('Enter unlock code:', 'cul-unlock') . '</label>';
                    echo '<input type="text" id="cul_unlock_code" name="cul_unlock_code" value="' . esc_attr($value) . '" style="width:100%;margin-top:5px;" />';
                },
                $screen,
                'side'
            );
        }
    }
});

add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['cul_unlock_code'])) {
        update_post_meta($post_id, '_cul_unlock_code', sanitize_text_field(wp_unslash($_POST['cul_unlock_code'])));
    }
});

/**
 * ---------------------------------------------------------
 * 2️⃣ Unlock Form Shortcode
 * ---------------------------------------------------------
 */
add_shortcode('cul_unlock_form', function () {
    ob_start(); ?>
    <form method="post" action="">
        <?php wp_nonce_field('cul_unlock_action', 'cul_unlock_nonce'); ?>
        <label for="cul_unlock_code"><?php esc_html_e('Enter your unlock code:', 'cul-unlock'); ?></label><br>
        <input type="text" name="cul_unlock_code" id="cul_unlock_code" required style="margin:5px 0;padding:6px;width:100%;">
        <button type="submit" style="padding:6px 12px;"><?php esc_html_e('Unlock', 'cul-unlock'); ?></button>
    </form>
    <?php
    return ob_get_clean();
});

/**
 * ---------------------------------------------------------
 * 3️⃣ Handle Unlock Form Submission
 * ---------------------------------------------------------
 */
add_action('template_redirect', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['cul_unlock_code'])) return;

    if (empty($_POST['cul_unlock_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cul_unlock_nonce'])), 'cul_unlock_action')) {
        wp_die(__('Security check failed. Please try again.', 'cul-unlock'));
    }

    $code = sanitize_text_field(wp_unslash($_POST['cul_unlock_code']));
    $found = get_posts([
        'post_type'   => ['post', 'blogs'],
        'meta_key'    => '_cul_unlock_code',
        'meta_value'  => $code,
        'numberposts' => 1,
    ]);

    if (!empty($found)) {
        $post_id = (int) $found[0]->ID;

        // Redirect with a one-time query key so the next request is valid
        $redirect_url = add_query_arg('unlocked', md5($code . NONCE_KEY), get_permalink($post_id));
        wp_safe_redirect($redirect_url);
        exit;
    } else {
        add_filter('the_content', function ($content) {
            return '<p style="color:red;">❌ ' . esc_html__('Invalid unlock code. Please try again.', 'cul-unlock') . '</p>' . $content;
        });
    }
});

/**
 * ---------------------------------------------------------
 * 4️⃣ Protect Locked Posts + Blogs
 * ---------------------------------------------------------
 */
add_action('template_redirect', function () {
    if (!is_single()) return;

    global $post;
    if (empty($post) || empty($post->ID)) return;

    $code = get_post_meta($post->ID, '_cul_unlock_code', true);
    if (empty($code)) return;

    // Check if unlocked query key is valid
    $expected_key = md5($code . NONCE_KEY);
    $provided_key = isset($_GET['unlocked']) ? sanitize_text_field(wp_unslash($_GET['unlocked'])) : '';

    if ($provided_key !== $expected_key) {
        wp_safe_redirect(home_url('/enter-code'));
        exit;
    }
});
