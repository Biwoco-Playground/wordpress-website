<?php

/*
Plugin Name: Chapter 2 - Private Item Text
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

// add_action('wp_enqueue_scripts', 'ch2pit_queue_stylesheet');
add_action('admin_menu', 'ch2pit_settings_menu');
add_action('admin_init', 'ch2pit_admin_init');
add_action('wp_head', 'ch2pit_page_header_output');
add_action('user_new_form', 'ch2pit_show_user_profile');
add_action('edit_user_profile', 'ch2pit_show_user_profile');
add_action('user_register', 'ch2pit_save_user_data');
add_action('profile_update', 'ch2pit_save_user_data');
add_action('restrict_manage_users', 'ch2pit_add_user_filter');
add_action('admin_footer', 'ch2pit_user_filter_js');


add_filter('manage_users_columns', 'ch2pit_add_user_columns');
add_filter(
    'manage_users_custom_column',
    'ch2pit_display_user_columns_data',
    10,
    3
);
add_filter('pre_get_users', 'ch2pit_filter_users');


add_shortcode('paid', 'ch2pit_paid_shortcode');

global $user_levels;
$user_levels = array('regular' => 'Regular', 'paid' => 'Paid');

function ch2pit_page_header_output()
{ ?>
    <style type='text/css'>
        <?php
        $options = ch2pit_get_options();
        echo $options['stylesheet'];
        ?>
    </style>
<?php }

function ch2pit_queue_stylesheet()
{
    wp_enqueue_style(
        'privateshortcodestyle',
        plugins_url('stylesheet.css', __FILE__)
    );
}

register_activation_hook(__FILE__, 'ch2pit_get_options');
function ch2pit_get_options()
{
    $options = get_option('ch2pit_options', array());
    $stylesheet_location = plugin_dir_path(__FILE__) .
        'stylesheet.css';
    $new_options['stylesheet'] =
        file_get_contents($stylesheet_location);
    $merged_options = wp_parse_args($options, $new_options);
    $compare_options = array_diff_key($new_options, $options);
    if (empty($options) || !empty($compare_options)) {
        update_option('ch2pit_options', $merged_options);
    }
    return $merged_options;
}

function ch2pit_settings_menu()
{
    add_options_page(
        'Private Item Text Configuration',
        'Private Item Text',
        'manage_options',
        'ch2pit-private-item-text',
        'ch2pit_config_page'
    );
}

function ch2pit_config_page()
{
    // Retrieve plugin configuration options from database
    $options = ch2pit_get_options(); ?>
    <div id="ch2pit-general" class="wrap">
        <h2>Private Item Text</h2>
        <!-- Code to display confirmation messages when settings
    are saved or reset -->
        <?php if (
            isset($_GET['message']) &&
            $_GET['message'] == '1'
        ) { ?>
            <div id='message' class='updated fade'>
                <p>
                    <strong>Settings Saved</strong>
                </p>
            </div>
        <?php } elseif (
            isset($_GET['message'])
            && $_GET['message'] == '2'
        ) { ?>
            <div id='message' class='updated fade'>
                <p>
                    <strong>Stylesheet reverted to original</strong>
                </p>
            </div>
        <?php } ?>
        <form name="ch2pit_options_form" method="post" action="admin-post.php">
            <input type="hidden" name="action" value="save_ch2pit_options" />
            <?php wp_nonce_field('ch2pit'); ?>
            Stylesheet<br />
            <textarea name="stylesheet" rows="10" cols="40" style="font-
        family:Consolas,Monaco,monospace"><?php echo esc_html(
                                                $options['stylesheet']
                                            ); ?></textarea><br />
            <input type="submit" value="Submit" class="button-primary" />
            <input type="submit" value="Reset" name="resetstyle" class="button-primary" />
        </form>
    </div>
<?php }


function ch2pit_admin_init()
{
    add_action(
        'admin_post_save_ch2pit_options',
        'process_ch2pit_options'
    );
}
function process_ch2pit_options()
{
    // Check that user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    // Check if nonce field is present
    check_admin_referer('ch2pit');
    // Retrieve original plugin options array
    $options = ch2pit_get_options();
    if (isset($_POST['resetstyle'])) {
        $stylesheet_location = plugin_dir_path(__FILE__) .
            'stylesheet.css';
        $options['stylesheet'] = file_get_contents($stylesheet_location);
        $message = 2;
    } else {
        // Cycle through all fields and store their values
        // in the options array
        foreach (array('stylesheet') as $option_name) {
            if (isset($_POST[$option_name])) {
                $options[$option_name] = $_POST[$option_name];
            }
        }
        $message = 1;
    }
    // Store updated options array to database
    update_option('ch2pit_options', $options);
    // Redirect the page to the configuration form
    wp_redirect(add_query_arg(
        array(
            'page' => 'ch2pit-private-item-text',
            'message' => $message
        ),
        admin_url('options-general.php')
    ));
    exit;
}

function ch2pit_show_user_profile($user)
{
    global $user_levels;
    if ('add-new-user' == $user) {
        $current_user_level = '';
    } elseif (!empty($user) && isset($user->data->ID)) {
        $user_id = $user->data->ID;
        $current_user_level = get_user_meta($user_id, 'user_level', true);
    } ?>
    <h3>Site membership level</h3>
    <table class="form-table">
        <tr>
            <th>Level</th>
            <td><select name="user_level" id="user_level">
                    <?php foreach ($user_levels as
                        $user_level_index => $user_level) { ?>
                        <option value="<?php echo $user_level_index; ?>" <?php selected(
                                                                                $current_user_level,
                                                                                $user_level_index
                                                                            ); ?>>
                            <?php echo $user_level; ?></option>
                    <?php } ?>
                </select></td>
        </tr>
    </table>
<?php }


function ch2pit_save_user_data($user_id)
{
    global $user_levels;
    if (
        isset($_POST['user_level']) &&
        !empty($_POST['user_level']) &&
        array_key_exists(
            $_POST['user_level'],
            $user_levels
        )
    ) {
        update_user_meta(
            $user_id,
            'user_level',
            $_POST['user_level']
        );
    } else {
        update_user_meta($user_id, 'user_level', 'regular');
    }
}

function ch2pit_add_user_columns($columns)
{
    $new_columns = array_slice($columns, 0, 2, true) +
        array('level' => 'User Level') +
        array_slice($columns, 2, NULL, true);
    return $new_columns;
}

function ch2pit_display_user_columns_data(
    $val,
    $column_name,
    $user_id
) {
    global $user_levels;
    if ('level' == $column_name) {
        $current_user_level = get_user_meta(
            $user_id,
            'user_level',
            true
        );
        if (!empty($current_user_level)) {
            $val = $user_levels[$current_user_level];
        }
    }
    return $val;
}

function ch2pit_add_user_filter()
{
    global $user_levels;
    $filter_value = '';
    if (isset($_GET['user_level'])) {
        $filter_value = $_GET['user_level'];
    } ?>
    <select name="user_level" class="user_level" style="float:none;">
        <option value="">No filter</option>
        <?php foreach ($user_levels as
            $user_level_index => $user_level) { ?>
            <option value="<?php echo $user_level_index; ?>" <?php selected($filter_value, $user_level_index); ?>>
                <?php echo $user_level; ?></option>
        <?php } ?>
        <input type="submit" class="button" value="Filter">
    <?php }

function ch2pit_user_filter_js()
{
    global $current_screen;
    if ('users' != $current_screen->id) {
        return;
    } ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('.user_level').first().change(function() {
                    jQuery('.user_level').
                    last().val(jQuery(this).val());
                });
                jQuery('.user_level').last().change(function() {
                    jQuery('.user_level').
                    first().val(jQuery(this).val());
                });
            });
        </script>
    <?php }

function ch2pit_filter_users($query)
{
    global $pagenow;
    global $user_levels;
    if (
        is_admin() && 'users.php' == $pagenow &&
        isset($_GET['user_level'])
    ) {
        $filter_value = $_GET['user_level'];
        if (
            !empty($filter_value) &&
            array_key_exists(
                $_GET['user_level'],
                $user_levels
            )
        ) {
            $query->set('meta_key', 'user_level');
            $query->set('meta_query', array(
                array(
                    'key' => 'user_level',
                    'value' => $filter_value
                )
            ));
        }
    }
}


function ch2pit_paid_shortcode($atts, $content = null)
{
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $current_user_level = get_user_meta(
            $current_user->ID,
            'user_level',
            true
        );
        if (
            'paid' == $current_user_level ||
            current_user_can('activate_plugins')
        ) {
            return '<div class="paid">' . $content . '</div>';
        }
    }
    $output = '<div class="register">';
    $output .= 'You need to be a paid member to access ';
    $output .= 'this content.</div>';
    return $output;
}
