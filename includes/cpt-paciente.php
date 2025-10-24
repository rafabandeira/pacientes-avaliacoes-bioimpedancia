<?php
/**
 * CPT Paciente - registro e hooks simplificados para trash/restore/delete
 *
 * Objetivo: remover hooks conflitantes e simplificar o fluxo de:
 * - mover para lixeira (wp_trash_post)
 * - restaurar da lixeira (untrashed_post)
 * - exclusão permanente (before_delete_post)
 *
 * Não faz redirecionamentos nem exit() — deixa o WordPress controlar o fluxo.
 */

if (!defined("ABSPATH")) {
    exit();
}

// ... (Restante do registro CPT aqui, sem alterações) ...
// ... (Linhas 15 a 81 do arquivo original) ...

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
 * ADMIN HANDLER - SOMENTE DIAGNÓSTICO:
 * Detecta requisições de 'trash' para 'pab_paciente' e registra callbacks e status
 * para investigar se há hooks conflitantes ou problemas de permissão.
 *
 * **ATENÇÃO: Removido o fallback de wp_trash_post/wp_update_post que causava conflito
 * com o fluxo nativo do WordPress e a tela branca.**
 */
add_action("admin_init", function () {
    if (!is_admin()) {
        return;
    }

    $action = isset($_REQUEST["action"])
        ? sanitize_text_field((string) $_REQUEST["action"])
        : "";
    $post_id = isset($_REQUEST["post"]) ? intval($_REQUEST["post"]) : 0;

    if ($action !== "trash" || $post_id <= 0) {
        return;
    }

    $post = get_post($post_id);
    if (!$post) {
        error_log(
            "PAB DEBUG admin_init: trash action detected but post not found: {$post_id}",
        );
        return;
    }

    if ($post->post_type !== "pab_paciente") {
        // não interferimos com outros post types
        return;
    }

    // Logs básicos para diagnóstico
    error_log(
        "PAB DEBUG admin_init: Detected trash action for patient {$post_id}; status before=" .
            get_post_status($post_id),
    );
    error_log(
        "PAB DEBUG admin_init: current_user_can(delete_post) = " .
            (current_user_can("delete_post", $post_id) ? "yes" : "no"),
    );
    error_log(
        "PAB DEBUG admin_init: REQUEST_URI = " .
            (isset($_SERVER["REQUEST_URI"])
                ? $_SERVER["REQUEST_URI"]
                : "(n/a)"),
    );
    error_log("PAB DEBUG admin_init: REQUEST action param = {$action}");

    // Adicional: log dos callbacks registrados em hooks críticos para diagnóstico
    if (isset($GLOBALS["wp_filter"]) && is_array($GLOBALS["wp_filter"])) {
        $critical_hooks = [
            "wp_trash_post",
            "transition_post_status",
            "wp_insert_post_data",
            "before_delete_post",
            "pre_delete_post",
            "pre_post_update",
        ];

        foreach ($critical_hooks as $hook) {
            if (!isset($GLOBALS["wp_filter"][$hook])) {
                error_log("PAB DEBUG admin_init: hook {$hook} not registered");
                continue;
            }

            $hook_obj = $GLOBALS["wp_filter"][$hook];
            $callbacks = [];

            // WP_Hook instance in modern WP stores callbacks in ->callbacks
            if (
                is_object($hook_obj) &&
                property_exists($hook_obj, "callbacks")
            ) {
                $cbsets = $hook_obj->callbacks;
            } elseif (is_array($hook_obj)) {
                $cbsets = $hook_obj;
            } else {
                $cbsets = [];
            }

            foreach ((array) $cbsets as $priority => $prio_cb) {
                foreach ((array) $prio_cb as $id => $cb) {
                    $fn_desc = "(unknown)";
                    if (is_array($cb["function"])) {
                        $obj_or_class = $cb["function"][0];
                        $method = isset($cb["function"][1])
                            ? $cb["function"][1]
                            : "(method)";
                        if (is_object($obj_or_class)) {
                            $fn_desc =
                                get_class($obj_or_class) . "::" . $method;
                        } else {
                            $fn_desc = $obj_or_class . "::" . $method;
                        }
                    } elseif (is_string($cb["function"])) {
                        $fn_desc = $cb["function"];
                    } elseif ($cb["function"] instanceof Closure) {
                        $fn_desc = "closure";
                    } elseif (is_object($cb["function"])) {
                        $fn_desc = get_class($cb["function"]);
                    } else {
                        $fn_desc = "callable";
                    }

                    $callbacks[] = "prio={$priority};fn={$fn_desc}";
                }
            }

            error_log(
                "PAB DEBUG admin_init: hook {$hook} callbacks: " .
                    (empty($callbacks)
                        ? "(none listed)"
                        : implode(" | ", $callbacks)),
            );
        }
    } else {
        error_log(
            "PAB DEBUG admin_init: global wp_filter not available or not an array/object",
        );
    }

    // --- TRECHO DE FALLBACK REMOVIDO PARA EVITAR CONFLITO COM FLUXO NATIVO ---
    /*
    // Se o post ainda não estiver em 'trash', tentamos executar wp_trash_post
    if (get_post_status($post_id) !== "trash") {
        error_log(
            "PAB DEBUG admin_init: Post not in trash yet - attempting wp_trash_post({$post_id})",
        );
        $res = wp_trash_post($post_id);
        error_log(
            "PAB DEBUG admin_init: wp_trash_post result = " .
                ($res ? "ok" : "fail") .
                "; status now = " .
                get_post_status($post_id),
        );

        // Se falhar, tentamos atualizar diretamente o status como fallback
        if (get_post_status($post_id) !== "trash") {
            error_log(
                "PAB DEBUG admin_init: wp_trash_post failed; attempting wp_update_post to set post_status=trash for {$post_id}",
            );
            wp_update_post(["ID" => $post_id, "post_status" => "trash"]);
            error_log(
                "PAB DEBUG admin_init: wp_update_post status now = " .
                    get_post_status($post_id),
            );
        }
    } else {
        error_log(
            "PAB DEBUG admin_init: Post {$post_id} already in trash (status=trash)",
        );
    }
    */
    // --------------------------------------------------------------------------
});

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
            "post_parent" => (int) $patient_id,
            "posts_per_page" => -1,
            "post_status" => $post_status,
            "fields" => "ids",
        ]);

        return is_array($children) ? $children : [];
    }
}

/**
 * Quando um paciente é movido para a lixeira, mover também os filhos para lixeira.
 *
 * Usamos 'wp_trash_post' para cada filho; não forçamos redirecionamento nem exit().
 */
add_action(
    "wp_trash_post",
    function ($post_id) {
        // Diagnostic logs: registrar que o hook foi chamado e contexto
        error_log(
            "PAB DEBUG: wp_trash_post chamado para post_id: " .
                intval($post_id),
        );

        $post = get_post($post_id);
        if (!$post || $post->post_type !== "pab_paciente") {
            error_log(
                "PAB DEBUG: wp_trash_post abortando - post inexistente ou post_type diferente (post_id: " .
                    intval($post_id) .
                    ")",
            );
            return;
        }

        // Log do status atual do post antes de qualquer ação
        error_log(
            "PAB DEBUG: Status do post antes do trash (ID {$post_id}): " .
                $post->post_status,
        );

        // Log do usuário atual e capacidades relevantes
        $current_user = wp_get_current_user();
        $user_id = isset($current_user->ID) ? intval($current_user->ID) : 0;
        $caps =
            is_object($current_user) && isset($current_user->allcaps)
                ? array_keys(array_filter((array) $current_user->allcaps))
                : [];
        error_log(
            "PAB DEBUG: Usuário atual ID: {$user_id}; caps: " .
                implode(",", $caps),
        );
        error_log(
            "PAB DEBUG: current_user_can(delete_post, {$post_id}): " .
                (current_user_can("delete_post", $post_id) ? "yes" : "no"),
        );
        error_log(
            "PAB DEBUG: current_user_can(delete_posts): " .
                (current_user_can("delete_posts") ? "yes" : "no"),
        );

        // Log parâmetros da requisição que aciona a ação (nonce / action)
        $req_action = isset($_REQUEST["action"])
            ? sanitize_text_field((string) $_REQUEST["action"])
            : "(none)";
        $req_nonce = isset($_REQUEST["_wpnonce"])
            ? sanitize_text_field((string) $_REQUEST["_wpnonce"])
            : "(none)";
        error_log(
            "PAB DEBUG: REQUEST action={$req_action}, _wpnonce={$req_nonce}",
        );

        // Pega filhos (qualquer status) e envia para lixeira se ainda não estiverem
        $child_ids = pab_get_child_post_ids($post_id, "any");
        error_log(
            "PAB DEBUG: Filhos encontrados para mover para trash: " .
                (is_array($child_ids) ? implode(",", $child_ids) : "(nenhum)"),
        );

        foreach ($child_ids as $child_id) {
            // evita re-trash desnecessário
            $child_status_before = get_post_status($child_id);
            if ($child_status_before !== "trash") {
                $res = wp_trash_post($child_id);
                $child_status_after = get_post_status($child_id);
                error_log(
                    "PAB DEBUG: wp_trash_post filho ID {$child_id} resultado: " .
                        ($res ? "ok" : "fail") .
                        "; status antes={$child_status_before}; status depois={$child_status_after}",
                );
            } else {
                error_log(
                    "PAB DEBUG: filho ID {$child_id} já estava em trash; status={$child_status_before}",
                );
            }
        }

        // Log final do status do post principal após o WP processar (não alteramos o fluxo)
        $post_after = get_post($post_id);
        $status_after = $post_after
            ? $post_after->post_status
            : "(não encontrado)";
        error_log(
            "PAB DEBUG: Status do post principal após processamento inicial (ID {$post_id}): " .
                $status_after,
        );

        // Não fazemos redirect/exit; WP continuará o fluxo padrão.
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
            // wp_untrash_post verifica permissões e faz hooks necessários
            if (function_exists("wp_untrash_post")) {
                wp_untrash_post($child_id);
            } else {
                // Fallback: alterar status diretamente (menos recomendado)
                wp_update_post(["ID" => $child_id, "post_status" => "publish"]);
            }
        }
    },
    10,
);

/**
 * Antes da exclusão permanente de um paciente (quando for deletado do banco),
 * remove os filhos permanentemente para evitar órfãos.
 *
 * Usamos 'before_delete_post' que é executado antes de o WP remover as linhas do DB.
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

        // Deixe o WP prosseguir com a exclusão do próprio paciente.
    },
    10,
);

/**
 * Não removemos metadados aqui manualmente — deixamos o WP tratar por padrão.
 * Se houver necessidade específica de limpar meta keys órfãos, podemos adicionar
 * uma limpeza adicional, mas apenas se for realmente necessária.
 */

/**
 * Diagnostic: log de transições de status e de wp_insert_post_data para investigar
 * por que o post não está sendo movido para 'trash'.
 *
 * Esses hooks são apenas de diagnóstico e podem ser removidos depois que o problema
 * for identificado.
 */

/* Log de transition_post_status para pacientes */
add_action(
    "transition_post_status",
    function ($new_status, $old_status, $post) {
        // só registrar para o CPT paciente
        if (!isset($post->post_type) || $post->post_type !== "pab_paciente") {
            return;
        }

        $id = isset($post->ID) ? intval($post->ID) : "(unknown)";
        error_log(
            "PAB DEBUG transition_post_status: post_id={$id} {$old_status} -> {$new_status}",
        );

        // registrar uma stack trace curta para ver quem está provocando a transição
        if (function_exists("debug_backtrace")) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
            $frames = [];
            foreach ($trace as $frame) {
                $fn = isset($frame["function"]) ? $frame["function"] : "(main)";
                $file = isset($frame["file"])
                    ? basename($frame["file"])
                    : "(internal)";
                $line = isset($frame["line"]) ? $frame["line"] : "";
                $frames[] = "{$fn}@{$file}:{$line}";
            }
            error_log(
                "PAB DEBUG transition_post_status trace: " .
                    implode(" | ", $frames),
            );
        }
    },
    10,
    3,
);

/* Log em wp_insert_post_data para inspecionar alterações antes do salvamento */
add_filter(
    "wp_insert_post_data",
    function ($data, $postarr) {
        $post_type = isset($data["post_type"])
            ? $data["post_type"]
            : (isset($postarr["post_type"])
                ? $postarr["post_type"]
                : "");
        if ($post_type !== "pab_paciente") {
            return $data;
        }

        $id = isset($postarr["ID"]) ? intval($postarr["ID"]) : "(new)";
        $incoming_status = isset($data["post_status"])
            ? $data["post_status"]
            : "(none)";
        error_log(
            "PAB DEBUG wp_insert_post_data: post_id={$id}, incoming_status={$incoming_status}, post_type={$post_type}",
        );

        // registrar quem chamou (pequena stack trace)
        if (function_exists("debug_backtrace")) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
            $frames = [];
            foreach ($trace as $frame) {
                $fn = isset($frame["function"]) ? $frame["function"] : "(main)";
                $file = isset($frame["file"])
                    ? basename($frame["file"])
                    : "(internal)";
                $line = isset($frame["line"]) ? $frame["line"] : "";
                $frames[] = "{$fn}@{$file}:{$line}";
            }
            error_log(
                "PAB DEBUG wp_insert_post_data trace: " .
                    implode(" | ", $frames),
            );
        }

        return $data;
    },
    10,
    2,
);
