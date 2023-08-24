<?php
/* 
Plugin Name: CustomPostMonetization
Plugin URI: http://post-monitization.local/
Description: Create Custom Post Monetization plugin. Display the number of viewers for each post.
Version: 1.0
Author: RSM
Author URI: http://post-monitization.local/
Text Domain: custompostmonetization
*/

// Hook into the wp action when a single post is viewed
add_action('wp', 'custom_store_post_data_on_view');

function custom_store_post_data_on_view() {
    if (is_single()) { // Check if it's a single post view
        $post_id = get_the_ID();
        $post_url = get_permalink();
        $post_title = get_the_title();
        $author_id = get_post_field('post_author', $post_id); // Get the author ID
        $current_time = current_time('mysql');
        $user_ip_address = $_SERVER['REMOTE_ADDR'];

        // Retrieve existing post view data array from the option
        $post_view_data = get_option('custom_post_view_data', array());
        $monetization_data = get_option('custom_post_monetization_all_data', array());

        if( "" === $post_view_data ){
            $post_view_data = array();
        }

        // Add new post view data to the array
        $post_view_data[$post_id][] = array(
            'post_id' => $post_id,
            'post_url' => $post_url,
            'post_title' => $post_title,
            'author_id' => $author_id,
            'viewed_time' => $current_time,
            'user_ip' => $user_ip_address
        );

        if( ! isset( $post_view_data[$post_id]['earnings'] ) ) {
            $post_view_data[$post_id]['earnings'] = 0;
        }

        $post_view_data[$post_id]['earnings'] += $monetization_data['post_view_rate'];;
        // Store the updated array in the wp_options table
        update_option('custom_post_view_data', $post_view_data);
    }
}

// Hook into the manage_posts_columns hook to add a new column
add_filter('manage_posts_columns', 'custom_add_monetization_column');

function custom_add_monetization_column($columns) {
    $columns['post_monetization'] = 'Post Monetization';
    return $columns;
}

// Hook into the manage_posts_custom_column hook to display data in the new column
add_action('manage_posts_custom_column', 'custom_display_monetization_column_data', 10, 2);


function custom_display_monetization_column_data($column_name, $post_id) {
    if ($column_name === 'post_monetization') {
        // Retrieve the post view data array
        $post_view_data = get_option('custom_post_view_data', array());

        if (isset($post_view_data[$post_id])) {
            $view_data = $post_view_data[$post_id];
            $total_viewers = count($view_data); // Total number of viewers

            echo "Total viewers: $total_viewers";

            $last_viewer = end($view_data); // Get the last viewer's data

            if (is_array($last_viewer) && isset($last_viewer['viewed_time'])) {
                $viewer_time = $last_viewer['viewed_time'];
                echo "<br>Last viewed: $viewer_time";
            } else {
                echo '<br>No viewers';
            }
        } else {
            echo 'No viewers';
        }
    }
    elseif ($column_name === 'post_earnings') {
        // Retrieve the post view data array and monetization data
        $post_view_data = get_option('custom_post_view_data', array());
        $monetization_data = get_option('custom_post_monetization_all_data', array());

        $earnings = 0;

        // Calculate earnings for this post
        if (isset($post_view_data[$post_id]) && !empty($monetization_data)) {
            $view_data = $post_view_data[$post_id];
            $total_viewers = count($view_data);

            foreach ($monetization_data as $data) {
                $post_view_rate = isset($data['post_view_rate']) ? $data['post_view_rate'] : 0;
                $earnings += $total_viewers * $post_view_rate;
            }
        }

        echo "Earnings: $earnings"; // Display calculated earnings
    }
}
// Register the admin menu
add_action('admin_menu', 'custom_post_monetization_menu');

function custom_post_monetization_menu() {
    $parent_slug = 'custom-post-monetization';

    add_menu_page(
        'Post Monetization',
        'Post Monetization',
        'manage_options',
        $parent_slug,
        'custom_post_monetization_page',
        'dashicons-chart-line', // Use appropriate dashicon class
        6 // Position in the menu
    );

    add_submenu_page(
        $parent_slug,
        'Sub Menu Page Title',
        'Monetization Details',
        'manage_options',
        'custom-sub-menu',
        'custom_sub_menu_page'
    );
}

// Function to display content on the custom admin page
function custom_post_monetization_page() {
    include 'submenu/setting.php';
}
// Function to display content on the custom sub-menu page
function custom_sub_menu_page() {
    include 'submenu/details.php';
}
?>
