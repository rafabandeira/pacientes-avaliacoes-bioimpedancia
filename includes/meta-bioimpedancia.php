<?php // includes/meta-bioimpedancia.php - CORRIGIDO: Slug e Lógica OMS

if (!defined('ABSPATH')) exit;

// =========================================================================
// 1. REGISTRO DAS METABOXES
// =========================================================================
add_action('add_meta_boxes', function() {
    add_meta_box('pab_bi_paciente', 'Paciente vinculado', 'pab_bi_paciente_cb', 'pab_bioimpedancia', 'side', 'high');
    add_meta_box('pab_bi_dados', 'Dados de Bioimpedância', 'pab_bi_dados_cb', 'pab_bioimpedancia', 'normal', 'high');
    add_meta_box('pab_bi_avatares', 'Avatares (OMS)', 'pab_bi_avatares_cb', 'pab_bioimpedancia', 'normal', 'default', ['__back_compat_meta_box' => false, 'class' => 'postbox-bio-avatars']);
    add_meta_box('pab_bi_comp_tab', 'Composição corporal', 'pab_bi_comp_tab_cb', 'pab_bioimpedancia', 'normal', 'default');
    add_meta_box('pab_bi_diag_obes', 'Diagnóstico de Obesidade', 'pab_bi_diag_obes_cb', 'pab_bioimpedancia', 'normal', 'default');
    add_meta_box('pab_bi_historico', 'Histórico', 'pab_bi_historico_cb', 'pab_bioimpedancia', 'normal', 'default');
});

// =========================================================================
// 2. CALLBACKS DE EXIBIÇÃO
// =========================================================================

/**
 * Metabox de Paciente Vinculado (com botão de visualização)
 */
function pab_bi_paciente_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    $pid_from_post = isset($_POST['pab_paciente_id']) ? (int) $_POST['pab_paciente_id'] : 0;
    $patient_id_to_show = $pid ?: $pid_from_post;

    if ($patient_id_to_show) {
        $patient_name = pab_get($patient_id_to_show, 'pab_nome', get_the_title($patient_id_to_show));

        echo '<div style="padding: 10px; background: white; border-radius: 6px;">';
        echo '<p style="margin: 0 0 8px 0;"><strong>👤 Paciente:</strong></p>';
        echo '<p style="margin: 0 0 15px 0; font-size: 15px;">';
        echo '<a href="' . esc_url(get_edit_post_link($patient_id_to_show)) . '" style="text-decoration: none; color: #2271b1;">';
        echo esc_html($patient_name);
        echo '</a></p>';
        echo '<input type="hidden" name="pab_paciente_id" value="' . esc_attr($patient_id_to_show) . '">';

        if ($post->post_status === 'publish') {
            $permalink = get_permalink($post->ID);
            ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #f0f0f1;">

                <a href="<?php echo esc_url($permalink); ?>"
                   class="button button-primary button-large"
                   target="_blank"
                   style="width: 100%; text-align: center; height: 40px; line-height: 38px; display: block; margin-bottom: 12px; font-size: 14px;">
                    🔗 Abrir Relatório
                </a>

                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px; border-radius: 6px; color: white;">
                    <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 600; text-transform: uppercase; opacity: 0.9;">
                        Link para Compartilhar
                    </p>
                    <input type="text"
                           readonly
                           value="<?php echo esc_attr($permalink); ?>"
                           onclick="this.select(); document.execCommand('copy'); this.style.background='#4ade80';"
                           style="width: 100%; padding: 8px; font-size: 11px; border: none; border-radius: 4px; cursor: pointer; background: white; color: #333; font-family: monospace; transition: all 0.3s;">
                    <p style="margin: 8px 0 0 0; font-size: 11px; text-align: center; opacity: 0.9;">
                        ☝️ Clique para copiar automaticamente
                    </p>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div style="margin-top: 15px; padding: 12px; background: #fff9e6; border-left: 4px solid #ffc107; border-radius: 4px;">
                <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.5;">
                    <strong>⚠️ Atenção:</strong><br>
                    Publique esta bioimpedância para gerar o link de compartilhamento com o paciente.
                </p>
            </div>
            <?php
        }

        echo '</div>';
    } else {
        echo '<p style="color: #d63638;">⚠️ Esta bioimpedância não está vinculada a um paciente.</p>';
    }
}


/**
 * Metabox de Dados de Bioimpedância (Formulário)
 */
function pab_bi_dados_cb($post) {
    wp_nonce_field('pab_bi_save','pab_bi_nonce');
    $f = [
        'peso' => pab_get($post->ID,'pab_bi_peso'),
        'gc' => pab_get($post->ID,'pab_bi_gordura_corporal'),
        'me' => pab_get($post->ID,'pab_bi_musculo_esq'),
        'gv' => pab_get($post->ID,'pab_bi_gordura_visc'),
        'mb' => pab_get($post->ID,'pab_bi_metab_basal'),
        'idade' => pab_get($post->ID,'pab_bi_idade_corporal'),
    ];
    ?>
    <div class="pab-grid">
        <label><strong>Peso (kg)</strong><input type="number" step="0.1" name="pab_bi_peso" value="<?php echo esc_attr($f['peso']); ?>"></label>
        <label><strong>Gordura corporal (%)</strong><input type="number" step="0.1" name="pab_bi_gordura_corporal" value="<?php echo esc_attr($f['gc']); ?>"></label>
        <label><strong>Músculo esquelético (%)</strong><input type="number" step="0.1" name="pab_bi_musculo_esq" value="<?php echo esc_attr($f['me']); ?>"></label>
        <label><strong>Gordura visceral (nível)</strong><input type="number" step="0.1" name="pab_bi_gordura_visc" value="<?php echo esc_attr($f['gv']); ?>"></label>
        <label><strong>Metabolismo basal (kcal)</strong><input type="number" step="1" name="pab_bi_metab_basal" value="<?php echo esc_attr($f['mb']); ?>"></label>
        <label><strong>Idade corporal (anos)</strong><input type="number" step="1" name="pab_bi_idade_corporal" value="<?php echo esc_attr($f['idade']); ?>"></label>
    </div>
    <p class="description">As avaliações OMS nas metaboxes abaixo são baseadas em gênero e idade do paciente.</p>
    <?php
}

/**
 * Função Auxiliar para Calcular Faixa de Peso Ideal por IMC
 * Fonte: OMS (IMC Normal: 18.5 a 24.9)
 */
function pab_calc_faixa_peso_ideal($altura_cm) {
    if (!$altura_cm || $altura_cm <= 0) {
        return null;
    }
    $altura_m = $altura_cm / 100.0;
    $imc_min = 18.5;
    $imc_max = 24.9;

    return [
        'min' => round($imc_min * ($altura_m * $altura_m), 1),
        'max' => round($imc_max * ($altura_m * $altura_m), 1),
    ];
}

/**
 * Funções de Classificação OMS/Padrão: ATUALIZADAS
 * * Implementa faixas por Gênero e Idade (usando 60 como corte para idoso/jovem).
 * * Adicionado cálculo de Peso Ideal por IMC.
 * ATENÇÃO: Os valores são exemplos baseados em padrões comuns e devem ser VALIDADOS.
 */
function pab_oms_classificacao($metric, $value, $genero, $idade, $context = []) {
    // Retorna se o valor for nulo ou vazio
    if ($value === '' || $value === null) return ['nivel' => '—', 'ref' => 'Falta dado'];

    // Configuração de corte de idade (Adulto vs. Idoso)
    $is_elderly = ($idade !== null && $idade >= 60);

    // ----------------------------------------------------------------------
    // 0. PESO (baseado na faixa de IMC ideal)
    // ----------------------------------------------------------------------
    if ($metric === 'peso') {
        $altura_cm = isset($context['altura_cm']) ? $context['altura_cm'] : null;
        $faixa_ideal = pab_calc_faixa_peso_ideal($altura_cm);

        if (!$faixa_ideal) {
            return ['nivel' => '—', 'ref' => 'Falta altura'];
        }

        $ref_text = 'Ideal: ' . $faixa_ideal['min'] . 'kg - ' . $faixa_ideal['max'] . 'kg';

        if ($value < $faixa_ideal['min']) return ['nivel' => 'abaixo', 'ref' => $ref_text];
        if ($value > $faixa_ideal['max']) return ['nivel' => 'acima1', 'ref' => $ref_text];
        return ['nivel' => 'normal', 'ref' => $ref_text];
    }
    
    // ----------------------------------------------------------------------
    // 1. GORDURA CORPORAL (GC - Exemplo com Faixas por Idade/Gênero)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === 'gc') {
        $ranges = [
            'M' => [ // Masculino
                'jovem' => ['normal' => [11, 21], 'acima1' => [22, 26], 'acima2' => [27, 30], 'alto1' => [31, 100]],
                'idoso' => ['normal' => [13, 23], 'acima1' => [24, 28], 'acima2' => [29, 32], 'alto1' => [33, 100]],
            ],
            'F' => [ // Feminino
                'jovem' => ['normal' => [18, 28], 'acima1' => [29, 33], 'acima2' => [34, 38], 'alto1' => [39, 100]],
                'idoso' => ['normal' => [20, 30], 'acima1' => [31, 35], 'acima2' => [36, 40], 'alto1' => [41, 100]],
            ],
        ];

        $age_group = $is_elderly ? 'idoso' : 'jovem';
        $current_ranges = $ranges[$genero][$age_group];

        if ($value < $current_ranges['normal'][0]) return ['nivel' => 'abaixo', 'ref' => 'Baixa/Essencial'];
        if ($value <= $current_ranges['normal'][1]) return ['nivel' => 'normal', 'ref' => 'Normal'];
        if ($value <= $current_ranges['acima1'][1]) return ['nivel' => 'acima1', 'ref' => 'Limítrofe/Sobrepeso'];
        if ($value <= $current_ranges['acima2'][1]) return ['nivel' => 'acima2', 'ref' => 'Obesidade Moderada'];
        return ['nivel' => 'alto1', 'ref' => 'Obesidade Elevada'];
    }

    // ----------------------------------------------------------------------
    // 2. MÚSCULO ESQUELÉTICO (ME - Exemplo com Faixas por Gênero)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === 'musculo') {
         $ranges = [
            'M' => ['abaixo' => 33.3, 'normal' => 39.4, 'acima1' => 100],
            'F' => ['abaixo' => 24.4, 'normal' => 32.8, 'acima1' => 100],
        ];

        $current_ranges = $ranges[$genero];

        if ($value < $current_ranges['abaixo']) return ['nivel' => 'abaixo', 'ref' => 'Baixo'];
        if ($value <= $current_ranges['normal']) return ['nivel' => 'normal', 'ref' => 'Normal'];
        return ['nivel' => 'acima1', 'ref' => 'Alto'];
    }

    // ----------------------------------------------------------------------
    // 3. IMC (Índice de Massa Corporal - Padrão OMS)
    // Fonte: OMS (World Health Organization)
    // ----------------------------------------------------------------------
    if ($metric === 'imc') {
        if ($is_elderly) { // Faixas Sugeridas para Idosos
            if ($value < 22) return ['nivel' => 'abaixo', 'ref' => 'Baixo Peso (Idoso)'];
            if ($value < 27) return ['nivel' => 'normal', 'ref' => 'Normal (Idoso)'];
            return ['nivel' => 'acima1', 'ref' => 'Sobrepeso/Obesidade (Idoso)'];
        } else { // Faixas Padrão Adulto
            if ($value < 18.5) return ['nivel' => 'abaixo', 'ref' => 'Baixo Peso'];
            if ($value < 25) return ['nivel' => 'normal', 'ref' => 'Normal'];
            if ($value < 30) return ['nivel' => 'acima1', 'ref' => 'Sobrepeso'];
            if ($value < 35) return ['nivel' => 'acima2', 'ref' => 'Obesidade Grau I'];
            if ($value < 40) return ['nivel' => 'acima3', 'ref' => 'Obesidade Grau II'];
            return ['nivel' => 'alto1', 'ref' => 'Obesidade Grau III'];
        }
    }

    // ----------------------------------------------------------------------
    // 4. GORDURA VISCERAL (GV - Exemplo)
    // Fonte: Padrões comuns de Bioimpedância (Nível 1-59)
    // ----------------------------------------------------------------------
    if ($metric === 'gv') {
        if ($value <= 9) return ['nivel' => 'normal', 'ref' => 'Normal'];
        if ($value <= 14) return ['nivel' => 'alto1', 'ref' => 'Alto'];
        return ['nivel' => 'alto2', 'ref' => 'Muito Alto'];
    }

    // Default (e.g., mb sem referência específica)
    return ['nivel' => 'normal', 'ref' => '—'];
}

/**
 * Metabox de Avatares (CORRIGIDO para usar IMC)
 */
function pab_bi_avatares_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    if (!$pid) { echo '<p>Vincule um paciente para exibir os avatares.</p>'; return; }

    // Lógica para calcular o IMC, que é o correto para esta visualização
    $peso = (float) pab_get($post->ID, 'pab_bi_peso');
    $altura_cm = (float) pab_get($pid, 'pab_altura');
    $altura_m = $altura_cm ? ($altura_cm / 100.0) : null;
    $imc = ($altura_m && $peso) ? round($peso / ($altura_m * $altura_m), 1) : null;
    
    $genero = pab_get($pid, 'pab_genero', 'M');
    $idade_real = pab_calc_idade_real($pid);
    
    // A classificação para os avatares deve ser baseada no IMC
    $class = pab_oms_classificacao('imc', $imc, $genero, $idade_real);
    $nivel = $class['nivel'];
    $prefix = $genero === 'F' ? 'f' : 'm';
    $levels = ['abaixo','normal','acima1','acima2','acima3','alto1','alto2','alto3'];

    echo '<div class="pab-avatars-line" data-count="' . count($levels) . '">';
    foreach ($levels as $lvl) {
        $active = ($lvl === $nivel) ? 'active' : '';
        $img = defined('PAB_URL') ? PAB_URL . "assets/img/avatars/{$prefix}-{$lvl}.png" : '';
        echo '<div class="pab-avatar ' . $active . '" title="' . esc_attr(ucfirst($lvl)) . '">';
        echo '<img src="' . esc_url($img) . '" alt="' . esc_attr($lvl) . '">';
        echo '</div>';
    }
    echo '</div>';

    echo '<div style="padding: 12px; background: #f8f9fa; border-radius: 6px; margin-top: 12px; border-left: 4px solid #228be6;">';
    echo '<p style="margin: 0; font-size: 13px; color: #666; line-height: 1.5;">';
    echo '<strong style="color: #333;">📊 Classificação de IMC:</strong><br>';
    echo '<span style="color: #228be6; font-weight: 600;">' . esc_html(ucfirst($nivel)) . '</span> - ';
    echo esc_html($class['ref']);
    echo ' (IMC: ' . ($imc ? esc_html($imc) : 'N/D') . ')';
    echo '</p>';
    echo '</div>';

    echo '<p class="description" style="margin-top: 12px; font-size: 12px; color: #999; font-style: italic;">';
    echo 'Seleção automática baseada na classificação de IMC (Índice de Massa Corporal) da OMS.';
    echo '</p>';
}


function pab_calc_idade_real($patient_id) {
    $nasc = pab_get($patient_id, 'pab_nascimento');
    if (!$nasc) return null;
    try {
        $dt = new DateTime($nasc);
        $now = new DateTime();
        return (int) $dt->diff($now)->y;
    } catch(Exception $e) { return null; }
}

function pab_bi_comp_tab_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    $genero = pab_get($pid, 'pab_genero', 'M');
    $idade_real = pab_calc_idade_real($pid);
    $altura_cm = pab_get($pid, 'pab_altura'); // Necessário para o cálculo do peso

    $peso = pab_get($post->ID,'pab_bi_peso');
    $mus = pab_get($post->ID,'pab_bi_musculo_esq');
    $idade_corporal = pab_get($post->ID,'pab_bi_idade_corporal');

    // Passa a altura como contexto para a classificação do peso
    $c_peso = pab_oms_classificacao('peso', (float)$peso, $genero, $idade_real, ['altura_cm' => $altura_cm]);
    $c_mus = pab_oms_classificacao('musculo', (float)$mus, $genero, $idade_real);

    $delta_idade = ($idade_real !== null && $idade_corporal !== '') ? ((int)$idade_real - (int)$idade_corporal) : null;
    $delta_color = ($delta_idade !== null && $delta_idade < 0) ? 'red' : 'green';

    ?>
    <table class="widefat fixed">
        <thead><tr><th>Descrição</th><th>Resultado</th><th>Avaliação</th></tr></thead>
        <tbody>
            <tr>
                <td> Peso <div class="pab-ref"> <?php echo esc_html($c_peso['ref']); ?> </div></td>
                <td><?php echo esc_html($peso).' kg'; ?></td>
                <td><span class="pab-badge-<?php echo esc_attr($c_peso['nivel']); ?>"><?php echo esc_html($c_peso['nivel']); ?></span></td>
            </tr>
            <tr>
                <td> Músculo Esquelético <div class="pab-ref"> <?php echo esc_html($c_mus['ref']); ?> </div></td>
                <td><?php echo esc_html($mus).' %'; ?></td>
                <td><span class="pab-badge-<?php echo esc_attr($c_mus['nivel']); ?>"><?php echo esc_html($c_mus['nivel']); ?></span></td>
            </tr>
            <tr>
                <td> Idade Corporal </td>
                <td><?php echo esc_html($idade_corporal).' anos'; ?></td>
                <td><span style="color:<?php echo esc_attr($delta_color); ?>">
                    <?php
                    if ($delta_idade !== null) {
                        echo ($delta_idade > 0 ? '+' : '') . $delta_idade . ' anos';
                    } else {
                        echo '—';
                    }
                    ?>
                </span></td>
            </tr>
        </tbody>
    </table>
    <?php
}

function pab_bi_diag_obes_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    $genero = pab_get($pid, 'pab_genero', 'M');
    $idade_real = pab_calc_idade_real($pid);

    $peso = (float) pab_get($post->ID,'pab_bi_peso');
    $gc = (float) pab_get($post->ID,'pab_bi_gordura_corporal');
    $gv = (float) pab_get($post->ID,'pab_bi_gordura_visc');
    $mb = (float) pab_get($post->ID,'pab_bi_metab_basal');

    $altura_cm = (float) pab_get($pid, 'pab_altura'); // cm
    $altura_m = $altura_cm ? ($altura_cm / 100.0) : null;
    $imc = ($altura_m && $peso) ? round($peso / ($altura_m * $altura_m), 1) : null;

    $c_imc = pab_oms_classificacao('imc', $imc, $genero, $idade_real);
    $c_gc = pab_oms_classificacao('gc', $gc, $genero, $idade_real);
    $c_gv = pab_oms_classificacao('gv', $gv, $genero, $idade_real);
    $c_mb = pab_oms_classificacao('mb', $mb, $genero, $idade_real);

    ?>
    <table class="widefat fixed">
        <thead><tr><th>Descrição</th><th>Resultado</th><th>Avaliação</th></tr></thead>
        <tbody>
            <tr>
                <td> IMC <div class="pab-ref"><?php echo esc_html($c_imc['ref']); ?></div></td>
                <td><?php echo ($imc !== null) ? esc_html($imc) : '—'; ?></td>
                <td><span class="pab-badge-<?php echo esc_attr($c_imc['nivel']); ?>"><?php echo esc_html($c_imc['nivel']); ?></span></td>
            </tr>
            <tr>
                <td> Gordura Corporal <div class="pab-ref"><?php echo esc_html($c_gc['ref']); ?></div></td>
                <td><?php echo esc_html($gc).' %'; ?></td>
                <td><span class="pab-badge-<?php echo esc_attr($c_gc['nivel']); ?>"><?php echo esc_html($c_gc['nivel']); ?></span></td>
            </tr>
            <tr>
                <td> Gordura Visceral <div class="pab-ref"><?php echo esc_html($c_gv['ref']); ?></div></td>
                <td><?php echo esc_html($gv).' nível'; ?></td>
                <td><span class="pab-badge-<?php echo esc_attr($c_gv['nivel']); ?>"><?php echo esc_html($c_gv['nivel']); ?></span></td>
            </tr>
            <tr>
                <td> Metabolismo Basal <div class="pab-ref"><?php echo esc_html($c_mb['ref']); ?></div></td>
                <td><?php echo esc_html($mb).' kcal'; ?></td>
                <td><span class="pab-badge-<?php echo esc_attr($c_mb['nivel']); ?>"><?php echo esc_html($c_mb['nivel']); ?></span></td>
            </tr>
        </tbody>
    </table>
    <?php
}

function pab_bi_historico_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    if (!$pid) { echo '<p>Vincule um paciente para exibir histórico.</p>'; return; }

    $bio_series = new WP_Query([
        'post_type' => 'pab_bioimpedancia',
        'post_parent' => $pid,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC',
        'fields' => 'ids',
    ]);

    $datas = []; $pesos = []; $ref_peso = [];
    $gorduras = []; $musculos = []; $idade_real = pab_calc_idade_real($pid);

    foreach ($bio_series->posts as $bid) {
        $datas[] = get_the_date('Y-m-d', $bid);
        $pesos[] = (float) pab_get($bid,'pab_bi_peso');
        $gorduras[] = (float) pab_get($bid,'pab_bi_gordura_corporal');
        $musculos[] = (float) pab_get($bid,'pab_bi_musculo_esq');
        $ref_peso[] = 75; // Manter o placeholder
    }

    wp_enqueue_script('pab-charts');

    ?>
    <div class="pab-charts">
        <canvas id="pabChartPeso"></canvas>
        <canvas id="pabChartBiComp"></canvas>
        <canvas id="pabChartCompLineBar"></canvas>
        <canvas id="pabChartIdadeCorporal"></canvas>
    </div>
    <script>
    window.PAB_CHART_DATA = {
        datas: <?php echo wp_json_encode($datas); ?>,
        pesos: <?php echo wp_json_encode($pesos); ?>,
        refPeso: <?php echo wp_json_encode($ref_peso); ?>,
        gorduras: <?php echo wp_json_encode($gorduras); ?>,
        musculos: <?php echo wp_json_encode($musculos); ?>,
        idadeReal: <?php echo json_encode($idade_real); ?>,
        idadesCorp: <?php
            echo wp_json_encode(array_map(function($bid){
                return (int) pab_get($bid,'pab_bi_idade_corporal');
            }, $bio_series->posts));
        ?>
    };
    </script>
    <?php
}


// =========================================================================
// 3. SALVAMENTO DOS DADOS (CORRIGIDO)
// =========================================================================

add_action('save_post_pab_bioimpedancia', function($post_id) {
    // 1. Checagens de Segurança
    if (!isset($_POST['pab_bi_nonce']) || !wp_verify_nonce($_POST['pab_bi_nonce'],'pab_bi_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    // 2. Vinculação do Paciente
    // A lógica de vinculação deve estar em uma função separada para evitar loops.
    // Ex: if (isset($_POST['pab_paciente_id'])) { pab_link_patient_on_save(...) }

    // 3. Salvamento dos Campos Numéricos
    $fields = ['pab_bi_peso','pab_bi_gordura_corporal','pab_bi_musculo_esq','pab_bi_gordura_visc','pab_bi_metab_basal','pab_bi_idade_corporal'];

    foreach ($fields as $k) {
        if (isset($_POST[$k]) && $_POST[$k] !== '') {
            update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
        } else {
            delete_post_meta($post_id, $k);
        }
    }
}, 10, 1);