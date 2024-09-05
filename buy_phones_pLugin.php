<?php
/*
Plugin Name: Phones Request
Description: A plugin to manage phone sales and inventory.
Version: 1.3
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


function enqueue_media_library_scripts()
{
    wp_enqueue_media();
    wp_enqueue_script('my-plugin-media-script', plugin_dir_url(__FILE__) . 'buy_phones_plugin_media_script.js', array('jquery'), '1.0', true);
}

function enqueue_plugin_styles()
{
    wp_enqueue_style('my-plugin-styles', plugin_dir_url(__FILE__) . 'style.css');
}

add_action('wp_enqueue_scripts', 'enqueue_plugin_styles');

// Function to add menu to the admin bar
function add_admin_bar_menu($wp_admin_bar)
{
    $args = array(
        'id' => 'my_plugin_menu',
        'title' => 'Buy Phones',
        'href' => admin_url('admin.php?page=buy_phones'),
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
        'buy_phones',
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
        echo '<script type="text/javascript">window.location="' . admin_url('admin.php?page=buy_phones') . '";</script>';
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
        echo '<a href="?page=buy_phones&edit=' . esc_attr($row->id) . '" class="button-secondary"><span class="dashicons dashicons-edit"></span></a> ';
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
    <div class="buy_phone_search">
        <input type="text" id="phoneSearch" class="buy_phone_search_phoneSearch" placeholder="Enter your item, e.g. 'iPhone 14'">
        <div id="searchResults" class="buy_phone_search_searchResults"></div>
        <div id="priceDisplay" class="buy_phone_search_priceDisplay">
            <div id="priceContent" class="buy_phone_search_priceContent"></div>
        </div>
        <!-- Modal for displaying selected item details -->
        <div id="sellItemModal" class="buy_phone_search_sellItemModal">
            <div id="modalTitle" class="buy_phone_search_modalTitle"></div>
            <div id="modalDetails" class="buy_phone_search_modalDetails"></div>
            <button class="buy_phone_search_close_button" onclick="closeModal()">Close</button>
        </div>
        <div id="overlay" class="buy_phone_search_overlay"></div>
    </div>

    <script type="text/javascript">
        const searchInput = document.getElementById('phoneSearch');
        const resultsDiv = document.getElementById('searchResults');
        const priceDisplay = document.getElementById('priceDisplay');
        const priceContent = document.getElementById('priceContent');
        const sellItemModal = document.getElementById('sellItemModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalDetails = document.getElementById('modalDetails');

        document.getElementById('phoneSearch').addEventListener('input', function () {
            const searchText = this.value.trim();

            if (searchText.length > 0) {
                fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=buy_phones_search&query=${encodeURIComponent(searchText)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            resultsDiv.innerHTML = '<div class="buy_phone_search_not_found">No results found</div>';
                        } else {
                            resultsDiv.innerHTML = '';
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'buy_phone_search_result_item';
                                div.innerHTML = `<img src="${item.image_url}" alt="${item.model_name}" style="width:50px; height:auto;"> ${item.variant ? `${item.model_name} (${item.variant})` : item.model_name}`;
                                div.onclick = () => {
                                    displayPriceOptions(item);
                                    searchInput.value = div.textContent;
                                };
                                resultsDiv.appendChild(div);
                            });
                        }
                        resultsDiv.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        resultsDiv.innerHTML = '<div class="buy_phone_search_not_found">Error fetching results</div>';
                        resultsDiv.style.display = 'block';
                    });
            } else {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
            }
        });

        function displayPriceOptions(item) {
            resultsDiv.style.display = 'none';
            priceContent.innerHTML = `
                    <div class="buy_phone_search_header">${item.variant ? `${item.model_name} (${item.variant})` : `${item.model_name}`}</div>
                    <div class="buy_phone_search_model_and_condition_button">
                    <div class="buy_phone_search_model">
                    <div class="buy_phone_search_img">
                    <img src="${item.image_url}">
                    </div>
                    </div>
                    <div class="buy_phone_search_conditions_button_and_already_sold">
                    <div class="buy_phone_search_conditions_text">Please select the condition</div>
                    <div class="buy_phone_condition_button_main">
                    <button id="excellentBtn" class="buy_phone_condition_button" onclick="displayPrice(${item.excellent}, '${item.model_name}', '${item.variant}', 'Excellent', '${item.image_url}', ${item.image_id});">Excellent Condition</button>
                    <button class="buy_phone_condition_button" onclick="displayPrice(${item.good}, '${item.model_name}', '${item.variant}', 'Good', '${item.image_url}', ${item.image_id});">Good Condition</button>
                    <button class="buy_phone_condition_button" onclick="displayPrice(${item.average}, '${item.model_name}', '${item.variant}', 'Average', '${item.image_url}', ${item.image_id});">Average Condition</button>
                    </div>
                    <div class="buy_phone_search_condition_details" id="condition_details">
                    </div>
                    <div>${item.sold_out}+ already sold on Phonestation Plus</div>
                    </div>
                    </div>
                `;
            priceDisplay.style.display = 'block';
            document.getElementById('excellentBtn').click();
        }

        function displayPrice(price, model, variant, condition, imageUrl, imageId) {
            let mainDiv = document.querySelector('.buy_phone_search_model_and_condition_button');
            let itemSummaryDiv = mainDiv.querySelector('.buy_phone_search_item_summary');

            // If an item summary already exists, update it
            if (itemSummaryDiv) {
                itemSummaryDiv.innerHTML = `
                    <div>Item Summary</div>
                    <div>Item - ${model}</div>
                    <div>Variant - ${variant}</div>
                    <div>Condition - ${condition}</div>
                    <div>We'll pay you: £${price}</div>
                    <button onclick="showSellItemForm('${model}', '${variant}', ${price}, '${imageUrl}', ${imageId}, '${condition}')">Sell This Item</button>
                `;
            } else {
                // If no item summary exists, create it
                itemSummaryDiv = document.createElement('div');
                itemSummaryDiv.className = 'buy_phone_search_item_summary';
                itemSummaryDiv.innerHTML = `
                    <div>Item Summary</div>
                    <div>Item - ${model}</div>
                    <div>Variant - ${variant}</div>
                    <div>Condition - ${condition}</div>
                    <div>We'll pay you: £${price}</div>
                    <button onclick="showSellItemForm('${model}', '${variant}', ${price}, '${imageUrl}', ${imageId}, '${condition}')">Sell This Item</button>
                `;
                mainDiv.appendChild(itemSummaryDiv);
            }

            let conditionDetails = document.getElementById('condition_details');
            let conditionText = '';
            switch (condition) {
                case 'Excellent':
                    conditionText = `
                            <strong>Excellent Condition</strong>
                            <ul>
                                <li>Flawless appearance with no visible scratches on screen and/or body</li>
                                <li>No cracks, chips, dents or defective pixels (e.g screen burn, dead pixels, liquid damage), and the touchscreen works</li>
                                <li>Battery health above 80%</li>
                                <li>All parts of the device are fully working</li>
                            </ul>
                        `;
                    break;
                case 'Good':
                    conditionText = `
                            <strong>Good Condition</strong>
                            <ul>
                                <li>Signs of wear on screen and/or body</li>
                                <li>No cracks, chips, dents or defective pixels (e.g screen burn, dead pixels, liquid damage), and the touchscreen works</li>
                                <li>All parts of the device are fully working</li>
                            </ul>
                        `;
                    break;
                case 'Average':
                    conditionText = `
                            <strong>Average Condition</strong>
                            <ul>
                                <li>Heavy signs of scratching and/or wear on device</li>
                                <li>Cracks, chips, dents or defective pixels (e.g screen burn, dead pixels, liquid damage) to screen or back</li>
                                <li>Any functional defect and/or intermittent issues</li>
                                <li>We cannot buy your device if it is missing components or is bent, crushed, snapped in half or does not power on</li>
                            </ul>
                        `;
                    break;
            }
            conditionDetails.innerHTML = conditionText;
        }

        function showSellItemForm(model, variant, price, imageUrl, imageId, condition) {
            modalTitle.textContent = 'Sell This Item';
            modalDetails.innerHTML = `
                    <img src="${imageUrl}" style="width:100px; height:auto;"><br>
                    Model: ${model}, Variant: ${variant}, Price: £${price}
                    <form id="sellItemForm" method="post">
                        <input type="hidden" name="model" value="${model}">
                        <input type="hidden" name="variant" value="${variant}">
                        <input type="hidden" name="price" value="${price}">
                        <input type="hidden" name="image_url" value="${imageUrl}">
                        <input type="hidden" name="image_id" value="${imageId}">
                        <input type="hidden" name="phone_condition" value="${condition}">
                        <label>Name:<input type="text" name="name" required></label><br>
                        <label>Email:<input type="email" name="email" required></label><br>
                        <label>Mobile:<input type="text" name="mobile" required pattern="07\\d{9}"></label><br>
                        <label>Address Line 1:<input type="text" name="address1" required></label><br>
                        <label>Address Line 2:<input type="text" name="address2"></label><br>
                        <label>Postal Code:<input type="text" name="postalCode" required pattern="[A-Z]{1,2}[0-9][A-Z0-9]?\\s?[0-9][A-Z]{2}"></label><br>
                        <p>Choose Payment Method:</p>
                        <input type="radio" id="bank" name="paymentMethod" value="bank" onclick="togglePaymentMethod('bank')" checked>
                        <label for="bank">Bank</label>
                        <input type="radio" id="paypal" name="paymentMethod" value="paypal" onclick="togglePaymentMethod('paypal')">
                        <label for="paypal">PayPal</label>
                        <div id="bankDetails" style="display:block;">
                            <label>Bank Name:<input type="text" name="bankName"></label><br>
                            <label>Account Holder:<input type="text" name="accountHolder"></label><br>
                            <label>Sort Code:<input type="text" name="sortCode" pattern="\\d{6}"></label><br>
                            <label>Account Number:<input type="number" name="accountNumber"></label><br>
                            <label>IBAN:<input type="text" name="iban"></label><br>
                        </div>
                        <div id="paypalDetails" style="display:none;">
                            <label>PayPal ID:<input type="text" name="paypalId"></label><br>
                            <label>PayPal Associated Email:<input type="email" name="paypalEmail"></label><br>
                        </div>
                        <button type="submit" name="submit_sell_item">Submit</button>
                    </form>
                `;
            document.body.classList.add('buy_phone_search_no_scroll');
            document.getElementById('overlay').style.display = 'block';
            sellItemModal.style.display = 'block';
        }

        function togglePaymentMethod(method) {
            if (method === 'bank') {
                document.getElementById('bankDetails').style.display = 'block';
                document.getElementById('paypalDetails').style.display = 'none';
            } else {
                document.getElementById('bankDetails').style.display = 'none';
                document.getElementById('paypalDetails').style.display = 'block';
            }
        }

        function closeModal() {
            sellItemModal.style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
            document.body.classList.remove('buy_phone_search_no_scroll');
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

    $json_output = json_encode($results);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON encode error: ' . json_last_error_msg());
        wp_die('Error encoding JSON', '', array('response' => 500));
    }

    echo $json_output;
    wp_die();
}
add_action('wp_ajax_buy_phones_search', 'buy_phones_search_handler');
add_action('wp_ajax_nopriv_buy_phones_search', 'buy_phones_search_handler');

// Correctly set up the activation hook
register_activation_hook(__FILE__, 'create_sell_request_table');

// Function to create the sell_request table
function create_sell_request_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'sell_request';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        model VARCHAR(255) NOT NULL,
        variant VARCHAR(255) NOT NULL,
        phone_condition VARCHAR(255),
        price DECIMAL(10, 2) NOT NULL,
        image_id INT(11),
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        mobile VARCHAR(255) NOT NULL,
        address_line_1 VARCHAR(255),
        address_line_2 VARCHAR(255),
        postal_code VARCHAR(255),
        bank_name VARCHAR(255),
        account_holder VARCHAR(255),
        sort_code VARCHAR(255),
        account_number VARCHAR(255),
        iban VARCHAR(255),
        paypal_id VARCHAR(255),
        paypal_email VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Function to handle sell item form submission
function handle_sell_item_form_submission()
{
    if (isset($_POST['submit_sell_item'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sell_request';

        $payment_method = sanitize_text_field($_POST['paymentMethod']);
        $data = array(
            'model' => sanitize_text_field($_POST['model']),
            'variant' => sanitize_text_field($_POST['variant']),
            'price' => floatval($_POST['price']),
            'image_id' => isset($_POST['image_id']) ? intval($_POST['image_id']) : null,
            'phone_condition' => sanitize_text_field($_POST['phone_condition']),  // Include the condition
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'mobile' => sanitize_text_field($_POST['mobile']),
            'address_line_1' => sanitize_text_field($_POST['address1']),
            'address_line_2' => sanitize_text_field($_POST['address2']),
            'postal_code' => sanitize_text_field($_POST['postalCode'])
        );

        if ($payment_method == 'bank') {
            $data['bank_name'] = sanitize_text_field($_POST['bankName']);
            $data['account_holder'] = sanitize_text_field($_POST['accountHolder']);
            $data['sort_code'] = sanitize_text_field($_POST['sortCode']);
            $data['account_number'] = sanitize_text_field($_POST['accountNumber']);
            $data['iban'] = sanitize_text_field($_POST['iban']);
        } else {
            $data['paypal_id'] = sanitize_text_field($_POST['paypalId']);
            $data['paypal_email'] = sanitize_email($_POST['paypalEmail']);
        }

        $wpdb->insert($table_name, $data);
        wp_redirect(add_query_arg('message', 'success', wp_get_referer()));
        exit;
    }
}

add_action('init', 'handle_sell_item_form_submission');

require_once(plugin_dir_path(__FILE__) . 'sell_request.php');
