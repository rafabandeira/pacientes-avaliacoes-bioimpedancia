<?php
/**
 * Metabox: Paciente Vinculado (Bioimpedância)
 *
 * @package PAB
 * @subpackage Bioimpedancia\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de paciente vinculado
 *
 * @param WP_Post $post O post atual
 */
function pab_bi_paciente_cb($post)
{
    // Adicionar nonce para garantir segurança do salvamento
    wp_nonce_field("pab_bi_save", "pab_bi_nonce");

    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    $pid_from_post = isset($_POST["pab_paciente_id"])
        ? (int) $_POST["pab_paciente_id"]
        : 0;
    $patient_id_to_show = $pid ?: $pid_from_post;

    if (!$patient_id_to_show) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Esta bioimpedância não está vinculada a um paciente. Se chegou pelo botão "Nova Bioimpedância" do paciente, será vinculada automaticamente ao salvar.
        </div>';
        return;
    }

    if ($patient_id_to_show) {
        $patient_name = pab_get(
            $patient_id_to_show,
            "pab_nome",
            get_the_title($patient_id_to_show),
        );

        echo '<div class="pab-fade-in" style="padding: 0;">';
        echo '<div style="margin-bottom: 16px;">';
        echo '<p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">👤 Paciente Vinculado</p>';
        echo '<p style="margin: 0 0 15px 0; font-size: 16px; font-weight: 600;">';
        echo '<a href="' .
            esc_url(get_edit_post_link($patient_id_to_show)) .
            '" style="text-decoration: none; color: #1e40af; transition: color 0.3s;" onmouseover="this.style.color=\'#3b82f6\'" onmouseout="this.style.color=\'#1e40af\'">';
        echo esc_html($patient_name);
        echo "</a></p>";
        echo '<input type="hidden" name="pab_paciente_id" value="' .
            esc_attr($patient_id_to_show) .
            '">';
        echo "</div>";

        // =================================================================
        // CORREÇÃO INICIA AQUI
        // =================================================================
        // O objeto $post pode estar obsoleto no primeiro carregamento pós-salvar.
        // Vamos re-verificar o status do post diretamente para garantir.
        $current_status = get_post_status($post->ID);

        // Usamos $current_status ao invés de $post->post_status
        if ($current_status === "publish") {
            $permalink = get_permalink($post->ID); ?>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                <a href="<?php echo esc_url($permalink); ?>"
                   class="button button-primary button-large"
                   target="_blank"
                   style="width: 100%; text-align: center; display: block; margin-bottom: 16px; text-decoration: none;">
                    🔗 Abrir Relatório Completo
                </a>

                <?php
                // Verificar se o permalink contém "item-orfao", título problemático, "NOVO" ou "TEMP"
                $has_bad_permalink =
                    strpos($post->post_name, "item-orfao") !== false ||
                    strpos($post->post_title, "ITEM ORFAO") !== false ||
                    strpos($post->post_title, "- NOVO") !== false ||
                    strpos($post->post_title, "- TEMP") !== false ||
                    strpos($post->post_name, "-novo") !== false ||
                    strpos($post->post_name, "-temp") !== false;

                if ($has_bad_permalink): ?>
                <div style="margin-bottom: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <p style="margin: 0 0 8px 0; color: #856404; font-size: 12px;">
                        ⚠️ Este relatório tem um link incorreto<?php if (
                            strpos($post->post_title, "- NOVO") !== false
                        ) {
                            echo ' (contém "NOVO" no nome)';
                        } elseif (
                            strpos($post->post_title, "- TEMP") !== false
                        ) {
                            echo ' (contém "TEMP" no nome)';
                        } ?>. Clique no botão abaixo para corrigi-lo:
                    </p>
                    <a href="<?php echo esc_url(
                        add_query_arg(
                            [
                                "pab_fix_permalink" => $post->ID,
                                "nonce" => wp_create_nonce("pab_fix_permalink"),
                            ],
                            admin_url("post.php?action=edit&post=" . $post->ID),
                        ),
                    ); ?>"
                       class="button button-secondary"
                       style="font-size: 11px;">
                        🔧 Corrigir Link
                    </a>
                </div>
                <?php endif;
                ?>

                <div class="pab-share-container">
                    <p class="pab-share-label">
                        🌐 Link para Compartilhar
                    </p>
                    <input type="text"
                           class="pab-share-input"
                           readonly
                           value="<?php echo esc_attr($permalink); ?>"
                           onclick="this.select(); document.execCommand('copy'); this.style.background='#10b981'; this.style.color='white'; setTimeout(() => { this.style.background='white'; this.style.color='#374151'; }, 1000);">
                    <p class="pab-share-hint">
                        ☝️ Clique para copiar automaticamente
                    </p>
                </div>
            </div>
            <?php
        } else {
             ?>
            <div class="pab-alert pab-alert-warning" style="margin-top: 20px;">
                <strong>⚠️ Atenção:</strong><br>
                Publique esta bioimpedância para gerar o link de compartilhamento com o paciente.
            </div>
            <?php
        }
        // =================================================================
        // CORREÇÃO TERMINA AQUI
        // =================================================================

        echo "</div>";
    } else {
        echo '<div class="pab-alert pab-alert-warning">⚠️ Esta bioimpedância não está vinculada a um paciente.</div>';
    }
}
