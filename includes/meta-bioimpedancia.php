<?php // includes/meta-bioimpedancia.php - CORRIGIDO: Slug e Lógica OMS

if (!defined("ABSPATH")) {
    exit();
}

// =========================================================================
// 1. REGISTRO DAS METABOXES
// =========================================================================
add_action("add_meta_boxes", function () {
    add_meta_box(
        "pab_bi_paciente",
        "Paciente vinculado",
        "pab_bi_paciente_cb",
        "pab_bioimpedancia",
        "side",
        "high",
    );
    add_meta_box(
        "pab_bi_dados",
        "Dados de Bioimpedância",
        "pab_bi_dados_cb",
        "pab_bioimpedancia",
        "normal",
        "high",
    );
    add_meta_box(
        "pab_bi_avatares",
        "Avatares (OMS)",
        "pab_bi_avatares_cb",
        "pab_bioimpedancia",
        "normal",
        "default",
        ["__back_compat_meta_box" => false, "class" => "postbox-bio-avatars"],
    );
    add_meta_box(
        "pab_bi_comp_tab",
        "Composição corporal",
        "pab_bi_comp_tab_cb",
        "pab_bioimpedancia",
        "normal",
        "default",
    );
    add_meta_box(
        "pab_bi_diag_obes",
        "Diagnóstico de Obesidade",
        "pab_bi_diag_obes_cb",
        "pab_bioimpedancia",
        "normal",
        "default",
    );
    add_meta_box(
        "pab_bi_historico",
        "Histórico",
        "pab_bi_historico_cb",
        "pab_bioimpedancia",
        "normal",
        "default",
    );
});

// =========================================================================
// 2. CALLBACKS DE EXIBIÇÃO
// =========================================================================

/**
 * Metabox de Paciente Vinculado (com botão de visualização)
 */
function pab_bi_paciente_cb($post)
{
    // Adicionar nonce para garantir segurança do salvamento
    wp_nonce_field("pab_bi_save", "pab_bi_nonce");

    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    $pid_from_post = isset($_POST["pab_paciente_id"])
        ? (int) $_POST["pab_paciente_id"]
        : 0;
    $patient_id_to_show = $pid ?: $pid_from_post;

    if (!$patient_id_to_show) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Esta bioimpedância não está vinculada a um paciente. Se chegou pelo botão "Nova Bioimpedância" do paciente, será vinculada automaticamente ao salvar.
        </div>';
        return;
    }

    if ($patient_id_to_show) {
        $patient_name = pab_get(
            $patient_id_to_show,
            "pab_nome",
            get_the_title($patient_id_to_show),
        );

        echo '<div class="pab-fade-in" style="padding: 0;">';
        echo '<div style="margin-bottom: 16px;">';
        echo '<p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">👤 Paciente Vinculado</p>';
        echo '<p style="margin: 0 0 15px 0; font-size: 16px; font-weight: 600;">';
        echo '<a href="' .
            esc_url(get_edit_post_link($patient_id_to_show)) .
            '" style="text-decoration: none; color: #1e40af; transition: color 0.3s;" onmouseover="this.style.color=\'#3b82f6\'" onmouseout="this.style.color=\'#1e40af\'">';
        echo esc_html($patient_name);
        echo "</a></p>";
        echo '<input type="hidden" name="pab_paciente_id" value="' .
            esc_attr($patient_id_to_show) .
            '">';
        echo "</div>";

        if ($post->post_status === "publish") {
            $permalink = get_permalink($post->ID); ?>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                <a href="<?php echo esc_url($permalink); ?>"
                   class="button button-primary button-large"
                   target="_blank"
                   style="width: 100%; text-align: center; display: block; margin-bottom: 16px; text-decoration: none;">
                    🔗 Abrir Relatório Completo
                </a>

                <?php
                // Verificar se o permalink contém "item-orfao", título problemático, "NOVO" ou "TEMP"
                $has_bad_permalink = strpos($post->post_name, 'item-orfao') !== false ||
                                   strpos($post->post_title, 'ITEM ORFAO') !== false ||
                                   strpos($post->post_title, '- NOVO') !== false ||
                                   strpos($post->post_title, '- TEMP') !== false ||
                                   strpos($post->post_name, '-novo') !== false ||
                                   strpos($post->post_name, '-temp') !== false;

                if ($has_bad_permalink): ?>
                <div style="margin-bottom: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <p style="margin: 0 0 8px 0; color: #856404; font-size: 12px;">
                        ⚠️ Este relatório tem um link incorreto<?php
                        if (strpos($post->post_title, '- NOVO') !== false) {
                            echo ' (contém "NOVO" no nome)';
                        } elseif (strpos($post->post_title, '- TEMP') !== false) {
                            echo ' (contém "TEMP" no nome)';
                        } ?>. Clique no botão abaixo para corrigi-lo:
                    </p>
                    <a href="<?php echo esc_url(add_query_arg(['pab_fix_permalink' => $post->ID, 'nonce' => wp_create_nonce('pab_fix_permalink')], admin_url('post.php?action=edit&post=' . $post->ID))); ?>"
                       class="button button-secondary"
                       style="font-size: 11px;">
                        🔧 Corrigir Link
                    </a>
                </div>
                <?php endif; ?>

                <div class="pab-share-container">
                    <p class="pab-share-label">
                        🌐 Link para Compartilhar
                    </p>
                    <input type="text"
                           class="pab-share-input"
                           readonly
                           value="<?php echo esc_attr($permalink); ?>"
                           onclick="this.select(); document.execCommand('copy'); this.style.background='#10b981'; this.style.color='white'; setTimeout(() => { this.style.background='white'; this.style.color='#374151'; }, 1000);">
                    <p class="pab-share-hint">
                        ☝️ Clique para copiar automaticamente
                    </p>
                </div>
            </div>
            <?php
        } else {
             ?>
            <div class="pab-alert pab-alert-warning" style="margin-top: 20px;">
                <strong>⚠️ Atenção:</strong><br>
                Publique esta bioimpedância para gerar o link de compartilhamento com o paciente.
            </div>
            <?php
        }

        echo "</div>";
    } else {
        echo '<div class="pab-alert pab-alert-warning">⚠️ Esta bioimpedância não está vinculada a um paciente.</div>';
    }
}

/**
 * Metabox de Dados de Bioimpedância (Formulário) - DESIGN APRIMORADO
 */
function pab_bi_dados_cb($post)
{
    // Nonce já foi adicionado no metabox do paciente, não duplicar
    $f = [
        "peso" => pab_get($post->ID, "pab_bi_peso"),
        "gc" => pab_get($post->ID, "pab_bi_gordura_corporal"),
        "me" => pab_get($post->ID, "pab_bi_musculo_esq"),
        "gv" => pab_get($post->ID, "pab_bi_gordura_visc"),
        "mb" => pab_get($post->ID, "pab_bi_metab_basal"),
        "idade" => pab_get($post->ID, "pab_bi_idade_corporal"),
    ]; ?>

    <div class="pab-fade-in">
        <div class="pab-grid">
            <label>
                <strong>⚖️ Peso (kg)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_peso"
                       value="<?php echo esc_attr($f["peso"]); ?>"
                       placeholder="Ex: 70.5">
            </label>

            <label>
                <strong>🔥 Gordura Corporal (%)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_gordura_corporal"
                       value="<?php echo esc_attr($f["gc"]); ?>"
                       placeholder="Ex: 18.5">
            </label>

            <label>
                <strong>💪 Músculo Esquelético (%)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_musculo_esq"
                       value="<?php echo esc_attr($f["me"]); ?>"
                       placeholder="Ex: 35.2">
            </label>

            <label>
                <strong>🫀 Gordura Visceral (nível)</strong>
                <input type="number"
                       step="0.1"
                       name="pab_bi_gordura_visc"
                       value="<?php echo esc_attr($f["gv"]); ?>"
                       placeholder="Ex: 8.0">
            </label>

            <label>
                <strong>⚡ Metabolismo Basal (kcal)</strong>
                <input type="number"
                       step="1"
                       name="pab_bi_metab_basal"
                       value="<?php echo esc_attr($f["mb"]); ?>"
                       placeholder="Ex: 1580">
            </label>

            <label>
                <strong>🕐 Idade Corporal (anos)</strong>
                <input type="number"
                       step="1"
                       name="pab_bi_idade_corporal"
                       value="<?php echo esc_attr($f["idade"]); ?>"
                       placeholder="Ex: 28">
            </label>
        </div>

        <div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> As avaliações OMS nas seções abaixo são calculadas automaticamente baseadas no gênero e idade do paciente vinculado.
        </div>
    </div>
    <?php
}

/**
 * Função Auxiliar para Calcular Faixa de Peso Ideal por IMC
 * Fonte: OMS (IMC Normal: 18.5 a 24.9)
 */
function pab_calc_faixa_peso_ideal($altura_cm)
{
    if (!$altura_cm || $altura_cm <= 0) {
        return null;
    }
    $altura_m = $altura_cm / 100.0;
    $imc_min = 18.5;
    $imc_max = 24.9;

    return [
        "min" => round($imc_min * ($altura_m * $altura_m), 1),
        "max" => round($imc_max * ($altura_m * $altura_m), 1),
    ];
}

/**
 * Funções de Classificação OMS/Padrão: ATUALIZADAS
 * * Implementa faixas por Gênero e Idade (usando 60 como corte para idoso/jovem).
 * * Adicionado cálculo de Peso Ideal por IMC.
 * ATENÇÃO: Os valores são exemplos baseados em padrões comuns e devem ser VALIDADOS.
 */
function pab_oms_classificacao($metric, $value, $genero, $idade, $context = [])
{
    // Debug log
    error_log(
        "PAB DEBUG: pab_oms_classificacao chamada com metric=$metric, value=$value, genero=$genero, idade=$idade",
    );

    // Retorna se o valor for nulo ou vazio
    if ($value === "" || $value === null || !is_numeric($value)) {
        error_log("PAB DEBUG: Valor inválido para $metric: $value");
        return ["nivel" => "—", "ref" => "Falta dado"];
    }

    // Validação de gênero
    if (!in_array($genero, ["M", "F"])) {
        error_log("PAB DEBUG: Gênero inválido '$genero', usando M como padrão");
        $genero = "M"; // Default
    }

    // Configuração de corte de idade (Adulto vs. Idoso)
    $is_elderly = $idade !== null && $idade >= 60;

    // ----------------------------------------------------------------------
    // 0. PESO (baseado na faixa de IMC ideal)
    // ----------------------------------------------------------------------
    if ($metric === "peso") {
        $altura_cm = isset($context["altura_cm"])
            ? $context["altura_cm"]
            : null;
        $faixa_ideal = pab_calc_faixa_peso_ideal($altura_cm);

        if (!$faixa_ideal) {
            return ["nivel" => "—", "ref" => "Falta altura"];
        }

        $ref_text =
            "Ideal: " .
            $faixa_ideal["min"] .
            "kg - " .
            $faixa_ideal["max"] .
            "kg";

        if ($value < $faixa_ideal["min"]) {
            return ["nivel" => "abaixo", "ref" => $ref_text];
        }
        if ($value > $faixa_ideal["max"]) {
            return ["nivel" => "acima1", "ref" => $ref_text];
        }
        return ["nivel" => "normal", "ref" => $ref_text];
    }

    // ----------------------------------------------------------------------
    // 1. GORDURA CORPORAL (GC - Exemplo com Faixas por Idade/Gênero)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === "gc") {
        $ranges = [
            "M" => [
                // Masculino
                "jovem" => [
                    "normal" => [11, 21],
                    "acima1" => [22, 26],
                    "acima2" => [27, 30],
                    "alto1" => [31, 100],
                ],
                "idoso" => [
                    "normal" => [13, 23],
                    "acima1" => [24, 28],
                    "acima2" => [29, 32],
                    "alto1" => [33, 100],
                ],
            ],
            "F" => [
                // Feminino
                "jovem" => [
                    "normal" => [18, 28],
                    "acima1" => [29, 33],
                    "acima2" => [34, 38],
                    "alto1" => [39, 100],
                ],
                "idoso" => [
                    "normal" => [20, 30],
                    "acima1" => [31, 35],
                    "acima2" => [36, 40],
                    "alto1" => [41, 100],
                ],
            ],
        ];

        $age_group = $is_elderly ? "idoso" : "jovem";

        // Verificação de segurança dos ranges
        if (!isset($ranges[$genero][$age_group])) {
            return ["nivel" => "—", "ref" => "Erro de configuração"];
        }

        $current_ranges = $ranges[$genero][$age_group];

        if ($value < $current_ranges["normal"][0]) {
            return ["nivel" => "abaixo", "ref" => "Baixa/Essencial"];
        }
        if ($value <= $current_ranges["normal"][1]) {
            return ["nivel" => "normal", "ref" => "Normal"];
        }
        if ($value <= $current_ranges["acima1"][1]) {
            return ["nivel" => "acima1", "ref" => "Limítrofe/Sobrepeso"];
        }
        if ($value <= $current_ranges["acima2"][1]) {
            return ["nivel" => "acima2", "ref" => "Obesidade Moderada"];
        }
        return ["nivel" => "alto1", "ref" => "Obesidade Elevada"];
    }

    // ----------------------------------------------------------------------
    // 2. MÚSCULO ESQUELÉTICO (ME - Exemplo com Faixas por Gênero)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === "musculo") {
        $ranges = [
            "M" => ["abaixo" => 33.3, "normal" => 39.4, "acima1" => 100],
            "F" => ["abaixo" => 24.4, "normal" => 32.8, "acima1" => 100],
        ];

        // Verificação de segurança dos ranges
        if (!isset($ranges[$genero])) {
            return ["nivel" => "—", "ref" => "Erro de configuração"];
        }

        $current_ranges = $ranges[$genero];

        if ($value < $current_ranges["abaixo"]) {
            return ["nivel" => "abaixo", "ref" => "Baixo"];
        }
        if ($value <= $current_ranges["normal"]) {
            return ["nivel" => "normal", "ref" => "Normal"];
        }
        return ["nivel" => "acima1", "ref" => "Alto"];
    }

    // ----------------------------------------------------------------------
    // 3. IMC (Índice de Massa Corporal - Padrão OMS)
    // Fonte: OMS (World Health Organization)
    // ----------------------------------------------------------------------
    if ($metric === "imc") {
        if ($is_elderly) {
            // Faixas Sugeridas para Idosos
            if ($value < 22) {
                return ["nivel" => "abaixo", "ref" => "Baixo Peso (Idoso)"];
            }
            if ($value < 27) {
                return ["nivel" => "normal", "ref" => "Normal (Idoso)"];
            }
            return [
                "nivel" => "acima1",
                "ref" => "Sobrepeso/Obesidade (Idoso)",
            ];
        } else {
            // Faixas Padrão Adulto
            if ($value < 18.5) {
                return ["nivel" => "abaixo", "ref" => "Baixo Peso"];
            }
            if ($value < 25) {
                return ["nivel" => "normal", "ref" => "Normal"];
            }
            if ($value < 30) {
                return ["nivel" => "acima1", "ref" => "Sobrepeso"];
            }
            if ($value < 35) {
                return ["nivel" => "acima2", "ref" => "Obesidade Grau I"];
            }
            if ($value < 40) {
                return ["nivel" => "acima3", "ref" => "Obesidade Grau II"];
            }
            return ["nivel" => "alto1", "ref" => "Obesidade Grau III"];
        }
    }

    // ----------------------------------------------------------------------
    // 4. GORDURA VISCERAL (GV - Exemplo)
    // Fonte: Padrões comuns de Bioimpedância (Nível 1-59)
    // ----------------------------------------------------------------------
    if ($metric === "gv") {
        if ($value <= 9) {
            return ["nivel" => "normal", "ref" => "Normal"];
        }
        if ($value <= 14) {
            return ["nivel" => "alto1", "ref" => "Alto"];
        }
        return ["nivel" => "alto2", "ref" => "Muito Alto"];
    }

    // Default (e.g., mb sem referência específica)
    return ["nivel" => "normal", "ref" => "—"];
}

/**
 * Metabox de Avatares (DESIGN MODERNO - baseado em IMC)
 */
function pab_bi_avatares_cb($post)
{
    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    if (!$pid) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Vincule um paciente para exibir os avatares de classificação corporal.
        </div>';
        return;
    }

    // Lógica para calcular o IMC, que é o correto para esta visualização
    $peso = (float) pab_get($post->ID, "pab_bi_peso");
    $altura_cm = (float) pab_get($pid, "pab_altura");
    $altura_m = $altura_cm ? $altura_cm / 100.0 : null;
    $imc =
        $altura_m && $peso ? round($peso / ($altura_m * $altura_m), 1) : null;

    $genero = pab_get($pid, "pab_genero", "M");
    $idade_real = pab_calc_idade_real($pid);

    // A classificação para os avatares deve ser baseada no IMC
    $class = pab_oms_classificacao("imc", $imc, $genero, $idade_real);
    $nivel = $class["nivel"];
    $prefix = $genero === "F" ? "f" : "m";
    $levels = [
        "abaixo" => "Baixo Peso",
        "normal" => "Normal",
        "acima1" => "Sobrepeso",
        "acima2" => "Obesidade I",
        "acima3" => "Obesidade II",
        "alto1" => "Obesidade III",
        "alto2" => "Muito Alto",
        "alto3" => "Extremo",
    ];

    echo '<div class="pab-fade-in">';

    // Header informativo
    echo '<div style="margin-bottom: 20px; text-align: center;">';
    echo '<p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">';
    echo ($genero === "F" ? "👩" : "👨") .
        " Classificação Corporal - " .
        ($genero === "F" ? "Feminino" : "Masculino");
    echo "</p>";

    if ($imc) {
        $badge_class = "pab-badge pab-badge-" . $nivel;
        echo '<div style="margin: 12px 0;">';
        echo '<span style="font-size: 18px; font-weight: 700; color: #1e293b;">IMC: ' .
            $imc .
            "</span> ";
        echo '<span class="' .
            $badge_class .
            '">' .
            esc_html($class["ref"]) .
            "</span>";
        echo "</div>";
    }
    echo "</div>";

    echo '<div class="pab-avatars-line" data-count="' . count($levels) . '">';
    foreach ($levels as $lvl => $label) {
        $active = $lvl === $nivel ? "active" : "";
        $img = defined("PAB_URL")
            ? PAB_URL . "assets/img/avatars/{$prefix}-{$lvl}.png"
            : "";
        echo '<div class="pab-avatar ' .
            $active .
            '" title="' .
            esc_attr(ucfirst($lvl)) .
            '">';
        echo '<img src="' . esc_url($img) . '" alt="' . esc_attr($lvl) . '">';
        echo "</div>";
    }
    echo "</div>";

    echo '<div style="padding: 12px; background: #f8f9fa; border-radius: 6px; margin-top: 12px; border-left: 4px solid #228be6;">';
    echo '<p style="margin: 0; font-size: 13px; color: #666; line-height: 1.5;">';
    echo '<strong style="color: #333;">📊 Classificação de IMC:</strong><br>';
    echo '<span style="color: #228be6; font-weight: 600;">' .
        esc_html(ucfirst($nivel)) .
        "</span> - ";
    echo esc_html($class["ref"]);
    echo " (IMC: " . ($imc ? esc_html($imc) : "N/D") . ")";
    echo "</p>";
    echo "</div>";
}

function pab_calc_idade_real($patient_id)
{
    $nasc = pab_get($patient_id, "pab_nascimento");
    if (!$nasc) {
        return null;
    }
    try {
        $dt = new DateTime($nasc);
        $now = new DateTime();
        return (int) $dt->diff($now)->y;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Metabox de Composição Corporal - DESIGN MODERNO COM CARDS
 */
function pab_bi_comp_tab_cb($post)
{
    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    if (!$pid) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Vincule um paciente para visualizar as análises de composição corporal.
        </div>';
        return;
    }

    $genero = pab_get($pid, "pab_genero", "M");
    $idade_real = pab_calc_idade_real($pid);
    $altura_cm = pab_get($pid, "pab_altura");

    $peso = pab_get($post->ID, "pab_bi_peso");
    $mus = pab_get($post->ID, "pab_bi_musculo_esq");
    $idade_corporal = pab_get($post->ID, "pab_bi_idade_corporal");

    // Verificar se temos dados suficientes
    if (!$peso && !$mus && !$idade_corporal) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> Preencha os dados de bioimpedância na seção acima para ver as análises detalhadas aqui.
        </div>';
        return;
    }

    // Classificações
    $c_peso = pab_oms_classificacao(
        "peso",
        (float) $peso,
        $genero,
        $idade_real,
        ["altura_cm" => $altura_cm],
    );
    $c_mus = pab_oms_classificacao(
        "musculo",
        (float) $mus,
        $genero,
        $idade_real,
    );

    $delta_idade =
        $idade_real !== null && $idade_corporal !== ""
            ? (int) $idade_corporal - (int) $idade_real
            : null;
    ?>

    <div class="pab-fade-in">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 20px 0;">

            <!-- Card: Peso -->
            <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #3b82f6;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 24px; margin-right: 12px;">⚖️</div>
                    <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Peso Corporal</h4>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="font-size: 28px; font-weight: 700; color: #1e40af;">
                        <?php echo $peso ? esc_html($peso) . " kg" : "—"; ?>
                    </span>

                    <?php if ($peso && $altura_cm): ?>
                        <?php
                        $faixa_ideal = pab_calc_faixa_peso_ideal($altura_cm);
                        if ($faixa_ideal) {
                            $peso_medio_ideal =
                                ($faixa_ideal["min"] + $faixa_ideal["max"]) / 2;
                            $delta_peso = $peso - $peso_medio_ideal;
                            $delta_text =
                                ($delta_peso > 0 ? "+" : "") .
                                number_format($delta_peso, 1) .
                                " kg";
                            $delta_color =
                                $delta_peso > 0
                                    ? "#dc2626"
                                    : ($delta_peso < 0
                                        ? "#0891b2"
                                        : "#059669");
                        }
                        ?>
                        <?php if (isset($delta_text)): ?>
                            <div style="margin-top: 4px;">
                                <span style="font-size: 16px; font-weight: 600; color: <?php echo $delta_color; ?>;">
                                    (<?php echo $delta_text; ?> do ideal)
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ($peso): ?>
                    <div style="margin-bottom: 8px;">
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $c_peso["nivel"],
                        ); ?>">
                            <?php echo esc_html(ucfirst($c_peso["nivel"])); ?>
                        </span>
                    </div>
                    <div class="pab-ref" style="margin: 0;">
                        <?php echo esc_html($c_peso["ref"]); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card: Músculo Esquelético -->
            <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #10b981;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 24px; margin-right: 12px;">💪</div>
                    <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Massa Muscular</h4>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="font-size: 28px; font-weight: 700; color: #047857;">
                        <?php echo $mus ? esc_html($mus) . "%" : "—"; ?>
                    </span>
                    <?php if ($mus && $peso): ?>
                        <div style="margin-top: 4px;">
                            <span style="font-size: 16px; font-weight: 600; color: #047857;">
                                (<?php echo number_format(
                                    ($mus / 100) * $peso,
                                    1,
                                ); ?> kg)
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($mus): ?>
                    <div style="margin-bottom: 8px;">
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $c_mus["nivel"],
                        ); ?>">
                            <?php echo esc_html(ucfirst($c_mus["nivel"])); ?>
                        </span>
                    </div>
                    <div class="pab-ref" style="margin: 0;">
                        <?php echo esc_html($c_mus["ref"]); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card: Idade Corporal -->
            <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #8b5cf6;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 24px; margin-right: 12px;">🕐</div>
                    <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Idade Biológica</h4>
                </div>

                <div style="margin-bottom: 12px;">
                    <span style="font-size: 28px; font-weight: 700; color: #7c3aed;">
                        <?php echo $idade_corporal
                            ? esc_html($idade_corporal) . " anos"
                            : "—"; ?>
                    </span>
                </div>

                <?php if ($idade_corporal && $idade_real !== null): ?>
                    <div style="margin-bottom: 8px;">
                        <?php if ($delta_idade !== null): ?>
                            <?php
                            $delta_text =
                                ($delta_idade > 0 ? "+" : "") .
                                $delta_idade .
                                " anos";
                            $delta_badge =
                                $delta_idade <= 0
                                    ? "normal"
                                    : ($delta_idade > 5
                                        ? "acima2"
                                        : "acima1");
                            $delta_icon = $delta_idade <= 0 ? "👍" : "⚠️";
                            ?>
                            <span class="pab-badge pab-badge-<?php echo $delta_badge; ?>">
                                <?php echo $delta_icon . " " . $delta_text; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="pab-ref" style="margin: 0;">
                        <?php if ($delta_idade <= 0) {
                            echo "Idade corporal " .
                                abs($delta_idade) .
                                " anos mais jovem que a cronológica (" .
                                esc_html($idade_real) .
                                " anos)";
                        } else {
                            echo "Idade corporal " .
                                $delta_idade .
                                " anos mais velha que a cronológica (" .
                                esc_html($idade_real) .
                                " anos)";
                        } ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Resumo Interpretativo -->
        <?php if ($peso && $mus && $idade_corporal): ?>
        <div style="margin-top: 24px; padding: 20px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border: 1px solid #0ea5e9;">
            <h4 style="margin: 0 0 12px 0; color: #0c4a6e; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                📊 Resumo da Composição Corporal
            </h4>
            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #0f172a;">
                <?php
                $resumo = "O paciente apresenta ";
                $resumo .= "peso " . strtolower($c_peso["ref"]) . " e ";
                $resumo .= "massa muscular " . strtolower($c_mus["ref"]) . ". ";

                if ($delta_idade !== null) {
                    if ($delta_idade <= 0) {
                        $resumo .=
                            "A idade corporal indica boa condição física, sendo ";
                        $resumo .=
                            abs($delta_idade) > 0
                                ? abs($delta_idade) .
                                    " anos mais jovem que a idade cronológica."
                                : "equivalente à idade cronológica.";
                    } else {
                        $resumo .=
                            "A idade corporal sugere possível necessidade de melhoria da condição física, sendo ";
                        $resumo .=
                            $delta_idade .
                            " anos mais elevada que a idade cronológica.";
                    }
                }
                echo esc_html($resumo);
                ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Metabox de Diagnóstico de Obesidade - DESIGN MODERNO
 */
function pab_bi_diag_obes_cb($post)
{
    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    if (!$pid) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Vincule um paciente para visualizar o diagnóstico de obesidade.
        </div>';
        return;
    }

    $genero = pab_get($pid, "pab_genero", "M");
    $idade_real = pab_calc_idade_real($pid);

    $peso = (float) pab_get($post->ID, "pab_bi_peso");
    $gc = (float) pab_get($post->ID, "pab_bi_gordura_corporal");
    $gv = (float) pab_get($post->ID, "pab_bi_gordura_visc");
    $mb = (float) pab_get($post->ID, "pab_bi_metab_basal");

    $altura_cm = (float) pab_get($pid, "pab_altura");
    $altura_m = $altura_cm ? $altura_cm / 100.0 : null;
    $imc =
        $altura_m && $peso ? round($peso / ($altura_m * $altura_m), 1) : null;

    // Verificar se temos dados suficientes
    if (!$peso && !$gc && !$gv && !$mb) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> Preencha os dados de bioimpedância para visualizar o diagnóstico completo.
        </div>';
        return;
    }

    $c_imc = pab_oms_classificacao("imc", $imc, $genero, $idade_real);
    $c_gc = pab_oms_classificacao("gc", $gc, $genero, $idade_real);
    $c_gv = pab_oms_classificacao("gv", $gv, $genero, $idade_real);
    $c_mb = pab_oms_classificacao("mb", $mb, $genero, $idade_real);
    ?>

    <div class="pab-fade-in">
        <!-- Header do Diagnóstico -->
        <div style="text-align: center; margin-bottom: 24px; padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border: 1px solid #f59e0b;">
            <h4 style="margin: 0 0 8px 0; color: #92400e; font-size: 16px; font-weight: 600;">
                🏥 Diagnóstico de Obesidade
            </h4>
            <p style="margin: 0; font-size: 13px; color: #78350f;">
                Análise baseada em diretrizes da OMS e padrões de bioimpedância
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin: 20px 0;">

            <!-- Card: IMC -->
            <div style="background: white; border-radius: 12px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #3b82f6; position: relative; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div>
                        <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">📐 IMC</h5>
                        <p style="margin: 4px 0 0 0; font-size: 20px; font-weight: 700; color: #1e40af;">
                            <?php echo $imc !== null ? esc_html($imc) : "—"; ?>
                        </p>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">📊</div>
                </div>
                <?php if ($imc): ?>
                <div style="margin-bottom: 8px;">
                    <span class="pab-badge pab-badge-<?php echo esc_attr(
                        $c_imc["nivel"],
                    ); ?>">
                        <?php echo esc_html($c_imc["ref"]); ?>
                    </span>
                </div>
                <div class="pab-ref" style="margin: 0; font-size: 11px;">
                    Índice de Massa Corporal
                </div>
                <?php endif; ?>
            </div>

            <!-- Card: Gordura Corporal -->
            <div style="background: white; border-radius: 12px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #f59e0b; position: relative; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div>
                        <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">🔥 Gordura</h5>
                        <p style="margin: 4px 0 0 0; font-size: 20px; font-weight: 700; color: #d97706;">
                            <?php echo $gc ? esc_html($gc) . "%" : "—"; ?>
                            <?php if ($gc && $peso): ?>
                                <br>
                                <span style="font-size: 14px; font-weight: 600; color: #d97706;">
                                    (<?php echo number_format(
                                        ($gc / 100) * $peso,
                                        1,
                                    ); ?> kg)
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">🎯</div>
                </div>
                <?php if ($gc): ?>
                <div style="margin-bottom: 8px;">
                    <span class="pab-badge pab-badge-<?php echo esc_attr(
                        $c_gc["nivel"],
                    ); ?>">
                        <?php echo esc_html($c_gc["ref"]); ?>
                    </span>
                </div>
                <div class="pab-ref" style="margin: 0; font-size: 11px;">
                    Percentual de gordura corporal
                </div>
                <?php endif; ?>
            </div>

            <!-- Card: Gordura Visceral -->
            <div style="background: white; border-radius: 12px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #dc2626; position: relative; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div>
                        <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">🫀 Visceral</h5>
                        <p style="margin: 4px 0 0 0; font-size: 20px; font-weight: 700; color: #dc2626;">
                            <?php echo $gv ? esc_html($gv) : "—"; ?>
                        </p>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">⚠️</div>
                </div>
                <?php if ($gv): ?>
                <div style="margin-bottom: 8px;">
                    <span class="pab-badge pab-badge-<?php echo esc_attr(
                        $c_gv["nivel"],
                    ); ?>">
                        <?php echo esc_html($c_gv["ref"]); ?>
                    </span>
                </div>
                <div class="pab-ref" style="margin: 0; font-size: 11px;">
                    Nível de gordura interna (1-59)
                </div>
                <?php endif; ?>
            </div>

            <!-- Card: Metabolismo -->
            <div style="background: white; border-radius: 12px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #10b981; position: relative; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div>
                        <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">⚡ Metabolismo</h5>
                        <p style="margin: 4px 0 0 0; font-size: 20px; font-weight: 700; color: #047857;">
                            <?php echo $mb ? esc_html($mb) . " kcal" : "—"; ?>
                        </p>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">🔋</div>
                </div>
                <?php if ($mb): ?>
                <div style="margin-bottom: 8px;">
                    <span class="pab-badge pab-badge-<?php echo esc_attr(
                        $c_mb["nivel"],
                    ); ?>">
                        <?php echo esc_html($c_mb["ref"]); ?>
                    </span>
                </div>
                <div class="pab-ref" style="margin: 0; font-size: 11px;">
                    Taxa metabólica basal
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Diagnóstico Consolidado -->
        <?php if ($imc && $gc && $gv): ?>
        <div style="margin-top: 24px; padding: 20px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border: 1px solid #0ea5e9;">
            <h4 style="margin: 0 0 16px 0; color: #0c4a6e; font-size: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center;">
                <span style="margin-right: 8px;">🩺</span> Diagnóstico Consolidado
            </h4>

            <?php
            $niveis_preocupantes = 0;
            $alertas = [];

            if (
                in_array($c_imc["nivel"], [
                    "acima1",
                    "acima2",
                    "acima3",
                    "alto1",
                    "alto2",
                    "alto3",
                ])
            ) {
                $niveis_preocupantes++;
                $alertas[] = "IMC elevado (" . $c_imc["ref"] . ")";
            }
            if (
                in_array($c_gc["nivel"], [
                    "acima1",
                    "acima2",
                    "acima3",
                    "alto1",
                    "alto2",
                    "alto3",
                ])
            ) {
                $niveis_preocupantes++;
                $alertas[] = "Gordura corporal elevada (" . $c_gc["ref"] . ")";
            }
            if (in_array($c_gv["nivel"], ["alto1", "alto2", "alto3"])) {
                $niveis_preocupantes++;
                $alertas[] = "Gordura visceral alta (nível " . $gv . ")";
            }

            if ($niveis_preocupantes === 0): ?>
                <div style="display: flex; align-items: center; padding: 12px 16px; background: #dcfce7; border-radius: 8px; border-left: 4px solid #16a34a;">
                    <span style="font-size: 24px; margin-right: 12px;">✅</span>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: #15803d;">Composição Corporal Saudável</p>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #166534;">Todos os indicadores estão dentro dos parâmetros normais para idade e gênero.</p>
                    </div>
                </div>
            <?php elseif ($niveis_preocupantes === 1): ?>
                <div style="display: flex; align-items: center; padding: 12px 16px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <span style="font-size: 24px; margin-right: 12px;">⚠️</span>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: #92400e;">Atenção - Monitoramento Recomendado</p>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #78350f;">
                            Detectado: <?php echo implode(
                                ", ",
                                $alertas,
                            ); ?>. Recomenda-se acompanhamento nutricional.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div style="display: flex; align-items: center; padding: 12px 16px; background: #fecaca; border-radius: 8px; border-left: 4px solid #dc2626;">
                    <span style="font-size: 24px; margin-right: 12px;">🚨</span>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: #b91c1c;">Alto Risco - Intervenção Necessária</p>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #7f1d1d;">
                            Múltiplos indicadores alterados: <?php echo implode(
                                ", ",
                                $alertas,
                            ); ?>.
                            Recomenda-se avaliação médica e nutricional urgente.
                        </p>
                    </div>
                </div>
            <?php endif;
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Metabox de Histórico - DESIGN MODERNO COM GRÁFICOS
 */
function pab_bi_historico_cb($post)
{
    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    if (!$pid) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Vincule um paciente para visualizar o histórico de bioimpedâncias.
        </div>';
        return;
    }

    $bio_series = new WP_Query([
        "post_type" => "pab_bioimpedancia",
        "post_parent" => $pid,
        "posts_per_page" => -1,
        "orderby" => "date",
        "order" => "ASC",
        "fields" => "ids",
    ]);

    $datas = [];
    $pesos = [];
    $ref_peso = [];
    $gorduras = [];
    $musculos = [];
    $idade_real = pab_calc_idade_real($pid);

    foreach ($bio_series->posts as $bid) {
        // Garantir que $bid é um inteiro (ID do post)
        $bid = (int) $bid;
        $datas[] = get_the_date("Y-m-d", $bid);
        $pesos[] = (float) pab_get($bid, "pab_bi_peso");
        $gorduras[] = (float) pab_get($bid, "pab_bi_gordura_corporal");
        $musculos[] = (float) pab_get($bid, "pab_bi_musculo_esq");
        $ref_peso[] = 75; // Manter o placeholder
    }

    // Verificar se há dados suficientes
    if (empty($bio_series->posts) || count($bio_series->posts) < 1) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> Ainda não há histórico de bioimpedâncias para este paciente. Este será o primeiro registro.
        </div>';
        return;
    }

    wp_enqueue_script("pab-charts");
    ?>

    <div class="pab-fade-in">
        <!-- Header do Histórico -->
        <div style="text-align: center; margin-bottom: 24px; padding: 16px; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-radius: 12px; border: 1px solid #0ea5e9;">
            <h4 style="margin: 0 0 8px 0; color: #0c4a6e; font-size: 16px; font-weight: 600;">
                📈 Histórico de Evolução
            </h4>
            <p style="margin: 0; font-size: 13px; color: #075985;">
                <?php echo count(
                    $bio_series->posts,
                ); ?> avaliações registradas • Acompanhamento temporal da composição corporal
            </p>
        </div>

        <!-- Container dos Gráficos -->
        <div class="pab-charts">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">

                <!-- Gráfico de Peso -->
                <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <h5 style="margin: 0 0 16px 0; color: #374151; font-size: 14px; font-weight: 600; text-align: center; display: flex; align-items: center; justify-content: center;">
                        <span style="margin-right: 8px;">⚖️</span> Evolução do Peso
                    </h5>
                    <canvas id="pabChartPeso"></canvas>
                </div>

                <!-- Gráfico de Composição -->
                <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <h5 style="margin: 0 0 16px 0; color: #374151; font-size: 14px; font-weight: 600; text-align: center; display: flex; align-items: center; justify-content: center;">
                        <span style="margin-right: 8px;">🔥</span> Gordura vs Músculo
                    </h5>
                    <canvas id="pabChartBiComp"></canvas>
                </div>

            </div>

            <!-- Gráficos em linha completa -->
            <div style="margin-top: 24px;">
                <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-bottom: 20px;">
                    <h5 style="margin: 0 0 16px 0; color: #374151; font-size: 14px; font-weight: 600; text-align: center; display: flex; align-items: center; justify-content: center;">
                        <span style="margin-right: 8px;">📊</span> Análise Comparativa Completa
                    </h5>
                    <canvas id="pabChartCompLineBar"></canvas>
                </div>

                <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <h5 style="margin: 0 0 16px 0; color: #374151; font-size: 14px; font-weight: 600; text-align: center; display: flex; align-items: center; justify-content: center;">
                        <span style="margin-right: 8px;">🕐</span> Evolução da Idade Corporal
                    </h5>
                    <canvas id="pabChartIdadeCorporal"></canvas>
                </div>
            </div>
        </div>

        <!-- Resumo Estatístico -->
        <div style="margin-top: 24px; padding: 20px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border: 1px solid #0ea5e9;">
            <h4 style="margin: 0 0 16px 0; color: #0c4a6e; font-size: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center;">
                <span style="margin-right: 8px;">📋</span> Resumo Estatístico
            </h4>

            <?php
            // Identificar a posição da bioimpedância atual no histórico
            $current_bio_id = (int) $post->ID;
            $current_bio_position = -1;
            $total_avaliacoes = count($bio_series->posts);

            // Encontrar a posição da bioimpedância atual
            for ($i = 0; $i < $total_avaliacoes; $i++) {
                if ((int) $bio_series->posts[$i] === $current_bio_id) {
                    $current_bio_position = $i;
                    break;
                }
            }

            // Se não encontrou a bioimpedância atual (primeira vez editando), não mostrar resumo
            if ($current_bio_position === -1) {
                echo '<div class="pab-alert pab-alert-info">
                    <strong>ℹ️ Informação:</strong> O resumo estatístico será exibido após salvar a bioimpedância.
                </div>';
                return;
            }

            // Se é a primeira bioimpedância, não há comparação possível
            if ($current_bio_position === 0) {
                echo '<div class="pab-alert pab-alert-info">
                    <strong>ℹ️ Informação:</strong> Esta é a primeira bioimpedância registrada. O resumo estatístico estará disponível a partir da segunda avaliação.
                </div>';
                return;
            }

            // Pegar dados até a posição atual (inclusive)
            $peso_atual = $pesos[$current_bio_position];
            $peso_inicial = $pesos[0];
            $variacao_peso_inicial = $peso_atual - $peso_inicial;

            $gordura_atual = $gorduras[$current_bio_position];
            $gordura_inicial = $gorduras[0];
            $variacao_gordura_inicial = $gordura_atual - $gordura_inicial;

            // Calcular variação absoluta de gordura em kg (desde o início)
            $massa_gordura_inicial = ($gordura_inicial / 100) * $peso_inicial;
            $massa_gordura_atual = ($gordura_atual / 100) * $peso_atual;
            $variacao_gordura_kg_inicial =
                $massa_gordura_atual - $massa_gordura_inicial;

            $musculo_atual = $musculos[$current_bio_position];
            $musculo_inicial = $musculos[0];
            $variacao_musculo_inicial = $musculo_atual - $musculo_inicial;

            // Calcular variação absoluta de músculo em kg (desde o início)
            $massa_musculo_inicial = ($musculo_inicial / 100) * $peso_inicial;
            $massa_musculo_atual = ($musculo_atual / 100) * $peso_atual;
            $variacao_musculo_kg_inicial =
                $massa_musculo_atual - $massa_musculo_inicial;

            // Calcular estatísticas para comparação com a bioimpedância anterior
            $variacao_peso_ultima = 0;
            $variacao_gordura_ultima = 0;
            $variacao_gordura_kg_ultima = 0;
            $variacao_musculo_ultima = 0;
            $variacao_musculo_kg_ultima = 0;
            $dias_ultima_avaliacao = 0;
            $has_previous = $current_bio_position > 0;

            if ($has_previous) {
                // Pegar a bioimpedância anterior à atual
                $peso_anterior = $pesos[$current_bio_position - 1];
                $gordura_anterior = $gorduras[$current_bio_position - 1];
                $musculo_anterior = $musculos[$current_bio_position - 1];
                $data_anterior = $datas[$current_bio_position - 1];

                $variacao_peso_ultima = $peso_atual - $peso_anterior;
                $variacao_gordura_ultima = $gordura_atual - $gordura_anterior;
                $variacao_musculo_ultima = $musculo_atual - $musculo_anterior;

                // Calcular variação absoluta em kg (desde a anterior)
                $massa_gordura_anterior =
                    ($gordura_anterior / 100) * $peso_anterior;
                $massa_musculo_anterior =
                    ($musculo_anterior / 100) * $peso_anterior;
                $variacao_gordura_kg_ultima =
                    $massa_gordura_atual - $massa_gordura_anterior;
                $variacao_musculo_kg_ultima =
                    $massa_musculo_atual - $massa_musculo_anterior;

                $data_atual_timestamp = strtotime(
                    $datas[$current_bio_position],
                );
                $data_anterior_timestamp = strtotime($data_anterior);
                $dias_ultima_avaliacao =
                    ($data_atual_timestamp - $data_anterior_timestamp) /
                    (60 * 60 * 24);
            }

            $data_inicial = $datas[0];
            $data_atual = $datas[$current_bio_position];
            $dias_acompanhamento_total =
                (strtotime($data_atual) - strtotime($data_inicial)) /
                (60 * 60 * 24);
            ?>

            <?php if ($has_previous): ?>
            <!-- Comparativo desde a bioimpedância ANTERIOR -->
            <div style="margin-bottom: 20px;">
                <h5 style="margin: 0 0 12px 0; color: #0c4a6e; font-size: 13px; font-weight: 600; text-align: center; padding: 8px; background: rgba(16, 185, 129, 0.1); border-radius: 6px;">
                    🔄 Evolução desde a ANTERIOR (<?php echo date(
                        "d/m/Y",
                        strtotime($datas[$current_bio_position - 1]),
                    ); ?>) até ATUAL (<?php echo date(
    "d/m/Y",
    strtotime($data_atual),
); ?>)
                </h5>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Período</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: #0c4a6e;">
                            <?php echo round($dias_ultima_avaliacao); ?> dias
                        </p>
                    </div>

                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Peso</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: <?php echo $variacao_peso_ultima >=
                        0
                            ? "#dc2626"
                            : "#10b981"; ?>;">
                            <?php echo ($variacao_peso_ultima >= 0 ? "+" : "") .
                                number_format($variacao_peso_ultima, 1); ?> kg
                        </p>
                    </div>

                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Gordura</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: <?php echo $variacao_gordura_ultima >=
                        0
                            ? "#dc2626"
                            : "#10b981"; ?>;">
                            <?php echo ($variacao_gordura_ultima >= 0
                                ? "+"
                                : "") .
                                number_format($variacao_gordura_ultima, 1); ?>%
                        </p>
                        <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 600; color: <?php echo $variacao_gordura_kg_ultima >=
                        0
                            ? "#dc2626"
                            : "#10b981"; ?>;">
                            (<?php echo ($variacao_gordura_kg_ultima >= 0
                                ? "+"
                                : "") .
                                number_format(
                                    $variacao_gordura_kg_ultima,
                                    1,
                                ); ?> kg)
                        </p>
                    </div>

                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Músculo</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: <?php echo $variacao_musculo_ultima >=
                        0
                            ? "#10b981"
                            : "#dc2626"; ?>;">
                            <?php echo ($variacao_musculo_ultima >= 0
                                ? "+"
                                : "") .
                                number_format($variacao_musculo_ultima, 1); ?>%
                        </p>
                        <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 600; color: <?php echo $variacao_musculo_kg_ultima >=
                        0
                            ? "#10b981"
                            : "#dc2626"; ?>;">
                            (<?php echo ($variacao_musculo_kg_ultima >= 0
                                ? "+"
                                : "") .
                                number_format(
                                    $variacao_musculo_kg_ultima,
                                    1,
                                ); ?> kg)
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Comparativo desde o INÍCIO -->
            <div>
                <h5 style="margin: 0 0 12px 0; color: #0c4a6e; font-size: 13px; font-weight: 600; text-align: center; padding: 8px; background: rgba(14, 165, 233, 0.1); border-radius: 6px;">
                    📈 Evolução desde o INÍCIO (<?php echo date(
                        "d/m/Y",
                        strtotime($data_inicial),
                    ); ?>) até ATUAL (<?php echo date("d/m/Y", strtotime($data_atual)); ?>)
                </h5>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Período</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: #0c4a6e;">
                            <?php echo round(
                                $dias_acompanhamento_total,
                            ); ?> dias
                        </p>
                    </div>

                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Peso</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: <?php echo $variacao_peso_inicial >=
                        0
                            ? "#dc2626"
                            : "#10b981"; ?>;">
                            <?php echo ($variacao_peso_inicial >= 0
                                ? "+"
                                : "") .
                                number_format($variacao_peso_inicial, 1); ?> kg
                        </p>
                    </div>

                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Gordura</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: <?php echo $variacao_gordura_inicial >=
                        0
                            ? "#dc2626"
                            : "#10b981"; ?>;">
                            <?php echo ($variacao_gordura_inicial >= 0
                                ? "+"
                                : "") .
                                number_format($variacao_gordura_inicial, 1); ?>%
                        </p>
                        <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 600; color: <?php echo $variacao_gordura_kg_inicial >=
                        0
                            ? "#dc2626"
                            : "#10b981"; ?>;">
                            (<?php echo ($variacao_gordura_kg_inicial >= 0
                                ? "+"
                                : "") .
                                number_format(
                                    $variacao_gordura_kg_inicial,
                                    1,
                                ); ?> kg)
                        </p>
                    </div>

                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Músculo</p>
                        <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: 700; color: <?php echo $variacao_musculo_inicial >=
                        0
                            ? "#10b981"
                            : "#dc2626"; ?>;">
                            <?php echo ($variacao_musculo_inicial >= 0
                                ? "+"
                                : "") .
                                number_format($variacao_musculo_inicial, 1); ?>%
                        </p>
                        <p style="margin: 2px 0 0 0; font-size: 14px; font-weight: 600; color: <?php echo $variacao_musculo_kg_inicial >=
                        0
                            ? "#10b981"
                            : "#dc2626"; ?>;">
                            (<?php echo ($variacao_musculo_kg_inicial >= 0
                                ? "+"
                                : "") .
                                number_format(
                                    $variacao_musculo_kg_inicial,
                                    1,
                                ); ?> kg)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    window.PAB_CHART_DATA = {
        datas: <?php echo wp_json_encode($datas); ?>,
        pesos: <?php echo wp_json_encode($pesos); ?>,
        refPeso: <?php echo wp_json_encode($ref_peso); ?>,
        gorduras: <?php echo wp_json_encode($gorduras); ?>,
        musculos: <?php echo wp_json_encode($musculos); ?>,
        idadeReal: <?php echo json_encode($idade_real); ?>,
        idadesCorp: <?php echo wp_json_encode(
            array_map(function ($bid) {
                return (int) pab_get((int) $bid, "pab_bi_idade_corporal");
            }, $bio_series->posts),
        ); ?>
    };
    </script>
    <?php
}

// =========================================================================
// 3. SALVAMENTO DOS DADOS (CORRIGIDO)
// =========================================================================

add_action(
    "save_post_pab_bioimpedancia",
    function ($post_id) {
        // Debug log
        error_log("PAB DEBUG: Iniciando salvamento bioimpedancia ID: $post_id");
        error_log(
            "PAB DEBUG: Ação atual: " .
                (isset($_REQUEST["action"])
                    ? $_REQUEST["action"]
                    : "não definida"),
        );
        error_log(
            "PAB DEBUG: POST data keys: " . implode(", ", array_keys($_POST)),
        );

        // NÃO processar se for operação de lixo/exclusão
        $current_post = get_post($post_id);
        if (
            $current_post &&
            in_array($current_post->post_status, ["trash", "inherit"])
        ) {
            error_log(
                "PAB DEBUG: Post em lixo ou herdado (status: {$current_post->post_status}), não processando",
            );
            return;
        }

        // NÃO processar se for uma ação de lixo via REQUEST
        if (
            isset($_REQUEST["action"]) &&
            in_array($_REQUEST["action"], ["trash", "delete", "untrash"])
        ) {
            error_log(
                "PAB DEBUG: Ação de lixo/exclusão detectada via REQUEST ({$_REQUEST["action"]}), não processando",
            );
            return;
        }

        // NÃO processar se for uma ação de lixo via POST
        if (
            isset($_POST["action"]) &&
            in_array($_POST["action"], ["trash", "delete", "untrash"])
        ) {
            error_log(
                "PAB DEBUG: Ação de lixo/exclusão detectada via POST ({$_POST["action"]}), não processando",
            );
            return;
        }

        // Prevenir loops infinitos
        static $processing = [];
        if (isset($processing[$post_id])) {
            error_log(
                "PAB DEBUG: Loop detectado para post $post_id, abortando",
            );
            return;
        }
        $processing[$post_id] = true;

        // 1. Checagens de Segurança
        $has_valid_nonce = isset($_POST["pab_bi_nonce"]) &&
                          wp_verify_nonce($_POST["pab_bi_nonce"], "pab_bi_save");

        if (!$has_valid_nonce) {
            error_log("PAB DEBUG: Nonce inválido para post $post_id - mas tentando salvar pab_paciente_id se disponível");

            // Se não há nonce válido, só salvar o pab_paciente_id se estiver no POST
            if (isset($_POST["pab_paciente_id"])) {
                $patient_id = (int) $_POST["pab_paciente_id"];
                error_log("PAB DEBUG: Salvando apenas pab_paciente_id=$patient_id para post $post_id (sem nonce)");
                pab_link_to_patient($post_id, $patient_id);
            }

            unset($processing[$post_id]);
            return;
        }

        // 1.1. Verificar capabilities
        if (!current_user_can("edit_post", $post_id)) {
            error_log(
                "PAB DEBUG: Usuário sem permissão para editar post $post_id",
            );
            unset($processing[$post_id]);
            return;
        }
        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            error_log(
                "PAB DEBUG: Autosave detectado para post $post_id, ignorando",
            );
            unset($processing[$post_id]);
            return;
        }
        if (wp_is_post_revision($post_id)) {
            error_log(
                "PAB DEBUG: Revisão detectada para post $post_id, ignorando",
            );
            unset($processing[$post_id]);
            return;
        }

        // 2. Vinculação do Paciente
        if (isset($_POST["pab_paciente_id"])) {
            $patient_id = (int) $_POST["pab_paciente_id"];
            error_log(
                "PAB DEBUG: Vinculando bioimpedancia $post_id ao paciente $patient_id",
            );
            pab_link_to_patient($post_id, $patient_id);
        } else {
            error_log(
                "PAB DEBUG: Nenhum paciente_id encontrado no POST para bioimpedancia $post_id",
            );
        }

        // 3. Salvamento dos Campos Numéricos
        $fields = [
            "pab_bi_peso",
            "pab_bi_gordura_corporal",
            "pab_bi_musculo_esq",
            "pab_bi_gordura_visc",
            "pab_bi_metab_basal",
            "pab_bi_idade_corporal",
        ];

        foreach ($fields as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== "") {
                $value = sanitize_text_field($_POST[$k]);
                error_log("PAB DEBUG: Salvando $k = $value para post $post_id");
                update_post_meta($post_id, $k, $value);
            } else {
                error_log(
                    "PAB DEBUG: Removendo meta $k para post $post_id (valor vazio)",
                );
                delete_post_meta($post_id, $k);
            }
        }

        // 4. Atualizar título com ID real se necessário
        $current_post = get_post($post_id);
        if (
            $current_post &&
            strpos($current_post->post_title, "- NOVO") !== false
        ) {
            $patient_id = (int) get_post_meta(
                $post_id,
                "pab_paciente_id",
                true,
            );
            if ($patient_id) {
                $patient_name =
                    get_the_title($patient_id) ?: "Paciente Sem Nome";
                $new_title = trim("$patient_name - Bioimpedância - $post_id");
                $new_slug = sanitize_title($new_title);

                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    [
                        "post_title" => $new_title,
                        "post_name" => $new_slug
                    ],
                    ["ID" => $post_id],
                    ["%s", "%s"],
                    ["%d"],
                );
                clean_post_cache($post_id);

                error_log("PAB DEBUG: Título atualizado para: $new_title");
            }
        }

        error_log(
            "PAB DEBUG: Finalizando salvamento bioimpedancia ID: $post_id",
        );

        // Limpar flag de processamento
        unset($processing[$post_id]);
    },
    10,
    1,
);

/**
 * Hook específico para controlar o status dos posts de bioimpedância
 * Executado ANTES do save_post para garantir o status correto
 */
add_action(
    "wp_insert_post_data",
    function ($data, $postarr) {
        // Só processar bioimpedâncias
        if ($data["post_type"] !== "pab_bioimpedancia") {
            return $data;
        }

        error_log(
            "PAB DEBUG: wp_insert_post_data - Status original: {$data["post_status"]}",
        );
        error_log(
            "PAB DEBUG: wp_insert_post_data - Ação REQUEST: " .
                (isset($_REQUEST["action"])
                    ? $_REQUEST["action"]
                    : "não definida"),
        );

        // NÃO interferir com operações de lixo, exclusão ou outros status especiais
        if (
            in_array($data["post_status"], [
                "trash",
                "inherit",
                "private",
                "future",
                "pending",
            ])
        ) {
            error_log(
                "PAB DEBUG: Status especial detectado ({$data["post_status"]}), não interferindo",
            );
            return $data;
        }

        // NÃO interferir se for uma operação de lixo via REQUEST
        if (
            isset($_REQUEST["action"]) &&
            in_array($_REQUEST["action"], ["trash", "delete", "untrash"])
        ) {
            error_log(
                "PAB DEBUG: Operação de lixo/exclusão detectada via REQUEST action ({$_REQUEST["action"]}), não interferindo",
            );
            return $data;
        }

        // NÃO interferir se for uma operação de lixo via POST
        if (
            isset($_POST["action"]) &&
            in_array($_POST["action"], ["trash", "delete", "untrash"])
        ) {
            error_log(
                "PAB DEBUG: Operação de lixo/exclusão detectada via POST action ({$_POST["action"]}), não interferindo",
            );
            return $data;
        }

        // NÃO interferir se for uma operação de bulk action
        if (
            isset($_POST["action2"]) &&
            in_array($_POST["action2"], ["trash", "delete", "untrash"])
        ) {
            error_log(
                "PAB DEBUG: Operação de bulk action detectada ({$_POST["action2"]}), não interferindo",
            );
            return $data;
        }

        // Debug dos dados recebidos (apenas para operações normais)
        error_log(
            "PAB DEBUG: wp_insert_post_data - Status original: " .
                $data["post_status"],
        );
        error_log(
            "PAB DEBUG: wp_insert_post_data - POST status: " .
                (isset($_POST["post_status"])
                    ? $_POST["post_status"]
                    : "não definido"),
        );
        error_log(
            "PAB DEBUG: wp_insert_post_data - Botão publish: " .
                (isset($_POST["publish"]) ? "SIM" : "NÃO"),
        );
        error_log(
            "PAB DEBUG: wp_insert_post_data - Botão save: " .
                (isset($_POST["save"]) ? "SIM" : "NÃO"),
        );

        // Detectar intenção de publicar através de múltiplas verificações
        $wants_to_publish = false;

        // 1. Status explícito
        if (
            isset($_POST["post_status"]) &&
            $_POST["post_status"] === "publish"
        ) {
            $wants_to_publish = true;
            error_log("PAB DEBUG: Publicação detectada via post_status");
        }

        // 2. Botão de publicar
        if (isset($_POST["publish"])) {
            $wants_to_publish = true;
            error_log("PAB DEBUG: Publicação detectada via botão publish");
        }

        // 3. Post sendo salvo de auto-draft (novo post)
        if ($data["post_status"] === "auto-draft" && !isset($_POST["save"])) {
            $wants_to_publish = true;
            error_log(
                "PAB DEBUG: Publicação detectada - convertendo auto-draft",
            );
        }

        // 4. Se já existe e não é rascunho explícito
        if (isset($postarr["ID"]) && $postarr["ID"] > 0) {
            $existing_post = get_post($postarr["ID"]);
            if (
                $existing_post &&
                $existing_post->post_status === "publish" &&
                !isset($_POST["save"])
            ) {
                $wants_to_publish = true;
                error_log(
                    "PAB DEBUG: Mantendo status publish de post existente",
                );
            }
        }

        // Aplicar o status correto apenas se for uma operação normal
        if ($wants_to_publish && $data["post_status"] !== "trash") {
            $data["post_status"] = "publish";
            error_log("PAB DEBUG: Status definido como publish");
        } else {
            error_log(
                "PAB DEBUG: Status mantido como: " . $data["post_status"],
            );
        }

        return $data;
    },
    99,
    2,
);

/**
 * Hook para monitorar operações de exclusão/lixo
 */
add_action(
    "wp_trash_post",
    function ($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === "pab_bioimpedancia") {
            error_log(
                "PAB DEBUG: wp_trash_post chamado para bioimpedância ID: $post_id",
            );
            error_log("PAB DEBUG: Status antes do lixo: " . $post->post_status);
        }
    },
    10,
    1,
);

add_action(
    "trashed_post",
    function ($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === "pab_bioimpedancia") {
            error_log(
                "PAB DEBUG: trashed_post executado para bioimpedância ID: $post_id",
            );
            error_log("PAB DEBUG: Status após lixo: " . $post->post_status);
        }
    },
    10,
    1,
);

add_action(
    "wp_untrash_post",
    function ($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === "pab_bioimpedancia") {
            error_log(
                "PAB DEBUG: wp_untrash_post chamado para bioimpedância ID: $post_id",
            );
            error_log(
                "PAB DEBUG: Status antes da restauração: " . $post->post_status,
            );
        }
    },
    10,
    1,
);

add_action(
    "untrashed_post",
    function ($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === "pab_bioimpedancia") {
            error_log(
                "PAB DEBUG: untrashed_post executado para bioimpedância ID: $post_id",
            );
            error_log(
                "PAB DEBUG: Status após restauração: " . $post->post_status,
            );
        }
    },
    10,
    1,
);

add_action(
    "before_delete_post",
    function ($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === "pab_bioimpedancia") {
            error_log(
                "PAB DEBUG: before_delete_post chamado para bioimpedância ID: $post_id",
            );
            error_log(
                "PAB DEBUG: Status antes da exclusão: " . $post->post_status,
            );
        }
    },
    10,
    1,
);

add_action(
    "deleted_post",
    function ($post_id) {
        error_log("PAB DEBUG: deleted_post executado para post ID: $post_id");
    },
    10,
    1,
);

// Handler para correção de permalink individual
add_action("admin_init", function () {
    if (isset($_GET["pab_fix_permalink"]) && isset($_GET["nonce"]) && current_user_can("edit_posts")) {
        if (!wp_verify_nonce($_GET["nonce"], "pab_fix_permalink")) {
            wp_die("Nonce inválido");
        }

        $post_id = (int) $_GET["pab_fix_permalink"];
        $result = pab_regenerate_bioimpedancia_permalink($post_id);

        if ($result) {
            $redirect_url = add_query_arg([
                "post" => $post_id,
                "action" => "edit",
                "pab_permalink_fixed" => "1"
            ], admin_url("post.php"));
        } else {
            $redirect_url = add_query_arg([
                "post" => $post_id,
                "action" => "edit",
                "pab_permalink_error" => "1"
            ], admin_url("post.php"));
        }

        wp_redirect($redirect_url);
        exit;
    }

    // Mostrar mensagens de feedback
    if (isset($_GET["pab_permalink_fixed"])) {
        add_action("admin_notices", function () {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✅ Permalink da bioimpedância foi corrigido com sucesso!</p>';
            echo '</div>';
        });
    }

    if (isset($_GET["pab_permalink_error"])) {
        add_action("admin_notices", function () {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>❌ Erro ao corrigir permalink da bioimpedância.</p>';
            echo '</div>';
        });
    }
});
