<?php
/**
 * Plugin Name: Pacientes, Avaliações e Bioimpedâncias
 * Description: CPT principal Paciente + Avaliação + Bioimpedância, metaboxes, associações automáticas, avatares, OMS, gráficos.
 * Version: 1.0.10
 * Author: BandeiraGroup
 * Text Domain: pab
 */

if (!defined("ABSPATH")) {
    exit();
}

define("PAB_PATH", plugin_dir_path(__FILE__));
define("PAB_URL", plugin_dir_url(__FILE__));

require_once PAB_PATH . "includes/helpers.php";
require_once PAB_PATH . "includes/assets.php";
require_once PAB_PATH . "includes/cpt-paciente.php";
require_once PAB_PATH . "includes/cpt-avaliacao.php";
require_once PAB_PATH . "includes/cpt-bioimpedancia.php";
require_once PAB_PATH . "includes/cpt-medidas.php";

// Estrutura Modular - Funções Compartilhadas
require_once PAB_PATH . "includes/shared/calculations.php";

// Estrutura Modular - Metaboxes por Post Type
require_once PAB_PATH . "includes/paciente/meta-boxes.php";
require_once PAB_PATH . "includes/avaliacao/meta-boxes.php";
require_once PAB_PATH . "includes/bioimpedancia/meta-boxes.php";
require_once PAB_PATH . "includes/medidas/meta-boxes.php";

require_once PAB_PATH . "includes/admin-listings.php";
require_once PAB_PATH . "includes/charts.php";
require_once PAB_PATH . "includes/template-loader.php";

add_action("init", function () {
    // Carregar traduções se necessário
    load_plugin_textdomain(
        "pab",
        false,
        dirname(plugin_basename(__FILE__)) . "/languages",
    );
});

// ADICIONAR ESTE CÓDIGO PARA FORÇAR FLUSH DE REWRITE
register_activation_hook(__FILE__, "pab_activation");
function pab_activation()
{
    // Registra os CPTs
    require_once PAB_PATH . "includes/cpt-paciente.php";
    require_once PAB_PATH . "includes/cpt-avaliacao.php";
    require_once PAB_PATH . "includes/cpt-bioimpedancia.php";
    require_once PAB_PATH . "includes/cpt-medidas.php";

    // Força atualização das regras de URL
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, "pab_deactivation");
function pab_deactivation()
{
    flush_rewrite_rules();
}
