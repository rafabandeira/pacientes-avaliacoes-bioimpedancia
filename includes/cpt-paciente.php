<?php
/**
 * CPT Paciente - registro e hooks simplificados para trash/restore/delete
 *
 * CORRIGIDO: Removidos hooks de diagnóstico (logs) que não são necessários
 * para produção. Mantidos apenas os hooks funcionais de lixeira/exclusão.
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Registra o Custom Post Type 'pab_paciente'
 */
add_action(
    "init",
    function () {
        $labels = [
            "name" => _x("Pacientes", "post type general name", "pab"),
            "singular_name" => _x("Paciente", "post type singular name", "pab"),
            "menu_name" => _x("Pacientes", "admin menu", "pab"),
            "name_admin_bar" => _x("Paciente", "add new on admin bar", "pab"),
            "add_new" => _x("Novo Paciente", "paciente", "pab"),
            "add_new_item" => __("Adicionar Novo Paciente", "pab"),
            "new_item" => __("Novo Paciente", "pab"),
            "edit_item" => __("Editar Paciente", "pab"),
            "view_item" => __("Ver Paciente", "pab"),
            "all_items" => __("Todos os Pacientes", "pab"),
            "search_items" => __("Buscar Pacientes", "pab"),
            "parent_item_colon" => __("Paciente Pai:", "pab"),
            "not_found" => __("Nenhum paciente encontrado.", "pab"),
            "not_found_in_trash" => __(
                "Nenhum paciente encontrado no lixo.",
                "pab",
            ),
        ];

        $args = [
            "labels" => $labels,
            "public" => true,
            // Não é consultável publicamente por URL individuais (apenas UI)
            "publicly_queryable" => false,
            "show_ui" => true,
            "show_in_menu" => true,
            "query_var" => true,
            "rewrite" => ["slug" => "pacientes"],
            "capability_type" => "post",
            "map_meta_cap" => true,
            "has_archive" => false,
            "hierarchical" => false,
            "menu_icon" => "dashicons-admin-users",
            // Habilita título (importante para status e lixeira aparecerem corretamente)
            "supports" => ["title"],
            "show_in_rest" => true,
        ];

        register_post_type("pab_paciente", $args);
    },
    10,
);

/**
 * ADMIN HANDLER - REMOVIDO
 * O log de diagnóstico em 'admin_init' foi removido.
 */
// add_action("admin_init", ...); // Removido

/**
 * Helpers
 */
if (!function_exists("pab_get_child_post_ids")) {
    /**
     * Retorna IDs de posts filhos relacionados a um paciente.
     *
     * @param int $patient_id
     * @param string|array $post_status (opcional) status filter, default 'any'
     * @return int[] lista de IDs
     */
    function pab_get_child_post_ids($patient_id, $post_status = "any")
    {
        $children = get_posts([
            "post_type" => [
                "pab_avaliacao",
                "pab_bioimpedancia",
                "pab_medidas",
            ],
            // CORREÇÃO: Usar 'post_parent' é mais confiável que meta_query
            "post_parent" => (int) $patient_id,
            "posts_per_page" => -1,
            "post_status" => $post_status,
            "fields" => "ids",
            "cache_results" => false, // Não cachear esta query interna
        ]);

        return is_array($children) ? $children : [];
    }
}

/**
 * Quando um paciente é movido para a lixeira, mover também os filhos para lixeira.
 */
add_action(
    "wp_trash_post",
    function ($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== "pab_paciente") {
            return;
        }

        // Pega filhos (qualquer status, exceto lixeira) e envia para lixeira
        $child_ids = pab_get_child_post_ids($post_id, "any");

        // Remover hooks de log

        foreach ($child_ids as $child_id) {
            // evita re-trash desnecessário
            if (get_post_status($child_id) !== "trash") {
                wp_trash_post($child_id);
            }
        }
    },
    10,
);

/**
 * Quando um paciente é restaurado da lixeira (untrashed), restaurar filhos que estavam em trash.
 */
add_action(
    "untrashed_post",
    function ($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== "pab_paciente") {
            return;
        }

        // Apenas restaurar filhos que estão no 'trash'
        $child_ids = pab_get_child_post_ids($post_id, "trash");
        foreach ($child_ids as $child_id) {
            wp_untrash_post($child_id);
        }
    },
    10,
);

/**
 * Antes da exclusão permanente de um paciente (quando for deletado do banco),
 * remove os filhos permanentemente para evitar órfãos.
 */
add_action(
    "before_delete_post",
    function ($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== "pab_paciente") {
            return;
        }

        // Deleta permanentemente todos os filhos
        $child_ids = pab_get_child_post_ids($post_id, "any");
        foreach ($child_ids as $child_id) {
            // delete_post com $force_delete = true garante remoção completa
            wp_delete_post($child_id, true);
        }
    },
    10,
);

/**
 * Diagnostic: Hooks removidos
 * Os hooks 'transition_post_status' e 'wp_insert_post_data' para 'pab_paciente'
 * foram removidos pois eram apenas para diagnóstico e não são
 * necessários para a funcionalidade.
 */
// add_action( "transition_post_status", ... ); // Removido
// add_filter( "wp_insert_post_data", ... ); // Removido
