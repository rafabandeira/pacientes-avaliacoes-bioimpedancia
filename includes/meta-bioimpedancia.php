<?php // includes/meta-bioimpedancia.php - CORRIGIDO: Slug e Lógica OMS

if (!defined('ABSPATH')) exit;

// =========================================================================
// 1. REGISTRO DAS METABOXES
// =========================================================================
add_action('add_meta_boxes', function() {
    add_meta_box('pab_bi_paciente', 'Paciente vinculado', 'pab_bi_paciente_cb', 'pab_bioimpedancia', 'side', 'high');
    add_meta_box('pab_bi_dados', 'Dados de Bioimpedância', 'pab_bi_dados_cb', 'pab_bioimpedancia', 'normal', 'high');
    add_meta_box('pab_bi_avatares', 'Avatares (OMS)', 'pab_bi_avatares_cb', 'pab_bioimpedancia', 'normal', 'default');
    add_meta_box('pab_bi_comp_tab', 'Composição corporal', 'pab_bi_comp_tab_cb', 'pab_bioimpedancia', 'normal', 'default');
    add_meta_box('pab_bi_diag_obes', 'Diagnóstico de Obesidade', 'pab_bi_diag_obes_cb', 'pab_bioimpedancia', 'normal', 'default');
    add_meta_box('pab_bi_historico', 'Histórico', 'pab_bi_historico_cb', 'pab_bioimpedancia', 'normal', 'default');
});

// =========================================================================
// 2. CALLBACKS DE EXIBIÇÃO
// =========================================================================

/**
 * Metabox de Paciente Vinculado (Apenas exibição, sem salvamento)
 */
function pab_bi_paciente_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id'); 
    $pid_from_post = isset($_POST['pab_paciente_id']) ? (int) $_POST['pab_paciente_id'] : 0;
    $patient_id_to_show = $pid ?: $pid_from_post;

    if ($patient_id_to_show) {
        $patient_name = pab_get($patient_id_to_show, 'pab_nome', get_the_title($patient_id_to_show));
        
        echo '<p><strong>Paciente:</strong> <a href="' . esc_url(get_edit_post_link($patient_id_to_show)) . '">' . esc_html($patient_name) . '</a></p>';
        echo '<input type="hidden" name="pab_paciente_id" value="' . esc_attr($patient_id_to_show) . '">';
    } else {
        echo '<p>Esta bioimpedância não está vinculada a um paciente.</p>';
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
 * Funções de Classificação OMS/Padrão: ATUALIZADAS
 * * Implementa faixas por Gênero e Idade (usando 60 como corte para idoso/jovem).
 * ATENÇÃO: Os valores são exemplos baseados em padrões comuns e devem ser VALIDADOS.
 */
function pab_oms_classificacao($metric, $value, $genero, $idade) {
    // Retorna se o valor for nulo
    if ($value === '' || $value === null) return ['nivel' => '—', 'ref' => 'Falta dado'];
    
    // Configuração de corte de idade (Adulto vs. Idoso)
    $is_elderly = ($idade !== null && $idade >= 60);

    // ----------------------------------------------------------------------
    // 1. GORDURA CORPORAL (GC - Exemplo com Faixas por Idade/Gênero)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === 'gc') {
        $ranges = [
            // Masculino (M)
            'M' => [
                'jovem' => ['normal' => [11, 21], 'acima1' => [22, 26], 'acima2' => [27, 30], 'alto1' => [30, 100]], // Jovem/Adulto
                'idoso' => ['normal' => [13, 23], 'acima1' => [24, 28], 'acima2' => [29, 32], 'alto1' => [32, 100]], // Idoso (>= 60)
            ],
            // Feminino (F)
            'F' => [
                'jovem' => ['normal' => [18, 28], 'acima1' => [29, 33], 'acima2' => [34, 38], 'alto1' => [38, 100]], 
                'idoso' => ['normal' => [20, 30], 'acima1' => [31, 35], 'acima2' => [36, 40], 'alto1' => [40, 100]], 
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
        // Faixas de IMC Padrão OMS (Não depende de gênero, apenas idade para idosos)
        if ($is_elderly) {
            // Faixas Sugeridas para Idosos (Fonte: Sociedades de Geriatria)
            if ($value < 22) return ['nivel' => 'abaixo', 'ref' => 'Baixo Peso (Idoso)'];
            if ($value < 27) return ['nivel' => 'normal', 'ref' => 'Normal (Idoso)'];
            return ['nivel' => 'acima1', 'ref' => 'Sobrepeso/Obesidade (Idoso)'];
        } else {
            // Faixas Padrão Adulto
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

    // Default (e.g., peso, mb sem referência específica)
    return ['nivel' => 'normal', 'ref' => '—'];
}

function pab_bi_avatares_cb($post) {
    $pid = (int) pab_get($post->ID, 'pab_paciente_id');
    $genero = pab_get($pid, 'pab_genero', 'M');
    $peso = (float) pab_get($post->ID, 'pab_bi_peso');
    $class = pab_oms_classificacao('peso', $peso, $genero, pab_calc_idade_real($pid));
    $nivel = $class['nivel'];
    $prefix = $genero === 'F' ? 'f' : 'm';
    $levels = ['abaixo','normal','acima1','acima2','acima3','alto1','alto2','alto3'];

    echo '<div class="pab-avatars-line">';
    foreach ($levels as $lvl) {
        $active = ($lvl === $nivel) ? 'active' : '';
        $img = defined('PAB_URL') ? PAB_URL . "assets/img/avatars/{$prefix}-{$lvl}.png" : ''; 
        echo '<div class="pab-avatar '.$active.'"><img src="'.esc_url($img).'" alt="'.esc_attr($lvl).'"></div>';
    }
    echo '</div>';
    echo '<p class="description">Seleção automática baseada na avaliação OMS para o peso.</p>';
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

    $peso = pab_get($post->ID,'pab_bi_peso');
    $mus = pab_get($post->ID,'pab_bi_musculo_esq');
    $idade_corporal = pab_get($post->ID,'pab_bi_idade_corporal');

    $c_peso = pab_oms_classificacao('peso', (float)$peso, $genero, $idade_real);
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
                    <?php echo ($delta_idade !== null) ? ($delta_idade) . ' anos' : '—'; ?>
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
    // ATENÇÃO: Se o erro de memória persistir, COMENTE ESTA SEÇÃO e verifique
    // o código da função pab_link_to_patient, que deve estar causando um loop.
    // if (isset($_POST['pab_paciente_id'])) {
        // Se pab_link_to_patient contiver wp_update_post, ele pode causar o loop.
        // A correção de loop deve ser na própria pab_link_to_patient.
        // pab_link_to_patient($post_id, (int)$_POST['pab_paciente_id']);
    // }
    
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