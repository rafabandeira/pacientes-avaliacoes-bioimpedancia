<?php
/**
 * Registro de Metaboxes - Bioimpedância
 *
 * Registra todas as metaboxes que aparecem na tela de edição do post type pab_bioimpedancia
 *
 * @package PAB
 * @subpackage Bioimpedancia
 */

if (!defined('ABSPATH')) {
    exit;
}

// Incluir arquivos de metaboxes
require_once __DIR__ . '/metaboxes/paciente.php';
require_once __DIR__ . '/metaboxes/dados.php';
require_once __DIR__ . '/metaboxes/avatares.php';
require_once __DIR__ . '/metaboxes/composicao.php';
require_once __DIR__ . '/metaboxes/diagnostico.php';
require_once __DIR__ . '/metaboxes/historico.php';

/**
 * Registra as metaboxes da bioimpedância
 */
add_action('add_meta_boxes', function () {
    // Metabox de paciente vinculado (sidebar)
    add_meta_box(
        'pab_bi_paciente',
        'Paciente vinculado',
        'pab_bi_paciente_cb',
        'pab_bioimpedancia',
        'side',
        'high'
    );

    // Metabox de dados de bioimpedância
    add_meta_box(
        'pab_bi_dados',
        'Dados de Bioimpedância',
        'pab_bi_dados_cb',
        'pab_bioimpedancia',
        'normal',
        'high'
    );

    // Metabox de avatares (classificação visual)
    add_meta_box(
        'pab_bi_avatares',
        'Avatares (OMS)',
        'pab_bi_avatares_cb',
        'pab_bioimpedancia',
        'normal',
        'default',
        ['__back_compat_meta_box' => false, 'class' => 'postbox-bio-avatars']
    );

    // Metabox de composição corporal
    add_meta_box(
        'pab_bi_comp_tab',
        'Composição corporal',
        'pab_bi_comp_tab_cb',
        'pab_bioimpedancia',
        'normal',
        'default'
    );

    // Metabox de diagnóstico de obesidade
    add_meta_box(
        'pab_bi_diag_obes',
        'Diagnóstico de Obesidade',
        'pab_bi_diag_obes_cb',
        'pab_bioimpedancia',
        'normal',
        'default'
    );

    // Metabox de histórico
    add_meta_box(
        'pab_bi_historico',
        'Histórico',
        'pab_bi_historico_cb',
        'pab_bioimpedancia',
        'normal',
        'default'
    );
});

/**
 * Salva os dados da bioimpedância
 *
 * @param int $post_id ID do post
 */
add_action('save_post_pab_bioimpedancia', function ($post_id) {
    // Debug log
    error_log("PAB DEBUG: Iniciando salvamento bioimpedancia ID: $post_id");
    error_log(
        "PAB DEBUG: Ação atual: " .
            (isset($_REQUEST['action']) ? $_REQUEST['action'] : 'não definida')
    );

    // NÃO processar se for operação de lixo/exclusão
    $current_post = get_post($post_id);
    if ($current_post && in_array($current_post->post_status, ['trash', 'inherit'])) {
        error_log("PAB DEBUG: Post em lixo ou herdado, não processando");
        return;
    }

    // NÃO processar se for uma ação de lixo via REQUEST
    if (
        isset($_REQUEST['action']) &&
        in_array($_REQUEST['action'], ['trash', 'delete', 'untrash'])
    ) {
        error_log("PAB DEBUG: Ação de lixo/exclusão detectada, não processando");
        return;
    }

    // Prevenir loops infinitos
    static $processing = [];
    if (isset($processing[$post_id])) {
        error_log("PAB DEBUG: Loop detectado para post $post_id, abortando");
        return;
    }
    $processing[$post_id] = true;

    // Verificar nonce
    $has_valid_nonce =
        isset($_POST['pab_bi_nonce']) &&
        wp_verify_nonce($_POST['pab_bi_nonce'], 'pab_bi_save');

    if (!$has_valid_nonce) {
        error_log("PAB DEBUG: Nonce inválido para post $post_id");

        // Se não há nonce válido, só salvar o pab_paciente_id se estiver no POST
        if (isset($_POST['pab_paciente_id'])) {
            $patient_id = (int) $_POST['pab_paciente_id'];
            error_log("PAB DEBUG: Salvando apenas pab_paciente_id=$patient_id (sem nonce)");
            pab_link_to_patient($post_id, $patient_id);
        }

        unset($processing[$post_id]);
        return;
    }

    // Verificar capabilities
    if (!current_user_can('edit_post', $post_id)) {
        error_log("PAB DEBUG: Usuário sem permissão para editar post $post_id");
        unset($processing[$post_id]);
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        error_log("PAB DEBUG: Autosave detectado, ignorando");
        unset($processing[$post_id]);
        return;
    }

    if (wp_is_post_revision($post_id)) {
        error_log("PAB DEBUG: Revisão detectada, ignorando");
        unset($processing[$post_id]);
        return;
    }

    // Vinculação do Paciente
    if (isset($_POST['pab_paciente_id'])) {
        $patient_id = (int) $_POST['pab_paciente_id'];
        error_log("PAB DEBUG: Vinculando bioimpedancia $post_id ao paciente $patient_id");
        pab_link_to_patient($post_id, $patient_id);
    }

    // Salvamento dos Campos Numéricos
    $fields = [
        'pab_bi_peso',
        'pab_bi_gordura_corporal',
        'pab_bi_musculo_esq',
        'pab_bi_gordura_visc',
        'pab_bi_metab_basal',
        'pab_bi_idade_corporal',
    ];

    foreach ($fields as $k) {
        if (isset($_POST[$k]) && $_POST[$k] !== '') {
            $value = sanitize_text_field($_POST[$k]);
            error_log("PAB DEBUG: Salvando $k = $value");
            update_post_meta($post_id, $k, $value);
        } else {
            error_log("PAB DEBUG: Removendo meta $k (valor vazio)");
            delete_post_meta($post_id, $k);
        }
    }

    // Atualizar título se necessário
    $current_post = get_post($post_id);
    if ($current_post && strpos($current_post->post_title, '- NOVO') !== false) {
        $patient_id = (int) get_post_meta($post_id, 'pab_paciente_id', true);
        if ($patient_id) {
            $patient_name = get_the_title($patient_id) ?: 'Paciente Sem Nome';
            $new_title = trim("$patient_name - Bioimpedância - $post_id");
            $new_slug = sanitize_title($new_title);

            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                [
                    'post_title' => $new_title,
                    'post_name' => $new_slug,
                ],
                ['ID' => $post_id],
                ['%s', '%s'],
                ['%d']
            );
            clean_post_cache($post_id);

            error_log("PAB DEBUG: Título atualizado para: $new_title");
        }
    }

    error_log("PAB DEBUG: Finalizando salvamento bioimpedancia ID: $post_id");

    // Limpar flag de processamento
    unset($processing[$post_id]);
}, 10, 1);

/**
 * Hook para controlar o status dos posts de bioimpedância
 * Executado ANTES do save_post para garantir o status correto
 */
add_action('wp_insert_post_data', function ($data, $postarr) {
    // Só processar bioimpedâncias
    if ($data['post_type'] !== 'pab_bioimpedancia') {
        return $data;
    }

    error_log("PAB DEBUG: wp_insert_post_data - Status original: {$data['post_status']}");

    // NÃO interferir com operações de lixo, exclusão ou outros status especiais
    if (
        in_array($data['post_status'], [
            'trash',
            'inherit',
            'private',
            'future',
            'pending',
        ])
    ) {
        error_log("PAB DEBUG: Status especial detectado, não interferindo");
        return $data;
    }

    // NÃO interferir se for uma operação de lixo via REQUEST
    if (
        isset($_REQUEST['action']) &&
        in_array($_REQUEST['action'], ['trash', 'delete', 'untrash'])
    ) {
        error_log("PAB DEBUG: Operação de lixo/exclusão detectada, não interferindo");
        return $data;
    }

    // Garantir que bioimpedâncias sejam sempre publicadas
    if ($data['post_status'] === 'draft' || $data['post_status'] === 'auto-draft') {
        error_log("PAB DEBUG: Forçando status 'publish' para bioimpedância");
        $data['post_status'] = 'publish';
    }

    return $data;
}, 10, 2);
