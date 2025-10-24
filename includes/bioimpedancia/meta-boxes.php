<?php
/**
 * Registro de Metaboxes - Bioimpedância
 *
 * @package PAB
 * @subpackage Bioimpedancia
 */

if (!defined("ABSPATH")) {
    exit();
}

// Incluir arquivos de metaboxes
require_once __DIR__ . "/metaboxes/paciente.php";
require_once __DIR__ . "/metaboxes/dados.php";
require_once __DIR__ . "/metaboxes/avatares.php";
require_once __DIR__ . "/metaboxes/composicao.php";
require_once __DIR__ . "/metaboxes/diagnostico.php";
require_once __DIR__ . "/metaboxes/historico.php";

/**
 * Registra as metaboxes da bioimpedância
 */
add_action("add_meta_boxes", function () {
    // Metabox de paciente vinculado (sidebar)
    add_meta_box(
        "pab_bi_paciente",
        "Paciente vinculado",
        "pab_bi_paciente_cb",
        "pab_bioimpedancia",
        "side",
        "high",
    );

    // Metabox de dados de bioimpedância
    add_meta_box(
        "pab_bi_dados",
        "Dados de Bioimpedância",
        "pab_bi_dados_cb",
        "pab_bioimpedancia",
        "normal",
        "high",
    );

    // Metabox de avatares (classificação visual)
    add_meta_box(
        "pab_bi_avatares",
        "Avatares (OMS)",
        "pab_bi_avatares_cb",
        "pab_bioimpedancia",
        "normal",
        "default",
        ["__back_compat_meta_box" => false, "class" => "postbox-bio-avatars"],
    );

    // Metabox de análise corporal completa (unificada)
    add_meta_box(
        "pab_bi_comp_tab",
        "Análise Corporal Completa",
        "pab_bi_comp_tab_cb",
        "pab_bioimpedancia",
        "normal",
        "default",
    );

    // Metabox de histórico
    add_meta_box(
        "pab_bi_historico",
        "Histórico",
        "pab_bi_historico_cb",
        "pab_bioimpedancia",
        "normal",
        "default",
    );
});

/**
 * Salva os dados da bioimpedância
 *
 * CORRIGIDO: Lógica de salvamento de status
 *
 * @param int $post_id ID do post
 */
add_action(
    "save_post_pab_bioimpedancia",
    function ($post_id) {
        // 1. Checagens de segurança
        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }

        $has_valid_nonce =
            isset($_POST["pab_bi_nonce"]) &&
            wp_verify_nonce($_POST["pab_bi_nonce"], "pab_bi_save");

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

        // 3. Salvamento dos Campos Numéricos
        $fields = [
            "pab_bi_peso",
            "pab_bi_gordura_corporal",
            "pab_bi_musculo_esq",
            "pab_bi_gordura_visc",
            "pab_bi_metab_basal",
            "pab_bi_idade_corporal",
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

            $item_type = __("Bioimpedância", "pab");

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
