<?php // includes/meta-avaliacao.php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function() {
    add_meta_box('pab_av_paciente', 'Paciente vinculado', 'pab_av_paciente_cb', 'pab_avaliacao', 'side', 'high');
    add_meta_box('pab_av_anamnese', 'Anamnese', 'pab_av_anamnese_cb', 'pab_avaliacao', 'normal', 'high');
    add_meta_box('pab_av_habitos', 'Hábitos de vida', 'pab_av_habitos_cb', 'pab_avaliacao', 'normal', 'high');
    add_meta_box('pab_av_antecedentes', 'Antecedentes patológicos e familiares', 'pab_av_antecedentes_cb', 'pab_avaliacao', 'normal', 'high');
    add_meta_box('pab_av_gineco', 'Histórico ginecológico', 'pab_av_gineco_cb', 'pab_avaliacao', 'normal', 'high');
});

function pab_av_paciente_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    $attach = isset($_POST['pab_paciente_id']) ? (int) $_POST['pab_paciente_id'] : 0;
    $pid = $pid ?: $attach;
    if ($pid) {
        pab_link_to_patient($post->ID, $pid);
        echo '<p><strong>Paciente:</strong> ' . esc_html(pab_get($pid, 'pab_nome', get_the_title($pid))) . '</p>';
    } else {
        echo '<p>Esta avaliação não está vinculada. Se chegou por "Cadastrar Avaliação", será vinculada ao salvar.</p>';
    }
}

function pab_av_anamnese_cb($post) {
    $fields = [
        'pab_av_qp' => pab_get($post->ID, 'pab_av_qp'),
        'pab_av_hda' => pab_get($post->ID, 'pab_av_hda'),
        'pab_av_obj' => pab_get($post->ID, 'pab_av_obj'),
    ];
    wp_nonce_field('pab_av_save', 'pab_av_nonce');
    ?>
    <div class="pab-grid">
        <label><strong>Q.P.</strong><input type="text" name="pab_av_qp" value="<?php echo esc_attr($fields['pab_av_qp']); ?>" /></label>
        <label><strong>H.D.A.</strong><textarea rows="2" name="pab_av_hda"><?php echo esc_textarea($fields['pab_av_hda']); ?></textarea></label>
        <label><strong>Objetivos</strong><textarea rows="2" name="pab_av_obj"><?php echo esc_textarea($fields['pab_av_obj']); ?></textarea></label>
    </div>
    <?php
}

function pab_av_habitos_cb($post) {
    $f = [
        'alc_s' => pab_get($post->ID,'pab_av_alc_sim'),
        'alc_f' => pab_get($post->ID,'pab_av_alc_freq'),
        'tab_s' => pab_get($post->ID,'pab_av_tabag_sim'),
        'tab_f' => pab_get($post->ID,'pab_av_tabag_freq'),
        'atv_s' => pab_get($post->ID,'pab_av_atv_sim'),
        'atv_q' => pab_get($post->ID,'pab_av_atv_quais'),
        'atv_f' => pab_get($post->ID,'pab_av_atv_freq'),
        'alim_tipo' => pab_get($post->ID,'pab_av_alim_tipo'),
        'alim_ref' => pab_get($post->ID,'pab_av_alim_ref'),
        'liq' => pab_get($post->ID,'pab_av_liq'),
        'sono_q' => pab_get($post->ID,'pab_av_sono_qual'),
        'sono_hd' => pab_get($post->ID,'pab_av_sono_hd'),
        'sono_ha' => pab_get($post->ID,'pab_av_sono_ha'),
        'intest' => pab_get($post->ID,'pab_av_intest'),
    ];
    ?>
    <div class="pab-grid">
        <label><strong>Consome bebida alcoólica</strong>
            <select name="pab_av_alc_sim" class="pab-toggle" data-target="#alc_freq">
                <option value="nao" <?php selected($f['alc_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['alc_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="alc_freq" class="pab-conditional" data-show="sim">
            <label><strong>Frequência</strong><input type="text" name="pab_av_alc_freq" value="<?php echo esc_attr($f['alc_f']); ?>"></label>
        </div>

        <label><strong>Tabagista</strong>
            <select name="pab_av_tabag_sim" class="pab-toggle" data-target="#tab_freq">
                <option value="nao" <?php selected($f['tab_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['tab_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="tab_freq" class="pab-conditional" data-show="sim">
            <label><strong>Frequência</strong><input type="text" name="pab_av_tabag_freq" value="<?php echo esc_attr($f['tab_f']); ?>"></label>
        </div>

        <label><strong>Atividade física</strong>
            <select name="pab_av_atv_sim" class="pab-toggle" data-target="#atv_box">
                <option value="nao" <?php selected($f['atv_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['atv_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="atv_box" class="pab-conditional" data-show="sim">
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_atv_quais" value="<?php echo esc_attr($f['atv_q']); ?>"></label>
            <label><strong>Frequência</strong><input type="text" name="pab_av_atv_freq" value="<?php echo esc_attr($f['atv_f']); ?>"></label>
        </div>

        <label><strong>Tipo de Alimentação</strong>
            <select name="pab_av_alim_tipo">
                <option value="hipo" <?php selected($f['alim_tipo'],'hipo'); ?>>Hipocalórica</option>
                <option value="normal" <?php selected($f['alim_tipo'],'normal'); ?>>Normal</option>
                <option value="hiper" <?php selected($f['alim_tipo'],'hiper'); ?>>Hipercalórica</option>
            </select>
        </label>
        <label><strong>Nº de refeições diárias</strong><input type="text" name="pab_av_alim_ref" value="<?php echo esc_attr($f['alim_ref']); ?>"></label>

        <label><strong>Consumo de líquido diário</strong><input type="text" name="pab_av_liq" value="<?php echo esc_attr($f['liq']); ?>"></label>

        <label><strong>Qualidade do sono</strong>
            <select name="pab_av_sono_qual">
                <option value="ruim" <?php selected($f['sono_q'],'ruim'); ?>>Ruim</option>
                <option value="bom" <?php selected($f['sono_q'],'bom'); ?>>Bom</option>
                <option value="excelente" <?php selected($f['sono_q'],'excelente'); ?>>Excelente</option>
            </select>
        </label>
        <label><strong>Horário de dormir</strong><input type="time" name="pab_av_sono_hd" value="<?php echo esc_attr($f['sono_hd']); ?>"></label>
        <label><strong>Horário de acordar</strong><input type="time" name="pab_av_sono_ha" value="<?php echo esc_attr($f['sono_ha']); ?>"></label>
        <label><strong>Funcionamento intestinal</strong><input type="text" name="pab_av_intest" value="<?php echo esc_attr($f['intest']); ?>"></label>
    </div>
    <?php
}

function pab_av_antecedentes_cb($post) {
    $f = [
        'patol' => pab_get($post->ID,'pab_av_patol'),
        'circ_s' => pab_get($post->ID,'pab_av_circ_sim'),
        'circ_q' => pab_get($post->ID,'pab_av_circ_quais'),
        'circ_fam' => pab_get($post->ID,'pab_av_circ_fam'),
        'end_s' => pab_get($post->ID,'pab_av_end_sim'),
        'end_q' => pab_get($post->ID,'pab_av_end_quais'),
        'end_fam' => pab_get($post->ID,'pab_av_end_fam'),
        'med_s' => pab_get($post->ID,'pab_av_med_sim'),
        'med_t' => pab_get($post->ID,'pab_av_med_tempo'),
        'med_q' => pab_get($post->ID,'pab_av_med_quais'),
    ];
    ?>
    <div class="pab-grid">
        <label><strong>Antecedentes patológicos</strong><textarea rows="2" name="pab_av_patol"><?php echo esc_textarea($f['patol']); ?></textarea></label>

        <label><strong>Distúrbios circulatórios</strong>
            <select name="pab_av_circ_sim" class="pab-toggle" data-target="#circ_box">
                <option value="nao" <?php selected($f['circ_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['circ_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="circ_box" class="pab-conditional" data-show="sim">
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_circ_quais" value="<?php echo esc_attr($f['circ_q']); ?>"></label>
            <label><strong>Antecedentes familiares</strong>
                <select name="pab_av_circ_fam">
                    <option value="nao" <?php selected($f['circ_fam'],'nao'); ?>>Não</option>
                    <option value="sim" <?php selected($f['circ_fam'],'sim'); ?>>Sim</option>
                </select>
            </label>
        </div>

        <label><strong>Distúrbios endócrino-metabólicos</strong>
            <select name="pab_av_end_sim" class="pab-toggle" data-target="#end_box">
                <option value="nao" <?php selected($f['end_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['end_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="end_box" class="pab-conditional" data-show="sim">
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_end_quais" value="<?php echo esc_attr($f['end_q']); ?>"></label>
            <label><strong>Antecedentes familiares</strong>
                <select name="pab_av_end_fam">
                    <option value="nao" <?php selected($f['end_fam'],'nao'); ?>>Não</option>
                    <option value="sim" <?php selected($f['end_fam'],'sim'); ?>>Sim</option>
                </select>
            </label>
        </div>

        <label><strong>Uso de medicamentos</strong>
            <select name="pab_av_med_sim" class="pab-toggle" data-target="#med_box">
                <option value="nao" <?php selected($f['med_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['med_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="med_box" class="pab-conditional" data-show="sim">
            <label><strong>Quanto tempo</strong><input type="text" name="pab_av_med_tempo" value="<?php echo esc_attr($f['med_t']); ?>"></label>
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_med_quais" value="<?php echo esc_attr($f['med_q']); ?>"></label>
        </div>
    </div>
    <?php
}

function pab_av_gineco_cb($post) {
    $f = [
        'mens' => pab_get($post->ID,'pab_av_mens'),
        'tpm' => pab_get($post->ID,'pab_av_tpm'),
        'meno_s' => pab_get($post->ID,'pab_av_meno_sim'),
        'meno_idade' => pab_get($post->ID,'pab_av_meno_idade'),
        'gest_s' => pab_get($post->ID,'pab_av_gest_sim'),
        'gest_qt' => pab_get($post->ID,'pab_av_gest_qt'),
        'filhos' => pab_get($post->ID,'pab_av_filhos'),
        'med_s' => pab_get($post->ID,'pab_av_gine_med_sim'),
        'med_q' => pab_get($post->ID,'pab_av_gine_med_quais'),
    ];
    ?>
    <div class="pab-grid">
        <label><strong>Menstruação</strong>
            <select name="pab_av_mens">
                <option value="regular" <?php selected($f['mens'],'regular'); ?>>Regular</option>
                <option value="irregular" <?php selected($f['mens'],'irregular'); ?>>Irregular</option>
            </select>
        </label>
        <label><strong>TPM</strong>
            <select name="pab_av_tpm">
                <option value="nao" <?php selected($f['tpm'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['tpm'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <label><strong>Menopausa</strong>
            <select name="pab_av_meno_sim" class="pab-toggle" data-target="#meno_box">
                <option value="nao" <?php selected($f['meno_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['meno_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="meno_box" class="pab-conditional" data-show="sim">
            <label><strong>Idade da menopausa</strong><input type="text" name="pab_av_meno_idade" value="<?php echo esc_attr($f['meno_idade']); ?>"></label>
        </div>
        <label><strong>Gestação</strong>
            <select name="pab_av_gest_sim" class="pab-toggle" data-target="#gest_box">
                <option value="nao" <?php selected($f['gest_s'],'nao'); ?>>Não</option>
                <option value="sim" <?php selected($f['gest_s'],'sim'); ?>>Sim</option>
            </select>
        </label>
        <div id="gest_box" class="pab-conditional" data-show="sim">
            <label><strong>Quantas</strong><input type="number" name="pab_av_gest_qt" value="<?php echo esc_attr($f['gest_qt']); ?>"></label>
            <label><strong>Nº de filhos</strong><input type="number" name="pab_av_filhos" value="<?php echo esc_attr($f['filhos']); ?>"></label>
            <label><strong>Faz uso de medicamentos</strong>
                <select name="pab_av_gine_med_sim" class="pab-toggle" data-target="#gmed_box">
                    <option value="nao" <?php selected($f['med_s'],'nao'); ?>>Não</option>
                    <option value="sim" <?php selected($f['med_s'],'sim'); ?>>Sim</option>
                </select>
            </label>
            <div id="gmed_box" class="pab-conditional" data-show="sim">
                <label><strong>Qual(is)</strong><input type="text" name="pab_av_gine_med_quais" value="<?php echo esc_attr($f['med_q']); ?>"></label>
            </div>
        </div>
    </div>
    <?php
}

add_action('save_post_pab_avaliacao', function($post_id) {
    if (!isset($_POST['pab_av_nonce']) || !wp_verify_nonce($_POST['pab_av_nonce'],'pab_av_save')) return;

    // Vincular paciente se veio do botão
    if (isset($_POST['pab_paciente_id'])) {
        pab_link_to_patient($post_id, (int)$_POST['pab_paciente_id']);
    }

    $fields = [
        'pab_av_qp','pab_av_hda','pab_av_obj',
        'pab_av_alc_sim','pab_av_alc_freq','pab_av_tabag_sim','pab_av_tabag_freq',
        'pab_av_atv_sim','pab_av_atv_quais','pab_av_atv_freq',
        'pab_av_alim_tipo','pab_av_alim_ref','pab_av_liq',
        'pab_av_sono_qual','pab_av_sono_hd','pab_av_sono_ha','pab_av_intest',
        'pab_av_patol','pab_av_circ_sim','pab_av_circ_quais','pab_av_circ_fam',
        'pab_av_end_sim','pab_av_end_quais','pab_av_end_fam',
        'pab_av_med_sim','pab_av_med_tempo','pab_av_med_quais',
        'pab_av_mens','pab_av_tpm','pab_av_meno_sim','pab_av_meno_idade',
        'pab_av_gest_sim','pab_av_gest_qt','pab_av_filhos',
        'pab_av_gine_med_sim','pab_av_gine_med_quais',
    ];
    foreach ($fields as $k) {
        if (isset($_POST[$k])) update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
    }
}, 10, 1);
