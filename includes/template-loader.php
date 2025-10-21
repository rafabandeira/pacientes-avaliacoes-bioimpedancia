<?php // includes/template-loader.php

if (!defined('ABSPATH')) exit;

/**
 * Carrega template personalizado para pab_bioimpedancia
 * Funciona independente do tema ativo
 */
class PAB_Template_Loader {
    
    public function __construct() {
        add_filter('template_include', [$this, 'load_bioimpedancia_template']);
        add_filter('single_template', [$this, 'load_bioimpedancia_template']);
    }

    /**
     * Intercepta o carregamento de template para bioimpedância
     */
    public function load_bioimpedancia_template($template) {
        if (is_singular('pab_bioimpedancia')) {
            $plugin_template = PAB_PATH . 'templates/single-pab_bioimpedancia.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
}

// Inicializa o loader
new PAB_Template_Loader();