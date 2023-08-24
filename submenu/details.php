<?php
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

?>