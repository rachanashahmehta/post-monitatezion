<?php
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

?>