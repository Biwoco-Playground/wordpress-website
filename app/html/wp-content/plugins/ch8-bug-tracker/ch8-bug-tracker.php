<?php

/*
Plugin Name: Chapter 8 - Bug Tracker
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

register_activation_hook(__FILE__, 'ch8bt_activation');

add_action('admin_menu', 'ch8bt_settings_menu');
add_action('admin_init', 'ch8bt_admin_init');
add_action('admin_post_delete_ch8bt_bug', 'delete_ch8bt_bug');
add_shortcode('bug-tracker-list', 'ch8bt_shortcode_list');
add_action('admin_post_import_ch8bt_bug', 'import_ch8bt_bug');
add_action('wp_head', 'ch8bt_declare_ajaxurl');
add_action('wp_ajax_ch8bt_buglist_ajax', 'ch8bt_buglist_ajax');
add_action(
    'wp_ajax_nopriv_ch8bt_buglist_ajax',
    'ch8bt_buglist_ajax'
);
add_action('wp_enqueue_scripts', 'ch8bt_load_jquery');

function ch8bt_activation()
{
    // Get access to global database access class
    global $wpdb;
    // Create table on main blog in network mode or single blog
    ch8bt_create_table($wpdb->get_blog_prefix());
}

function ch8bt_create_table($prefix)
{
    // Prepare SQL query to create database table
    // using function parameter
    $creation_query = 'CREATE TABLE ' . $prefix .
        'ch8_bug_data (
        `bug_id` int(20) NOT NULL AUTO_INCREMENT,
        `bug_description` text,
        `bug_version` varchar(10) DEFAULT NULL,
        `bug_report_date` date DEFAULT NULL,
        `bug_status` int(3) NOT NULL DEFAULT 0,
        `bug_title` VARCHAR( 128 ) NULL,
        PRIMARY KEY (`bug_id`)
        );';
    global $wpdb;
    $wpdb->query($creation_query);

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($creation_query);
}

function ch8bt_settings_menu()
{
    add_options_page(
        'Bug Tracker Data Management',
        'Bug Tracker',
        'manage_options',
        'ch8bt-bug-tracker',
        'ch8bt_config_page'
    );
}
function ch8bt_config_page()
{
    global $wpdb;
?>
    <!-- Top-level menu -->
    <div id="ch8bt-general" class="wrap">
        <h2>Bug Tracker <a class="add-new-h2" href="<?php echo
                                                    add_query_arg(
                                                        array(
                                                            'page' => 'ch8bt-bug-tracker',
                                                            'id' => 'new'
                                                        ),
                                                        admin_url('options-general.php')
                                                    ); ?>">
                Add New Bug</a></h2>
        <!-- Display bug list if no parameter sent in URL -->
        <?php if (empty($_GET['id'])) {
            $bug_query = 'select * from ' . $wpdb->get_blog_prefix();
            $bug_query .= 'ch8_bug_data ORDER by bug_report_date DESC';
            if ($search_mode) {
                $bug_items = $wpdb->get_results(
                    $wpdb->prepare(
                        $bug_query,
                        $search_term,
                        $search_term
                    ),
                    ARRAY_A
                );
            } else {
                $bug_items = $wpdb->get_results($bug_query, ARRAY_A);
            }
        ?>
            <h3>Manage Bug Entries</h3>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="delete_ch8bt_bug" />
                <!-- Adding security through hidden referrer field -->
                <?php wp_nonce_field('ch8bt_deletion'); ?>
                <table class="wp-list-table widefat fixed">
                    <thead>
                        <tr>
                            <th style="width: 50px"></th>
                            <th style='width: 80px'>ID</th>
                            <th style="width: 80px">ID</th>
                            <th style="width: 300px">Title</th>
                            <th>Version</th>
                        </tr>
                    </thead>
                    <?php
                    // Display bugs if query returned results
                    if ($bug_items) {
                        foreach ($bug_items as $bug_item) {
                            echo '<tr style="background: #FFF">';
                            echo '<td><input type="checkbox" name="bugs[]" value="';
                            echo intval($bug_item['bug_id']) . '" /></td>';
                            echo '<td>' . $bug_item['bug_id'] . '</td>';
                            echo '<td><a href="';
                            echo add_query_arg(
                                array(
                                    'page' => 'ch8bt-bug-tracker',
                                    'id' => $bug_item['bug_id']
                                ),
                                admin_url('options-general.php')
                            );
                            echo '">' . $bug_item['bug_title'] . '</a></td>';
                            echo '<td>' . $bug_item['bug_version'];
                            echo '</td></tr>';
                        }
                    } else {
                        echo '<tr style="background: #FFF">';
                        echo '<td colspan="4">No Bug Found</td></tr>';
                    }
                    ?>
                </table><br />
                <input type="submit" value="Delete Selected" class="button-primary" />
            </form>
            <!-- Form to upload new bugs in csv format -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_ch8bt_bug" />
                <!-- Adding security through hidden referrer field -->
                <?php wp_nonce_field('ch8bt_import'); ?>
                <h3>Import Bugs</h3>
                <div class="import_data">Import Bugs from CSV File
                    (<a href="<?php echo plugins_url(
                                    'importtemplate.csv',
                                    __FILE__
                                ); ?>">Template</a>)
                    <input name="import_bugs_file" type="file" />
                </div>
                <input type="submit" value="Import" class="button-primary" />
            </form>
        <?php } elseif (
            isset($_GET['id']) &&
            ('new' == $_GET['id'] ||
                is_numeric($_GET['id']))
        ) {
            $bug_id = intval($_GET['id']);
            $mode = 'new';
            // Query database if numeric id is present
            if ($bug_id > 0) {
                $bug_query = 'select * from ' . $wpdb->get_blog_prefix();
                $bug_query .= 'ch8_bug_data where bug_id = %d';
                $bug_data =
                    $wpdb->get_row(
                        $wpdb->prepare($bug_query, $bug_id),
                        ARRAY_A
                    );
                // Set variable to indicate page mode
                if ($bug_data) {
                    $mode = 'edit';
                }
            }
            if ('new' == $mode) {
                $bug_data = array(
                    'bug_title' => '', 'bug_description' => '',
                    'bug_version' => '', 'bug_status' => ''
                );
            }
            // Display title based on current mode
            if ('new' == $mode) {
                echo '<h3>Add New Bug</h3>';
            } elseif ('edit' == $mode) {
                echo '<h3>Edit Bug #' . $bug_data['bug_id'] . ' - ';
                echo $bug_data['bug_title'] . '</h3>';
            }
        ?>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_ch8bt_bug" />
                <input type="hidden" name="bug_id" value="<?php echo $bug_id; ?>" />
                <!-- Adding security through hidden referrer field -->
                <?php wp_nonce_field('ch8bt_add_edit'); ?>
                <!-- Display bug editing form -->
                <table>
                    <tr>
                        <td style="width: 150px">Title</td>
                        <td><input type="text" name="bug_title" size="60" value="<?php echo esc_html(
                                                                                        $bug_data['bug_title']
                                                                                    ); ?>" /></td>
                    </tr>
                    <tr>
                        <td>Description</td>
                        <td><textarea name="bug_description" cols="60"><?php echo
                                                                        esc_textarea($bug_data['bug_description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <td>Version</td>
                        <td><input type="text" name="bug_version" value="<?php echo esc_html(
                                                                                $bug_data['bug_version']
                                                                            ); ?>" /></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>
                            <select name="bug_status">
                                <?php
                                // Display drop-down list of bug statuses
                                $bug_statuses = array(
                                    0 => 'Open', 1 => 'Closed',
                                    2 => 'Not-a-Bug'
                                );
                                foreach ($bug_statuses as $status_id => $status) {
                                    // Add selected tag when entry matches
                                    echo '<option value="' . $status_id . '" ';
                                    selected(
                                        $bug_data['bug_status'],
                                        $status_id
                                    );
                                    echo '>' . $status;
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <input type="submit" value="Submit" class="button-primary" />
            </form>
        <?php } ?>
    </div>
<?php }


function ch8bt_admin_init()
{
    add_action('admin_post_save_ch8bt_bug', 'process_ch8bt_bug');
}

function process_ch8bt_bug()
{
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    // Check if nonce field is present for security
    check_admin_referer('ch8bt_add_edit');
    global $wpdb;
    // Place all user submitted values in an array (or empty
    // strings if no value was sent)
    $bug_data = array();
    $bug_data['bug_title'] = (isset($_POST['bug_title']) ?
        sanitize_text_field($_POST['bug_title']) : '');
    $bug_data['bug_description'] =
        (isset($_POST['bug_description']) ?
            sanitize_text_field($_POST['bug_description']) : '');
    $bug_data['bug_version'] = (isset($_POST['bug_version']) ?
        sanitize_text_field($_POST['bug_version']) : '');
    // Set bug report date as current date
    $bug_data['bug_report_date'] = date('Y-m-d');
    // Set status of all new bugs to 0 (Open)
    $bug_data['bug_status'] = (isset($_POST['bug_status']) ?
        intval($_POST['bug_status']) : 0);
    // Call the wpdb insert or update method based on value
    // of hidden bug_id field
    if (isset($_POST['bug_id']) && 0 == $_POST['bug_id']) {
        $wpdb->insert(
            $wpdb->get_blog_prefix() . 'ch8_bug_data',
            $bug_data
        );
    } elseif (
        isset($_POST['bug_id']) &&
        $_POST['bug_id'] > 0
    ) {
        $wpdb->update(
            $wpdb->get_blog_prefix() . 'ch8_bug_data',
            $bug_data,
            array('bug_id' => intval($_POST['bug_id']))
        );
    }
    // Redirect the page to the user submission form
    wp_redirect(add_query_arg(
        'page',
        'ch8bt-bug-tracker',
        admin_url('options-general.php')
    ));
    exit;
}

function delete_ch8bt_bug()
{
    // Check that user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    // Check if nonce field is present
    check_admin_referer('ch8bt_deletion');
    // If bugs are present, cycle through array and call SQL
    // command to delete entries one by one
    if (!empty($_POST['bugs'])) {
        // Retrieve array of bugs IDs to be deleted
        $bugs_to_delete = $_POST['bugs'];
        global $wpdb;
        foreach ($bugs_to_delete as $bug_to_delete) {
            $query = 'DELETE from ' . $wpdb->get_blog_prefix();
            $query .= 'ch8_bug_data WHERE bug_id = %d';
            $wpdb->query($wpdb->prepare(
                $query,
                intval($bug_to_delete)
            ));
        }
    }
    // Redirect the page to the user submission form
    wp_redirect(add_query_arg(
        'page',
        'ch8bt-bug-tracker',
        admin_url('options-general.php')
    ));
    exit;
}


function ch8bt_shortcode_list()
{
    global $wpdb;

    if (!empty($_GET['searchbt'])) {
        $search_string = sanitize_text_field($_GET['searchbt']);
        $search_mode = true;
    } else {
        $search_string = "Search...";
        $search_mode = false;
    }

    $bug_query = 'select * from ' . $wpdb->get_blog_prefix();
    $bug_query .= 'ch8_bug_data ';
    $bug_query .= 'where bug_status = 0 ';

    // Add search string in query if present
    if ($search_mode) {
        $search_term = '%' . $search_string . '%';
        $bug_query .= "and ( bug_title like '%s' ";
        $bug_query .= "or bug_description like '%s' ) ";
    } else {
        $search_term = '';
    }

    $bug_query .= 'ORDER by bug_id DESC';
    $bug_items =  $wpdb->get_results($bug_query, ARRAY_A);
    // Prepare output to be returned to replace shortcode
    $output = '';

    $output .= '<div class="ch8_bt_search">';
    $output .= '<form method="get" id="ch8_bt_search">';
    $output .= '<div>Search bugs ';
    $output .= '<input type="text" onfocus="this.value=\'\'" ';
    $output .= 'value="' . esc_html($search_string) . '" ';
    $output .= 'name="searchbt" />';
    $output .= '<input type="submit" value="Search" />';
    $output .= '</div>';
    $output .= '</form></div>';

    $output .= '<div class="show_closed_bugs">';
    $output .= 'Show closed bugs';
    $output .= '</div>';

    $output .= '<div class="bug-tracker-list"><table>';
    // Check if any bugs were found
    if (!empty($bug_items)) {
        $output .= '<tr><th style="width: 80px">ID</th>';
        $output .= '<th style="width: 300px">Title / Desc</th>';
        $output .= '<th>Version</th></tr>';
        // Create row in table for each bug
        foreach ($bug_items as $bug_item) {
            $output .= '<tr style="background: #FFF">';
            $output .= '<td>' . $bug_item['bug_id'] . '</td>';
            $output .= '<td>' . $bug_item['bug_title'] . '</td>';
            $output .= '<td>' . $bug_item['bug_version'] . '</td>';
            $output .= '</tr><tr><td></td><td colspan="2">';
            $output .= $bug_item['bug_description'];
            $output .= '</td></tr>';
        }
    } else {
        // Message displayed if no bugs are found
        $output .= '<tr style="background: #FFF">';
        $output .= '<td colspan="3">No Bugs to Display</td>';
    }
    $output .= '</table></div>';
    // Return data prepared to replace shortcode on page/post

    $output .= "<script type='text/javascript'>";
    $nonce = wp_create_nonce('ch8bt_ajax');
    $output .= "function replacecontent( bug_status )" .
        "{ jQuery.ajax( {" .
        "
type: 'POST', url: ajax_url," .
        "
data: { action: 'ch8bt_buglist_ajax'," .
        "
_ajax_nonce: '" . $nonce . "'," .
        "
bug_status: bug_status }," .
        "
success: function( data ) {" .
        "
jQuery('.bug-tracker-list').html( data );"
        .
        "
}" .
        "
});" .
        "};";
    $output .= "jQuery( document ).ready( function() {";
    $output .= "jQuery('.show_closed_bugs').click( function()
{ replacecontent( 1 ); } ";
    $output .= ")});";
    $output .= "</script>";
    return $output;
}


function import_ch8bt_bug()
{
    // Check that user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    // Check if nonce field is present
    check_admin_referer('ch8bt_import');
    // Check if file has been uploaded
    if (array_key_exists('import_bugs_file', $_FILES)) {
        // If file exists, open it in read mode
        $handle =
            fopen($_FILES['import_bugs_file']['tmp_name'], 'r');
        // If file is successfully open, extract a row of data
        // based on comma separator, and store in $data array
        if ($handle) {
            while (
                FALSE !==
                ($data = fgetcsv($handle, 5000, ','))
            ) {
                $row += 1;
                // If row count is ok and row is not header row
                // Create array and insert in database
                if (count($data) == 4 && $row != 1) {
                    $new_bug = array(
                        'bug_title' => $data[0],
                        'bug_description' => $data[1],
                        'bug_version' => $data[2],
                        'bug_status' => $data[3],
                        'bug_report_date' => date('Y-m-d')
                    );
                    global $wpdb;
                    $wpdb->insert($wpdb->get_blog_prefix() .
                        'ch8_bug_data', $new_bug);
                }
            }
        }
    }
    // Redirect the page to the user submission form
    wp_redirect(add_query_arg(
        'page',
        'ch8bt-bug-tracker',
        admin_url('options-general.php')
    ));
    exit;
}

function ch8bt_declare_ajaxurl()
{ ?>
    <script type="text/javascript">
        var ajax_url =
            '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
<?php }

function ch8bt_buglist_ajax()
{
    check_ajax_referer('ch8bt_ajax');
    if (
        isset($_POST['bug_status']) &&
        is_numeric($_POST['bug_status'])
    ) {
        global $wpdb;
        // Prepare
        $bug_query  = 'select * from ' . $wpdb->get_blog_prefix();
        $bug_query .= 'ch8_bug_data where bug_status = ';
        $bug_query .= intval($_POST['bug_status']);
        $bug_query .= ' ORDER by bug_id DESC';
        $bug_items = $wpdb->get_results(
            $wpdb->prepare($bug_query),
            ARRAY_A
        );
        // Prepare output to be returned to AJAX requestor
        $output = '<div class="bug-tracker-list"><table>';
        // Check if any bugs were found
        if ($bug_items) {
            $output .= '<tr><th style="width: 80px">ID</th>';
            $output .= '<th style="width: 300px">';
            $output .= 'Title / Desc</th><th>Version</th></tr>';
            // Create row in table for each bug
            foreach ($bug_items as $bug_item) {
                $output .= '<tr style="background: #FFF">';
                $output .= '<td>' . $bug_item['bug_id'] . '</td>';
                $output .= '<td>' . $bug_item['bug_title'];
                $output .= '</td><td>' . $bug_item['bug_version'];
                $output .= '</td></tr>';
                $output .= '<tr><td></td><td colspan="2">';
                $output .= $bug_item['bug_description'];
                $output .= '</td></tr>';
            }
        } else {
            // Message displayed if no bugs are found
            $output .= '<tr style="background: #FFF">';
            $output .= '<td colspan="3">No Bugs to Display</td>';
        }
        $output .= '</table></div><br />';
        echo $output;
    }
    die();
}

function ch8bt_load_jquery()
{
    wp_enqueue_script('jquery');
    wp_enqueue_style(
        'bug_tracker_css',
        plugins_url('stylesheet.css', __FILE__),
        array(),
        '1.0'
    );
}
