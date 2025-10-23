<?php
/**
 * Metabox: Hábitos de Vida (Avaliação)
 *
 * Exibe o formulário de hábitos de vida da avaliação
 *
 * @package PAB
 * @subpackage Avaliacao\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de hábitos de vida
 *
 * @param WP_Post $post O post atual
 */
function pab_av_habitos_cb($post)
{
    $f = [
        'alc_s' => pab_get($post->ID, 'pab_av_alc_sim'),
        'alc_f' => pab_get($post->ID, 'pab_av_alc_freq'),
        'tab_s' => pab_get($post->ID, 'pab_av_tabag_sim'),
        'tab_f' => pab_get($post->ID, 'pab_av_tabag_freq'),
        'atv_s' => pab_get($post->ID, 'pab_av_atv_sim'),
        'atv_q' => pab_get($post->ID, 'pab_av_atv_quais'),
        'atv_f' => pab_get($post->ID, 'pab_av_atv_freq'),
        'alim_tipo' => pab_get($post->ID, 'pab_av_alim_tipo'),
        'alim_ref' => pab_get($post->ID, 'pab_av_alim_ref'),
        'liq' => pab_get($post->ID, 'pab_av_liq'),
        'sono_q' => pab_get($post->ID, 'pab_av_sono_qual'),
        'sono_hd' => pab_get($post->ID, 'pab_av_sono_hd'),
        'sono_ha' => pab_get($post->ID, 'pab_av_sono_ha'),
        'intest' => pab_get($post->ID, 'pab_av_intest'),
    ];
    ?>
    <div class="pab-grid">
        <label><strong>Consome bebida alcoólica</strong>
            <select name="pab_av_alc_sim" class="pab-toggle" data-target="#alc_freq">
                <option value="nao" <?php selected($f['alc_s'], 'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['alc_s'], 'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="alc_freq" class="pab-conditional" data-show="sim">
            <label><strong>Frequência</strong><input type="text" name="pab_av_alc_freq" value="<?php echo esc_attr($f['alc_f']); ?>"></label>
        </div>

        <label><strong>Tabagista</strong>
            <select name="pab_av_tabag_sim" class="pab-toggle" data-target="#tab_freq">
                <option value="nao" <?php selected($f['tab_s'], 'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['tab_s'], 'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="tab_freq" class="pab-conditional" data-show="sim">
            <label><strong>Frequência</strong><input type="text" name="pab_av_tabag_freq" value="<?php echo esc_attr($f['tab_f']); ?>"></label>
        </div>

        <label><strong>Atividade física</strong>
            <select name="pab_av_atv_sim" class="pab-toggle" data-target="#atv_box">
                <option value="nao" <?php selected($f['atv_s'], 'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['atv_s'], 'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="atv_box" class="pab-conditional" data-show="sim">
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_atv_quais" value="<?php echo esc_attr($f['atv_q']); ?>"></label>
            <label><strong>Frequência</strong><input type="text" name="pab_av_atv_freq" value="<?php echo esc_attr($f['atv_f']); ?>"></label>
        </div>

        <label><strong>Tipo de Alimentação</strong>
            <select name="pab_av_alim_tipo">
                <option value="hipo" <?php selected($f['alim_tipo'], 'hipo'); ?>>Hipocalórica</option>
                <option value="normal" <?php selected($f['alim_tipo'], 'normal'); ?>>Normal</option>
                <option value="hiper" <?php selected($f['alim_tipo'], 'hiper'); ?>>Hipercalórica</option>
            </select>
        </label>
        <label><strong>Nº de refeições diárias</strong><input type="text" name="pab_av_alim_ref" value="<?php echo esc_attr($f['alim_ref']); ?>"></label>

        <label><strong>Consumo de líquido diário</strong><input type="text" name="pab_av_liq" value="<?php echo esc_attr($f['liq']); ?>"></label>

        <label><strong>Qualidade do sono</strong>
            <select name="pab_av_sono_qual">
                <option value="ruim" <?php selected($f['sono_q'], 'ruim'); ?>>Ruim</option>
                <option value="bom" <?php selected($f['sono_q'], 'bom'); ?>>Bom</option>
                <option value="excelente" <?php selected($f['sono_q'], 'excelente'); ?>>Excelente</option>
            </select>
        </label>
        <label><strong>Horário de dormir</strong><input type="time" name="pab_av_sono_hd" value="<?php echo esc_attr($f['sono_hd']); ?>"></label>
        <label><strong>Horário de acordar</strong><input type="time" name="pab_av_sono_ha" value="<?php echo esc_attr($f['sono_ha']); ?>"></label>
        <label><strong>Funcionamento intestinal</strong><input type="text" name="pab_av_intest" value="<?php echo esc_attr($f['intest']); ?>"></label>
    </div>
    <?php
}
