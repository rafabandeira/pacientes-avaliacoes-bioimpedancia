<?php // includes/cpt-avaliacao.php
if (!defined("ABSPATH")) {
    exit();
}

add_action("init", function () {
    register_post_type("pab_avaliacao", [
        "label" => __("Avaliações", "pab"),
        "public" => false,
        "show_ui" => true,
        "menu_icon" => "dashicons-clipboard",
        "supports" => [],
        "capability_type" => "post",
        "map_meta_cap" => true,
        "show_in_menu" => "edit.php?post_type=pab_paciente", // dentro do menu Pacientes
    ]);
});
