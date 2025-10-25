<?php
/**
 * PAB - Módulo de Importação e Exportação (Core)
 *
 * Registra a página de admin, renderiza a UI e carrega
 * os módulos de lógica de importação e exportação.
 *
 * @package PAB
 */

if (!defined("ABSPATH")) {
    exit();
}

// Criar pasta /admin/ se não existir
if (!is_dir(PAB_PATH . "includes/admin")) {
    wp_mkdir_p(PAB_PATH . "includes/admin");
}

// Carregar módulos de lógica
require_once PAB_PATH . "includes/admin/import-pacientes.php";
require_once PAB_PATH . "includes/admin/export-pacientes.php";
require_once PAB_PATH . "includes/admin/export-relacionados.php";

/**
 * Adiciona a página de menu "Importar/Exportar"
 */
add_action("admin_menu", function () {
    add_submenu_page(
        "edit.php?post_type=pab_paciente", // Parent slug (Menu Pacientes)
        __("Importar/Exportar Dados", "pab"), // Título da Página
        __("Importar/Exportar", "pab"), // Título do Menu
        "manage_options", // Capability
        "pab-import-export", // Menu slug
        "pab_render_import_export_page", // Função de callback
    );
});

/**
 * Renderiza o HTML da página de Importar/Exportar
 */
function pab_render_import_export_page()
{
    if (!current_user_can("manage_options")) {
        wp_die(__("Você não tem permissão para acessar esta página.", "pab"));
    }

    // Processar a importação se um arquivo foi enviado (função de 'import-pacientes.php')
    if (
        isset($_FILES["pab_import_file"]) &&
        check_admin_referer("pab_import_nonce", "pab_import_nonce_field")
    ) {
        pab_handle_import_upload($_FILES["pab_import_file"]);
    }
    ?>
    <div class="wrap pab-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>Use esta ferramenta para importar pacientes ou exportar dados do sistema.</p>

        <div class="card">
            <h2>Importar Pacientes (CSV)</h2>
            <p>Faça upload de um arquivo CSV para cadastrar novos pacientes. O arquivo <strong>deve</strong> ser codificado em UTF-8.</p>
            <p>
                As colunas obrigatórias são <strong>nome</strong> e <strong>email</strong>.
                Colunas opcionais: <strong>genero</strong>, <strong>nascimento</strong> (formato YYYY-MM-DD), <strong>altura</strong> (em cm), <strong>celular</strong>.
            </p>
            <p>
                <a href="<?php echo esc_url(
                    wp_nonce_url(
                        admin_url("admin.php?pab_action=export_template"),
                        "pab_export_nonce",
                    ),
                ); ?>">
                    Baixe o modelo de planilha (Google Sheets/CSV)
                </a>
            </p>

            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field(
                    "pab_import_nonce",
                    "pab_import_nonce_field",
                ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="pab_import_file">Arquivo CSV</label>
                        </th>
                        <td>
                            <input type="file" id="pab_import_file" name="pab_import_file" accept=".csv" required>
                        </td>
                    </tr>
                </table>
                <?php submit_button("Iniciar Importação de Pacientes"); ?>
            </form>
        </div>

        <div class="card">
            <h2>Exportar Pacientes</h2>
            <p>Baixe um arquivo CSV com todos os pacientes cadastrados e seus dados principais (ID, nome, e-mail, etc.).</p>
            <a href="<?php echo esc_url(
                wp_nonce_url(
                    admin_url("admin.php?pab_action=export_pacientes"),
                    "pab_export_nonce",
                ),
            ); ?>" class="button button-secondary">
                Baixar CSV de Pacientes
            </a>
        </div>

        <div class="card">
            <h2>Exportar Dados Relacionados</h2>
            <p>Baixe arquivos CSV contendo os dados de Bioimpedâncias, Avaliações ou Medidas. Cada linha incluirá o ID e o e-mail do paciente vinculado.</p>

            <a href="<?php echo esc_url(
                wp_nonce_url(
                    admin_url("admin.php?pab_action=export_bioimpedancias"),
                    "pab_export_nonce",
                ),
            ); ?>" class="button button-primary" style="margin: 10px;">
                Exportar Bioimpedâncias
            </a>

            <a href="<?php echo esc_url(
                wp_nonce_url(
                    admin_url("admin.php?pab_action=export_avaliacoes"),
                    "pab_export_nonce",
                ),
            ); ?>" class="button button-primary" style="margin: 10px;">
                Exportar Avaliações
            </a>

            <a href="<?php echo esc_url(
                wp_nonce_url(
                    admin_url("admin.php?pab_action=export_medidas"),
                    "pab_export_nonce",
                ),
            ); ?>" class="button button-primary" style="margin: 10px;">
                Exportar Medidas
            </a>
            <br><br>
            <p><small><strong>Aviso:</strong> A exportação de Avaliações pode ser lenta se houver milhares de registros, devido à grande quantidade de campos.</small></p>
        </div>

    </div>
    <?php
}

/**
 * Helper para exibir notices de admin
 * (Mantido aqui ou movido para helpers.php, mas necessário para a importação)
 */
if (!function_exists("pab_admin_notice")) {
    function pab_admin_notice($message, $type = "info")
    {
        add_action("admin_notices", function () use ($message, $type) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                esc_html($message),
            );
        });
    }
}
