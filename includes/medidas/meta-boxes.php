<?php
/**
 * Registro de Metaboxes - Medidas
 *
 * @package PAB
 * @subpackage Medidas
 */

if (!defined("ABSPATH")) {
    exit();
}

// Incluir arquivos de metaboxes
require_once __DIR__ . "/metaboxes/paciente.php";
require_once __DIR__ . "/metaboxes/corporais.php";
require_once __DIR__ . "/metaboxes/historico.php";

/**
 * Registra as metaboxes de Medidas
 */
add_action("add_meta_boxes", function () {
    // Metabox de paciente vinculado (sidebar)
    add_meta_box(
        "pab_med_paciente",
        "Paciente vinculado",
        "pab_med_paciente_cb",
        "pab_medidas",
        "side",
        "high",
    );

    // Metabox de Medidas Corporais
    add_meta_box(
        "pab_med_corporais",
        "Medidas Corporais (cm)",
        "pab_med_corporais_cb",
        "pab_medidas",
        "normal",
        "high",
    );

    // Metabox de histórico
    add_meta_box(
        "pab_med_historico",
        "Histórico de Medidas",
        "pab_med_historico_cb",
        "pab_medidas",
        "normal",
        "default",
    );
});

/**
 * Salva os dados de Medidas
 *
 * CORRIGIDO: Lógica de salvamento de status
 *
 * @param int $post_id ID do post
 */
add_action(
    "save_post_pab_medidas",
    function ($post_id) {
        // 1. Checagens de segurança
        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }

        $has_valid_nonce =
            isset($_POST["pab_med_nonce"]) &&
            wp_verify_nonce($_POST["pab_med_nonce"], "pab_med_save");

        if (!$has_valid_nonce) {
            if (
                isset($_POST["pab_paciente_id"]) &&
                !get_post_meta($post_id, "pab_paciente_id", true)
            ) {
                $patient_id = (int) $_POST["pab_paciente_id"];
                pab_link_to_patient($post_id, $patient_id);
            }
            return;
        }

        if (!current_user_can("edit_post", $post_id)) {
            return;
        }

        static $processing = [];
        if (isset($processing[$post_id])) {
            return;
        }
        $processing[$post_id] = true;

        // 2. Vinculação do Paciente
        $patient_id = 0;
        if (isset($_POST["pab_paciente_id"])) {
            $patient_id = (int) $_POST["pab_paciente_id"];
            pab_link_to_patient($post_id, $patient_id);
        } else {
            $patient_id = (int) get_post_meta(
                $post_id,
                "pab_paciente_id",
                true,
            );
        }

        // 3. Salvamento dos Campos de Medidas
        $fields = [
            "pab_med_peso",
            "pab_med_pescoco",
            "pab_med_torax",
            "pab_med_braco_d",
            "pab_med_braco_e",
            "pab_med_antebraco_d",
            "pab_med_antebraco_e",
            "pab_med_cintura",
            "pab_med_abdomen",
            "pab_med_quadril",
            "pab_med_coxa_d",
            "pab_med_coxa_e",
            "pab_med_panturrilha_d",
            "pab_med_panturrilha_e",
        ];

        foreach ($fields as $k) {
            if (isset($_POST[$k])) {
                $value = sanitize_text_field($_POST[$k]);
                update_post_meta($post_id, $k, $value);
            }
        }

        // 4. CORRIGIDO: Gerar Título, Slug e Status
        if ($patient_id) {
            $patient_name =
                get_the_title($patient_id) ?: __("Paciente Sem Nome", "pab");

            $item_type = __("Medidas", "pab");

            $new_title = trim("$patient_name - $item_type - $post_id");
            $new_slug = sanitize_title($new_title);

            // Obter o status atual do post
            $current_status = get_post_status($post_id);

            // Só forçar 'publish' se o post for novo ou rascunho.
            // NÃO interferir se for 'trash'.
            $new_status = $current_status;
            if (
                in_array($current_status, [
                    "auto-draft",
                    "draft",
                    "pending",
                    "future",
                ])
            ) {
                $new_status = "publish";
            }

            // Atualizar o post de uma só vez, sem disparar hooks
            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                [
                    "post_title" => $new_title,
                    "post_name" => $new_slug,
                    "post_status" => $new_status,
                ],
                ["ID" => $post_id], // Onde
                ["%s", "%s", "%s"], // Formatos
                ["%d"], // Formato do WHERE
            );

            clean_post_cache($post_id);
        }

        unset($processing[$post_id]);
    },
    10,
    1,
);
