<?php
// Add the admin menu during the admin_menu hook
add_action('admin_menu', 'register_sell_phones_page');

function register_sell_phones_page() {
    add_menu_page(
        'Sell Phones', // Page title
        'Sell Phones', // Menu title
        'manage_options', // Capability
        'sell_phones', // Menu slug
        'sell_phones_page_content', // Function to display the page content
        'dashicons-smartphone', // Icon URL
        21 // Position
    );
}

// Function to display the content of the page
function sell_phones_page_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sell_request';

    handle_sell_request_form_submission(); // Handle form submissions
    if (isset($_GET['edit'])) {
        display_sell_request_form(); // Display the form only if edit is set
    }

    // Fetch and display entries
    $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    echo '<div class="wrap"><h1>Sell Phones Entries</h1>';
    echo '<div id="records-table" style="height: 600px; overflow-y: scroll;">';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead style="position: sticky; top: 0; background-color: #f1f1f1; z-index: 1;"><tr><th>ID</th><th>Model</th><th>Variant</th><th>Condition</th><th>Price</th><th>Image</th><th>Name</th><th>Email</th><th>Mobile</th><th>Address 1</th><th>Address 2</th><th>Postal Code</th><th>Bank Name</th><th>AccountHolder</th><th>Sort Code</th><th>Account No.</th><th>IBAN</th><th>PayPal ID</th><th>PayPal Email</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row['id']) . '</td>';
        echo '<td>' . esc_html($row['model']) . '</td>';
        echo '<td>' . esc_html($row['variant']) . '</td>';
        echo '<td>' . esc_html($row['phone_condition']) . '</td>';
        echo '<td>' . esc_html($row['price']) . '</td>';
        echo '<td><img src="' . wp_get_attachment_url($row['image_id']) . '" style="width:50px;height:auto;"></td>';
        echo '<td>' . esc_html($row['name']) . '</td>';
        echo '<td>' . esc_html($row['email']) . '</td>';
        echo '<td>' . esc_html($row['mobile']) . '</td>';
        echo '<td>' . esc_html($row['address_line_1']) . '</td>';
        echo '<td>' . esc_html($row['address_line_2']) . '</td>';
        echo '<td>' . esc_html($row['postal_code']) . '</td>';
        echo '<td>' . esc_html($row['bank_name']) . '</td>';
        echo '<td>' . esc_html($row['account_holder']) . '</td>';
        echo '<td>' . esc_html($row['sort_code']) . '</td>';
        echo '<td>' . esc_html($row['account_number']) . '</td>';
        echo '<td>' . esc_html($row['iban']) . '</td>';
        echo '<td>' . esc_html($row['paypal_id']) . '</td>';
        echo '<td>' . esc_html($row['paypal_email']) . '</td>';
        // Add action buttons
        echo '<td>';
        echo '<a href="?page=sell_phones&edit=' . esc_attr($row['id']) . '" class="button-secondary"><span class="dashicons dashicons-edit"></span></a> ';
        echo '<form method="post" style="display:inline-block;">';
        echo '<input type="hidden" name="id" value="' . esc_attr($row['id']) . '">';
        echo '<button type="submit" name="delete" class="button-secondary" onclick="return confirm(\'Are you sure you want to delete this entry?\');"><span class="dashicons dashicons-trash"></span></button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div></div>';
}

function display_sell_request_form() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sell_request';

    // Check if edit mode is active
    $edit_data = null;
    if (isset($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id), ARRAY_A);
    }

    // Display the form
    echo '<style>';
    echo '.form-group { float: left; width: 25%; padding: 5px; box-sizing: border-box; }';
    echo '.form-group label, .form-group input { display: block; width: 100%; }';
    echo '.form-row, .form-button-row { clear: both; margin-bottom: 50px}';
    echo '</style>';

    echo '<div id="edit-form" style="padding: 20px;">';
    echo '<h2>Edit Sell Request</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="id" value="' . esc_attr($edit_data['id'] ?? '') . '">';
    echo '<input type="hidden" name="form_type" value="update">';

    // Each group of label and input is wrapped in a div with class "form-group"
    echo '<div class="form-group"><label>Model:<input type="text" name="model" value="' . esc_attr($edit_data['model'] ?? '') . '" required></label></div>';
    echo '<div class="form-group"><label>Variant:<input type="text" name="variant" value="' . esc_attr($edit_data['variant'] ?? '') . '" required></label></div>';
    echo '<div class="form-group"><label>Price:<input type="number" step="0.01" name="price" value="' . esc_attr($edit_data['price'] ?? '') . '" required></label></div>';

    echo '<div class="form-group"><label>Name:<input type="text" name="name" value="' . esc_attr($edit_data['name'] ?? '') . '" required></label></div>';
    echo '<div class="form-group"><label>Email:<input type="email" name="email" value="' . esc_attr($edit_data['email'] ?? '') . '" required></label></div>';
    echo '<div class="form-group"><label>Mobile:<input type="text" name="mobile" value="' . esc_attr($edit_data['mobile'] ?? '') . '" required></label></div>';

    echo '<div class="form-group"><label>Address Line 1:<input type="text" name="address1" value="' . esc_attr($edit_data['address_line_1'] ?? '') . '" required></label></div>';
    echo '<div class="form-group"><label>Address Line 2:<input type="text" name="address2" value="' . esc_attr($edit_data['address_line_2'] ?? '') . '"></label></div>';
    echo '<div class="form-group"><label>Postal Code:<input type="text" name="postalCode" value="' . esc_attr($edit_data['postal_code'] ?? '') . '" required></label></div>';

    echo '<div class="form-group"><label>Bank Name:<input type="text" name="bankName" value="' . esc_attr($edit_data['bank_name'] ?? '') . '"></label></div>';
    echo '<div class="form-group"><label>Account Holder:<input type="text" name="accountHolder" value="' . esc_attr($edit_data['account_holder'] ?? '') . '"></label></div>';
    echo '<div class="form-group"><label>Sort Code:<input type="text" name="sortCode" value="' . esc_attr($edit_data['sort_code'] ?? '') . '"></label></div>';

    echo '<div class="form-group"><label>Account Number:<input type="text" name="accountNumber" value="' . esc_attr($edit_data['account_number'] ?? '') . '"></label></div>';
    echo '<div class="form-group"><label>IBAN:<input type="text" name="iban" value="' . esc_attr($edit_data['iban'] ?? '') . '"></label></div>';
    echo '<div class="form-group"><label>PayPal ID:<input type="text" name="paypalId" value="' . esc_attr($edit_data['paypal_id'] ?? '') . '"></label></div>';
    echo '<div class="form-group"><label>PayPal Email:<input type="email" name="paypalEmail" value="' . esc_attr($edit_data['paypal_email'] ?? '') . '"></label></div>';

    echo '<div class="form-button-row">';
    echo '<button type="submit" class="button-secondary" name="submit">Update</button>';
    echo '</div>';

    echo '</form>';
    echo '</div>';
}

function handle_sell_request_form_submission() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sell_request';

    if (isset($_POST['submit'])) {
        $data = array(
            'model' => sanitize_text_field($_POST['model']),
            'variant' => sanitize_text_field($_POST['variant']),
            'price' => floatval($_POST['price']),
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'mobile' => sanitize_text_field($_POST['mobile']),
            'address_line_1' => sanitize_text_field($_POST['address1']),
            'address_line_2' => sanitize_text_field($_POST['address2']),
            'postal_code' => sanitize_text_field($_POST['postalCode']),
            'bank_name' => sanitize_text_field($_POST['bankName']),
            'account_holder' => sanitize_text_field($_POST['accountHolder']),
            'sort_code' => sanitize_text_field($_POST['sortCode']),
            'account_number' => sanitize_text_field($_POST['accountNumber']),
            'iban' => sanitize_text_field($_POST['iban']),
            'paypal_id' => sanitize_text_field($_POST['paypalId']),
            'paypal_email' => sanitize_email($_POST['paypalEmail'])
        );

        // Handle image upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $uploadedfile = $_FILES['image'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $filename = $movefile['file'];
                $wp_filetype = wp_check_filetype(basename($filename), null);
                $wp_upload_dir = wp_upload_dir();
                $attachment = array(
                    'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $filename);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                wp_update_attachment_metadata($attach_id, $attach_data);

                // Store the attachment ID in the form data array
                $data['image_id'] = $attach_id;
            }
        }

        if ($_POST['form_type'] === 'update') {
            $wpdb->update($table_name, $data, array('id' => intval($_POST['id'])));
            echo '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function() { document.getElementById("edit-form").style.display = "none"; });</script>';
        }
    }

    if (isset($_POST['delete']) && isset($_POST['id'])) {
        $wpdb->delete($table_name, ['id' => intval($_POST['id'])]);
        echo '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function() { document.getElementById("edit-form").style.display = "none"; });</script>';
    }
}