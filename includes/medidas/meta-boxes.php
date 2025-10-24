<?php
/**
 * Registro de Metaboxes - Medidas
 *
 * Registra todas as metaboxes que aparecem na tela de edição do post type pab_medidas
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
 * Registra as metaboxes das medidas
 */
add_action("add_meta_boxes", function () {
    // Metabox de paciente vinculado
    add_meta_box(
        "pab_med_paciente",
        "Paciente vinculado",
        "pab_med_paciente_cb",
        "pab_medidas",
        "side",
        "high",
    );

    // Metabox de medidas corporais
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
        "Histórico",
        "pab_med_historico_cb",
        "pab_medidas",
        "normal",
        "default",
    );
});

/**
 * Salva os dados das medidas
 *
 * @param int $post_id ID do post
 */
add_action(
    "save_post_pab_medidas",
    function ($post_id) {
        // Debug log
        error_log("PAB DEBUG: Iniciando salvamento medidas ID: $post_id");
        error_log(
            "PAB DEBUG: Ação atual: " .
                (isset($_REQUEST["action"])
                    ? $_REQUEST["action"]
                    : "não definida"),
        );

        // NÃO processar se for operação de lixo/exclusão
        $current_post = get_post($post_id);
        if (
            $current_post &&
            in_array($current_post->post_status, ["trash", "inherit"])
        ) {
            error_log("PAB DEBUG: Post em lixo ou herdado, não processando");
            return;
        }

        // NÃO processar se for uma ação de lixo via REQUEST
        if (
            isset($_REQUEST["action"]) &&
            in_array($_REQUEST["action"], ["trash", "delete", "untrash"])
        ) {
            error_log(
                "PAB DEBUG: Ação de lixo/exclusão detectada, não processando",
            );
            return;
        }

        // Prevenir loops infinitos
        static $processing = [];
        if (isset($processing[$post_id])) {
            error_log(
                "PAB DEBUG: Loop detectado para post $post_id, abortando",
            );
            return;
        }
        $processing[$post_id] = true;

        // Verificar nonce
        $has_valid_nonce =
            isset($_POST["pab_med_nonce"]) &&
            wp_verify_nonce($_POST["pab_med_nonce"], "pab_med_save");

        if (!$has_valid_nonce) {
            error_log("PAB DEBUG: Nonce inválido para post $post_id");

            // Se não há nonce válido, só salvar o pab_paciente_id se estiver no POST
            if (isset($_POST["pab_paciente_id"])) {
                $patient_id = (int) $_POST["pab_paciente_id"];
                error_log(
                    "PAB DEBUG: Salvando apenas pab_paciente_id=$patient_id (sem nonce)",
                );
                pab_link_to_patient($post_id, $patient_id);
            }

            unset($processing[$post_id]);
            return;
        }

        // Verificar capabilities
        if (!current_user_can("edit_post", $post_id)) {
            error_log(
                "PAB DEBUG: Usuário sem permissão para editar post $post_id",
            );
            unset($processing[$post_id]);
            return;
        }

        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            error_log("PAB DEBUG: Autosave detectado, ignorando");
            unset($processing[$post_id]);
            return;
        }

        if (wp_is_post_revision($post_id)) {
            error_log("PAB DEBUG: Revisão detectada, ignorando");
            unset($processing[$post_id]);
            return;
        }

        // Vinculação do Paciente
        if (isset($_POST["pab_paciente_id"])) {
            $patient_id = (int) $_POST["pab_paciente_id"];
            error_log(
                "PAB DEBUG: Vinculando medidas $post_id ao paciente $patient_id",
            );
            pab_link_to_patient($post_id, $patient_id);
        }

        // Lista de campos das medidas corporais
        $fields = [
            "pab_med_pescoco",
            "pab_med_torax",
            "pab_med_braco_direito",
            "pab_med_braco_esquerdo",
            "pab_med_abd_superior",
            "pab_med_cintura",
            "pab_med_abd_inferior",
            "pab_med_quadril",
            "pab_med_coxa_direita",
            "pab_med_coxa_esquerda",
            "pab_med_panturrilha_direita",
            "pab_med_panturrilha_esquerda",
        ];

        // Salvar cada campo
        foreach ($fields as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== "") {
                $value = sanitize_text_field($_POST[$k]);

                // Converter vírgulas para pontos para padronizar formato decimal
                $value = str_replace(",", ".", $value);

                // Validar se é um número válido
                if (is_numeric($value)) {
                    // Formatar com 1 casa decimal
                    $value = number_format((float) $value, 1, ".", "");
                }

                error_log("PAB DEBUG: Salvando $k = $value");
                update_post_meta($post_id, $k, $value);
            } else {
                error_log("PAB DEBUG: Removendo meta $k (valor vazio)");
                delete_post_meta($post_id, $k);
            }
        }

        // Atualizar título se necessário
        $current_post = get_post($post_id);
        if (
            $current_post &&
            strpos($current_post->post_title, "- NOVO") !== false
        ) {
            $patient_id = (int) get_post_meta(
                $post_id,
                "pab_paciente_id",
                true,
            );
            if ($patient_id) {
                $patient_name =
                    get_the_title($patient_id) ?: "Paciente Sem Nome";
                $new_title = trim("$patient_name - Medidas - $post_id");
                $new_slug = sanitize_title($new_title);

                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    [
                        "post_title" => $new_title,
                        "post_name" => $new_slug,
                    ],
                    ["ID" => $post_id],
                    ["%s", "%s"],
                    ["%d"],
                );
                clean_post_cache($post_id);

                error_log("PAB DEBUG: Título atualizado para: $new_title");
            }
        }

        error_log("PAB DEBUG: Finalizando salvamento medidas ID: $post_id");

        // Limpar flag de processamento
        unset($processing[$post_id]);
    },
    20,
    1,
);

/**
 * Hook para controlar o status dos posts de medidas
 * Executado ANTES do save_post para garantir o status correto
 */
add_action(
    "wp_insert_post_data",
    function ($data, $postarr) {
        // Só processar medidas
        if ($data["post_type"] !== "pab_medidas") {
            return $data;
        }

        error_log(
            "PAB DEBUG: wp_insert_post_data - Status original: {$data["post_status"]}",
        );

        // NÃO interferir com operações de lixo, exclusão ou outros status especiais
        if (
            in_array($data["post_status"], [
                "trash",
                "inherit",
                "private",
                "future",
                "pending",
            ])
        ) {
            error_log("PAB DEBUG: Status especial detectado, não interferindo");
            return $data;
        }

        // NÃO interferir se for uma operação de lixo via REQUEST
        if (
            isset($_REQUEST["action"]) &&
            in_array($_REQUEST["action"], ["trash", "delete", "untrash"])
        ) {
            error_log(
                "PAB DEBUG: Operação de lixo/exclusão detectada, não interferindo",
            );
            return $data;
        }

        // Garantir que medidas sejam sempre publicadas
        if (
            $data["post_status"] === "draft" ||
            $data["post_status"] === "auto-draft"
        ) {
            error_log("PAB DEBUG: Forçando status 'publish' para medidas");
            $data["post_status"] = "publish";
        }

        return $data;
    },
    10,
    2,
);
