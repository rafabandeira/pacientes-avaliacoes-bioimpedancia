<?php
/**
 * Plugin Name:       Pacientes, Avaliações e Bioimpedâncias
 * Plugin URI:        https://bandeiragroup.com/
 * Description:       Gerencia Pacientes, Avaliações, Bioimpedâncias e Medidas para a Clínica Thayse Brito. Inclui metaboxes, relatórios e gráficos de progresso.
 * Version:           1.1.6
 * Author:            BandeiraGroup
 * Author URI:        https://bandeiragroup.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pab
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
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
require_once PAB_PATH . "includes/admin-import-export.php";
require_once PAB_PATH . "includes/admin/dashboard/dashboard.php";

add_action("init", function () {
    // Carregar traduções
    load_plugin_textdomain(
        "pab",
        false,
        dirname(plugin_basename(__FILE__)) . "/languages",
    );
});

/**
 * Hook de Ativação: Registra CPTs e atualiza regras de rewrite.
 */
register_activation_hook(__FILE__, "pab_activation");
function pab_activation()
{
    // Registra os CPTs para garantir que existam
    require_once PAB_PATH . "includes/cpt-paciente.php";
    require_once PAB_PATH . "includes/cpt-avaliacao.php";
    require_once PAB_PATH . "includes/cpt-bioimpedancia.php";
    require_once PAB_PATH . "includes/cpt-medidas.php";

    // Dispara os hooks 'init' manualmente para registro
    do_action("init");

    // Força atualização das regras de URL
    flush_rewrite_rules();
}

/**
 * Hook de Desativação: Apenas atualiza regras de rewrite.
 */
register_deactivation_hook(__FILE__, "pab_deactivation");
function pab_deactivation()
{
    flush_rewrite_rules();
}