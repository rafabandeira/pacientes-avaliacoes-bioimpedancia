<?php // includes/admin-listings.php - CORRIGIDO: Hook 'parse_query' vazio removido.

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Pré-carrega todos os metadados dos posts listados
 *
 * CORREÇÃO: Removido o hook 'parse_query' (linhas 16-56)
 * que estava vazio e não era necessário.
 */
// add_action( "parse_query", ... ); // Removido

/**
 * Define as colunas personalizadas na listagem de Pacientes.
 */
add_filter("manage_pab_paciente_posts_columns", function ($cols) {
    // Remove colunas que podem não ser relevantes e define a ordem
    unset($cols["date"]);

    $new_cols = [
        "cb" => $cols["cb"], // Checkbox
        "title" => __("Nome do Paciente", "pab"), // Usa title, mas renomeado
        "pab_genero" => __("Gênero", "pab"),
        "pab_nascimento" => __("Nascimento", "pab"),
        "pab_contato" => __("Contato", "pab"),
        "date" => __("Data de Cadastro", "pab"),
    ];

    return $new_cols;
});

/**
 * Popula as colunas personalizadas.
 */
add_action(
    "manage_pab_paciente_posts_custom_column",
    function ($col, $post_id) {
        // pab_get() agora será performático graças ao hook abaixo.

        switch ($col) {
            case "pab_genero":
                echo esc_html(pab_get($post_id, "pab_genero"));
                break;

            case "pab_nascimento":
                $data = pab_get($post_id, "pab_nascimento");
                // Formata a data para padrão brasileiro
                echo $data ? esc_html(date("d/m/Y", strtotime($data))) : "-";
                break;

            case "pab_contato":
                $celular = pab_get($post_id, "pab_celular");
                $email = pab_get($post_id, "pab_email");

                $output = [];
                if ($celular) {
                    $output[] = esc_html($celular);
                }
                if ($email) {
                    $output[] = esc_html($email);
                }

                echo implode(" | ", $output);
                break;
        }
    },
    10,
    2,
);

/**
 * Otimização: Força o carregamento do cache de meta antes da renderização da tabela.
 * Isso garante que pab_get() não faça consultas individuais ao banco.
 */
add_action(
    "manage_posts_extra_tablenav",
    function ($which) {
        if (is_admin() && $which === "top") {
            global $wp_query;

            // Verifica se a query principal é a de um dos nossos CPTs e tem resultados
            $pab_post_types = [
                "pab_paciente",
                "pab_avaliacao",
                "pab_bioimpedancia",
                "pab_medidas",
            ];
            if (
                $wp_query->is_main_query() && // Garantir que é a query principal
                in_array($wp_query->get("post_type"), $pab_post_types) &&
                $wp_query->have_posts()
            ) {
                // Usa a função do core para pré-carregar os metadados dos posts listados
                // update_meta_cache espera um array de IDs — extrair apenas os IDs dos objetos WP_Post
                $post_ids = wp_list_pluck($wp_query->posts, "ID");
                if (!empty($post_ids)) {
                    update_meta_cache("post", $post_ids);
                }
            }
        }
    },
    10,
    1,
);

/**
 * Define as colunas personalizadas na listagem de Medidas.
 */
add_filter("manage_pab_medidas_posts_columns", function ($cols) {
    unset($cols["date"]);

    $new_cols = [
        "cb" => $cols["cb"],
        "title" => __("Título", "pab"),
        "pab_paciente" => __("Paciente", "pab"),
        "pab_resumo" => __("Resumo das Medidas", "pab"),
        "date" => __("Data", "pab"),
    ];

    return $new_cols;
});

/**
 * Popula as colunas personalizadas das Medidas.
 */
add_action(
    "manage_pab_medidas_posts_custom_column",
    function ($col, $post_id) {
        switch ($col) {
            case "pab_paciente":
                // CORREÇÃO: Usar post_parent é mais rápido que get_post_meta
                $patient_id = get_post($post_id)->post_parent;
                if ($patient_id) {
                    $patient_name = get_the_title($patient_id);
                    $edit_link = get_edit_post_link($patient_id);
                    echo '<a href="' .
                        esc_url($edit_link) .
                        '">' .
                        esc_html($patient_name) .
                        "</a>";
                } else {
                    echo '<span style="color: #dc3545;">Não vinculado</span>';
                }
                break;

            case "pab_resumo":
                $medidas = [
                    "Pescoço" => pab_get($post_id, "pab_med_pescoco"),
                    "Tórax" => pab_get($post_id, "pab_med_torax"),
                    "Cintura" => pab_get($post_id, "pab_med_cintura"),
                    "Quadril" => pab_get($post_id, "pab_med_quadril"),
                ];

                $resumo = [];
                foreach ($medidas as $label => $valor) {
                    if ($valor) {
                        $resumo[] = $label . ": " . $valor . "cm";
                    }
                }

                echo $resumo
                    ? esc_html(implode(" | ", array_slice($resumo, 0, 3)))
                    : "-";
                if (count($resumo) > 3) {
                    echo ' <small style="color: #666;">+ mais</small>';
                }
                break;
        }
    },
    10,
    2,
);
