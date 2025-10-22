<?php // includes/cpt-bioimpedancia.php - ATUALIZADO: Permite visualização pública

if (!defined("ABSPATH")) {
    exit();
}

add_action("init", function () {
    $labels = [
        "name" => _x("Bioimpedâncias", "post type general name", "pab"),
        "singular_name" => _x(
            "Bioimpedância",
            "post type singular name",
            "pab",
        ),
        "menu_name" => _x("Bioimpedância", "admin menu", "pab"),
        "add_new" => _x("Nova Bioimpedância", "bioimpedancia", "pab"),
        "add_new_item" => __("Adicionar Nova Bioimpedância", "pab"),
        "new_item" => __("Nova Bioimpedância", "pab"),
        "edit_item" => __("Editar Bioimpedância", "pab"),
        "view_item" => __("Ver Bioimpedância", "pab"),
        "all_items" => __("Bioimpedâncias", "pab"),
        "search_items" => __("Buscar Bioimpedâncias", "pab"),
        "parent_item_colon" => __("Paciente:", "pab"),
        "not_found" => __("Nenhuma bioimpedância encontrada.", "pab"),
        "not_found_in_trash" => __(
            "Nenhuma bioimpedância encontrada no lixo.",
            "pab",
        ),
    ];

    $args = [
        "labels" => $labels,
        "public" => true, // ALTERADO: true para permitir URL pública
        "publicly_queryable" => true, // ALTERADO: true para permitir acesso via URL
        "show_ui" => true,
        "show_in_menu" => "edit.php?post_type=pab_paciente",
        "query_var" => true,
        "rewrite" => ["slug" => "bioimpedancia"], // ADICIONADO: URL amigável
        "capability_type" => "post",
        "map_meta_cap" => true,
        "has_archive" => false,
        "hierarchical" => false,
        "menu_icon" => "dashicons-chart-area",
        "supports" => [],
        "show_in_rest" => true,
    ];

    register_post_type("pab_bioimpedancia", $args);
});
