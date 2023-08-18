<?php
global $wpdb;

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
    echo '<th>Renewal</th>'; // Add new column
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($post_view_data as $post_id => $view_data) {
        $post = get_post($post_id);
        if ($post) {
            $post_title = $post->post_title;
            $total_viewers = count($view_data);
            
            $earnings = 0; // Initialize earnings for this post

            // Calculate earnings for this post
            foreach ($monetization_data as $data) {
                $post_view_rate = isset($data['post_view_rate']) ? $data['post_view_rate'] : 0;
                $earnings += $total_viewers * $post_view_rate;
            }

            echo '<tr>';
            echo "<td>$post_id</td>";
            echo "<td>$post_title</td>";
            echo "<td>$total_viewers</td>";
            echo "<td>$earnings</td>"; // Display calculated earnings
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';

    echo '</div>';
?>