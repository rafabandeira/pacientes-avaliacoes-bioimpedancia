<?php
/**
 * Metabox: Dados de Bioimpedância
 *
 * Exibe o formulário com os dados coletados na bioimpedância
 *
 * @package PAB
 * @subpackage Bioimpedancia\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de dados de bioimpedância
 *
 * @param WP_Post $post O post atual
 */
function pab_bi_dados_cb($post)
{
    // Nonce já foi adicionado no metabox do paciente, não duplicar
    $f = [
        'peso' => pab_get($post->ID, 'pab_bi_peso'),
        'gc' => pab_get($post->ID, 'pab_bi_gordura_corporal'),
        'me' => pab_get($post->ID, 'pab_bi_musculo_esq'),
        'gv' => pab_get($post->ID, 'pab_bi_gordura_visc'),
        'mb' => pab_get($post->ID, 'pab_bi_metab_basal'),
        'idade' => pab_get($post->ID, 'pab_bi_idade_corporal'),
    ];
    ?>

    <div class="pab-fade-in">
        <div class="pab-grid">
            <label>
                <strong>⚖️ Peso (kg)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_peso"
                       value="<?php echo esc_attr($f['peso']); ?>"
                       placeholder="Ex: 70.5">
            </label>

            <label>
                <strong>🔥 Gordura Corporal (%)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_gordura_corporal"
                       value="<?php echo esc_attr($f['gc']); ?>"
                       placeholder="Ex: 18.5">
            </label>

            <label>
                <strong>💪 Músculo Esquelético (%)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_musculo_esq"
                       value="<?php echo esc_attr($f['me']); ?>"
                       placeholder="Ex: 35.2">
            </label>

            <label>
                <strong>🫀 Gordura Visceral (nível)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_gordura_visc"
                       value="<?php echo esc_attr($f['gv']); ?>"
                       placeholder="Ex: 8.0">
            </label>

            <label>
                <strong>⚡ Metabolismo Basal (kcal)</strong>
                <input type="number"
                       step="1"
                       name="pab_bi_metab_basal"
                       value="<?php echo esc_attr($f['mb']); ?>"
                       placeholder="Ex: 1580">
            </label>

            <label>
                <strong>🕐 Idade Corporal (anos)</strong>
                <input type="number"
                       step="1"
                       name="pab_bi_idade_corporal"
                       value="<?php echo esc_attr($f['idade']); ?>"
                       placeholder="Ex: 28">
            </label>
        </div>

        <div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> As avaliações OMS nas seções abaixo são calculadas automaticamente baseadas no gênero e idade do paciente vinculado.
        </div>
    </div>
    <?php
}
