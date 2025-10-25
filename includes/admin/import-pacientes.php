<?php
/**
 * PAB - Módulo de Lógica de Importação de Pacientes
 *
 * @package PAB
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Lida com o processamento do arquivo CSV de importação (Apenas Pacientes)
 *
 * @param array $file O array $_FILES do arquivo enviado
 */
function pab_handle_import_upload($file)
{
    if ($file["error"] > 0) {
        pab_admin_notice(
            "Erro no upload do arquivo. Código: " . $file["error"],
            "error",
        );
        return;
    }
    $filepath = $file["tmp_name"];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    if (
        $mime_type !== "text/csv" &&
        $mime_type !== "text/plain" &&
        $mime_type !== "application/csv"
    ) {
        pab_admin_notice(
            "Erro: Tipo de arquivo inválido (" .
                $mime_type .
                "). Envie um arquivo CSV.",
            "error",
        );
        return;
    }
    if (($handle = fopen($filepath, "r")) === false) {
        pab_admin_notice(
            "Erro: Não foi possível abrir o arquivo CSV.",
            "error",
        );
        return;
    }

    $expected_headers = [
        "nome",
        "email",
        "genero",
        "nascimento",
        "altura",
        "celular",
    ];
    $headers = fgetcsv($handle);
    if ($headers === false) {
        pab_admin_notice("Erro: Arquivo CSV vazio ou corrompido.", "error");
        return;
    }

    $column_map = [];
    foreach ($headers as $index => $header) {
        if ($index == 0) {
            $header = preg_replace("/^\x{FEFF}/u", "", $header);
        }
        $normalized_header = strtolower(trim($header));
        if (in_array($normalized_header, $expected_headers)) {
            $column_map[$normalized_header] = $index;
        }
    }

    if (!isset($column_map["nome"]) || !isset($column_map["email"])) {
        pab_admin_notice(
            'Erro: O CSV deve conter as colunas "nome" e "email".',
            "error",
        );
        fclose($handle);
        return;
    }

    $count_success = 0;
    $count_skipped = 0;
    $count_errors = 0;
    $line_number = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $line_number++;
        $nome = sanitize_text_field($row[$column_map["nome"]]);
        $email = sanitize_email($row[$column_map["email"]]);

        if (empty($nome) || empty($email)) {
            $count_skipped++;
            continue;
        }

        $existing_patient = get_posts([
            "post_type" => "pab_paciente",
            "meta_key" => "pab_email",
            "meta_value" => $email,
            "posts_per_page" => 1,
            "fields" => "ids",
        ]);

        if (!empty($existing_patient)) {
            $count_skipped++;
            continue;
        }

        $post_data = [
            "post_type" => "pab_paciente",
            "post_title" => $nome,
            "post_status" => "publish",
        ];
        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            $count_errors++;
            continue;
        }

        update_post_meta($post_id, "pab_nome", $nome);
        update_post_meta($post_id, "pab_email", $email);
        if (isset($column_map["genero"])) {
            update_post_meta(
                $post_id,
                "pab_genero",
                sanitize_text_field($row[$column_map["genero"]]),
            );
        }
        if (isset($column_map["nascimento"])) {
            $nascimento = sanitize_text_field($row[$column_map["nascimento"]]);
            $date_obj = date_create($nascimento);
            if ($date_obj) {
                update_post_meta(
                    $post_id,
                    "pab_nascimento",
                    $date_obj->format("Y-m-d"),
                );
            }
        }
        if (isset($column_map["altura"])) {
            update_post_meta(
                $post_id,
                "pab_altura",
                sanitize_text_field($row[$column_map["altura"]]),
            );
        }
        if (isset($column_map["celular"])) {
            update_post_meta(
                $post_id,
                "pab_celular",
                sanitize_text_field($row[$column_map["celular"]]),
            );
        }

        $count_success++;
    }

    fclose($handle);
    $message = sprintf(
        "Importação concluída: %d pacientes criados, %d pulados (e-mail duplicado ou dados faltando), %d erros.",
        $count_success,
        $count_skipped,
        $count_errors,
    );
    pab_admin_notice($message, "success");
}
