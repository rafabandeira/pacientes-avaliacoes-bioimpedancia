<?php
/**
 * Carrega Chart.js para gráficos de histórico
 *
 * @package PAB
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Enfileira Chart.js nas páginas de edição de bioimpedância
 */
add_action(
    "admin_enqueue_scripts",
    function ($hook) {
        // Debug log
        error_log("PAB DEBUG Charts: Hook = $hook");

        // Verificar se estamos em uma página de edição de post
        if ($hook !== "post.php" && $hook !== "post-new.php") {
            error_log("PAB DEBUG Charts: Não é página de edição de post");
            return;
        }

        // Verificar o post type atual
        global $post, $typenow, $pagenow;

        $post_type = null;

        // Tentar obter post_type de várias formas
        if ($post && isset($post->post_type)) {
            $post_type = $post->post_type;
        } elseif ($typenow) {
            $post_type = $typenow;
        } elseif (isset($_GET["post"])) {
            $post_type = get_post_type($_GET["post"]);
        } elseif (isset($_GET["post_type"])) {
            $post_type = $_GET["post_type"];
        }

        error_log(
            "PAB DEBUG Charts: Post type detectado = " .
                ($post_type ?: "nenhum"),
        );

        // Só carregar para bioimpedância
        if ($post_type !== "pab_bioimpedancia") {
            error_log("PAB DEBUG Charts: Post type não é pab_bioimpedancia");
            return;
        }

        error_log("PAB DEBUG Charts: Enfileirando Chart.js");

        // Enfileirar Chart.js da CDN
        wp_enqueue_script(
            "chartjs",
            "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js",
            [],
            "4.4.0",
            true,
        );

        // Enfileirar nosso script customizado (se existir)
        if (file_exists(PAB_PATH . "assets/js/charts.js")) {
            error_log(
                "PAB DEBUG Charts: Enfileirando pab-charts.js customizado",
            );
            wp_enqueue_script(
                "pab-charts",
                PAB_URL . "assets/js/charts.js",
                ["chartjs"],
                "1.0.1",
                true,
            );
        }

        error_log("PAB DEBUG Charts: Scripts enfileirados com sucesso");
    },
    10,
);

/**
 * Adiciona atributo defer aos scripts do Chart.js
 */
add_filter(
    "script_loader_tag",
    function ($tag, $handle, $src) {
        if ($handle === "chartjs") {
            // Adicionar defer para garantir que carregue antes do DOMContentLoaded
            $tag = str_replace(" src", " defer src", $tag);
        }
        return $tag;
    },
    10,
    3,
);
