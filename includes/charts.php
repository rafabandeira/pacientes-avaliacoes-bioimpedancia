<?php // includes/charts.php
if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', function($hook){
    // Carrega Chart.js por CDN ou arquivo local (aqui CDN por simplicidade)
    wp_register_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_register_script('pab-charts', PAB_URL.'assets/js/charts.js', ['chartjs'], '1.0', true);
});
