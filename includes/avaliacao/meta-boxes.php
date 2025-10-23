<?php
/**
 * Registro de Metaboxes - Avaliação
 *
 * Registra todas as metaboxes que aparecem na tela de edição do post type pab_avaliacao
 *
 * @package PAB
 * @subpackage Avaliacao
 */

if (!defined('ABSPATH')) {
    exit;
}

// Incluir arquivos de metaboxes
require_once __DIR__ . '/metaboxes/paciente.php';
require_once __DIR__ . '/metaboxes/anamnese.php';
require_once __DIR__ . '/metaboxes/habitos.php';
require_once __DIR__ . '/metaboxes/antecedentes.php';
require_once __DIR__ . '/metaboxes/ginecologico.php';

/**
 * Registra as metaboxes da avaliação
 */
add_action('add_meta_boxes', function () {
    // Metabox de paciente vinculado
    add_meta_box(
        'pab_av_paciente',
        'Paciente vinculado',
        'pab_av_paciente_cb',
        'pab_avaliacao',
        'side',
        'high'
    );

    // Metabox de anamnese
    add_meta_box(
        'pab_av_anamnese',
        'Anamnese',
        'pab_av_anamnese_cb',
        'pab_avaliacao',
        'normal',
        'high'
    );

    // Metabox de hábitos de vida
    add_meta_box(
        'pab_av_habitos',
        'Hábitos de vida',
        'pab_av_habitos_cb',
        'pab_avaliacao',
        'normal',
        'high'
    );

    // Metabox de antecedentes patológicos e familiares
    add_meta_box(
        'pab_av_antecedentes',
        'Antecedentes patológicos e familiares',
        'pab_av_antecedentes_cb',
        'pab_avaliacao',
        'normal',
        'high'
    );

    // Metabox de histórico ginecológico
    add_meta_box(
        'pab_av_gineco',
        'Histórico ginecológico',
        'pab_av_gineco_cb',
        'pab_avaliacao',
        'normal',
        'high'
    );
});

/**
 * Salva os dados da avaliação
 *
 * @param int $post_id ID do post
 */
add_action('save_post_pab_avaliacao', function ($post_id) {
    // Verificar nonce
    if (
        !isset($_POST['pab_av_nonce']) ||
        !wp_verify_nonce($_POST['pab_av_nonce'], 'pab_av_save')
    ) {
        return;
    }

    // Vincular paciente se veio do botão
    if (isset($_POST['pab_paciente_id'])) {
        pab_link_to_patient($post_id, (int) $_POST['pab_paciente_id']);
    }

    // Lista de campos a serem salvos
    $fields = [
        'pab_av_qp',
        'pab_av_hda',
        'pab_av_obj',
        'pab_av_alc_sim',
        'pab_av_alc_freq',
        'pab_av_tabag_sim',
        'pab_av_tabag_freq',
        'pab_av_atv_sim',
        'pab_av_atv_quais',
        'pab_av_atv_freq',
        'pab_av_alim_tipo',
        'pab_av_alim_ref',
        'pab_av_liq',
        'pab_av_sono_qual',
        'pab_av_sono_hd',
        'pab_av_sono_ha',
        'pab_av_intest',
        'pab_av_patol',
        'pab_av_circ_sim',
        'pab_av_circ_quais',
        'pab_av_circ_fam',
        'pab_av_end_sim',
        'pab_av_end_quais',
        'pab_av_end_fam',
        'pab_av_med_sim',
        'pab_av_med_tempo',
        'pab_av_med_quais',
        'pab_av_mens',
        'pab_av_tpm',
        'pab_av_meno_sim',
        'pab_av_meno_idade',
        'pab_av_gest_sim',
        'pab_av_gest_qt',
        'pab_av_filhos',
        'pab_av_gine_med_sim',
        'pab_av_gine_med_quais',
    ];

    // Salvar cada campo
    foreach ($fields as $k) {
        if (isset($_POST[$k])) {
            update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
        }
    }

    // Garantir que a avaliação seja sempre publicada
    $current_post = get_post($post_id);
    if ($current_post && $current_post->post_status !== 'publish') {
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ['post_status' => 'publish'],
            ['ID' => $post_id],
            ['%s'],
            ['%d']
        );
        clean_post_cache($post_id);
    }

    // Sempre atualizar o título da avaliação
    $patient_id = (int) get_post_meta($post_id, 'pab_paciente_id', true);

    if ($patient_id) {
        $patient_name = get_the_title($patient_id) ?: 'Paciente Sem Nome';
        $new_title = trim("$patient_name - Avaliação - $post_id");
    } else {
        $new_title = 'ITEM ORFAO - PAB_AVALIACAO';
    }

    // Atualizar título sempre
    global $wpdb;
    $wpdb->update(
        $wpdb->posts,
        ['post_title' => $new_title],
        ['ID' => $post_id],
        ['%s'],
        ['%d']
    );
    clean_post_cache($post_id);
}, 10, 1);
