# Buy Phones Plugin

![image](https://github.com/user-attachments/assets/2b7384af-0173-45fa-bfbb-bafe5674ca69)

![image](https://github.com/user-attachments/assets/f0e36bf8-5d8f-4992-b5dd-0cdd7dcb41a3)


## Description
**Buy Phones Plugin** is a comprehensive solution designed to manage phone sales and inventory directly within your WordPress site. Developed with ease of use in mind, it provides a seamless interface for handling phone inventories, including capabilities to add, update, delete, and search for phone records.

## Features

- **Database Integration**: Automatically creates custom tables in your WordPress database to store phone and sell request data.
- **Admin Interface**: Easy-to-use admin interface to manage phone inventory and sell requests.
- **CRUD Operations**: Supports Create, Read, Update, and Delete operations directly from the admin panel.
- **Search Functionality**: Includes a dynamic AJAX search feature that allows users to search for phones by brand or model.
- **Image Upload**: Allows image uploads for each phone entry, enhancing the visual information for inventory items.
- **Sell Phones Feature**: Manage sell requests from customers, including handling personal and payment information securely.
- **Shortcode Support**: Implements a shortcode `[buy_phones_search]` that can be used anywhere on your site to display a search interface.

## Installation

1. Download the plugin from the GitHub repository.
2. Upload the plugin files to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Access the plugin settings from the WordPress admin bar under "Buy Phones".

## Usage

After installation, the plugin will automatically create the necessary database tables. You can start adding phone data through the 'Buy Phones' menu in the admin dashboard.

Use the shortcode `[buy_phones_search]` in your posts or pages to allow visitors to search through the phone database. Manage sell requests through the 'Sell Phones' menu.

## Additional Documentation

- **Image Handling**: Utilizes WordPress's media uploader for image management. See the media script for details (`buy_phones_plugin_media_script.js`).
- **Sell Phones Management**: Handles form submissions for selling phones, including validation and database operations (`sell_request.php`).
