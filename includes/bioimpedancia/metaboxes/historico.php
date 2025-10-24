<?php
/**
 * Metabox: Histórico (Bioimpedância)
 *
 * Exibe o histórico de bioimpedâncias do paciente com gráficos de evolução
 *
 * @package PAB
 * @subpackage Bioimpedancia\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de histórico de bioimpedâncias
 *
 * @param WP_Post $post O post atual
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

    // Buscar todas as bioimpedâncias do paciente (ordenadas por data)
    $query = new WP_Query([
        "post_type" => "pab_bioimpedancia",
        "post_parent" => $pid,
        "posts_per_page" => -1,
        "orderby" => "date",
        "order" => "ASC",
        "post_status" => "publish",
    ]);

    if (!$query->have_posts()) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> Este é o primeiro registro de bioimpedância deste paciente. O histórico será exibido após mais avaliações.
        </div>';
        return;
    }

    // Coletar dados para gráficos
    $labels = [];
    $data_peso = [];
    $data_gc = [];
    $data_me = [];
    $data_imc = [];

    $altura_cm = (float) pab_get($pid, "pab_altura");
    $altura_m = $altura_cm ? $altura_cm / 100.0 : null;

    foreach ($query->posts as $p) {
        $data = get_the_date("d/m/Y", $p);
        $labels[] = $data;

        $peso = (float) pab_get($p->ID, "pab_bi_peso");
        $gc = (float) pab_get($p->ID, "pab_bi_gordura_corporal");
        $me = (float) pab_get($p->ID, "pab_bi_musculo_esq");

        $data_peso[] = $peso ?: null;
        $data_gc[] = $gc ?: null;
        $data_me[] = $me ?: null;

        // Calcular IMC
        $imc =
            $altura_m && $peso
                ? round($peso / ($altura_m * $altura_m), 1)
                : null;
        $data_imc[] = $imc;
    }

    // Preparar dados para JavaScript
    $chart_data = [
        "labels" => $labels,
        "peso" => $data_peso,
        "gc" => $data_gc,
        "me" => $data_me,
        "imc" => $data_imc,
    ];
    ?>

    <div class="pab-fade-in">
        <!-- Header -->
        <div style="margin-bottom: 20px; text-align: center; padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 8px;">
            <h4 style="margin: 0 0 8px 0; color: #0c4a6e; font-size: 18px; font-weight: 600;">
                📈 Evolução da Composição Corporal
            </h4>
            <p style="margin: 0; font-size: 13px; color: #64748b;">
                <span style="font-weight: 600; color: #0891b2;"><?php echo count(
                    $labels,
                ); ?></span> avaliações registradas
            </p>
        </div>

        <!-- Gráficos (2 por linha) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 24px;">

            <!-- Gráfico: Peso -->
            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">⚖️</span>Evolução do Peso
                    </h5>
                </div>
                <canvas id="pab-chart-peso" style="max-height: 200px;"></canvas>
            </div>

            <!-- Gráfico: IMC -->
            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">📊</span>Evolução do IMC
                    </h5>
                </div>
                <canvas id="pab-chart-imc" style="max-height: 200px;"></canvas>
            </div>

            <!-- Gráfico: Gordura Corporal -->
            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">🔥</span>Gordura Corporal
                    </h5>
                </div>
                <canvas id="pab-chart-gc" style="max-height: 200px;"></canvas>
            </div>

            <!-- Gráfico: Músculo Esquelético -->
            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">💪</span>Músculo Esquelético
                    </h5>
                </div>
                <canvas id="pab-chart-me" style="max-height: 200px;"></canvas>
            </div>

        </div>

        <!-- Tabela de Comparação -->
        <div style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
                <span style="font-size: 20px; margin-right: 8px;">📋</span>Comparativo das Avaliações
            </h4>

            <?php
            // Pegar primeira e última avaliação
            $primeira = $query->posts[0];
            $ultima = end($query->posts);
            $total_avaliacoes = count($query->posts);

            // Dados primeira
            $p1_peso = (float) pab_get($primeira->ID, "pab_bi_peso");
            $p1_gc = (float) pab_get($primeira->ID, "pab_bi_gordura_corporal");
            $p1_me = (float) pab_get($primeira->ID, "pab_bi_musculo_esq");
            $p1_gv = (float) pab_get($primeira->ID, "pab_bi_gordura_visc");
            $p1_mb = (float) pab_get($primeira->ID, "pab_bi_metab_basal");
            $p1_idade_corporal = (int) pab_get(
                $primeira->ID,
                "pab_bi_idade_corporal",
            );
            $p1_imc =
                $altura_m && $p1_peso
                    ? round($p1_peso / ($altura_m * $altura_m), 1)
                    : null;

            // Dados última
            $p2_peso = (float) pab_get($ultima->ID, "pab_bi_peso");
            $p2_gc = (float) pab_get($ultima->ID, "pab_bi_gordura_corporal");
            $p2_me = (float) pab_get($ultima->ID, "pab_bi_musculo_esq");
            $p2_gv = (float) pab_get($ultima->ID, "pab_bi_gordura_visc");
            $p2_mb = (float) pab_get($ultima->ID, "pab_bi_metab_basal");
            $p2_idade_corporal = (int) pab_get(
                $ultima->ID,
                "pab_bi_idade_corporal",
            );
            $p2_imc =
                $altura_m && $p2_peso
                    ? round($p2_peso / ($altura_m * $altura_m), 1)
                    : null;

            // Calcular diferenças
            $diff_peso = $p2_peso - $p1_peso;
            $diff_gc = $p2_gc - $p1_gc;
            $diff_me = $p2_me - $p1_me;
            $diff_gv = $p2_gv - $p1_gv;
            $diff_mb = $p2_mb - $p1_mb;
            $diff_idade_corporal = $p2_idade_corporal - $p1_idade_corporal;
            $diff_imc = $p2_imc - $p1_imc;

            // Calcular diferenças de tempo em dias
            $data_atual = new DateTime();
            $data_primeira = new DateTime($primeira->post_date);
            $data_ultima = new DateTime($ultima->post_date);

            $diff_dias_primeira = $data_atual->diff($data_primeira)->days;
            $diff_dias_ultima = $data_atual->diff($data_ultima)->days;

            // Função helper para calcular peso em kg de uma porcentagem
            function pab_calc_peso_percentual($percentual, $peso_total)
            {
                if (!$percentual || !$peso_total) {
                    return null;
                }
                return round(($percentual / 100) * $peso_total, 1);
            }

            // Função helper para exibir diferença com unidade
            function pab_format_diff($value, $reverse = false, $unit = "")
            {
                if ($value == 0) {
                    return '<span style="color: #64748b;">0</span>';
                }
                $sign = $value > 0 ? "+" : "";
                $color =
                    $value > 0
                        ? ($reverse
                            ? "#059669"
                            : "#dc2626")
                        : ($reverse
                            ? "#dc2626"
                            : "#059669");
                $icon =
                    $value > 0
                        ? ($reverse
                            ? "📈"
                            : "📉")
                        : ($reverse
                            ? "📉"
                            : "📈");
                return '<span style="color: ' .
                    $color .
                    '; font-weight: 600;">' .
                    $icon .
                    " " .
                    $sign .
                    number_format($value, 1) .
                    $unit .
                    "</span>";
            }

            // Função helper para exibir diferença com peso em kg
            function pab_format_diff_with_kg(
                $value,
                $peso_p1,
                $peso_p2,
                $reverse = false,
            ) {
                if ($value == 0) {
                    return '<span style="color: #64748b;">—</span>';
                }

                // Calcular diferença em kg
                $kg_p1 = pab_calc_peso_percentual($value + $peso_p1, $peso_p1);
                $kg_p2 = pab_calc_peso_percentual($peso_p2, $peso_p2);
                $diff_kg = $kg_p2 - $kg_p1;

                $sign = $value > 0 ? "+" : "";
                $sign_kg = $diff_kg > 0 ? "+" : "";
                $color =
                    $value > 0
                        ? ($reverse
                            ? "#059669"
                            : "#dc2626")
                        : ($reverse
                            ? "#dc2626"
                            : "#059669");
                $icon =
                    $value > 0
                        ? ($reverse
                            ? "📈"
                            : "📉")
                        : ($reverse
                            ? "📉"
                            : "📈");

                $kg_text = $diff_kg
                    ? " (" . $sign_kg . number_format($diff_kg, 1) . "kg)"
                    : "";

                return '<span style="color: ' .
                    $color .
                    '; font-weight: 600;">' .
                    $icon .
                    " " .
                    $sign .
                    number_format($value, 1) .
                    "%" .
                    $kg_text .
                    "</span>";
            }
            ?>

            <!-- Comparativo das Avaliações -->
            <div style="margin-bottom: 24px;">
                <h5 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #475569;">
                    🔄 Comparativo das Avaliações
                </h5>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 10px; text-align: left; color: #64748b; font-weight: 600;">Indicador</th>
                                <th style="padding: 10px; text-align: center; color: #64748b; font-weight: 600;">Última<br><small>(<?php echo get_the_date(
                                    "d/m/Y",
                                    $ultima,
                                ); ?>)</small></th>
                                <th style="padding: 10px; text-align: center; color: #64748b; font-weight: 600;">Primeira<br><small>(<?php echo get_the_date(
                                    "d/m/Y",
                                    $primeira,
                                ); ?>)</small></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">⏱️ Tempo</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600; color: #64748b;">
                                    <?php echo $diff_dias_ultima; ?> dias
                                </td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600; color: #64748b;">
                                    <?php echo $diff_dias_primeira; ?> dias
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">⚖️ Peso</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_peso,
                                    false,
                                    "kg",
                                ); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_peso,
                                    false,
                                    "kg",
                                ); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">📊 IMC</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_imc,
                                    false,
                                    "",
                                ); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_imc,
                                    false,
                                    "",
                                ); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">🔥 Gordura Corporal</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php if (
                                    $diff_gc != 0 &&
                                    $p1_peso &&
                                    $p2_peso
                                ) {
                                    $gc_kg_p1 = pab_calc_peso_percentual(
                                        $p1_gc,
                                        $p1_peso,
                                    );
                                    $gc_kg_p2 = pab_calc_peso_percentual(
                                        $p2_gc,
                                        $p2_peso,
                                    );
                                    $diff_gc_kg = $gc_kg_p2 - $gc_kg_p1;

                                    $sign = $diff_gc > 0 ? "+" : "";
                                    $sign_kg = $diff_gc_kg > 0 ? "+" : "";
                                    $color =
                                        $diff_gc > 0 ? "#dc2626" : "#059669";
                                    $icon = $diff_gc > 0 ? "📉" : "📈";

                                    echo '<span style="color: ' .
                                        $color .
                                        '; font-weight: 600;">' .
                                        $icon .
                                        " " .
                                        $sign .
                                        number_format($diff_gc, 1) .
                                        "% (" .
                                        $sign_kg .
                                        number_format($diff_gc_kg, 1) .
                                        "kg)</span>";
                                } else {
                                    echo '<span style="color: #64748b;">0</span>';
                                } ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php if (
                                    $diff_gc != 0 &&
                                    $p1_peso &&
                                    $p2_peso
                                ) {
                                    $gc_kg_p1 = pab_calc_peso_percentual(
                                        $p1_gc,
                                        $p1_peso,
                                    );
                                    $gc_kg_p2 = pab_calc_peso_percentual(
                                        $p2_gc,
                                        $p2_peso,
                                    );
                                    $diff_gc_kg = $gc_kg_p2 - $gc_kg_p1;

                                    $sign = $diff_gc > 0 ? "+" : "";
                                    $sign_kg = $diff_gc_kg > 0 ? "+" : "";
                                    $color =
                                        $diff_gc > 0 ? "#dc2626" : "#059669";
                                    $icon = $diff_gc > 0 ? "📉" : "📈";

                                    echo '<span style="color: ' .
                                        $color .
                                        '; font-weight: 600;">' .
                                        $icon .
                                        " " .
                                        $sign .
                                        number_format($diff_gc, 1) .
                                        "% (" .
                                        $sign_kg .
                                        number_format($diff_gc_kg, 1) .
                                        "kg)</span>";
                                } else {
                                    echo '<span style="color: #64748b;">0</span>';
                                } ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">💪 Músculo Esquelético</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php if (
                                    $diff_me != 0 &&
                                    $p1_peso &&
                                    $p2_peso
                                ) {
                                    $me_kg_p1 = pab_calc_peso_percentual(
                                        $p1_me,
                                        $p1_peso,
                                    );
                                    $me_kg_p2 = pab_calc_peso_percentual(
                                        $p2_me,
                                        $p2_peso,
                                    );
                                    $diff_me_kg = $me_kg_p2 - $me_kg_p1;

                                    $sign = $diff_me > 0 ? "+" : "";
                                    $sign_kg = $diff_me_kg > 0 ? "+" : "";
                                    $color =
                                        $diff_me > 0 ? "#059669" : "#dc2626";
                                    $icon = $diff_me > 0 ? "📈" : "📉";

                                    echo '<span style="color: ' .
                                        $color .
                                        '; font-weight: 600;">' .
                                        $icon .
                                        " " .
                                        $sign .
                                        number_format($diff_me, 1) .
                                        "% (" .
                                        $sign_kg .
                                        number_format($diff_me_kg, 1) .
                                        "kg)</span>";
                                } else {
                                    echo '<span style="color: #64748b;">0</span>';
                                } ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php if (
                                    $diff_me != 0 &&
                                    $p1_peso &&
                                    $p2_peso
                                ) {
                                    $me_kg_p1 = pab_calc_peso_percentual(
                                        $p1_me,
                                        $p1_peso,
                                    );
                                    $me_kg_p2 = pab_calc_peso_percentual(
                                        $p2_me,
                                        $p2_peso,
                                    );
                                    $diff_me_kg = $me_kg_p2 - $me_kg_p1;

                                    $sign = $diff_me > 0 ? "+" : "";
                                    $sign_kg = $diff_me_kg > 0 ? "+" : "";
                                    $color =
                                        $diff_me > 0 ? "#059669" : "#dc2626";
                                    $icon = $diff_me > 0 ? "📈" : "📉";

                                    echo '<span style="color: ' .
                                        $color .
                                        '; font-weight: 600;">' .
                                        $icon .
                                        " " .
                                        $sign .
                                        number_format($diff_me, 1) .
                                        "% (" .
                                        $sign_kg .
                                        number_format($diff_me_kg, 1) .
                                        "kg)</span>";
                                } else {
                                    echo '<span style="color: #64748b;">0</span>';
                                } ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">🫀 Gordura Visceral</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_gv,
                                    false,
                                    "",
                                ); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_gv,
                                    false,
                                    "",
                                ); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">⚡ Metabolismo Basal</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_mb,
                                    true,
                                    " kcal",
                                ); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff(
                                    $diff_mb,
                                    true,
                                    " kcal",
                                ); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">🕐 Idade Corporal</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php if (
                                    $diff_idade_corporal != 0
                                ) {
                                    $sign = $diff_idade_corporal > 0 ? "+" : "";
                                    $color =
                                        $diff_idade_corporal > 0
                                            ? "#dc2626"
                                            : "#059669";
                                    $icon =
                                        $diff_idade_corporal > 0 ? "📉" : "📈";
                                    echo '<span style="color: ' .
                                        $color .
                                        '; font-weight: 600;">' .
                                        $icon .
                                        " " .
                                        $sign .
                                        $diff_idade_corporal .
                                        " anos</span>";
                                } else {
                                    echo '<span style="color: #64748b;">0</span>';
                                } ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php if (
                                    $diff_idade_corporal != 0
                                ) {
                                    $sign = $diff_idade_corporal > 0 ? "+" : "";
                                    $color =
                                        $diff_idade_corporal > 0
                                            ? "#dc2626"
                                            : "#059669";
                                    $icon =
                                        $diff_idade_corporal > 0 ? "📉" : "📈";
                                    echo '<span style="color: ' .
                                        $color .
                                        '; font-weight: 600;">' .
                                        $icon .
                                        " " .
                                        $sign .
                                        $diff_idade_corporal .
                                        " anos</span>";
                                } else {
                                    echo '<span style="color: #64748b;">0</span>';
                                } ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumo da Evolução -->
            <div style="padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 8px; border-left: 4px solid #0891b2;">
                <h5 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #0c4a6e;">
                    💡 Análise de Progresso
                </h5>
                <p style="margin: 0; font-size: 13px; color: #0f172a; line-height: 1.6;">
                    <?php
                    // Gerar análise inteligente
                    $analise = [];

                    if (abs($diff_peso) > 0.5) {
                        if ($diff_peso < 0) {
                            $analise[] =
                                "Redução de " .
                                abs(number_format($diff_peso, 1)) .
                                " kg no peso corporal";
                        } else {
                            $analise[] =
                                "Ganho de " .
                                number_format($diff_peso, 1) .
                                " kg no peso corporal";
                        }
                    }

                    if (abs($diff_gc) > 1) {
                        if ($diff_gc < 0) {
                            $analise[] =
                                "Redução de " .
                                abs(number_format($diff_gc, 1)) .
                                "% na gordura corporal 🎯";
                        } else {
                            $analise[] =
                                "Aumento de " .
                                number_format($diff_gc, 1) .
                                "% na gordura corporal";
                        }
                    }

                    if (abs($diff_me) > 0.5) {
                        if ($diff_me > 0) {
                            $analise[] =
                                "Ganho de " .
                                number_format($diff_me, 1) .
                                "% em massa muscular 💪";
                        } else {
                            $analise[] =
                                "Redução de " .
                                abs(number_format($diff_me, 1)) .
                                "% em massa muscular";
                        }
                    }

                    if (empty($analise)) {
                        echo "Composição corporal mantida estável entre as avaliações.";
                    } else {
                        echo "<strong>Desde a primeira avaliação:</strong><br>";
                        echo "• " . implode("<br>• ", $analise);
                    }
                    ?>
                </p>
            </div>

        </div>

        <!-- Script para gráficos -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('PAB: Inicializando gráficos de histórico');

            if (typeof Chart === 'undefined') {
                console.error('PAB ERROR: Chart.js não está carregado!');
                console.log('Verifique se o Chart.js foi enfileirado corretamente');
                return;
            }

            console.log('PAB: Chart.js carregado com sucesso');

            const chartData = <?php echo json_encode($chart_data); ?>;
            console.log('PAB: Dados dos gráficos:', chartData);

            // Verificar se os canvas existem
            const canvasPeso = document.getElementById('pab-chart-peso');
            const canvasImc = document.getElementById('pab-chart-imc');
            const canvasGc = document.getElementById('pab-chart-gc');
            const canvasMe = document.getElementById('pab-chart-me');

            if (!canvasPeso || !canvasImc || !canvasGc || !canvasMe) {
                console.error('PAB ERROR: Um ou mais elementos canvas não foram encontrados');
                console.log('Canvas Peso:', canvasPeso);
                console.log('Canvas IMC:', canvasImc);
                console.log('Canvas GC:', canvasGc);
                console.log('Canvas ME:', canvasMe);
                return;
            }

            console.log('PAB: Todos os canvas encontrados');

            // Configuração comum
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            };

            // Gráfico: Peso
            console.log('PAB: Criando gráfico de Peso');
            new Chart(canvasPeso, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Peso (kg)',
                        data: chartData.peso,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });

            // Gráfico: IMC
            console.log('PAB: Criando gráfico de IMC');
            new Chart(canvasImc, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'IMC',
                        data: chartData.imc,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });

            // Gráfico: Gordura Corporal
            console.log('PAB: Criando gráfico de Gordura Corporal');
            new Chart(canvasGc, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Gordura Corporal (%)',
                        data: chartData.gc,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });

            // Gráfico: Músculo Esquelético
            console.log('PAB: Criando gráfico de Músculo Esquelético');
            new Chart(canvasMe, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Músculo Esquelético (%)',
                        data: chartData.me,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });

            console.log('PAB: Todos os gráficos foram criados com sucesso!');
        });
        </script>

    </div>
    <?php
}
