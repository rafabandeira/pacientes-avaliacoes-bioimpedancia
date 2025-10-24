<?php
/**
 * Metabox: Análise Corporal Completa (Bioimpedância)
 *
 * Unifica composição corporal e diagnóstico de obesidade em uma única interface
 *
 * @package PAB
 * @subpackage Bioimpedancia\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de análise corporal completa
 *
 * @param WP_Post $post O post atual
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
    $gc = pab_get($post->ID, "pab_bi_gordura_corporal");
    $mus = pab_get($post->ID, "pab_bi_musculo_esq");
    $gv = pab_get($post->ID, "pab_bi_gordura_visc");
    $mb = pab_get($post->ID, "pab_bi_metab_basal");
    $idade_corporal = pab_get($post->ID, "pab_bi_idade_corporal");

    // Verificar se temos dados suficientes
    if (!$peso && !$mus && !$idade_corporal && !$gc && !$gv && !$mb) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> Preencha os dados de bioimpedância na seção acima para ver as análises detalhadas aqui.
        </div>';
        return;
    }

    // Cálculo do IMC
    $altura_m = $altura_cm ? $altura_cm / 100.0 : null;
    $imc =
        $altura_m && $peso ? round($peso / ($altura_m * $altura_m), 1) : null;

    // Classificações
    $c_peso = pab_oms_classificacao(
        "peso",
        (float) $peso,
        $genero,
        $idade_real,
        ["altura_cm" => $altura_cm],
    );
    $c_gc = pab_oms_classificacao("gc", (float) $gc, $genero, $idade_real);
    $c_mus = pab_oms_classificacao(
        "musculo",
        (float) $mus,
        $genero,
        $idade_real,
    );
    $c_imc = pab_oms_classificacao("imc", $imc, $genero, $idade_real);
    $c_gv = pab_oms_classificacao("gv", (float) $gv, $genero, $idade_real);

    $delta_idade =
        $idade_real !== null && $idade_corporal !== ""
            ? (int) $idade_corporal - (int) $idade_real
            : null;
    ?>

    <div class="pab-fade-in">
        <!-- Header Principal -->
        <div style="margin-bottom: 24px; text-align: center; padding: 20px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border: 1px solid #0ea5e9;">
            <h3 style="margin: 0 0 8px 0; color: #0c4a6e; font-size: 20px; font-weight: 600;">
                📊 Análise Corporal Completa
            </h3>
            <p style="margin: 0; font-size: 14px; color: #64748b;">
                Avaliação integrada de composição corporal e indicadores de obesidade
            </p>
        </div>

        <!-- Seção 1: Composição Corporal Principal -->
        <div style="margin-bottom: 28px;">
            <h4 style="margin: 0 0 16px 0; color: #1e293b; font-size: 16px; font-weight: 600; padding-left: 8px; border-left: 3px solid #3b82f6;">
                💪 Composição Corporal
            </h4>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">

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
                            $faixa_ideal = pab_calc_faixa_peso_ideal(
                                $altura_cm,
                            );
                            if ($faixa_ideal) {
                                // Calcular delta baseado na posição do peso na faixa ideal
                                if ($peso < $faixa_ideal["min"]) {
                                    // Se peso está abaixo da faixa, calcular baseado no mínimo
                                    $delta_peso = $peso - $faixa_ideal["min"];
                                } elseif ($peso > $faixa_ideal["max"]) {
                                    // Se peso está acima da faixa, calcular baseado no máximo
                                    $delta_peso = $peso - $faixa_ideal["max"];
                                } else {
                                    // Se peso está dentro da faixa, calcular baseado na média
                                    $peso_medio_ideal =
                                        ($faixa_ideal["min"] +
                                            $faixa_ideal["max"]) /
                                        2;
                                    $delta_peso = $peso - $peso_medio_ideal;
                                }

                                // Só mostrar se a diferença for >= 0.1kg para maior precisão
                                if (abs($delta_peso) >= 0.1) {
                                    $delta_text =
                                        ($delta_peso > 0 ? "+" : "") .
                                        number_format($delta_peso, 1) .
                                        " kg";
                                    $delta_color =
                                        $delta_peso > 0 ? "#dc2626" : "#0891b2";
                                } else {
                                    $delta_text = "no peso ideal";
                                    $delta_color = "#059669";
                                }
                            }
                            ?>
                            <?php if (isset($delta_text)): ?>
                                <div style="margin-top: 4px;">
                                    <span style="font-size: 16px; font-weight: 600; color: <?php echo $delta_color; ?>;">
                                        (<?php echo $delta_text; ?>)
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
                                <?php echo esc_html(
                                    ucfirst($c_peso["nivel"]),
                                ); ?>
                            </span>
                        </div>
                        <div class="pab-ref" style="margin: 0;">
                            <?php echo esc_html($c_peso["ref"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Card: Gordura Corporal -->
                <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #f59e0b;">
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <div style="font-size: 24px; margin-right: 12px;">🔥</div>
                        <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Gordura Corporal</h4>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 28px; font-weight: 700; color: #d97706;">
                            <?php echo $gc ? esc_html($gc) . "%" : "—"; ?>
                        </span>
                        <?php if ($gc && $peso): ?>
                            <div style="margin-top: 4px;">
                                <span style="font-size: 16px; font-weight: 600; color: #d97706;">
                                    (<?php echo number_format(
                                        ($gc / 100) * $peso,
                                        1,
                                    ); ?> kg)
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($gc): ?>
                        <div style="margin-bottom: 8px;">
                            <span class="pab-badge pab-badge-<?php echo esc_attr(
                                $c_gc["nivel"],
                            ); ?>">
                                <?php echo esc_html($c_gc["ref"]); ?>
                            </span>
                        </div>
                        <div class="pab-ref" style="margin: 0;">
                            <?php
                            $genero_texto =
                                $genero === "M" ? "masculino" : "feminino";
                            $idade_grupo =
                                $idade_real >= 60 ? "idoso" : "adulto";
                            echo "Referência para {$genero_texto} {$idade_grupo} ({$idade_real} anos)";
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Card: Massa Muscular -->
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
                                <?php echo esc_html(
                                    ucfirst($c_mus["nivel"]),
                                ); ?>
                            </span>
                        </div>
                        <div class="pab-ref" style="margin: 0;">
                            <?php echo esc_html($c_mus["ref"]); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Card: Metabolismo Basal -->
                <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-left: 4px solid #8b5cf6;">
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <div style="font-size: 24px; margin-right: 12px;">⚡</div>
                        <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Metabolismo Basal</h4>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 28px; font-weight: 700; color: #7c3aed;">
                            <?php echo $mb ? esc_html($mb) . " kcal" : "—"; ?>
                        </span>
                        <?php if ($mb): ?>
                            <div style="margin-top: 4px;">
                                <span style="font-size: 16px; font-weight: 600; color: #7c3aed;">
                                    (~<?php echo round($mb / 24); ?> kcal/hora)
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($mb): ?>
                        <div class="pab-ref" style="margin: 0; color: #64748b; font-size: 12px;">
                            Energia mínima necessária para funções vitais em repouso
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Seção 2: Indicadores Complementares -->
        <div style="margin-bottom: 28px;">
            <h4 style="margin: 0 0 16px 0; color: #1e293b; font-size: 16px; font-weight: 600; padding-left: 8px; border-left: 3px solid #8b5cf6;">
                📈 Indicadores Complementares
            </h4>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">

                <!-- Card: IMC -->
                <div style="background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 24px; margin-right: 10px;">📏</div>
                        <div>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">IMC</h5>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">Índice Massa Corporal</p>
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 24px; font-weight: 700; color: #1e40af;">
                            <?php echo $imc ? esc_html($imc) : "—"; ?>
                        </span>
                    </div>
                    <div>
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $c_imc["nivel"],
                        ); ?>">
                            <?php echo esc_html($c_imc["ref"]); ?>
                        </span>
                    </div>
                </div>

                <!-- Card: Gordura Visceral -->
                <div style="background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ef4444;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 24px; margin-right: 10px;">🫀</div>
                        <div>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">Gordura Visceral</h5>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">Nível interno</p>
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 24px; font-weight: 700; color: #dc2626;">
                            <?php echo $gv ? esc_html($gv) : "—"; ?>
                        </span>
                    </div>
                    <div>
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $c_gv["nivel"],
                        ); ?>">
                            <?php echo esc_html($c_gv["ref"]); ?>
                        </span>
                    </div>
                </div>

                <!-- Card: Idade Biológica -->
                <div style="background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 24px; margin-right: 10px;">🕐</div>
                        <div>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">Idade Biológica</h5>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">vs cronológica</p>
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="font-size: 24px; font-weight: 700; color: #7c3aed;">
                            <?php echo $idade_corporal
                                ? esc_html($idade_corporal) . " anos"
                                : "—"; ?>
                        </span>
                    </div>
                    <?php if ($idade_corporal && $idade_real !== null): ?>
                        <div>
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
                                    <?php echo $delta_icon .
                                        " " .
                                        $delta_text; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Seção 3: Diagnóstico e Interpretação -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 24px; border: 1px solid #fbbf24;">
            <h4 style="margin: 0 0 16px 0; color: #78350f; font-size: 16px; font-weight: 600;">
                <span style="font-size: 24px; margin-right: 8px;">💡</span>
                Análise Integrada e Recomendações
            </h4>

            <?php
            // Análise combinada
            $alertas = [];
            $pontos_positivos = [];

            // IMC
            if (in_array($c_imc["nivel"], ["acima2", "acima3", "alto1"])) {
                $alertas[] = [
                    "icon" => "📏",
                    "title" => "IMC Elevado",
                    "desc" =>
                        "O IMC indica sobrepeso ou obesidade. Considere intervenção nutricional.",
                ];
            } elseif ($c_imc["nivel"] === "normal") {
                $pontos_positivos[] = "IMC dentro da faixa normal";
            }

            // Gordura Corporal
            if (in_array($c_gc["nivel"], ["acima2", "alto1"])) {
                $alertas[] = [
                    "icon" => "🔥",
                    "title" => "Gordura Corporal Elevada",
                    "desc" =>
                        "Percentual de gordura acima do recomendado. Atividade física é importante.",
                ];
            } elseif ($c_gc["nivel"] === "normal") {
                $pontos_positivos[] = "Gordura corporal adequada";
            }

            // Gordura Visceral
            if (in_array($c_gv["nivel"], ["alto1", "alto2"])) {
                $alertas[] = [
                    "icon" => "⚠️",
                    "title" => "Atenção: Gordura Visceral",
                    "desc" =>
                        "Gordura visceral elevada aumenta riscos metabólicos. Requer acompanhamento.",
                ];
            } elseif ($c_gv["nivel"] === "normal") {
                $pontos_positivos[] = "Gordura visceral em níveis normais";
            }

            // Massa Muscular
            if ($c_mus["nivel"] === "abaixo") {
                $alertas[] = [
                    "icon" => "💪",
                    "title" => "Massa Muscular Baixa",
                    "desc" =>
                        "Exercícios de resistência podem ajudar a aumentar a massa muscular.",
                ];
            } elseif (in_array($c_mus["nivel"], ["normal", "acima1"])) {
                $pontos_positivos[] = "Massa muscular adequada";
            }

            // Idade Corporal
            if ($delta_idade !== null) {
                if ($delta_idade <= 0) {
                    $pontos_positivos[] = "Idade biológica favorável";
                } elseif ($delta_idade > 5) {
                    $alertas[] = [
                        "icon" => "🕐",
                        "title" => "Idade Biológica Elevada",
                        "desc" =>
                            "A idade corporal sugere necessidade de melhoria da condição física geral.",
                    ];
                }
            }

            // Mostrar pontos positivos primeiro
            if (!empty($pontos_positivos)): ?>
            <div style="margin-bottom: 16px; padding: 16px; background: rgba(34, 197, 94, 0.1); border-radius: 8px; border-left: 3px solid #22c55e;">
                <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #166534;">
                    ✅ Pontos Positivos:
                </p>
                <p style="margin: 0; font-size: 13px; color: #166534; line-height: 1.5;">
                    <?php echo implode(", ", $pontos_positivos); ?>.
                </p>
            </div>
            <?php endif;
            ?>

            <!-- Alertas ou Status Normal -->
            <?php if (empty($alertas)): ?>
            <div style="display: flex; align-items: center; padding: 16px; background: rgba(255,255,255,0.7); border-radius: 8px;">
                <span style="font-size: 32px; margin-right: 16px;">✅</span>
                <div>
                    <p style="margin: 0; font-size: 14px; color: #78350f; line-height: 1.6;">
                        <strong>Composição corporal excelente!</strong><br>
                        Todos os indicadores estão dentro dos parâmetros adequados para gênero e idade.
                    </p>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($alertas as $alerta): ?>
                <div style="margin-bottom: 12px; padding: 16px; background: rgba(255,255,255,0.7); border-radius: 8px; border-left: 3px solid #f59e0b;">
                    <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #78350f;">
                        <?php echo $alerta["icon"]; ?> <?php echo esc_html(
     $alerta["title"],
 ); ?>
                    </p>
                    <p style="margin: 0; font-size: 12px; color: #92400e; line-height: 1.5;">
                        <?php echo esc_html($alerta["desc"]); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Resumo Interpretativo -->
            <?php if ($peso && ($gc || $mus)): ?>
            <div style="margin-top: 20px; padding: 16px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border-left: 3px solid #3b82f6;">
                <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1e40af;">
                    📋 Resumo da Análise:
                </p>
                <p style="margin: 0; font-size: 12px; color: #1e40af; line-height: 1.6;">
                    <?php
                    $resumo =
                        "Paciente " .
                        ($genero === "M"
                            ? "do sexo masculino"
                            : "do sexo feminino") .
                        " de {$idade_real} anos apresenta ";
                    $componentes = [];

                    if ($peso) {
                        $componentes[] = "peso " . strtolower($c_peso["ref"]);
                    }
                    if ($gc) {
                        $componentes[] =
                            "gordura corporal " . strtolower($c_gc["ref"]);
                    }
                    if ($mus) {
                        $componentes[] =
                            "massa muscular " . strtolower($c_mus["ref"]);
                    }

                    $resumo .= implode(", ", $componentes) . ". ";

                    if ($delta_idade !== null) {
                        if ($delta_idade <= 0) {
                            $resumo .=
                                "A idade biológica indica boa condição física geral.";
                        } else {
                            $resumo .=
                                "A idade biológica sugere oportunidades de melhoria na condição física.";
                        }
                    }

                    echo esc_html($resumo);
                    ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
