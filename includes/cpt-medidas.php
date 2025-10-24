<?php // includes/cpt-medidas.php
if (!defined("ABSPATH")) {
    exit();
}

add_action("init", function () {
    $labels = [
        "name" => _x("Medidas", "post type general name", "pab"),
        "singular_name" => _x(
            "Medidas",
            "post type singular name",
            "pab",
        ),
        "menu_name" => _x("Medidas", "admin menu", "pab"),
        "add_new" => _x("Novas Medidas", "medidas", "pab"),
        "add_new_item" => __("Adicionar Novas Medidas", "pab"),
        "new_item" => __("Novas Medidas", "pab"),
        "edit_item" => __("Editar Medidas", "pab"),
        "view_item" => __("Ver Medidas", "pab"),
        "all_items" => __("Medidas", "pab"),
        "search_items" => __("Buscar Medidas", "pab"),
        "parent_item_colon" => __("Paciente:", "pab"),
        "not_found" => __("Nenhuma medida encontrada.", "pab"),
        "not_found_in_trash" => __(
            "Nenhuma medida encontrada no lixo.",
            "pab",
        ),
    ];

    $args = [
        "labels" => $labels,
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_menu" => "edit.php?post_type=pab_paciente",
        "query_var" => true,
        "rewrite" => ["slug" => "medidas"],
        "capability_type" => "post",
        "map_meta_cap" => true,
        "has_archive" => false,
        "hierarchical" => false,
        "menu_icon" => "dashicons-admin-tools",
        "supports" => [],
        "show_in_rest" => true,
    ];

    register_post_type("pab_medidas", $args);
});
