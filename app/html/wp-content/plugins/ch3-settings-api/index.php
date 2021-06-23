<?php

/*
Plugin Name: Chapter 3 - Settings API
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
License: GPLv2
*/

register_activation_hook( __FILE__, 'ch3sapi_set_default_options' );

add_action( 'admin_init', 'ch3sapi_admin_init' );
add_action( 'admin_menu', 'ch3sapi_settings_menu' );


function ch3sapi_set_default_options() {
    ch3sapi_get_options();
}

function ch3sapi_get_options() {
    $options = get_option( 'ch3sapi_options', array() );
    $new_options['ga_account_name'] = 'UA-0000000-0';
    $new_options['track_outgoing_links'] = false;
    $merged_options = wp_parse_args( $options, $new_options );
    $compare_options = array_diff_key( $new_options, $options );
    if ( empty( $options ) || !empty( $compare_options ) ) {
        update_option( 'ch3sapi_options', $merged_options );
    }
    return $merged_options;
}

function ch3sapi_admin_init() {
    // Register a setting group with a validation function
    // so that post data handling is done automatically for us
    register_setting( 'ch3sapi_settings',
    'ch3sapi_options', 'ch3sapi_validate_options' );
    // Add a new settings section within the group
    add_settings_section(   'ch3sapi_main_section', 'Main Settings',
                            'ch3sapi_main_setting_section_callback',
                            'ch3sapi_settings_section');
    // Add each field with its name and function to use for
    // our new settings, put them in our new section
    add_settings_field( 'ga_account_name', 'Account Name',
                        'ch3sapi_display_text_field', 'ch3sapi_settings_section',
                        'ch3sapi_main_section', array( 'name' => 'ga_account_name' ) );

    add_settings_field( 'track_outgoing_links',
                        'Track Outgoing Links', 'ch3sapi_display_check_box',
                        'ch3sapi_settings_section', 'ch3sapi_main_section', array( 'name' => 'track_outgoing_links' ) );

    add_settings_field( 'select_list', 'Select List',
                        'ch3sapi_select_list', 'ch3sapi_settings_section',
                        'ch3sapi_main_section',
                        array( 'name' => 'select_list', 'choices' => array( 'First', 'Second', 'Third' ) ) );

    add_settings_field( 'text_area', 'Text area',
                        'ch3sapi_display_text_area', 'ch3sapi_settings_section',
                        'ch3sapi_main_section', array( 'name' => 'text_area', 'choices' => array( 'First', 'Second', 'Third' ) ) );
}

function ch3sapi_validate_options( $input ) {
    foreach ( array( 'ga_account_name' ) as $option_name ) {
        if ( isset( $input[$option_name] ) ) {
            $input[$option_name] = sanitize_text_field( $input[$option_name] );
        }
    }
    foreach ( array( 'track_outgoing_links' ) as $option_name ) {
        if ( isset( $input[$option_name] ) ) {
            $input[$option_name] = true;
        } else {
            $input[$option_name] = false;
        }
    }
    return $input;
}

function ch3sapi_main_setting_section_callback() { ?>
    <p>This is the main configuration section.</p>
<?php }

function ch3sapi_display_text_field( $data = array() ) {
    extract( $data );
    $options = ch3sapi_get_options();
    ?>
    <input type="text" name="ch3sapi_options[<?php echo $name; ?>]"
    value="<?php echo esc_html( $options[$name] ); ?>"/>
    <br />
<?php }


function ch3sapi_display_check_box( $data = array() ) {
    extract ( $data );
    $options = ch3sapi_get_options();
    ?>
    <input type="checkbox"
    name="ch3sapi_options[<?php echo $name; ?>]"
    <?php checked( $options[$name] ); ?>/>
<?php }

function ch3sapi_settings_menu() {
    add_options_page( 'My Google Analytics Configuration',
    'My Google Analytics - Settings API', 'manage_options',
    'ch3sapi-my-google-analytics', 'ch3sapi_config_page' );
}

function ch3sapi_config_page() { ?>
    <div id="ch3sapi-general" class="wrap">
    <h2>My Google Analytics - Settings API</h2>
    <form name="ch3sapi_options_form_settings_api" method="post"
    action="options.php">
    <?php settings_fields( 'ch3sapi_settings' ); ?>
    <?php do_settings_sections( 'ch3sapi_settings_section' ); ?>
    <input type="submit" value="Submit" class="button-primary" />
    </form></div>
<?php }

function ch3sapi_select_list( $data = array() ) {
    extract ( $data );
    $options = ch3sapi_get_options();
    ?>
    <select name="ch3sapi_options[<?php echo $name; ?>]">
    <?php foreach( $choices as $item ) { ?>
    <option value="<?php echo $item; ?>"
    <?php selected( $options[$name] == $item ); ?>>
    <?php echo $item; ?></option>;
    <?php } ?>
    </select>
<?php }

function ch3sapi_display_text_area( $data = array() ) {
    extract ( $data );
    $options = ch3sapi_get_options();
    ?>
    <textarea type="text" name="ch3sapi_options[<?php echo $name; ?>]" rows="5" cols="30">
    <?php echo esc_html ( $options[$name] ); ?></textarea>
<?php }