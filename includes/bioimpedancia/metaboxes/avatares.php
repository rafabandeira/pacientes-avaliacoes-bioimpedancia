<?php
/**
 * Metabox: Avatares (OMS) - Bioimpedância
 *
 * Exibe a classificação corporal visual baseada no IMC
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

    // Lógica para calcular o IMC, que é o correto para esta visualização
    $peso = (float) pab_get($post->ID, 'pab_bi_peso');
    $altura_cm = (float) pab_get($pid, 'pab_altura');
    $altura_m = $altura_cm ? $altura_cm / 100.0 : null;
    $imc = $altura_m && $peso ? round($peso / ($altura_m * $altura_m), 1) : null;

    $genero = pab_get($pid, 'pab_genero', 'M');
    $idade_real = pab_calc_idade_real($pid);

    // A classificação para os avatares deve ser baseada no IMC
    $class = pab_oms_classificacao('imc', $imc, $genero, $idade_real);
    $nivel = $class['nivel'];
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

    if ($imc) {
        $badge_class = 'pab-badge pab-badge-' . $nivel;
        echo '<div style="margin: 12px 0;">';
        echo '<span style="font-size: 18px; font-weight: 700; color: #1e293b;">IMC: ' .
            $imc .
            '</span> ';
        echo '<span class="' .
            $badge_class .
            '">' .
            esc_html($class['ref']) .
            '</span>';
        echo '</div>';
    }
    echo '</div>';

    echo '<div class="pab-avatars-line" data-count="' . count($levels) . '">';
    foreach ($levels as $lvl => $label) {
        $active = $lvl === $nivel ? 'active' : '';
        $img = defined('PAB_URL')
            ? PAB_URL . 'assets/img/avatars/' . $prefix . '-' . $lvl . '.png'
            : '';
        echo '<div class="pab-avatar ' .
            $active .
            '" title="' .
            esc_attr(ucfirst($lvl)) .
            '">';
        echo '<img src="' . esc_url($img) . '" alt="' . esc_attr($lvl) . '">';
        echo '</div>';
    }
    echo '</div>';

    echo '<div style="padding: 12px; background: #f8f9fa; border-radius: 6px; margin-top: 12px; border-left: 4px solid #228be6;">';
    echo '<p style="margin: 0; font-size: 13px; color: #666; line-height: 1.5;">';
    echo '<strong style="color: #333;">📊 Classificação de IMC:</strong><br>';
    echo '<span style="color: #228be6; font-weight: 600;">' .
        esc_html(ucfirst($nivel)) .
        '</span> - ';
    echo esc_html($class['ref']);
    echo ' (IMC: ' . ($imc ? esc_html($imc) : 'N/D') . ')';
    echo '</p>';
    echo '</div>';

    echo '</div>';
}
