<?php
/**
 * Plugin Name: Code Unlock Redirect2 Lite (Stateless Fixed - Single Entry)
 * Description: Protects posts and blogs with unique unlock codes. Visitors can view content after entering the correct code — no cookies or sessions.
 * Version: 1.7
 * Author: Custom Secure
 */

if (!defined('ABSPATH')) exit;

/**
 * ---------------------------------------------------------
 * 1️⃣ Add Unlock Code Meta Box (Posts + Blogs)
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
//                     $value = get_post_meta($post->ID, '_cul_unlock_code', true);
					   $value = get_field('enter_unlock_code' , $post->ID);
                    echo '<label for="cul_unlock_code">' . esc_html__('Enter unlock code:', 'cul-unlock') . '</label>';
                    echo '<input disabled type="text" id="cul_unlock_code" name="cul_unlock_code" value="' . esc_attr($value) . '" style="width:100%;margin-top:5px;" />';
                    echo '<p style="color:#777;font-size:12px;">' . esc_html__('Each post/blog must have a unique code.', 'cul-unlock') . '</p>';
                },
                $screen,
                'side'
            );
        }
    }
});

/**
 * ---------------------------------------------------------
 * 2️⃣ Save Unlock Code (Prevent Duplicates)
 * ---------------------------------------------------------
 */
// add_action('save_post', function ($post_id) {
//     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
//     if (isset($_POST['cul_unlock_code'])) {
//         $new_code = sanitize_text_field(wp_unslash($_POST['cul_unlock_code']));
//         // Check if code already exists elsewhere
//         $existing = get_posts([
//             'post_type'   => ['post', 'blogs'],
//             'meta_key'    => '_cul_unlock_code',
//             'meta_value'  => $new_code,
//             'exclude'     => [$post_id],
//             'numberposts' => 1,
//             'fields'      => 'ids'
//         ]);

//         if (!empty($existing)) {
//             // Keep the previous code unchanged
//             $old = get_post_meta($post_id, '_cul_unlock_code', true);
//             update_post_meta($post_id, '_cul_unlock_code', $old);

//             // Show warning in admin
//             add_action('admin_notices', function () use ($new_code) {
//                 echo '<div class="notice notice-error is-dismissible"><p>'
//                     . sprintf(
//                         esc_html__('⚠️ The unlock code "%s" is already used by another post or blog. Please use a different code.', 'cul-unlock'),
//                         esc_html($new_code)
//                     )
//                     . '</p></div>';
//             });
//             return;
//         }

//         update_post_meta($post_id, '_cul_unlock_code', $new_code);
//     }
// });
// 
add_filter('acf/update_value/name=enter_unlock_code', function ($value, $post_id, $field) {

    if (empty($value)) {
        return $value;
    }

    $existing = get_posts([
        'post_type'      => ['post','blogs'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'   => 'enter_unlock_code',
                'value' => $value
            ]
        ],
        'post__not_in'   => [$post_id]
    ]);

    if (!empty($existing)) {

        // return the old value instead of saving duplicate
        $old_value = get_field('enter_unlock_code', $post_id);

        return $old_value;
    }

    return $value;

}, 10, 3);

/**
 * ---------------------------------------------------------
 * 3️⃣ Unlock Form Shortcode (Masked Input)
 * ---------------------------------------------------------
 */
add_shortcode('cul_unlock_form', function () {
    ob_start(); ?>
    <form method="post" action="">
        <?php wp_nonce_field('cul_unlock_action', 'cul_unlock_nonce'); ?>
        <label for="cul_unlock_code"><?php esc_html_e('Enter your unlock code:', 'cul-unlock'); ?></label><br>
        <input type="text" name="cul_unlock_code" id="cul_unlock_code" required
               style="margin:5px 0;padding:6px;width:100%;" >
        <button type="submit" style="padding:6px 12px;"><?php esc_html_e('Unlock', 'cul-unlock'); ?></button>
    </form>
    <?php
    return ob_get_clean();
});

/**
 * ---------------------------------------------------------
 * 4️⃣ Handle Unlock Form Submission
 * ---------------------------------------------------------
 */
add_action('template_redirect', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['cul_unlock_code'])) return;

    if (
    !empty($_POST['cul_unlock_nonce']) &&
    !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cul_unlock_nonce'])), 'cul_unlock_action')
) {
    wp_die(__('Security check failed. Please try again.', 'cul-unlock'));
}

    $code = sanitize_text_field(wp_unslash($_POST['cul_unlock_code']));

    // Find the matching post/blog
    $found = get_posts([
        'post_type'   => ['post', 'blogs'],
        'meta_key'    => 'enter_unlock_code',
        'meta_value'  => $code,
        'numberposts' => 1,
    ]);

    if (!empty($found)) {
        $post_id = (int) $found[0]->ID;
        $redirect_url = add_query_arg('unlocked', md5($code . NONCE_KEY), get_permalink($post_id));

        // JS redirect to avoid losing POST data
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><script>
            window.location.href = "' . esc_url($redirect_url) . '";
        </script></head><body>
        <p>✅ Code accepted! Redirecting...</p>
        </body></html>';
        exit;
    } else {
        add_filter('the_content', function ($content) {
            return '<p style="color:red;">❌ ' . esc_html__('Invalid unlock code. Please try again.', 'cul-unlock') . '</p>' . $content;
        });
    }
});

/**
 * ---------------------------------------------------------
 * 5️⃣ Protect Locked Posts + Blogs
 * ---------------------------------------------------------
 */
add_action('template_redirect', function () {
    if (!is_single()) return;

    global $post;
    if (empty($post) || empty($post->ID)) return;

//     $code = get_post_meta($post->ID, '_cul_unlock_code', true);
		$code = get_field('enter_unlock_code' , $post->ID);
    if (empty($code)) return;

    $expected_key = md5($code . NONCE_KEY);
    $provided_key = isset($_GET['unlocked']) ? sanitize_text_field(wp_unslash($_GET['unlocked'])) : '';

    if ($provided_key !== $expected_key) {
        wp_safe_redirect(home_url('/enter-code'));
        exit;
    }
});
