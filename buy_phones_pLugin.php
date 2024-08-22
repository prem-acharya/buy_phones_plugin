<?php
/*
Plugin Name: Buy Phones PLugin
Description: A plugin to manage phone sales and inventory.
Version: 1.2
Author: Prem Acharya
*/

// Create the database table on plugin activation
register_activation_hook(__FILE__, 'create_my_custom_table');

// Function to create the custom table
function create_my_custom_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'buy_phones_plugin';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        brand_name VARCHAR(255) NOT NULL,
        model_name VARCHAR(255) NOT NULL,
        variant VARCHAR(255) NOT NULL,
        excellent DECIMAL(10, 2) NOT NULL,
        good DECIMAL(10, 2) NOT NULL,
        average DECIMAL(10, 2) NOT NULL,
        sold_out INT(11) NOT NULL,
        image_id INT(11),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook to initialize the plugin
add_action('init', 'my_plugin_init');

function my_plugin_init()
{
    // Add admin bar menu
    add_action('admin_bar_menu', 'add_admin_bar_menu', 100);

    // Register admin page to show the table list
    add_action('admin_menu', 'register_my_admin_page');

    // Enqueue media library scripts and styles
    add_action('admin_enqueue_scripts', 'enqueue_media_library_scripts');
}


function enqueue_media_library_scripts() {
    wp_enqueue_media();
    wp_enqueue_script('my-plugin-media-script', plugin_dir_url(__FILE__) . 'buy_phones_plugin_media_script.js', array('jquery'), '1.0', true);
}

// Function to add menu to the admin bar
function add_admin_bar_menu($wp_admin_bar)
{
    $args = array(
        'id' => 'my_plugin_menu',
        'title' => 'Buy Phones',
        'href' => admin_url('admin.php?page=my_custom_table_list'),
        'meta' => array('class' => 'my-plugin-menu')
    );
    $wp_admin_bar->add_node($args);
}

// Function to register the admin page
function register_my_admin_page()
{
    add_menu_page(
        'Buy Phones',
        'Buy Phones',
        'manage_options',
        'my_custom_table_list',
        'display_custom_table_list',
        'dashicons-smartphone',
        20
    );
}

// Function to handle insert, update, and delete operations
function handle_form_submission()
{

    global $wpdb;
    $table_name = $wpdb->prefix . 'buy_phones_plugin';

    // Handle image upload
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    // Handle image upload
    if (isset($_FILES['phone_image']) && $_FILES['phone_image']['error'] == UPLOAD_ERR_OK) {
        $uploadedfile = $_FILES['phone_image'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // File is uploaded successfully. Now insert it into the WordPress Media Library.
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
        }
    }

    // Insert new record
    if (isset($_POST['insert']) && isset($_POST['image_id'])) {
        $wpdb->insert($table_name, array(
            'brand_name' => sanitize_text_field($_POST['brand_name']),
            'model_name' => sanitize_text_field($_POST['model_name']),
            'variant' => sanitize_text_field($_POST['variant']),
            'excellent' => floatval($_POST['excellent']),
            'good' => floatval($_POST['good']),
            'average' => floatval($_POST['average']),
            'sold_out' => intval($_POST['sold_out']),
            'image_id' => intval($_POST['image_id']),
        ));
    }
    // Update existing record
    if (isset($_POST['update'])) {
        $wpdb->update(
            $table_name,
            array(
                'brand_name' => sanitize_text_field($_POST['brand_name']),
                'model_name' => sanitize_text_field($_POST['model_name']),
                'variant' => sanitize_text_field($_POST['variant']),
                'excellent' => floatval($_POST['excellent']),
                'good' => floatval($_POST['good']),
                'average' => floatval($_POST['average']),
                'sold_out' => intval($_POST['sold_out']),
                'image_id' => intval($_POST['image_id']),
            ),
            array('id' => intval($_POST['id']))
        );
        echo '<script type="text/javascript">window.location="' . admin_url('admin.php?page=my_custom_table_list') . '";</script>';
        exit;
    }

    // Delete record
    if (isset($_POST['delete'])) {
        $wpdb->delete($table_name, array('id' => intval($_POST['id'])));
    }
}

// Function to display the custom table list with search and layout adjustments
function display_custom_table_list()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'buy_phones_plugin';

    handle_form_submission();

    // Check if edit mode is active
    $edit_data = null;
    if (isset($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
    }

    echo '<div style="display: flex; justify-content: space-between;">';

    // Left side: Form for inserting or updating a record
    echo '<div style="flex: 1; padding: 20px;">';
    echo '<h2>' . ($edit_data ? 'Edit Entry' : 'Add New Entry') . '</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="id" value="' . esc_attr($edit_data ? $edit_data->id : '') . '">';
    echo '<table class="form-table">';
    echo '<tr><th>Brand Name</th><td><input type="text" name="brand_name" value="' . esc_attr($edit_data ? $edit_data->brand_name : '') . '" required></td></tr>';
    echo '<tr><th>Model Name</th><td><input type="text" name="model_name" value="' . esc_attr($edit_data ? $edit_data->model_name : '') . '" required></td></tr>';
    echo '<tr><th>Variant</th><td><input type="text" name="variant" value="' . esc_attr($edit_data ? $edit_data->variant : '') . '" required></td></tr>';
    echo '<tr><th>Excellent</th><td><input type="number" step="0.01" name="excellent" value="' . esc_attr($edit_data ? $edit_data->excellent : '') . '" required></td></tr>';
    echo '<tr><th>Good</th><td><input type="number" step="0.01" name="good" value="' . esc_attr($edit_data ? $edit_data->good : '') . '" required></td></tr>';
    echo '<tr><th>Average</th><td><input type="number" step="0.01" name="average" value="' . esc_attr($edit_data ? $edit_data->average : '') . '" required></td></tr>';
    echo '<tr><th>Sold Out</th><td><input type="number" name="sold_out" value="' . esc_attr($edit_data ? $edit_data->sold_out : '') . '" required></td></tr>';
    echo '<tr><th>Image</th><td>';
    echo '<button type="button" id="select-image-button" class="button">Select Image</button>';
    echo '<input type="hidden" name="image_id" id="image-id-input" value="' . esc_attr($edit_data ? $edit_data->image_id : '') . '">';
    echo '<div id="image-preview">';
    if ($edit_data && $edit_data->image_id) {
        $image_url = wp_get_attachment_url($edit_data->image_id);
        echo '<img src="' . esc_url($image_url) . '" style="max-width:100px;max-height:100px;" />';
    }
    echo '</div>';
    echo '</td></tr>';
    
    echo '</table>';
    echo '<input type="submit" name="' . ($edit_data ? 'update' : 'insert') . '" value="' . ($edit_data ? 'Update' : 'Add New') . '" class="button-primary">';
    echo '</form>';
    echo '</div>';

    // Right side: Table of existing records
    echo '<div style="flex: 2; padding: 20px;">';
    echo '<h2>Existing Entries</h2>';
    echo '<input type="text" id="search-input" placeholder="Search by brand or model..." style="margin-bottom: 20px;">';
    echo '<div id="records-table" style="height: 480px; overflow-y: scroll;">';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead style="position: sticky; top: 0; background-color: #f1f1f1; z-index: 1;"><tr><th>ID</th><th>Brand Name</th><th>Model Name</th><th>Variant</th><th>Excellent</th><th>Good</th><th>Average</th><th>Sold Out</th><th>Image</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    $results = $wpdb->get_results("SELECT * FROM $table_name");
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->brand_name) . '</td>';
        echo '<td>' . esc_html($row->model_name) . '</td>';
        echo '<td>' . esc_html($row->variant) . '</td>';
        echo '<td>' . esc_html($row->excellent) . '</td>';
        echo '<td>' . esc_html($row->good) . '</td>';
        echo '<td>' . esc_html($row->average) . '</td>';
        echo '<td>' . esc_html($row->sold_out) . '</td>';
        echo '<td><img src="' . wp_get_attachment_url($row->image_id) . '" style="width:50px;height:auto;"></td>';
        echo '<td>';
        echo '<a href="?page=my_custom_table_list&edit=' . esc_attr($row->id) . '" class="button-secondary"><span class="dashicons dashicons-edit"></span></a> ';
        echo '<form method="post" style="display:inline-block;">';
        echo '<input type="hidden" name="id" value="' . esc_attr($row->id) . '">';
        echo '<button type="submit" name="delete" class="button-secondary" onclick="return confirm(\'Are you sure you want to delete this entry?\');"><span class="dashicons dashicons-trash"></span></button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>'; // Close records-table div
    echo '</div>'; // Close right side div

    echo '</div>'; // Close layout division

    // JavaScript for search functionality
    echo '<script type="text/javascript">
        document.getElementById("search-input").addEventListener("keyup", function() {
            var searchValue = this.value.toLowerCase();
            var tableRows = document.querySelectorAll("#records-table tbody tr");
            tableRows.forEach(function(row) {
                var brandName = row.cells[1].textContent.toLowerCase();
                var modelName = row.cells[2].textContent.toLowerCase();
                if (brandName.includes(searchValue) || modelName.includes(searchValue)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    </script>';
}

// Register the shortcode
add_shortcode('buy_phones_search', 'buy_phones_search_shortcode');

// Function to display the search form and results container
function buy_phones_search_shortcode()
{
    ?>
    <div style="padding: 20px;">
        <input type="text" id="phoneSearch" placeholder="Search by brand or model...">
        <div id="searchResults" style="display:none; cursor: pointer;"></div>
        <div id="priceDisplay" style="display:none;">
            <div id="priceContent"></div>
        </div>
    </div>

    <script type="text/javascript">
        const searchInput = document.getElementById('phoneSearch');
        const resultsDiv = document.getElementById('searchResults');
        const priceDisplay = document.getElementById('priceDisplay');
        const priceContent = document.getElementById('priceContent');

        searchInput.addEventListener('input', function () {
            const searchText = searchInput.value.trim();

            if (searchText.length > 0) {
                fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=buy_phones_search&query=${encodeURIComponent(searchText)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            resultsDiv.innerHTML = '<div class="not-found">No results found</div>';
                        } else {
                            resultsDiv.innerHTML = '';
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'result-item';
                                div.textContent = item.variant ? `${item.model_name} (${item.variant})` : item.model_name;
                                div.onclick = () => {
                                    displayPrice(item);
                                    searchInput.value = div.textContent;
                                };
                                resultsDiv.appendChild(div);
                            });
                        }
                        resultsDiv.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        resultsDiv.innerHTML = '<div class="not-found">Error fetching results</div>';
                        resultsDiv.style.display = 'block';
                    });
            } else {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
            }
        });

        function displayPrice(item) {
            resultsDiv.style.display = 'none';
            const imageUrl = item.image_url;
            priceContent.innerHTML = `
                <img src="${imageUrl}" style="width:100px; height:auto;">
                <h2>${item.variant ? `${item.model_name} (${item.variant})` : `${item.model_name}`}</h2>
                <p class="price">Excellent Condition: ₹${item.excellent}</p>
                <p class="price">Good Condition: ₹${item.good}</p>
                <p class="price">Average Condition: ₹${item.average}</p>
                <p>${item.sold_out}+ already sold on Phonestation Plus</p>`;
            priceDisplay.style.display = 'block';
        }
    </script>
    <?php
}
add_shortcode('buy_phones_search', 'buy_phones_search_shortcode');

// AJAX handler for the search request
add_action('wp_ajax_buy_phones_search', 'buy_phones_search_handler');
add_action('wp_ajax_nopriv_buy_phones_search', 'buy_phones_search_handler');

function buy_phones_search_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'buy_phones_plugin';
    $search = sanitize_text_field($_GET['query']);

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, pm.guid AS image_url FROM $table_name p
         LEFT JOIN {$wpdb->prefix}posts pm ON p.image_id = pm.ID
         WHERE p.model_name LIKE %s",
        $wpdb->esc_like($search) . '%'
    ), ARRAY_A);

    echo json_encode($results);
    wp_die();
}
add_action('wp_ajax_buy_phones_search', 'buy_phones_search_handler');
add_action('wp_ajax_nopriv_buy_phones_search', 'buy_phones_search_handler');
