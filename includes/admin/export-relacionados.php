<?php
/**
 * PAB - Módulo de Lógica de Exportação de Dados Relacionados
 * (Bioimpedância, Avaliações, Medidas)
 *
 * @package PAB
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Roteador para ações de exportação de dados relacionados
 */
add_action('admin_init', function () {
    if (!isset($_GET['pab_action']) || !current_user_can('manage_options')) {
        return;
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'pab_export_nonce')) {
        wp_die('Ação não permitida.');
    }

    $action = sanitize_key($_GET['pab_action']);
    
    switch ($action) {
        case 'export_bioimpedancias':
            pab_export_bioimpedancias_csv();
            break;

        case 'export_avaliacoes':
            pab_export_avaliacoes_csv();
            break;

        case 'export_medidas':
            pab_export_medidas_csv();
            break;
    }
});


/**
 * Helper genérico para exportar dados relacionados (Bio, Avaliação, Medidas)
 *
 * @param string $cpt Post Type (ex: 'pab_bioimpedancia')
 * @param string $filename_prefix Prefixo do arquivo (ex: 'export_bioimpedancias')
 * @param array $meta_keys_map Mapa de [Label do CSV => meta_key]
 */
function pab_export_related_data_csv($cpt, $filename_prefix, $meta_keys_map)
{
    $filename = 'pab_' . $filename_prefix . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $f = fopen('php://output', 'w');
    fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers Padrão + Headers Específicos
    $standard_headers = ['paciente_id', 'paciente_email', 'data_registro'];
    $specific_labels = array_keys($meta_keys_map);
    $meta_keys = array_values($meta_keys_map);
    
    fputcsv($f, array_merge($standard_headers, $specific_labels));

    $items = get_posts([
        'post_type' => $cpt,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'post_parent',
        'order' => 'ASC'
    ]);

    $patient_email_cache = [];

    foreach ($items as $item) {
        $patient_id = $item->post_parent;
        $email = '';
        
        if ($patient_id > 0) {
            if (isset($patient_email_cache[$patient_id])) {
                $email = $patient_email_cache[$patient_id];
            } else {
                $email = get_post_meta($patient_id, 'pab_email', true);
                $patient_email_cache[$patient_id] = $email;
            }
        }

        $row = [
            'paciente_id' => $patient_id,
            'paciente_email' => $email,
            'data_registro' => $item->post_date,
        ];
        
        foreach ($meta_keys as $meta_key) {
            $value = get_post_meta($item->ID, $meta_key, true);
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $row[] = $value;
        }
        
        fputcsv($f, $row);
    }

    fclose($f);
    die();
}

/**
 * Exportação de BIOIMPEDÂNCIAS
 */
function pab_export_bioimpedancias_csv()
{
    $meta_keys = [
        'Peso (kg)' => 'pab_bi_peso',
        'Gordura Corporal (%)' => 'pab_bi_gordura_corporal',
        'Musculo Esq. (%)' => 'pab_bi_musculo_esq',
        'Gordura Visceral (Nível)' => 'pab_bi_gordura_visc',
        'Metab. Basal (kcal)' => 'pab_bi_metab_basal',
        'Idade Corporal' => 'pab_bi_idade_corporal',
    ];
    pab_export_related_data_csv('pab_bioimpedancia', 'export_bioimpedancias', $meta_keys);
}

/**
 * Exportação de MEDIDAS
 */
function pab_export_medidas_csv()
{
    $meta_keys = [
        'Peso (kg)' => 'pab_med_peso',
        'Pescoço (cm)' => 'pab_med_pescoco',
        'Tórax (cm)' => 'pab_med_torax',
        'Braço D (cm)' => 'pab_med_braco_d',
        'Braço E (cm)' => 'pab_med_braco_e',
        'Antebraço D (cm)' => 'pab_med_antebraco_d',
        'Antebraço E (cm)' => 'pab_med_antebraco_e',
        'Cintura (cm)' => 'pab_med_cintura',
        'Abdômen (cm)' => 'pab_med_abdomen',
        'Quadril (cm)' => 'pab_med_quadril',
        'Coxa D (cm)' => 'pab_med_coxa_d',
        'Coxa E (cm)' => 'pab_med_coxa_e',
        'Panturrilha D (cm)' => 'pab_med_panturrilha_d',
        'Panturrilha E (cm)' => 'pab_med_panturrilha_e',
    ];
    pab_export_related_data_csv('pab_medidas', 'export_medidas', $meta_keys);
}

/**
 * Exportação de AVALIAÇÕES
 */
function pab_export_avaliacoes_csv()
{
    $meta_keys = [
        // Anamnese
        'Queixa Principal' => 'pab_av_qp',
        'Hist. Doença Atual' => 'pab_av_hda',
        'Objetivo' => 'pab_av_obj',
        // Hábitos
        'Álcool' => 'pab_av_alc_sim',
        'Álcool Freq.' => 'pab_av_alc_freq',
        'Tabagismo' => 'pab_av_tabag_sim',
        'Tabagismo Freq.' => 'pab_av_tabag_freq',
        'Ativ. Física' => 'pab_av_atv_sim',
        'Ativ. Física Quais' => 'pab_av_atv_quais',
        'Ativ. Física Freq.' => 'pab_av_atv_freq',
        'Alimentação' => 'pab_av_alim_tipo',
        'Refeições/dia' => 'pab_av_alim_ref',
        'Líquidos/dia (L)' => 'pab_av_liq',
        'Qualid. Sono' => 'pab_av_sono_qual',
        'Sono (Deita)' => 'pab_av_sono_hd',
        'Sono (Acorda)' => 'pab_av_sono_ha',
        'Intestino' => 'pab_av_intest',
        // Antecedentes
        'Ant. Pessoais' => 'pab_av_ant_pesso',
        'Ant. Familiares' => 'pab_av_ant_fam',
        'Med. em Uso' => 'pab_av_med_uso',
        'Cirurgias' => 'pab_av_cirurg',
        // Ginecológico
        'Gestações' => 'pab_av_gine_gesta',
        'Partos' => 'pab_av_gine_partos', // CORRIGIDO AQUI
        'Abortos' => 'pab_av_gine_abortos',
        'Filhos' => 'pab_av_gine_filhos',
        'Menarca' => 'pab_av_gine_menarca',
        'DUM' => 'pab_av_gine_dum',
        'Ciclo' => 'pab_av_gine_ciclo',
        'SOP' => 'pab_av_gine_sop',
        'Endometriose' => 'pab_av_gine_endo',
        'Anticoncepcional' => 'pab_av_gine_anticon',
        'Anticoncep. Quais' => 'pab_av_gine_anticon_quais',
        'Med. Ginecológica' => 'pab_av_gine_med_sim',
        'Med. Gin. Quais' => 'pab_av_gine_med_quais',
    ];
    pab_export_related_data_csv('pab_avaliacao', 'export_avaliacoes', $meta_keys);
}