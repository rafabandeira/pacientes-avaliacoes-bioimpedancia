<?php // includes/helpers.php - CORRIGIDO: Ocultação de Título e Editor

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Obter meta simples com fallback.
 * @param int $post_id ID do post.
 * @param string $key Chave do meta.
 * @param mixed $default Valor de retorno padrão se o meta estiver vazio.
 * @return mixed O valor do meta ou o default.
 */
function pab_get($post_id, $key, $default = "")
{
    // Usamos get_post_meta com single=true para buscar do cache.
    $v = get_post_meta($post_id, $key, true);
    return $v !== "" ? $v : $default;
}

/**
 * Associar avaliação/bioimpedância ao paciente.
 * Armazena o ID do paciente em meta 'pab_paciente_id' e define post_parent.
 */
function pab_link_to_patient($child_id, $patient_id)
{
    update_post_meta($child_id, "pab_paciente_id", (int) $patient_id);
    // Também definimos post_parent sem disparar hooks
    $wpdb = $GLOBALS["wpdb"];
    $wpdb->update(
        $wpdb->posts,
        ["post_parent" => (int) $patient_id],
        ["ID" => (int) $child_id],
        ["%d"],
        ["%d"],
    );
    // Limpar cache do post
    clean_post_cache($child_id);
}

/**
 * Força a desativação do Block Editor (Gutenberg) para os nossos CPTs.
 */
add_filter(
    "use_block_editor_for_post_type",
    function ($is_enabled, $post_type) {
        if (
            in_array($post_type, [
                "pab_paciente",
                "pab_avaliacao",
                "pab_bioimpedancia",
            ])
        ) {
            return false;
        }
        return $is_enabled;
    },
    99,
    2,
);

/**
 * Filtra os dados de post antes de serem inseridos/atualizados para garantir
 * que o post_title seja construído corretamente.
 */
add_filter(
    "wp_insert_post_data",
    function ($data, $postarr) {
        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            return $data;
        }

        $post_type = $data["post_type"];

        // --- Lógica para pab_paciente ---
        if ($post_type === "pab_paciente") {
            // Puxa o nome do Meta Field (pab_nome) que deve estar no $_POST
            $nome_paciente_meta = isset($_POST["pab_nome"])
                ? sanitize_text_field($_POST["pab_nome"])
                : "";

            if ($nome_paciente_meta) {
                $data["post_title"] = $nome_paciente_meta;
            } else {
                $data["post_title"] =
                    __("Paciente sem nome", "pab") .
                    " (" .
                    current_time("Y-m-d H:i") .
                    ")";
            }
        }

        // --- Lógica para Avaliação/Bioimpedância ---
        if (in_array($post_type, ["pab_avaliacao", "pab_bioimpedancia"])) {
            $patient_id = isset($_POST["pab_paciente_id"])
                ? (int) $_POST["pab_paciente_id"]
                : 0;

            if ($patient_id) {
                $name =
                    get_the_title($patient_id) ?:
                    __("Paciente Sem Nome", "pab");

                $item_type =
                    $post_type === "pab_avaliacao"
                        ? __("AVALIAÇÃO", "pab")
                        : __("BIOIMPEDÂNCIA", "pab");

                // Para novos posts, usar titulo temporário
                if (isset($postarr["ID"]) && $postarr["ID"] > 0) {
                    // Post existente - usar o ID real
                    $data["post_title"] = trim(
                        "$name - $item_type - {$postarr["ID"]}",
                    );
                } else {
                    // Novo post - usar título temporário
                    $data["post_title"] = trim("$name - $item_type - NOVO");
                }
            } else {
                $data["post_title"] = __("Item Órfão - $post_type", "pab");
            }
        }

        return $data;
    },
    99,
    2,
);

/**
 * Hook para atualizar o título após a inserção/atualização do post
 * quando o ID estiver disponível
 */
add_action(
    "wp_insert_post",
    function ($post_id, $post, $update) {
        // Só executar para nossos CPTs
        if (
            !in_array($post->post_type, ["pab_avaliacao", "pab_bioimpedancia"])
        ) {
            return;
        }

        // Só executar se o título contém "NOVO" (indicando que precisa ser atualizado)
        if (strpos($post->post_title, "- NOVO") === false) {
            return;
        }

        // Buscar o ID do paciente
        $patient_id = (int) get_post_meta($post_id, "pab_paciente_id", true);
        if (!$patient_id) {
            return;
        }

        $patient_name =
            get_the_title($patient_id) ?: __("Paciente Sem Nome", "pab");
        $item_type =
            $post->post_type === "pab_avaliacao"
                ? __("AVALIAÇÃO", "pab")
                : __("BIOIMPEDÂNCIA", "pab");

        // Construir o novo título com o ID real
        $new_title = trim("$patient_name - $item_type - $post_id");

        // Atualizar o título sem disparar hooks (evitar loop)
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ["post_title" => $new_title],
            ["ID" => $post_id],
            ["%s"],
            ["%d"],
        );

        // Limpar cache
        clean_post_cache($post_id);
    },
    10,
    3,
);

/**
 * Ocultar campo de Título e Editor (clássico/tinymce) via CSS.
 * Esta é a forma mais direta de garantir que não apareçam na UI.
 */
add_action("admin_head", function () {
    $screen = get_current_screen();
    if (!$screen) {
        return;
    }

    if (
        in_array($screen->post_type, [
            "pab_paciente",
            "pab_avaliacao",
            "pab_bioimpedancia",
        ])
    ) {
        // Oculta a div do título, o campo de input do título (Gutenberg)
        // e a div do editor clássico (postdivrich)
        echo '<style>
            #titlediv,
            #postdivrich,
            .editor-post-title__input,
            #wp-content-wrap { /* Às vezes o wrapper inteiro do editor clássico precisa ser ocultado */
                display: none !important;
            }
        </style>';
    }
});
