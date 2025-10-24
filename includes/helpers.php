<?php // includes/helpers.php - CORRIGIDO: Lógica de salvamento e hooks conflitantes removidos

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
 * Associar post filho (avaliação/bioimpedância) ao paciente.
 * Armazena o ID do paciente em meta 'pab_paciente_id' e define post_parent.
 *
 * @param int $child_id ID do post filho
 * @param int $patient_id ID do post pai (paciente)
 */
function pab_link_to_patient($child_id, $patient_id)
{
    // Salva o meta para referência
    update_post_meta($child_id, "pab_paciente_id", (int) $patient_id);

    // Atualiza o post_parent diretamente no banco para evitar loops de save_post
    // Esta é a forma mais segura de definir o parentesco.
    global $wpdb;
    $wpdb->update(
        $wpdb->posts,
        ["post_parent" => (int) $patient_id], // O que queremos atualizar
        ["ID" => (int) $child_id], // Onde
        ["%d"], // Formato do valor
        ["%d"], // Formato do WHERE
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
                "pab_medidas", // Adicionado medidas
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
 * Filtra os dados de post antes de serem inseridos/atualizados
 *
 * CORRIGIDO: Mantém *apenas* a lógica do 'pab_paciente' (para o título).
 * A lógica dos CPTs filhos (avaliação, bioimpedância) foi REMOVIDA
 * pois causava conflitos com a lixeira e era frágil.
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
            $nome_paciente_meta = "";

            // Tenta obter o nome do POST
            if (isset($_POST["pab_nome"])) {
                $nome_paciente_meta = sanitize_text_field($_POST["pab_nome"]);
            }
            // Se não houver no POST, tenta obter do meta existente
            elseif (isset($postarr["ID"])) {
                $nome_paciente_meta = get_post_meta(
                    $postarr["ID"],
                    "pab_nome",
                    true,
                );
            }

            if ($nome_paciente_meta) {
                $data["post_title"] = $nome_paciente_meta;
                $data["post_name"] = sanitize_title($nome_paciente_meta); // Garante slug
            } else {
                // Evita criar títulos duplicados se o nome estiver vazio
                if ($data["post_status"] !== "auto-draft") {
                    $data["post_title"] =
                        __("Paciente sem nome", "pab") .
                        " (" .
                        ($postarr["ID"] > 0 ? $postarr["ID"] : "Novo") .
                        ")";
                }
            }
        }

        // --- Lógica de CPTs filhos (avaliação, bio, medidas) ---
        // REMOVIDA INTENCIONALMENTE.
        // Esta lógica será tratada no hook 'save_post' de cada CPT
        // para garantir o ID do paciente e evitar conflitos com a lixeira.

        return $data;
    },
    5, // Prioridade alta para rodar antes de outros
    2,
);

// =========================================================================
// Funções de Avatar (Mantidas)
// =========================================================================

/**
 * Função para gerar avatar cropped focado na cabeça para OpenGraph
 */
function pab_generate_avatar_head_crop($gender, $level)
{
    // Verificar se GD está disponível
    if (!extension_loaded("gd")) {
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
    $crop_height = (int) ($original_height * 0.4);
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
    imagecopyresampled(
        $final,
        $head_crop,
        0,
        0,
        0,
        0,
        400,
        400,
        $crop_width,
        $crop_height,
    );

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
add_action("template_redirect", function () {
    if (
        isset($_GET["pab_avatar_head"]) &&
        isset($_GET["gender"]) &&
        isset($_GET["level"])
    ) {
        $gender = sanitize_text_field($_GET["gender"]);
        $level = sanitize_text_field($_GET["level"]);

        if (
            !in_array($gender, ["F", "M"]) ||
            !in_array($level, [
                "abaixo",
                "normal",
                "acima1",
                "acima2",
                "acima3",
                "alto1",
                "alto2",
                "alto3",
            ])
        ) {
            status_header(400);
            die("Parâmetros inválidos");
        }

        $prefix = $gender === "F" ? "f" : "m";
        $avatar_path = PAB_PATH . "assets/img/avatars/{$prefix}-{$level}.png";

        if (!file_exists($avatar_path) || !extension_loaded("gd")) {
            status_header(404);
            die("Imagem não encontrada");
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
                die("Erro ao processar imagem");
            }

            $original_width = imagesx($original);
            $original_height = imagesy($original);
            $crop_height = (int) ($original_height * 0.4);

            // Criar crop da cabeça
            $head_crop = imagecreatetruecolor($original_width, $crop_height);
            imagealphablending($head_crop, false);
            imagesavealpha($head_crop, true);
            $transparent = imagecolorallocatealpha(
                $head_crop,
                255,
                255,
                255,
                127,
            );
            imagefill($head_crop, 0, 0, $transparent);
            imagecopy(
                $head_crop,
                $original,
                0,
                0,
                0,
                0,
                $original_width,
                $crop_height,
            );

            // Redimensionar para 400x400
            $final = imagecreatetruecolor(400, 400);
            imagealphablending($final, false);
            imagesavealpha($final, true);
            $transparent_final = imagecolorallocatealpha(
                $final,
                255,
                255,
                255,
                127,
            );
            imagefill($final, 0, 0, $transparent_final);
            imagecopyresampled(
                $final,
                $head_crop,
                0,
                0,
                0,
                0,
                400,
                400,
                $original_width,
                $crop_height,
            );

            // Salvar cache
            imagepng($final, $cache_file);
            imagedestroy($original);
            imagedestroy($head_crop);
            imagedestroy($final);
        }

        // Servir imagem
        if (file_exists($cache_file)) {
            header("Content-Type: image/png");
            header("Content-Length: " . filesize($cache_file));
            header("Cache-Control: public, max-age=86400"); // Cache por 1 dia
            readfile($cache_file);
        } else {
            status_header(500);
            die("Erro ao gerar imagem");
        }

        exit();
    }
});

/**
 * Adicionar ação AJAX para teste de avatares
 */
add_action("wp_ajax_pab_test_avatars", function () {
    if (!current_user_can("manage_options")) {
        wp_send_json_error(["error" => "Sem permissão"]);
        return;
    }
    $results = pab_test_avatar_generation();
    wp_send_json_success($results);
});

/**
 * Função de teste para verificar geração de avatares cropped
 */
function pab_test_avatar_generation()
{
    if (!current_user_can("manage_options")) {
        return ["error" => "Sem permissão"];
    }

    $results = [];
    $genders = ["F", "M"];
    $levels = [
        "abaixo",
        "normal",
        "acima1",
        "acima2",
        "acima3",
        "alto1",
        "alto2",
        "alto3",
    ];

    // Verificar extensão GD
    $results["gd_available"] = extension_loaded("gd") ? "OK" : "ERRO";

    // Testar algumas combinações
    $test_combinations = [
        ["F", "normal"],
        ["M", "normal"],
        ["F", "acima1"],
        ["M", "acima2"],
    ];

    foreach ($test_combinations as $combo) {
        [$gender, $level] = $combo;
        $prefix = $gender === "F" ? "f" : "m";

        // Verificar se arquivo original existe
        $original_path = PAB_PATH . "assets/img/avatars/{$prefix}-{$level}.png";
        $original_exists = file_exists($original_path);

        // Tentar gerar versão cropped
        $cropped_url = pab_generate_avatar_head_crop($gender, $level);

        // URL do endpoint dinâmico
        $endpoint_url = add_query_arg(
            [
                "pab_avatar_head" => "1",
                "gender" => $gender,
                "level" => $level,
            ],
            home_url(),
        );

        $results["tests"][] = [
            "combination" => "{$gender}-{$level}",
            "original_exists" => $original_exists ? "OK" : "ERRO",
            "cropped_generated" => $cropped_url ? "OK" : "ERRO",
            "cropped_url" => $cropped_url,
            "endpoint_url" => $endpoint_url,
        ];
    }

    return $results;
}

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
            "pab_medidas", // Adicionado medidas
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
