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
            $p1_imc =
                $altura_m && $p1_peso
                    ? round($p1_peso / ($altura_m * $altura_m), 1)
                    : null;

            // Dados última
            $p2_peso = (float) pab_get($ultima->ID, "pab_bi_peso");
            $p2_gc = (float) pab_get($ultima->ID, "pab_bi_gordura_corporal");
            $p2_me = (float) pab_get($ultima->ID, "pab_bi_musculo_esq");
            $p2_gv = (float) pab_get($ultima->ID, "pab_bi_gordura_visc");
            $p2_imc =
                $altura_m && $p2_peso
                    ? round($p2_peso / ($altura_m * $altura_m), 1)
                    : null;

            // Calcular diferenças
            $diff_peso = $p2_peso - $p1_peso;
            $diff_gc = $p2_gc - $p1_gc;
            $diff_me = $p2_me - $p1_me;
            $diff_gv = $p2_gv - $p1_gv;
            $diff_imc = $p2_imc - $p1_imc;

            // Função helper para exibir diferença
            function pab_format_diff($value, $reverse = false)
            {
                if ($value == 0) {
                    return '<span style="color: #64748b;">—</span>';
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
                    "</span>";
            }
            ?>

            <!-- Comparação: Primeira vs Última -->
            <div style="margin-bottom: 24px;">
                <h5 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #475569;">
                    🔄 Primeira Avaliação vs Avaliação Atual
                </h5>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 10px; text-align: left; color: #64748b; font-weight: 600;">Métrica</th>
                                <th style="padding: 10px; text-align: center; color: #64748b; font-weight: 600;">Primeira<br><small>(<?php echo get_the_date(
                                    "d/m/Y",
                                    $primeira,
                                ); ?>)</small></th>
                                <th style="padding: 10px; text-align: center; color: #64748b; font-weight: 600;">Atual<br><small>(<?php echo get_the_date(
                                    "d/m/Y",
                                    $ultima,
                                ); ?>)</small></th>
                                <th style="padding: 10px; text-align: center; color: #64748b; font-weight: 600;">Diferença</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px; font-weight: 500;">⚖️ Peso</td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p1_peso
                                    ? $p1_peso . " kg"
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p2_peso
                                    ? $p2_peso . " kg"
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo pab_format_diff(
                                    $diff_peso,
                                    false,
                                ); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px; font-weight: 500;">📊 IMC</td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p1_imc
                                    ? $p1_imc
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p2_imc
                                    ? $p2_imc
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo pab_format_diff(
                                    $diff_imc,
                                    false,
                                ); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px; font-weight: 500;">🔥 Gordura Corporal</td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p1_gc
                                    ? $p1_gc . "%"
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p2_gc
                                    ? $p2_gc . "%"
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo pab_format_diff(
                                    $diff_gc,
                                    false,
                                ); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px; font-weight: 500;">💪 Músculo Esquelético</td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p1_me
                                    ? $p1_me . "%"
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo $p2_me
                                    ? $p2_me . "%"
                                    : "—"; ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo pab_format_diff(
                                    $diff_me,
                                    true,
                                ); ?></td>
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
            if (typeof Chart === 'undefined') {
                console.error('Chart.js não carregado');
                return;
            }

            const chartData = <?php echo json_encode($chart_data); ?>;

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
            new Chart(document.getElementById('pab-chart-peso'), {
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
            new Chart(document.getElementById('pab-chart-imc'), {
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
            new Chart(document.getElementById('pab-chart-gc'), {
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
            new Chart(document.getElementById('pab-chart-me'), {
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
        });
        </script>

    </div>
    <?php
}
