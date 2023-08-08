<?php

/* 

Plugin Name: CustomPostMonitization

Plugin URI: http://post-monitization.local/  

Description: Create Custom Post Monitization plugin. There are some Featured To Get Post information.

Version: 1.0

Author: RSM 

Author URI: http://post-monitization.local/ 

Text Domain: custompostmonitization 

*/


// Create custom table during plugin activation
function custom_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_post_data'; // table name

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        post_id INT NOT NULL,
        post_url VARCHAR(255),
        post_title VARCHAR(255),
        viewed_time DATETIME,
        user_ip VARCHAR(45),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'custom_plugin_activate');

// Hook into the wp action when a single post is viewed
add_action('wp', 'custom_store_post_data_on_view');

function custom_store_post_data_on_view() {
    if (is_single()) { // Check if it's a single post view
        $post_id = get_the_ID();
        $post_url = get_permalink();
        $post_title = get_the_title();
        $current_time = current_time('mysql');
        $user_ip_address = $_SERVER['REMOTE_ADDR'];

        // Store post data in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_post_data'; // table name

        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'post_url' => $post_url,
                'post_title' => $post_title,
                'viewed_time' => $current_time,
                'user_ip' => $user_ip_address
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }
}
