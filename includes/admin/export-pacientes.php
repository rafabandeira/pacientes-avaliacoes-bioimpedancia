<?php
/**
 * PAB - Módulo de Lógica de Exportação de Pacientes
 *
 * @package PAB
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Lida com as ações de exportação (Pacientes ou Template)
 */
add_action("admin_init", function () {
    if (!isset($_GET["pab_action"]) || !current_user_can("manage_options")) {
        return;
    }
    if (
        !isset($_GET["_wpnonce"]) ||
        !wp_verify_nonce($_GET["_wpnonce"], "pab_export_nonce")
    ) {
        wp_die("Ação não permitida.");
    }

    $action = sanitize_key($_GET["pab_action"]);

    // Roteador de Ações
    switch ($action) {
        case "export_pacientes":
            // CORRIGIDO: Adicionado paciente_id
            $headers = [
                "paciente_id",
                "nome",
                "email",
                "genero",
                "nascimento",
                "altura",
                "celular",
            ];
            pab_generate_patient_csv($headers, "export_pacientes");
            break;

        case "export_template":
            // Template não precisa do ID
            $headers = [
                "nome",
                "email",
                "genero",
                "nascimento",
                "altura",
                "celular",
            ];
            pab_generate_patient_csv($headers, "template");
            break;
    }
});

/**
 * Gera e força o download de um arquivo CSV de PACIENTES ou TEMPLATE.
 *
 * @param array $headers Cabeçalhos do CSV
 * @param string $mode 'export_pacientes' ou 'template'
 */
function pab_generate_patient_csv($headers, $mode = "template")
{
    $filename = "pab_template_pacientes.csv";
    if ($mode === "export_pacientes") {
        $filename = "pab_export_pacientes_" . date("Y-m-d") . ".csv";
    }

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . $filename);
    $f = fopen("php://output", "w");
    fprintf($f, chr(0xef) . chr(0xbb) . chr(0xbf));
    fputcsv($f, $headers);

    if ($mode === "export_pacientes") {
        $pacientes = get_posts([
            "post_type" => "pab_paciente",
            "posts_per_page" => -1,
            "post_status" => "publish",
        ]);

        foreach ($pacientes as $paciente) {
            $row = [
                "paciente_id" => $paciente->ID, // CORREÇÃO: Adicionado ID
                "nome" => get_post_meta($paciente->ID, "pab_nome", true),
                "email" => get_post_meta($paciente->ID, "pab_email", true),
                "genero" => get_post_meta($paciente->ID, "pab_genero", true),
                "nascimento" => get_post_meta(
                    $paciente->ID,
                    "pab_nascimento",
                    true,
                ),
                "altura" => get_post_meta($paciente->ID, "pab_altura", true),
                "celular" => get_post_meta($paciente->ID, "pab_celular", true),
            ];
            $ordered_row = [];
            foreach ($headers as $key) {
                $ordered_row[] = isset($row[$key]) ? $row[$key] : "";
            }
            fputcsv($f, $ordered_row);
        }
    }
    fclose($f);
    die();
}
