<?php
/**
 * Metabox: Paciente Vinculado (Avaliação)
 *
 * Exibe informações do paciente vinculado à avaliação
 *
 * @package PAB
 * @subpackage Avaliacao\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de paciente vinculado
 *
 * @param WP_Post $post O post atual
 */
function pab_av_paciente_cb($post)
{
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    $attach = isset($_POST['pab_paciente_id'])
        ? (int) $_POST['pab_paciente_id']
        : 0;
    $pid = $pid ?: $attach;

    if ($pid) {
        pab_link_to_patient($post->ID, $pid);
        echo '<p><strong>Paciente:</strong> ' .
            esc_html(pab_get($pid, 'pab_nome', get_the_title($pid))) .
            '</p>';
    } else {
        echo '<p>Esta avaliação não está vinculada. Se chegou por "Cadastrar Avaliação", será vinculada ao salvar.</p>';
    }
}
