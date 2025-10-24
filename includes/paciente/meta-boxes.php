<?php
/**
 * Registro de Metaboxes - Paciente
 *
 * Registra todas as metaboxes que aparecem na tela de edição do post type pab_paciente
 *
 * @package PAB
 * @subpackage Paciente
 */

if (!defined("ABSPATH")) {
    exit();
}

// Incluir arquivos de metaboxes
require_once __DIR__ . "/metaboxes/dados.php";
require_once __DIR__ . "/metaboxes/avaliacoes.php";
require_once __DIR__ . "/metaboxes/bioimpedancias.php";
require_once __DIR__ . "/metaboxes/medidas.php";

/**
 * Registra as metaboxes do paciente
 */
add_action("add_meta_boxes", function () {
    // Metabox de dados cadastrais do paciente
    add_meta_box(
        "pab_paciente_dados",
        "Dados do Paciente",
        "pab_paciente_dados_cb",
        "pab_paciente",
        "normal",
        "high",
    );

    // Metabox de avaliações vinculadas
    add_meta_box(
        "pab_paciente_avaliacoes",
        "Avaliações do Paciente",
        "pab_paciente_avaliacoes_cb",
        "pab_paciente",
        "normal",
        "default",
    );

    // Metabox de bioimpedâncias vinculadas
    add_meta_box(
        "pab_paciente_bioimps",
        "Bioimpedâncias do Paciente",
        "pab_paciente_bioimps_cb",
        "pab_paciente",
        "normal",
        "default",
    );

    // Metabox de medidas vinculadas
    add_meta_box(
        "pab_paciente_medidas",
        "Medidas do Paciente",
        "pab_paciente_medidas_cb",
        "pab_paciente",
        "normal",
        "default",
    );
});

/**
 * Proteção contra criação direta de bioimpedâncias e avaliações
 *
 * Garante que bioimpedâncias e avaliações só possam ser criadas a partir
 * da tela do paciente, mantendo a integridade do vínculo.
 */
add_action("load-post-new.php", function () {
    $pt = isset($_GET["post_type"])
        ? sanitize_text_field($_GET["post_type"])
        : "";

    // Se for tentativa de criar bioimpedância, avaliação ou medidas sem paciente vinculado
    if (
        in_array($pt, ["pab_avaliacao", "pab_bioimpedancia", "pab_medidas"]) &&
        !isset($_GET["pab_attach"])
    ) {
        wp_redirect(admin_url("edit.php?post_type=pab_paciente"));
        exit();
    }

    if (!isset($_GET["pab_attach"])) {
        return;
    }

    $patient_id = (int) $_GET["pab_attach"];
    if (!in_array($pt, ["pab_avaliacao", "pab_bioimpedancia", "pab_medidas"])) {
        return;
    }

    // CORRIGIDO: Removemos a chamada AJAX para sessão.
    // Apenas injetar o input hidden é suficiente e mais robusto.
    add_action("admin_footer", function () use ($patient_id, $pt) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const patientId = <?php echo (int) $patient_id; ?>;

            // Cria um input hidden com o paciente
            const form = document.getElementById('post');
            if (form) {
                let hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'pab_paciente_id';
                hidden.value = patientId;
                form.appendChild(hidden);
            }
        });
        </script>
        <?php
    });
});

/**
 * Handler AJAX para armazenar informação de attachment
 *
 * REMOVIDO. Esta lógica baseada em sessão era frágil e
 * foi substituída pelo input hidden injetado no 'load-post-new.php'.
 */
// add_action('wp_ajax_pab_store_attachment', ...); // Removido
