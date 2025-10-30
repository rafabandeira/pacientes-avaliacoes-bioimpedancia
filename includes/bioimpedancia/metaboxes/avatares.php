<?php
/**
 * Metabox: Avatares (OMS) - Bioimpedância
 *
 * Exibe a classificação corporal visual baseada no Percentual de Gordura (PBF)
 *
 * @package PAB
 * @subpackage Bioimpedancia\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de avatares de classificação corporal
 *
 * @param WP_Post $post O post atual
 */
function pab_bi_avatares_cb($post)
{
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    if (!$pid) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Vincule um paciente para exibir os avatares de classificação corporal.
        </div>';
        return;
    }

    $genero = pab_get($pid, 'pab_genero', 'M');
    $idade_real = pab_calc_idade_real($pid);

    // A classificação (nível) para os AVATARES visuais deve ser baseada no Percentual de Gordura (PBF)
    // CORREÇÃO: Usando a meta key correta 'pab_bi_gordura_corporal'
    $gordura_p = (float) pab_get($post->ID, 'pab_bi_gordura_corporal');
    
    // CORREÇÃO: Usando a métrica 'gc' (Gordura Corporal) para consistência
    $class_pbf = pab_oms_classificacao('gc', $gordura_p, $genero, $idade_real); 
    $nivel = $class_pbf['nivel'];

    $prefix = $genero === 'F' ? 'f' : 'm';
    $levels = [
        'abaixo' => 'Baixo Peso',
        'normal' => 'Normal',
        'acima1' => 'Sobrepeso',
        'acima2' => 'Obesidade I',
        'acima3' => 'Obesidade II',
        'alto1' => 'Obesidade III',
        'alto2' => 'Muito Alto',
        'alto3' => 'Extremo',
    ];

    echo '<div class="pab-fade-in">';

    // Header informativo
    echo '<div style="margin-bottom: 20px; text-align: center;">';
    echo '<p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">';
    echo ($genero === 'F' ? '👩' : '👨') .
        ' Classificação Corporal - ' .
        ($genero === 'F' ? 'Feminino' : 'Masculino');
    echo '</p>';

    // Exibe PBF (Gordura) como métrica principal
    if ($gordura_p) {
        $badge_class = 'pab-badge pab-badge-' . $nivel;
        echo '<div style="margin: 12px 0;">';
        echo '<span style="font-size: 18px; font-weight: 700; color: #1e293b;">Gordura: ' .
            $gordura_p . // CORREÇÃO: Usando $gordura_p
            '%</span> ';
        echo '<span class="' .
            $badge_class .
            '">' .
            esc_html($class_pbf['ref']) .
            '</span>';
        echo '</div>';
    } else {
         echo '<div class="pab-alert pab-alert-warning" style="text-align: left; margin-top: 12px;">
            <strong>⚠️ Atenção:</strong> Insira o % de Gordura Corporal (no metabox "Dados da Bioimpedância") para gerar o avatar.
        </div>';
    }
    echo '</div>';

    echo '<div class="pab-avatars-line" data-count="' . count($levels) . '">';
    foreach ($levels as $lvl => $label) {
        $active = $lvl === $nivel ? 'active' : ''; // $nivel (baseado no PBF) controla o avatar
        $img = defined('PAB_URL')
            ? PAB_URL . 'assets/img/avatars/' . $prefix . '-' . $lvl . '.png'
            : '';
        echo '<div class="pab-avatar ' .
            $active .
            '" title="' .
            esc_attr(ucfirst($label)) . // Usando $label do loop
            '">';
        echo '<img src="' . esc_url($img) . '" alt="' . esc_attr($lvl) . '">';
        echo '</div>';
    }
    echo '</div>';

    // A nota de rodapé exibe apenas PBF
    echo '<div style="padding: 12px; background: #f8f9fa; border-radius: 6px; margin-top: 12px; border-left: 4px solid #228be6;">';

    // Classificação do Avatar (PBF)
    echo '<p style="margin: 0; font-size: 13px; color: #666; line-height: 1.5;">';
    echo '<strong style="color: #333;">💪 Classificação (Gordura Corporal):</strong><br>';
    echo '<span style="color: #228be6; font-weight: 600;">' .
        esc_html(ucfirst($nivel)) . // $nivel (PBF)
        '</span> - ';
    echo esc_html($class_pbf['ref']); // $class_pbf (PBF)
    echo ' (PBF: ' . ($gordura_p ? esc_html($gordura_p) . '%' : 'N/D') . ')'; // CORREÇÃO: Usando $gordura_p
    echo '</p>';

    echo '</div>';

    echo '</div>';
}