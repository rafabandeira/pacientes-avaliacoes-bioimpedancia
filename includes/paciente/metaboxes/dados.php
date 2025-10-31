<?php
/**
 * Metabox: Dados do Paciente
 *
 * Exibe o formulário com os dados cadastrais do paciente
 *
 * @package PAB
 * @subpackage Paciente\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de dados do paciente
 *
 * @param WP_Post $post O post atual
 */
function pab_paciente_dados_cb($post)
{
    wp_nonce_field("pab_paciente_dados", "pab_paciente_nonce");

    $f = [
        "pab_nome" => pab_get($post->ID, "pab_nome"),
        "pab_genero" => pab_get($post->ID, "pab_genero"),
        "pab_nascimento" => pab_get($post->ID, "pab_nascimento"),
        "pab_altura" => pab_get($post->ID, "pab_altura"),
        "pab_celular" => pab_get($post->ID, "pab_celular"),
        "pab_email" => pab_get($post->ID, "pab_email"),
    ];
    ?>
    <div class="pab-grid">
        <label><strong>Nome do Paciente</strong><input type="text" name="pab_nome" value="<?php echo esc_attr(
            $f["pab_nome"],
        ); ?>" /></label>
        <label><strong>Gênero</strong>
            <select name="pab_genero">
                <option value="">Selecione</option>
                <option value="M" <?php selected(
                    $f["pab_genero"],
                    "M",
                ); ?>>Masculino</option>
                <option value="F" <?php selected(
                    $f["pab_genero"],
                    "F",
                ); ?>>Feminino</option>
            </select>
        </label>
        <label><strong>Data Nascimento</strong><input type="date" name="pab_nascimento" value="<?php echo esc_attr(
            $f["pab_nascimento"],
        ); ?>" /></label>
        <label><strong>Altura (cm)</strong><input type="number" step="0.1" name="pab_altura" value="<?php echo esc_attr(
            $f["pab_altura"],
        ); ?>" /></label>
        <label><strong>Celular/WhatsApp</strong><input type="text" name="pab_celular" value="<?php echo esc_attr(
            $f["pab_celular"],
        ); ?>" /></label>
        <label><strong>E-mail</strong><input type="email" name="pab_email" class="pab-input" value="<?php echo esc_attr(
            $f["pab_email"],
        ); ?>" /></label>
    </div>
    <?php
}

/**
 * Salva os dados do paciente
 *
 * @param int $post_id ID do post
 */
function pab_paciente_dados_save($post_id)
{
    if (
        !isset($_POST["pab_paciente_nonce"]) ||
        !wp_verify_nonce($_POST["pab_paciente_nonce"], "pab_paciente_dados")
    ) {
        return;
    }

    // Garantir que o paciente seja sempre publicado
    // EXCEÇÃO: não forçar publish durante autosave, revisão ou quando a ação é 'trash'/'untrash'.
    // Isso evita que mudanças de status legítimas (ex.: mover para lixeira) sejam revertidas.
    // Se for autosave ou revisão, apenas retorna sem alterar o status.
    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }
    // Se a requisição indica que estamos executando uma ação 'trash' ou 'untrash',
    // não forçar o post_status para 'publish' aqui.
    $req_action = isset($_REQUEST["action"])
        ? sanitize_text_field($_REQUEST["action"])
        : "";
    $post_action = isset($_POST["action"])
        ? sanitize_text_field($_POST["action"])
        : "";
    if (
        in_array($req_action, ["trash", "untrash"], true) ||
        in_array($post_action, ["trash", "untrash"], true)
    ) {
        return;
    }
    $post = get_post($post_id);
    if (
        $post &&
        $post->post_status !== "publish" &&
        $post->post_status !== "trash"
    ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ["post_status" => "publish"],
            ["ID" => $post_id],
            ["%s"],
            ["%d"],
        );
        clean_post_cache($post_id);
    }

    $fields = [
        "pab_nome",
        "pab_genero",
        "pab_nascimento",
        "pab_altura",
        "pab_celular",
        "pab_email",
    ];

    foreach ($fields as $k) {
        if (isset($_POST[$k])) {
            update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
        }
    }
}
add_action("save_post_pab_paciente", "pab_paciente_dados_save", 10, 1);