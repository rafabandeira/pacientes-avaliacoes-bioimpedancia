<?php // includes/cpt-paciente.php - OTIMIZADO COM SUPORTE 'title'

if (!defined('ABSPATH')) exit;

add_action('init', function() {
    $labels = [
        'name'               => _x('Pacientes', 'post type general name', 'pab'),
        'singular_name'      => _x('Paciente', 'post type singular name', 'pab'),
        'menu_name'          => _x('Pacientes', 'admin menu', 'pab'),
        'name_admin_bar'     => _x('Paciente', 'add new on admin bar', 'pab'),
        'add_new'            => _x('Novo Paciente', 'paciente', 'pab'),
        'add_new_item'       => __('Adicionar Novo Paciente', 'pab'),
        'new_item'           => __('Novo Paciente', 'pab'),
        'edit_item'          => __('Editar Paciente', 'pab'),
        'view_item'          => __('Ver Paciente', 'pab'),
        'all_items'          => __('Todos os Pacientes', 'pab'),
        'search_items'       => __('Buscar Pacientes', 'pab'),
        'parent_item_colon'  => __('Paciente Pai:', 'pab'),
        'not_found'          => __('Nenhum paciente encontrado.', 'pab'),
        'not_found_in_trash' => __('Nenhum paciente encontrado no lixo.', 'pab')
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => ['slug' => 'pacientes'],
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-admin-users',
        'supports'           => [], // 'title' é crucial para o nome
        'show_in_rest'       => true
    ];

    register_post_type('pab_paciente', $args);
});
