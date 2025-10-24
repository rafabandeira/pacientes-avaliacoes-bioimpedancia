<?php // includes/template-loader.php

if (!defined('ABSPATH')) exit;

/**
 * Carrega templates personalizados para PAB (bioimpedância e medidas)
 * Funciona independente do tema ativo
 */
class PAB_Template_Loader {

    public function __construct() {
        add_filter('template_include', [$this, 'load_pab_template']);
        add_filter('single_template', [$this, 'load_pab_template']);
    }

    /**
     * Intercepta o carregamento de template para bioimpedância e medidas
     */
    public function load_pab_template($template) {
        if (is_singular('pab_bioimpedancia')) {
            $plugin_template = PAB_PATH . 'templates/single-pab_bioimpedancia.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        if (is_singular('pab_medidas')) {
            $plugin_template = PAB_PATH . 'templates/single-pab_medidas.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
}

// Inicializa o loader
new PAB_Template_Loader();
