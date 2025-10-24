<?php
/**
 * Registro de Metaboxes - Avaliação
 *
 * @package PAB
 * @subpackage Avaliacao
 */

if (!defined("ABSPATH")) {
    exit();
}

// Incluir arquivos de metaboxes
require_once __DIR__ . "/metaboxes/paciente.php";
require_once __DIR__ . "/metaboxes/anamnese.php";
require_once __DIR__ . "/metaboxes/habitos.php";
require_once __DIR__ . "/metaboxes/antecedentes.php";
require_once __DIR__ . "/metaboxes/ginecologico.php";

/**
 * Registra as metaboxes da avaliação
 */
add_action("add_meta_boxes", function () {
    // Metabox de paciente vinculado (sidebar)
    add_meta_box(
        "pab_av_paciente",
        __("Paciente vinculado", "pab"),
        "pab_av_paciente_cb",
        "pab_avaliacao",
        "side",
        "high",
    );

    // Metabox de Anamnese
    add_meta_box(
        "pab_av_anamnese",
        __("Anamnese", "pab"),
        "pab_av_anamnese_cb",
        "pab_avaliacao",
        "normal",
        "high",
    );

    // Metabox de Hábitos de Vida
    add_meta_box(
        "pab_av_habitos",
        __("Hábitos de vida", "pab"),
        "pab_av_habitos_cb",
        "pab_avaliacao",
        "normal",
        "default",
    );

    // Metabox de Antecedentes
    add_meta_box(
        "pab_av_antecedentes",
        __("Antecedentes patológicos e familiares", "pab"),
        "pab_av_antecedentes_cb",
        "pab_avaliacao",
        "normal",
        "default",
    );

    // Metabox de Histórico Ginecológico
    add_meta_box(
        "pab_av_gineco",
        __("Histórico ginecológico", "pab"),
        "pab_av_gineco_cb",
        "pab_avaliacao",
        "normal",
        "default",
    );
});

/**
 * Salva os dados da avaliação
 *
 * CORRIGIDO: Lógica de salvamento de status
 *
 * @param int $post_id ID do post
 */
add_action(
    "save_post_pab_avaliacao",
    function ($post_id) {
        // 1. Checagens de segurança
        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }

        $has_valid_nonce =
            isset($_POST["pab_av_nonce"]) &&
            wp_verify_nonce($_POST["pab_av_nonce"], "pab_av_save");

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

        // 3. Salvamento dos Campos
        $fields = [
            "pab_av_qp",
            "pab_av_hda",
            "pab_av_obj", // Anamnese
            "pab_av_alc_sim",
            "pab_av_alc_freq",
            "pab_av_tabag_sim",
            "pab_av_tabag_freq", // Hábitos
            "pab_av_atv_sim",
            "pab_av_atv_quais",
            "pab_av_atv_freq",
            "pab_av_alim_tipo",
            "pab_av_alim_ref",
            "pab_av_liq",
            "pab_av_sono_qual",
            "pab_av_sono_hd",
            "pab_av_sono_ha",
            "pab_av_intest",
            "pab_av_ant_pesso",
            "pab_av_ant_fam",
            "pab_av_med_uso",
            "pab_av_cirurg", // Antecedentes
            "pab_av_gine_gesta",
            "pab_av_gine_partos",
            "pab_av_gine_abortos", // Gineco
            "pab_av_gine_filhos",
            "pab_av_gine_menarca",
            "pab_av_gine_dum",
            "pab_av_gine_ciclo",
            "pab_av_gine_sop",
            "pab_av_gine_endo",
            "pab_av_gine_anticon",
            "pab_av_gine_anticon_quais",
            "pab_av_gine_med_sim",
            "pab_av_gine_med_quais",
        ];

        foreach ($fields as $k) {
            if (isset($_POST[$k])) {
                if (is_array($_POST[$k])) {
                    $value = array_map("sanitize_text_field", $_POST[$k]);
                } else {
                    $value = sanitize_text_field($_POST[$k]);
                }
                update_post_meta($post_id, $k, $value);
            } else {
                delete_post_meta($post_id, $k);
            }
        }

        // 4. CORRIGIDO: Gerar Título, Slug e Status
        if ($patient_id) {
            $patient_name =
                get_the_title($patient_id) ?: __("Paciente Sem Nome", "pab");

            $item_type = __("Avaliação", "pab");

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
