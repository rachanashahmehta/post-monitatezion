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

        $post_view_data[$post_id]['earnings'] += $monetization_data['post_view_rate'];
        // Store the updated array in the wp_options table
        update_option('custom_post_view_data', $post_view_data);

        // Get the log folder path
        $log_folder_path = wp_upload_dir()['basedir'] . '/post-monitization-logs';

        // Create the log folder if it doesn't exist
        if (!file_exists($log_folder_path)) {
            wp_mkdir_p($log_folder_path);
        }

        // Create the log file path
        $log_file_path = $log_folder_path . '/' . $post_id . '.log';

        // Get the current user's IP address
        $user_ip_address = $_SERVER['REMOTE_ADDR'];

        // Log the post view in the log file
        $log_content = "[$current_time]". " Post Viewed IP:$user_ip_address\n\n";

        // Append the log content to the log file
        file_put_contents($log_file_path, $log_content, FILE_APPEND);
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
    $post_view_data = get_option('custom_post_view_data', array());
$monetization_data = get_option('custom_post_monetization_all_data', array());

echo '<div class="wrap">';
echo '<h1>Monetization Details</h1>';

echo '<table class="widefat fixed" cellspacing="0">';
echo '<thead>';
echo '<tr>';
echo '<th>Post ID</th>';
echo '<th>Post Title</th>';
echo '<th>Views</th>';
echo '<th>Post View Rate</th>';
echo '<th>Earnings</th>'; // Add new column
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($post_view_data as $post_id => $view_data) {
    $post = get_post($post_id);
    if ($post) {
        $post_title = $post->post_title;
        $total_viewers = count($view_data);
        
         // Fetch post view rate from the custom_post_monetization_all_data option
        $post_view_rate = isset($monetization_data['post_view_rate']) ? $monetization_data['post_view_rate'] : 0;

        // Calculate earnings
        $earnings = $total_viewers * $post_view_rate;

        // Fetch stored earnings for this post
         if (isset($post_view_data[$post_id]['earnings'])) {
             $stored_earnings = $post_view_data[$post_id]['earnings'];
        } else {
            $stored_earnings = 0;
        }

        echo '<tr>';
        echo "<td>$post_id</td>";
        echo "<td>$post_title</td>";
        echo "<td>$total_viewers</td>";
        echo "<td>$post_view_rate</td>"; // Display last post_view_rate
        echo "<td>$stored_earnings</td>"; // Display calculated earnings
        echo '</tr>';
    }
}

echo '</tbody>';
echo '</table>';

echo '</div>';
}

// Hook into the activation of the plugin
register_activation_hook(__FILE__, 'custom_plugin_activation');

function custom_plugin_activation() {
    // Create a folder in the wp-content/uploads directory
    $upload_dir = wp_upload_dir();
    $new_folder_path = $upload_dir['basedir'] . '/post-monitization-logs';

    // Create the folder if it doesn't exist
    if (!file_exists($new_folder_path)) {
        wp_mkdir_p($new_folder_path);
    }
}
?>
