<?php
/**
 * Metabox: Paciente Vinculado (Medidas)
 *
 * Exibe informações do paciente vinculado às medidas
 *
 * @package PAB
 * @subpackage Medidas\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de paciente vinculado
 *
 * @param WP_Post $post O post atual
 */
function pab_med_paciente_cb($post)
{
    // Adicionar nonce para garantir segurança do salvamento
    wp_nonce_field('pab_med_save', 'pab_med_nonce');

    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    $pid_from_post = isset($_POST['pab_paciente_id'])
        ? (int) $_POST['pab_paciente_id']
        : 0;
    $patient_id_to_show = $pid ?: $pid_from_post;

    if (!$patient_id_to_show) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Estas medidas não estão vinculadas a um paciente. Se chegou pelo botão "Cadastrar Medidas" do paciente, será vinculada automaticamente ao salvar.
        </div>';
        return;
    }

    if ($patient_id_to_show) {
        $patient_name = pab_get(
            $patient_id_to_show,
            'pab_nome',
            get_the_title($patient_id_to_show)
        );

        echo '<div class="pab-fade-in" style="padding: 0;">';
        echo '<div style="margin-bottom: 16px;">';
        echo '<p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">👤 Paciente Vinculado</p>';
        echo '<p style="margin: 0; font-size: 16px; font-weight: 600;">';
        echo '<a href="' .
            esc_url(get_edit_post_link($patient_id_to_show)) .
            '" style="text-decoration: none; color: #1e40af; transition: color 0.3s;" onmouseover="this.style.color=\'#3b82f6\'" onmouseout="this.style.color=\'#1e40af\'">';
        echo esc_html($patient_name);
        echo '</a></p>';
        echo '<input type="hidden" name="pab_paciente_id" value="' .
            esc_attr($patient_id_to_show) .
            '">';
        echo '</div>';
        echo '</div>';
    }
}
