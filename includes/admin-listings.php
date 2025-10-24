<?php // includes/admin-listings.php - CORRIGIDO V2: Usando parse_query para evitar Fatal Error

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Pré-carrega todos os metadados dos posts listados para evitar consultas N+1
 * e exaustão de memória na função pab_get().
 *
 * Utiliza 'parse_query' (ou 'pre_get_posts' seria outra opção segura) para acessar
 * o objeto WP_Query e garantir que a otimização ocorra apenas na tela de listagem.
 */
add_action(
    "parse_query",
    function (\WP_Query $query) {
        global $pagenow;

        // Verifica se estamos no painel de admin, na tela de edição (edit.php)
        // e se o post type é um dos nossos CPTs.
        if (
            !is_admin() ||
            $pagenow !== "edit.php" ||
            !$query->is_main_query() ||
            !in_array($query->get("post_type"), [
                "pab_paciente",
                "pab_avaliacao",
                "pab_bioimpedancia",
                "pab_medidas",
            ])
        ) {
            return;
        }

        // Hook para ações após o carregamento da página
        add_action("admin_footer", function () {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Removemos todas as confirmações para tornar a exclusão direta
            });
            </script>
            <?php
        });

        if (!is_admin() || get_current_screen()->post_type !== "pab_paciente") {
            return;
        }

        // Se a query já foi executada (o que acontece antes de 'parse_query' em certos casos)
        // podemos usar a lista de posts para pré-carregar o cache.
        // Embora 'parse_query' seja antes da busca, vamos usar 'pre_get_posts' para ser mais robusto.

        // **NOTA DE REVISÃO:** Usar `pre_get_posts` é mais canônico para manipular a query.
        // Vamos usar a função `get_meta_cache` para garantir o carregamento antes da renderização das colunas.

        // Otimização: Garantir que o cache seja carregado antes de renderizar as colunas.
        // Usaremos `manage_posts_extra_tablenav` que é executado após a query e antes da tabela.

        // Desativando o hook problemático e usando o método canônico em seguida.
        // Este hook foi a causa do erro, o removeremos e usaremos o `manage_posts_extra_tablenav` no final do arquivo.
    },
    10,
    1,
);

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
                $patient_id = pab_get($post_id, "pab_paciente_id");
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
