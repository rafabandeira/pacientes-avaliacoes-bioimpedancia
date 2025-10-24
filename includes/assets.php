<?php // includes/assets.php
if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', function($hook){
    wp_enqueue_style('pab-admin', PAB_URL.'assets/css/admin.css', [], '1.1');
    wp_enqueue_script('pab-admin', PAB_URL.'assets/js/admin.js', ['jquery'], '1.1', true);

    // Esconder editor nas telas de medidas, avaliações e bioimpedância
    global $post_type;
    if (in_array($post_type, ['pab_medidas', 'pab_avaliacao', 'pab_bioimpedancia'])) {
        wp_add_inline_style('pab-admin', '
            body.post-type-pab_medidas #postdivrich,
            body.post-type-pab_avaliacao #postdivrich,
            body.post-type-pab_bioimpedancia #postdivrich,
            body.post-type-pab_medidas #wp-content-wrap,
            body.post-type-pab_avaliacao #wp-content-wrap,
            body.post-type-pab_bioimpedancia #wp-content-wrap,
            body.post-type-pab_medidas #wp-content-editor-tools,
            body.post-type-pab_avaliacao #wp-content-editor-tools,
            body.post-type-pab_bioimpedancia #wp-content-editor-tools,
            body.post-type-pab_medidas #wp-content-editor-container,
            body.post-type-pab_avaliacao #wp-content-editor-container,
            body.post-type-pab_bioimpedancia #wp-content-editor-container,
            body.post-type-pab_medidas .wp-editor-container,
            body.post-type-pab_avaliacao .wp-editor-container,
            body.post-type-pab_bioimpedancia .wp-editor-container,
            body.post-type-pab_medidas #postdiv,
            body.post-type-pab_avaliacao #postdiv,
            body.post-type-pab_bioimpedancia #postdiv {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                overflow: hidden !important;
            }
            body.post-type-pab_medidas #titlediv,
            body.post-type-pab_avaliacao #titlediv,
            body.post-type-pab_bioimpedancia #titlediv {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                overflow: hidden !important;
            }
        ');
    }
});

// Hook adicional para remover metaboxes do editor nos CPTs PAB
add_action('admin_menu', function() {
    remove_meta_box('postdivrich', 'pab_medidas', 'normal');
    remove_meta_box('postdivrich', 'pab_avaliacao', 'normal');
    remove_meta_box('postdivrich', 'pab_bioimpedancia', 'normal');
});

// Hook para remover suporte ao editor nos CPTs PAB
add_action('init', function() {
    remove_post_type_support('pab_medidas', 'editor');
    remove_post_type_support('pab_medidas', 'title');
    remove_post_type_support('pab_avaliacao', 'editor');
    remove_post_type_support('pab_avaliacao', 'title');
    remove_post_type_support('pab_bioimpedancia', 'editor');
    remove_post_type_support('pab_bioimpedancia', 'title');
}, 99);
