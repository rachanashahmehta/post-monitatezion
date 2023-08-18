<?php

    // Retrieve saved options
    $all_data = get_option('custom_post_monetization_all_data', array());

    // Handle form submission
    if (isset($_POST['custom_post_monetization_submit'])) {
        $show_checkbox = isset($_POST['Enable']) ? 'on' : 'off';
        $post_view_rate = intval($_POST['post_view_rate']); // Convert to integer
        $selected_role = sanitize_text_field($_POST['selected_role']); // Sanitize user role
        $selected_option = sanitize_text_field($_POST['selected_option']);
       
        // Add new data to the existing array
        $new_data = array(
            'Enable' => $show_checkbox,
            'post_view_rate' => $post_view_rate,
            'selected_role' => $selected_role,
            'selected_option' => $selected_option
        );

        // Add the new data to the array and update the option
        $all_data[] = $new_data;
        update_option('custom_post_monetization_all_data', $all_data);
    }
    ?>
    <div class="post-monitization-div">
    <h1>Settings</h1>
    <form method="post" action="">
        <br>
        <label class="post-monitization-label">
            Enable: <input type="checkbox" name="show_checkbox" checked="checked" <?php if (isset($new_data['show_checkbox']) && $new_data['show_checkbox'] === 'on') echo 'checked'; ?> />
        </label>
        <br>
        <label for="post_view_rate" class="post-monitization-label">Post View Rate: </label>
        <input type="number" name="post_view_rate" class="post-monitization-select" value="<?php echo isset($new_data['post_view_rate']) ? $new_data['post_view_rate'] : ''; ?>">
        <br>
        <label for="selected_role" class="post-monitization-label">Allowed User Roles: </label>
        <select name="selected_role" class="post-monitization-select">
            <?php
            $wp_roles = wp_roles();
            foreach ($wp_roles->roles as $role_slug => $role_info) {
                echo '<option value="' . $role_slug . '">' . $role_info['name'] . '</option>';
            }
            ?>
        </select>
        <br>
        <label for="selected_option" class="post-monitization-label">Post Monetization Currency: </label>
        <select name="selected_option" class="post-monitization-select">
            <option value="USD" <?php if (isset($new_data['selected_option']) && $new_data['selected_option'] === 'USD') echo 'selected'; ?>>USD</option>
            <option value="INR" <?php if (isset($new_data['selected_option']) && $new_data['selected_option'] === 'INR') echo 'selected'; ?>>INR</option>
            <option value="GBP" <?php if (isset($new_data['selected_option']) && $new_data['selected_option'] === 'GBP') echo 'selected'; ?>>GBP</option>
        </select>

        <br><br>
        <input type="submit" name="custom_post_monetization_submit" class="button-primary" value="Save">
    </form>
</div>
<?php
?>
<style>
    .post-monitization-div {
        max-width: 400px;
        margin-top: 35px;
        padding: 20px;
        border: 1px solid #ccc;
        background-color: #f7f7f7;
    }
    .post-monitization-label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
    }
    .post-monitization-select {
        width: 100%;
        margin-bottom: 10px;
    }
</style>