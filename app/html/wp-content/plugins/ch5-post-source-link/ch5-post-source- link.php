<?php

/*
Plugin Name: Chapter 5 - Post Source Link
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

add_action('add_meta_boxes', 'ch5_psl_register_meta_box');
add_action('save_post', 'ch5_psl_save_source_data', 10, 2);
add_filter('the_content', 'ch5_psl_display_source_link');

function ch5_psl_register_meta_box()
{
    add_meta_box(
        'ch5_psl_source_meta_box',
        'Post/Page Source',
        'ch5_psl_source_meta_box',
        'post',
        'normal'
    );
    add_meta_box(
        'ch5_psl_source_meta_box',
        'Post/Page Source',
        'ch5_psl_source_meta_box',
        'page',
        'normal'
    );
}

function ch5_psl_source_meta_box($post)
{
    // Retrieve current source name and address for post
    $post_source_name =
        esc_html(get_post_meta(
            $post->ID,
            'post_source_name',
            true
        ));
    $post_source_address =
        esc_html(get_post_meta(
            $post->ID,
            'post_source_address',
            true
        ));
?>
    <!-- Display fields to enter source name and address -->
    <table>
        <tr>
            <td style="width: 100px">Source Name</td>
            <td>
                <input type="text" size="40" name="post_source_name" value="<?php echo $post_source_name; ?>" />
            </td>
        </tr>
        <tr>
            <td>Source Address</td>
            <td>
                <input type="text" size="40" name="post_source_address" value="<?php echo $post_source_address; ?>" />
            </td>
        </tr>
    </table>
<?php }

function ch5_psl_save_source_data(
    $post_id = false,
    $post = false
) {
    // Check post type for posts or pages
    if (
        'post' == $post->post_type ||
        'page' == $post->post_type
    ) {
        // Store data in post meta table if present in post data
        if (isset($_POST['post_source_name'])) {
            update_post_meta(
                $post_id,
                'post_source_name',
                sanitize_text_field(
                    $_POST['post_source_name']
                )
            );
        }
        if (isset($_POST['post_source_address'])) {
            update_post_meta(
                $post_id,
                'post_source_address',
                esc_url($_POST['post_source_address'])
            );
        }
    }
}


function ch5_psl_display_source_link($content)
{
    $post_id = get_the_ID();
    if (!empty($post_id)) {
        if (
            'post' == get_post_type($post_id) ||
            'page' == get_post_type($post_id)
        ) {
            // Retrieve current source name and address for post
            $post_source_name =
                get_post_meta(
                    $post_id,
                    'post_source_name',
                    true
                );
            $post_source_address =
                get_post_meta(
                    $post_id,
                    'post_source_address',
                    true
                );
            // Output information to browser
            if (
                !empty($post_source_name) &&
                !empty($post_source_address)
            ) {
                $content .= '<div class="source_link">Source: ';
                $content .= '<a href="';
                $content .= esc_url($post_source_address);
                $content .= '">' . esc_html($post_source_name);
                $content .= '</a></div>';
            }
        }
    }
    return $content;
}
