<?php // includes/assets.php
if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', function($hook){
    wp_enqueue_style('pab-admin', PAB_URL.'assets/css/admin.css', [], '1.0');
    wp_enqueue_script('pab-admin', PAB_URL.'assets/js/admin.js', ['jquery'], '1.0', true);
});

