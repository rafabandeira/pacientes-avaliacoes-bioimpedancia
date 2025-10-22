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
            error_log("PAB DEBUG wp_insert_post_data: Processando $post_type");

            // Verificar múltiplas fontes para o patient_id
            $patient_id = 0;

            // 1. Verificar $_POST
            if (isset($_POST["pab_paciente_id"])) {
                $patient_id = (int) $_POST["pab_paciente_id"];
                error_log("PAB DEBUG wp_insert_post_data: patient_id do POST: $patient_id");
            }

            // 2. Verificar $_REQUEST (inclui GET e POST)
            if (!$patient_id && isset($_REQUEST["pab_paciente_id"])) {
                $patient_id = (int) $_REQUEST["pab_paciente_id"];
                error_log("PAB DEBUG wp_insert_post_data: patient_id do REQUEST: $patient_id");
            }

            // 3. Verificar $postarr (dados passados para wp_insert_post)
            if (!$patient_id && isset($postarr["pab_paciente_id"])) {
                $patient_id = (int) $postarr["pab_paciente_id"];
                error_log("PAB DEBUG wp_insert_post_data: patient_id do postarr: $patient_id");
            }

            // Para posts existentes, também verificar meta existente
            if (!$patient_id && isset($postarr["ID"]) && $postarr["ID"] > 0) {
                $patient_id = (int) get_post_meta($postarr["ID"], "pab_paciente_id", true);
                error_log("PAB DEBUG wp_insert_post_data: patient_id do meta: $patient_id");
            }

            // 5. Verificar sessão se ainda não temos patient_id
            if (!$patient_id) {
                if (!session_id()) {
                    session_start();
                }
                if (isset($_SESSION["pab_pending_attachment"])) {
                    $attachment_data = $_SESSION["pab_pending_attachment"];
                    error_log("PAB DEBUG wp_insert_post_data: Dados na sessão: " . print_r($attachment_data, true));
                    if ($attachment_data["post_type"] === $post_type &&
                        (time() - $attachment_data["timestamp"]) < 300) { // 5 minutos
                        $patient_id = (int) $attachment_data["patient_id"];
                        error_log("PAB DEBUG wp_insert_post_data: patient_id da sessão: $patient_id");
                    }
                } else {
                    error_log("PAB DEBUG wp_insert_post_data: Nenhuma sessão encontrada");
                }
            }

            // 6. Última tentativa: verificar se vem de um link pab_attach
            if (!$patient_id) {
                $attach_patient = isset($_GET["pab_attach"]) ? (int) $_GET["pab_attach"] : 0;
                if (!$attach_patient) {
                    $attach_patient = isset($_REQUEST["pab_attach"]) ? (int) $_REQUEST["pab_attach"] : 0;
                }
                error_log("PAB DEBUG wp_insert_post_data: attach_patient: $attach_patient");
                if ($attach_patient && get_post($attach_patient)) {
                    $patient_id = $attach_patient;
                    error_log("PAB DEBUG wp_insert_post_data: patient_id do attach: $patient_id");
                }
            }

            error_log("PAB DEBUG wp_insert_post_data: patient_id final: $patient_id");

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
                    // Também atualizar o slug para posts existentes
                    $data["post_name"] = sanitize_title($data["post_title"]);
                    error_log("PAB DEBUG wp_insert_post_data: Título gerado (existente): " . $data["post_title"]);
                    error_log("PAB DEBUG wp_insert_post_data: Slug gerado (existente): " . $data["post_name"]);
                } else {
                    // Novo post - usar título temporário
                    $data["post_title"] = trim("$name - $item_type - TEMP");
                    $data["post_name"] = sanitize_title($data["post_title"]);
                    error_log("PAB DEBUG wp_insert_post_data: Título gerado (novo): " . $data["post_title"]);
                    error_log("PAB DEBUG wp_insert_post_data: Slug gerado (novo): " . $data["post_name"]);
                }
            } else {
                $item_type = $post_type === "pab_avaliacao"
                    ? __("PAB_AVALIACAO", "pab")
                    : __("PAB_BIOIMPEDANCIA", "pab");
                $data["post_title"] = __("ITEM ORFAO", "pab") . " - " . $item_type;
                $data["post_name"] = sanitize_title($data["post_title"]);
                error_log("PAB DEBUG wp_insert_post_data: Título órfão gerado: " . $data["post_title"]);
                error_log("PAB DEBUG wp_insert_post_data: Slug órfão gerado: " . $data["post_name"]);
            }
        }

        return $data;
    },
    99,
    2,
);

/**
 * Hook para verificar e corrigir slug após cada salvamento
 */
add_action(
    "save_post",
    function ($post_id, $post, $update) {
        // Só processar bioimpedâncias
        if ($post->post_type !== "pab_bioimpedancia") {
            return;
        }

        // Prevenir loops e autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;

        // Verificar se slug está correto baseado no título
        $expected_slug = sanitize_title($post->post_title);

        // Se o slug atual não corresponde ao título, corrigir
        if ($post->post_name !== $expected_slug) {
            // Verificar se slug já existe
            $existing_post = get_page_by_path($expected_slug, OBJECT, 'pab_bioimpedancia');
            if ($existing_post && $existing_post->ID !== $post_id) {
                $expected_slug = $expected_slug . '-' . $post_id;
            }

            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                ["post_name" => $expected_slug],
                ["ID" => $post_id],
                ["%s"],
                ["%d"]
            );

            clean_post_cache($post_id);
            error_log("PAB DEBUG save_post: Slug corrigido de '{$post->post_name}' para '$expected_slug' (post $post_id)");
        }
    },
    30, // Prioridade baixa para executar depois de outros hooks
    3,
);

/**
 * Hook para atualizar o título e status após a inserção/atualização do post
 * quando o ID estiver disponível
 */
add_action(
    "wp_insert_post",
    function ($post_id, $post, $update) {
        // Corrigir status para pacientes e avaliações
        if (in_array($post->post_type, ["pab_paciente", "pab_avaliacao"])) {
            // Corrigir status do post se foi solicitado publicar ou se é um paciente
            if (
                ($post->post_type === "pab_paciente") ||
                (isset($_POST["post_status"]) && $_POST["post_status"] === "publish")
            ) {
                if ($post->post_status !== "publish") {
                    global $wpdb;
                    $wpdb->update(
                        $wpdb->posts,
                        ["post_status" => "publish"],
                        ["ID" => $post_id],
                        ["%s"],
                        ["%d"],
                    );
                    clean_post_cache($post_id);
                }
            }
        }

        // Só executar para avaliação e bioimpedância
        if (!in_array($post->post_type, ["pab_avaliacao", "pab_bioimpedancia"])) {
            return;
        }

        // Só executar se o título contém "NOVO" ou "TEMP" (indicando criação inicial)
        if (strpos($post->post_title, "- NOVO") === false &&
            strpos($post->post_title, "- TEMP") === false) {
            return;
        }

        // Buscar o ID do paciente
        $patient_id = (int) get_post_meta($post_id, "pab_paciente_id", true);
        if (!$patient_id) {
            return;
        }

        $patient_name =
            get_the_title($patient_id) ?: __("Paciente Sem Nome", "pab");
        $item_type = $post->post_type === "pab_avaliacao"
            ? __("AVALIAÇÃO", "pab")
            : __("BIOIMPEDÂNCIA", "pab");

        // Construir o novo título com o ID real (apenas na criação)
        $new_title = trim("$patient_name - $item_type - $post_id");

        // Gerar slug limpo baseado no novo título
        $new_slug = sanitize_title($new_title);

        // Atualizar o título e slug sem disparar hooks (evitar loop)
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            [
                "post_title" => $new_title,
                "post_name" => $new_slug
            ],
            ["ID" => $post_id],
            ["%s", "%s"],
            ["%d"],
        );

        // Limpar cache
        clean_post_cache($post_id);

        // Limpar sessão após salvamento bem-sucedido
        if (!session_id()) {
            session_start();
        }
        if (isset($_SESSION["pab_pending_attachment"])) {
            unset($_SESSION["pab_pending_attachment"]);
        }
    },
    20,
    3,
);

/**
 * Hook save_post com prioridade baixa para correção após salvamento de metas
 */
add_action(
    "save_post_pab_bioimpedancia",
    function ($post_id, $post, $update) {
        // Prevenir loops e autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;

        // Só executar se o título contém "NOVO" ou "TEMP"
        if (strpos($post->post_title, "- NOVO") === false &&
            strpos($post->post_title, "- TEMP") === false) {
            return;
        }

        // Prevenir loops
        static $processing = [];
        if (isset($processing[$post_id])) {
            return;
        }
        $processing[$post_id] = true;

        error_log("PAB DEBUG save_post_pab_bioimpedancia: Corrigindo bioimpedância $post_id");

        // Buscar o ID do paciente (deve estar salvo agora)
        $patient_id = (int) get_post_meta($post_id, "pab_paciente_id", true);

        if ($patient_id) {
            $patient_name = get_the_title($patient_id) ?: "Paciente Sem Nome";
            $new_title = trim("$patient_name - BIOIMPEDÂNCIA - $post_id");
            $new_slug = sanitize_title($new_title);

            // Verificar se slug já existe
            $existing_post = get_page_by_path($new_slug, OBJECT, 'pab_bioimpedancia');
            if ($existing_post && $existing_post->ID !== $post_id) {
                $new_slug = $new_slug . '-' . $post_id;
            }

            // Atualizar título e slug
            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                [
                    "post_title" => $new_title,
                    "post_name" => $new_slug
                ],
                ["ID" => $post_id],
                ["%s", "%s"],
                ["%d"]
            );

            // Limpar cache
            clean_post_cache($post_id);

            // Limpar sessão se existe
            if (!session_id()) {
                session_start();
            }
            if (isset($_SESSION["pab_pending_attachment"])) {
                unset($_SESSION["pab_pending_attachment"]);
            }

            error_log("PAB DEBUG save_post_pab_bioimpedancia: Título corrigido para: $new_title, slug: $new_slug");
        } else {
            error_log("PAB DEBUG save_post_pab_bioimpedancia: Nenhum patient_id encontrado para $post_id");
        }

        unset($processing[$post_id]);
    },
    15,
    3,
);

/**
 * Hook save_post como backup para correção de títulos "NOVO" de bioimpedâncias
 */
add_action(
    "save_post",
    function ($post_id, $post, $update) {
        // Só processar bioimpedâncias
        if ($post->post_type !== "pab_bioimpedancia") {
            return;
        }

        // Prevenir loops e autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;

        // Só executar se o título contém "NOVO"
        if (strpos($post->post_title, "- NOVO") === false) {
            return;
        }

        error_log("PAB DEBUG save_post backup: Tentando corrigir bioimpedância $post_id");

        // Buscar o ID do paciente (agora já deve estar salvo)
        $patient_id = (int) get_post_meta($post_id, "pab_paciente_id", true);
        if (!$patient_id) {
            error_log("PAB DEBUG save_post backup: Nenhum patient_id encontrado para $post_id");
            return;
        }

        $patient_name = get_the_title($patient_id) ?: "Paciente Sem Nome";
        $new_title = trim("$patient_name - BIOIMPEDÂNCIA - $post_id");
        $new_slug = sanitize_title($new_title);

        // Atualizar título e slug
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            [
                "post_title" => $new_title,
                "post_name" => $new_slug
            ],
            ["ID" => $post_id],
            ["%s", "%s"],
            ["%d"]
        );

        // Limpar cache
        clean_post_cache($post_id);

        error_log("PAB DEBUG save_post backup: Título corrigido para: $new_title");
    },
    25,
    3,
);

/**
 * Adicionar hook adicional para corrigir bioimpedâncias órfãs existentes
 */
add_action(
    "save_post_pab_bioimpedancia",
    function ($post_id, $post, $update) {
        // Prevenir loops
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;

        // Só corrigir se o título contém "ITEM ORFAO"
        if (strpos($post->post_title, "ITEM ORFAO") === false) {
            return;
        }

        // Buscar o ID do paciente (pode ter sido definido no salvamento atual)
        $patient_id = (int) get_post_meta($post_id, "pab_paciente_id", true);
        if (!$patient_id) {
            return;
        }

        $patient_name = get_the_title($patient_id) ?: __("Paciente Sem Nome", "pab");
        $new_title = trim("$patient_name - BIOIMPEDÂNCIA - $post_id");
        $new_slug = sanitize_title($new_title);

        // Atualizar título e slug
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            [
                "post_title" => $new_title,
                "post_name" => $new_slug
            ],
            ["ID" => $post_id],
            ["%s", "%s"],
            ["%d"],
        );

        // Limpar cache
        clean_post_cache($post_id);

        // Limpar sessão após correção
        if (!session_id()) {
            session_start();
        }
        if (isset($_SESSION["pab_pending_attachment"])) {
            unset($_SESSION["pab_pending_attachment"]);
        }
    },
    20,
    3,
);

/**
 * Função administrativa para corrigir bioimpedâncias órfãs
 */
add_action("admin_init", function () {
    if (isset($_GET["pab_fix_orphans"]) && current_user_can("manage_options")) {
        $orphan_posts = get_posts([
            "post_type" => ["pab_avaliacao", "pab_bioimpedancia"],
            "post_status" => "any",
            "numberposts" => -1,
            "meta_query" => [
                [
                    "key" => "pab_paciente_id",
                    "compare" => "NOT EXISTS",
                ],
            ],
        ]);

        $fixed_count = 0;
        foreach ($orphan_posts as $post) {
            if (strpos($post->post_title, "ITEM ORFAO") !== false) {
                // Tentar encontrar paciente pelo post_parent
                if ($post->post_parent > 0) {
                    $parent_post = get_post($post->post_parent);
                    if ($parent_post && $parent_post->post_type === "pab_paciente") {
                        pab_link_to_patient($post->ID, $post->post_parent);

                        $patient_name = get_the_title($post->post_parent) ?: "Paciente Sem Nome";
                        $item_type = $post->post_type === "pab_avaliacao" ? "AVALIAÇÃO" : "BIOIMPEDÂNCIA";
                        $new_title = trim("$patient_name - $item_type - {$post->ID}");
                        $new_slug = sanitize_title($new_title);

                        global $wpdb;
                        $wpdb->update(
                            $wpdb->posts,
                            [
                                "post_title" => $new_title,
                                "post_name" => $new_slug
                            ],
                            ["ID" => $post->ID],
                            ["%s", "%s"],
                            ["%d"]
                        );

                        clean_post_cache($post->ID);
                        $fixed_count++;
                    }
                }
            }
        }

        wp_redirect(add_query_arg([
            "post_type" => "pab_paciente",
            "pab_fixed" => $fixed_count
        ], admin_url("edit.php")));
        exit;
    }

    if (isset($_GET["pab_fixed"])) {
        add_action("admin_notices", function () {
            $count = (int) $_GET["pab_fixed"];
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✅ ' . sprintf(_n(
                "%d bioimpedância órfã foi corrigida.",
                "%d bioimpedâncias órfãs foram corrigidas.",
                $count, "pab"
            ), $count) . '</p>';
            echo '</div>';
        });
    }
});

/**
 * Adicionar botão para corrigir órfãs na listagem de pacientes
 */
add_action("manage_posts_extra_tablenav", function ($which) {
    if ($which === "top" && get_current_screen()->post_type === "pab_paciente") {
        $orphan_count = count(get_posts([
            "post_type" => ["pab_avaliacao", "pab_bioimpedancia"],
            "post_status" => "any",
            "numberposts" => -1,
            "meta_query" => [
                [
                    "key" => "pab_paciente_id",
                    "compare" => "NOT EXISTS",
                ],
            ],
        ]));

        if ($orphan_count > 0) {
            $fix_url = add_query_arg([
                "post_type" => "pab_paciente",
                "pab_fix_orphans" => "1"
            ], admin_url("edit.php"));

            echo '<div class="alignleft actions">';
            echo '<a href="' . esc_url($fix_url) . '" class="button button-secondary" onclick="return confirm(\'Tem certeza que deseja corrigir ' . $orphan_count . ' itens órfãos?\');">';
            echo '🔧 Corrigir ' . $orphan_count . ' órfãos';
            echo '</a>';
            echo '</div>';
        }

        // Verificar bioimpedâncias com "NOVO" ou "TEMP"
        $posts_temp = get_posts([
            "post_type" => "pab_bioimpedancia",
            "post_status" => "any",
            "numberposts" => -1
        ]);

        $novo_count = 0;
        foreach ($posts_temp as $temp_post) {
            if (strpos($temp_post->post_title, "- NOVO") !== false ||
                strpos($temp_post->post_title, "- TEMP") !== false) {
                $novo_count++;
            }
        }

        if ($novo_count > 0) {
            $fix_novo_url = add_query_arg([
                "post_type" => "pab_paciente",
                "pab_fix_novo" => "1"
            ], admin_url("edit.php"));

            echo '<div class="alignleft actions">';
            echo '<a href="' . esc_url($fix_novo_url) . '" class="button button-secondary" onclick="return confirm(\'Tem certeza que deseja corrigir ' . $novo_count . ' bioimpedâncias temporárias?\');" style="margin-left: 5px;">';
            echo '🔧 Corrigir ' . $novo_count . ' temporárias';
            echo '</a>';
            echo '</div>';
        }

        // Verificar slugs problemáticos
        $problematic_count = count(pab_check_problematic_slugs());
        if ($problematic_count > 0) {
            $fix_slugs_url = add_query_arg([
                "post_type" => "pab_paciente",
                "pab_fix_slugs" => "1"
            ], admin_url("edit.php"));

            echo '<div class="alignleft actions">';
            echo '<a href="' . esc_url($fix_slugs_url) . '" class="button button-secondary" onclick="return confirm(\'Tem certeza que deseja corrigir ' . $problematic_count . ' slugs problemáticos?\');" style="margin-left: 5px;">';
            echo '🔗 Corrigir ' . $problematic_count . ' slugs';
            echo '</a>';
            echo '</div>';
        }
    }
});

/**
 * Função para regenerar permalink de uma bioimpedância específica
 */
function pab_regenerate_bioimpedancia_permalink($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'pab_bioimpedancia') {
        return false;
    }

    $patient_id = (int) get_post_meta($post_id, 'pab_paciente_id', true);
    if (!$patient_id) {
        return false;
    }

    $patient_name = get_the_title($patient_id) ?: 'Paciente Sem Nome';
    $new_title = trim("$patient_name - BIOIMPEDÂNCIA - $post_id");
    $new_slug = sanitize_title($new_title);

    // Verificar se o slug já existe
    $existing_post = get_page_by_path($new_slug, OBJECT, 'pab_bioimpedancia');
    if ($existing_post && $existing_post->ID !== $post_id) {
        $new_slug = $new_slug . '-' . $post_id;
    }

    global $wpdb;
    $result = $wpdb->update(
        $wpdb->posts,
        [
            'post_title' => $new_title,
            'post_name' => $new_slug
        ],
        ['ID' => $post_id],
        ['%s', '%s'],
        ['%d']
    );

    if ($result !== false) {
        clean_post_cache($post_id);
        return [
            'old_title' => $post->post_title,
            'new_title' => $new_title,
            'old_slug' => $post->post_name,
            'new_slug' => $new_slug
        ];
    }

    return false;
}

/**
 * Função administrativa para corrigir slugs problemáticos
 */
add_action("admin_init", function () {
    if (isset($_GET["pab_fix_slugs"]) && current_user_can("manage_options")) {
        $problematic = pab_check_problematic_slugs();
        $fixed_count = 0;

        foreach ($problematic as $item) {
            $post_id = $item['ID'];
            $expected_slug = $item['expected_slug'];

            // Verificar se slug já existe
            $existing_post = get_page_by_path($expected_slug, OBJECT, 'pab_bioimpedancia');
            if ($existing_post && $existing_post->ID !== $post_id) {
                $expected_slug = $expected_slug . '-' . $post_id;
            }

            global $wpdb;
            $result = $wpdb->update(
                $wpdb->posts,
                ["post_name" => $expected_slug],
                ["ID" => $post_id],
                ["%s"],
                ["%d"]
            );

            if ($result !== false) {
                clean_post_cache($post_id);
                $fixed_count++;
            }
        }

        wp_redirect(add_query_arg([
            "post_type" => "pab_paciente",
            "pab_slugs_fixed" => $fixed_count
        ], admin_url("edit.php")));
        exit;
    }

    if (isset($_GET["pab_fix_novo"]) && current_user_can("manage_options")) {
        $posts_with_novo = get_posts([
            "post_type" => "pab_bioimpedancia",
            "post_status" => "any",
            "numberposts" => -1
        ]);

        $fixed_count = 0;
        foreach ($posts_with_novo as $post) {
            if (strpos($post->post_title, "- NOVO") !== false ||
                strpos($post->post_title, "- TEMP") !== false) {
                $patient_id = (int) get_post_meta($post->ID, "pab_paciente_id", true);
                if ($patient_id) {
                    $patient_name = get_the_title($patient_id) ?: "Paciente Sem Nome";
                    $new_title = trim("$patient_name - BIOIMPEDÂNCIA - {$post->ID}");
                    $new_slug = sanitize_title($new_title);

                    global $wpdb;
                    $wpdb->update(
                        $wpdb->posts,
                        [
                            "post_title" => $new_title,
                            "post_name" => $new_slug
                        ],
                        ["ID" => $post->ID],
                        ["%s", "%s"],
                        ["%d"]
                    );

                    clean_post_cache($post->ID);
                    $fixed_count++;
                }
            }
        }

        wp_redirect(add_query_arg([
            "post_type" => "pab_paciente",
            "pab_novo_fixed" => $fixed_count
        ], admin_url("edit.php")));
        exit;
    }

    if (isset($_GET["pab_novo_fixed"])) {
        add_action("admin_notices", function () {
            $count = (int) $_GET["pab_novo_fixed"];
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✅ ' . sprintf(_n(
                "%d bioimpedância temporária foi corrigida.",
                "%d bioimpedâncias temporárias foram corrigidas.",
                $count, "pab"
            ), $count) . '</p>';
            echo '</div>';
        });
    }

    if (isset($_GET["pab_slugs_fixed"])) {
        add_action("admin_notices", function () {
            $count = (int) $_GET["pab_slugs_fixed"];
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✅ ' . sprintf(_n(
                "%d slug problemático foi corrigido.",
                "%d slugs problemáticos foram corrigidos.",
                $count, "pab"
            ), $count) . '</p>';
            echo '</div>';
        });
    }
});

/**
 * Função de teste para verificar o fluxo de criação de bioimpedâncias
 */
function pab_test_bioimpedancia_flow() {
    if (!current_user_can('manage_options')) {
        return 'Sem permissão';
    }

    $results = [];

    // 1. Verificar se sessão está funcionando
    if (!session_id()) {
        session_start();
    }
    $_SESSION['pab_test'] = 'funcionando';
    $results['sessao'] = isset($_SESSION['pab_test']) ? 'OK' : 'ERRO';

    // 2. Simular dados de attachment
    $_SESSION['pab_pending_attachment'] = [
        'patient_id' => 1,
        'post_type' => 'pab_bioimpedancia',
        'timestamp' => time()
    ];
    $results['attachment_simulado'] = 'OK';

    // 3. Verificar se hook wp_insert_post_data está registrado
    $results['hook_registrado'] = has_filter('wp_insert_post_data') ? 'OK' : 'ERRO';

    // 4. Listar bioimpedâncias com problemas
    $posts_problem = get_posts([
        'post_type' => 'pab_bioimpedancia',
        'post_status' => 'any',
        'numberposts' => 5
    ]);

    $problems = [];
    foreach ($posts_problem as $post) {
        if (strpos($post->post_title, '- NOVO') !== false ||
            strpos($post->post_title, '- TEMP') !== false ||
            strpos($post->post_title, 'ITEM ORFAO') !== false) {
            $problems[] = $post->ID . ': ' . $post->post_title;
        }
    }
    $results['posts_com_problema'] = $problems;

    return $results;
}

/**
 * Adicionar ação AJAX para teste
 */
add_action('wp_ajax_pab_test_flow', function() {
    $results = pab_test_bioimpedancia_flow();
    wp_send_json_success($results);
});

/**
 * Função para verificar bioimpedâncias com slugs problemáticos
 */
function pab_check_problematic_slugs() {
    if (!current_user_can('manage_options')) {
        return [];
    }

    $bioimpedancias = get_posts([
        'post_type' => 'pab_bioimpedancia',
        'post_status' => 'any',
        'numberposts' => -1
    ]);

    $problematic = [];
    foreach ($bioimpedancias as $bio) {
        $has_problem = false;
        $issues = [];

        // Verificar se slug contém problemas conhecidos
        if (strpos($bio->post_name, 'temp') !== false) {
            $has_problem = true;
            $issues[] = 'slug contém TEMP';
        }
        if (strpos($bio->post_name, 'novo') !== false) {
            $has_problem = true;
            $issues[] = 'slug contém NOVO';
        }
        if (strpos($bio->post_name, 'item-orfao') !== false) {
            $has_problem = true;
            $issues[] = 'slug órfão';
        }

        // Verificar se título contém problemas
        if (strpos($bio->post_title, '- TEMP') !== false) {
            $has_problem = true;
            $issues[] = 'título contém TEMP';
        }
        if (strpos($bio->post_title, '- NOVO') !== false) {
            $has_problem = true;
            $issues[] = 'título contém NOVO';
        }
        if (strpos($bio->post_title, 'ITEM ORFAO') !== false) {
            $has_problem = true;
            $issues[] = 'título órfão';
        }

        // Verificar se slug não corresponde ao título
        $expected_slug = sanitize_title($bio->post_title);
        if ($bio->post_name !== $expected_slug) {
            $has_problem = true;
            $issues[] = 'slug não corresponde ao título';
        }

        if ($has_problem) {
            $problematic[] = [
                'ID' => $bio->ID,
                'title' => $bio->post_title,
                'slug' => $bio->post_name,
                'expected_slug' => $expected_slug,
                'issues' => $issues,
                'patient_id' => get_post_meta($bio->ID, 'pab_paciente_id', true)
            ];
        }
    }

    return $problematic;
}

/**
 * Adicionar ação AJAX para verificar slugs problemáticos
 */
add_action('wp_ajax_pab_check_slugs', function() {
    $problematic = pab_check_problematic_slugs();
    wp_send_json_success([
        'count' => count($problematic),
        'items' => $problematic
    ]);
});

/**
 * Função para gerar avatar cropped focado na cabeça para OpenGraph
 */
function pab_generate_avatar_head_crop($gender, $level) {
    // Verificar se GD está disponível
    if (!extension_loaded('gd')) {
        return false;
    }

    $prefix = $gender === "F" ? "f" : "m";
    $avatar_path = PAB_PATH . "assets/img/avatars/{$prefix}-{$level}.png";

    // Verificar se arquivo existe
    if (!file_exists($avatar_path)) {
        return false;
    }

    // Criar nome único para cache
    $cache_filename = "avatar-head-{$prefix}-{$level}.png";
    $cache_path = PAB_PATH . "assets/img/avatars/cache/";

    // Criar diretório cache se não existir
    if (!file_exists($cache_path)) {
        wp_mkdir_p($cache_path);
    }

    $cache_file = $cache_path . $cache_filename;

    // Se já existe em cache, retornar URL
    if (file_exists($cache_file)) {
        return PAB_URL . "assets/img/avatars/cache/" . $cache_filename;
    }

    // Carregar imagem original
    $original = imagecreatefrompng($avatar_path);
    if (!$original) {
        return false;
    }

    $original_width = imagesx($original);
    $original_height = imagesy($original);

    // Definir área da cabeça (aproximadamente 40% superior da imagem)
    $crop_height = (int)($original_height * 0.4);
    $crop_width = $original_width;

    // Criar nova imagem focada na cabeça
    $head_crop = imagecreatetruecolor($crop_width, $crop_height);

    // Preservar transparência
    imagealphablending($head_crop, false);
    imagesavealpha($head_crop, true);
    $transparent = imagecolorallocatealpha($head_crop, 255, 255, 255, 127);
    imagefill($head_crop, 0, 0, $transparent);

    // Copiar parte superior da imagem
    imagecopy($head_crop, $original, 0, 0, 0, 0, $crop_width, $crop_height);

    // Redimensionar para tamanho OpenGraph ideal (400x400)
    $final = imagecreatetruecolor(400, 400);
    imagealphablending($final, false);
    imagesavealpha($final, true);
    $transparent_final = imagecolorallocatealpha($final, 255, 255, 255, 127);
    imagefill($final, 0, 0, $transparent_final);

    // Redimensionar mantendo proporção
    imagecopyresampled($final, $head_crop, 0, 0, 0, 0, 400, 400, $crop_width, $crop_height);

    // Salvar arquivo em cache
    imagepng($final, $cache_file);

    // Limpar memória
    imagedestroy($original);
    imagedestroy($head_crop);
    imagedestroy($final);

    return PAB_URL . "assets/img/avatars/cache/" . $cache_filename;
}

/**
 * Endpoint para servir avatares cropped diretamente
 */
add_action('template_redirect', function() {
    if (isset($_GET['pab_avatar_head']) && isset($_GET['gender']) && isset($_GET['level'])) {
        $gender = sanitize_text_field($_GET['gender']);
        $level = sanitize_text_field($_GET['level']);

        if (!in_array($gender, ['F', 'M']) || !in_array($level, ['abaixo', 'normal', 'acima1', 'acima2', 'acima3', 'alto1', 'alto2', 'alto3'])) {
            status_header(400);
            die('Parâmetros inválidos');
        }

        $prefix = $gender === "F" ? "f" : "m";
        $avatar_path = PAB_PATH . "assets/img/avatars/{$prefix}-{$level}.png";

        if (!file_exists($avatar_path) || !extension_loaded('gd')) {
            status_header(404);
            die('Imagem não encontrada');
        }

        // Verificar cache primeiro
        $cache_filename = "avatar-head-{$prefix}-{$level}.png";
        $cache_path = PAB_PATH . "assets/img/avatars/cache/";
        $cache_file = $cache_path . $cache_filename;

        if (!file_exists($cache_file)) {
            // Gerar imagem cropped
            if (!file_exists($cache_path)) {
                wp_mkdir_p($cache_path);
            }

            $original = imagecreatefrompng($avatar_path);
            if (!$original) {
                status_header(500);
                die('Erro ao processar imagem');
            }

            $original_width = imagesx($original);
            $original_height = imagesy($original);
            $crop_height = (int)($original_height * 0.4);

            // Criar crop da cabeça
            $head_crop = imagecreatetruecolor($original_width, $crop_height);
            imagealphablending($head_crop, false);
            imagesavealpha($head_crop, true);
            $transparent = imagecolorallocatealpha($head_crop, 255, 255, 255, 127);
            imagefill($head_crop, 0, 0, $transparent);
            imagecopy($head_crop, $original, 0, 0, 0, 0, $original_width, $crop_height);

            // Redimensionar para 400x400
            $final = imagecreatetruecolor(400, 400);
            imagealphablending($final, false);
            imagesavealpha($final, true);
            $transparent_final = imagecolorallocatealpha($final, 255, 255, 255, 127);
            imagefill($final, 0, 0, $transparent_final);
            imagecopyresampled($final, $head_crop, 0, 0, 0, 0, 400, 400, $original_width, $crop_height);

            // Salvar cache
            imagepng($final, $cache_file);
            imagedestroy($original);
            imagedestroy($head_crop);
            imagedestroy($final);
        }

        // Servir imagem
        if (file_exists($cache_file)) {
            header('Content-Type: image/png');
            header('Content-Length: ' . filesize($cache_file));
            header('Cache-Control: public, max-age=86400'); // Cache por 1 dia
            readfile($cache_file);
        } else {
            status_header(500);
            die('Erro ao gerar imagem');
        }

        exit;
    }
});

/**
 * Função de teste para verificar geração de avatares cropped
 */
function pab_test_avatar_generation() {
    if (!current_user_can('manage_options')) {
        return ['error' => 'Sem permissão'];
    }

    $results = [];
    $genders = ['F', 'M'];
    $levels = ['abaixo', 'normal', 'acima1', 'acima2', 'acima3', 'alto1', 'alto2', 'alto3'];

    // Verificar extensão GD
    $results['gd_available'] = extension_loaded('gd') ? 'OK' : 'ERRO';

    // Testar algumas combinações
    $test_combinations = [
        ['F', 'normal'],
        ['M', 'normal'],
        ['F', 'acima1'],
        ['M', 'acima2']
    ];

    foreach ($test_combinations as $combo) {
        list($gender, $level) = $combo;
        $prefix = $gender === "F" ? "f" : "m";

        // Verificar se arquivo original existe
        $original_path = PAB_PATH . "assets/img/avatars/{$prefix}-{$level}.png";
        $original_exists = file_exists($original_path);

        // Tentar gerar versão cropped
        $cropped_url = pab_generate_avatar_head_crop($gender, $level);

        // URL do endpoint dinâmico
        $endpoint_url = add_query_arg([
            'pab_avatar_head' => '1',
            'gender' => $gender,
            'level' => $level
        ], home_url());

        $results['tests'][] = [
            'combination' => "{$gender}-{$level}",
            'original_exists' => $original_exists ? 'OK' : 'ERRO',
            'cropped_generated' => $cropped_url ? 'OK' : 'ERRO',
            'cropped_url' => $cropped_url,
            'endpoint_url' => $endpoint_url
        ];
    }

    return $results;
}

/**
 * Adicionar ação AJAX para teste de avatares
 */
add_action('wp_ajax_pab_test_avatars', function() {
    $results = pab_test_avatar_generation();
    wp_send_json_success($results);
});

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
