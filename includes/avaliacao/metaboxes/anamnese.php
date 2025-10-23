<?php
/**
 * Metabox: Anamnese (Avaliação)
 *
 * Exibe o formulário de anamnese da avaliação
 *
 * @package PAB
 * @subpackage Avaliacao\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de anamnese
 *
 * @param WP_Post $post O post atual
 */
function pab_av_anamnese_cb($post)
{
    $fields = [
        'pab_av_qp' => pab_get($post->ID, 'pab_av_qp'),
        'pab_av_hda' => pab_get($post->ID, 'pab_av_hda'),
        'pab_av_obj' => pab_get($post->ID, 'pab_av_obj'),
    ];

    wp_nonce_field('pab_av_save', 'pab_av_nonce');
    ?>
    <div class="pab-grid">
        <label>
            <strong>Q.P.</strong>
            <input type="text" name="pab_av_qp" value="<?php echo esc_attr($fields['pab_av_qp']); ?>" />
        </label>
        <label>
            <strong>H.D.A.</strong>
            <textarea rows="2" name="pab_av_hda"><?php echo esc_textarea($fields['pab_av_hda']); ?></textarea>
        </label>
        <label>
            <strong>Objetivos</strong>
            <textarea rows="2" name="pab_av_obj"><?php echo esc_textarea($fields['pab_av_obj']); ?></textarea>
        </label>
    </div>
    <?php
}
