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

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 24px;">

            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">⚖️</span>Evolução do Peso
                    </h5>
                </div>
                <canvas id="pab-chart-peso" style="max-height: 200px;"></canvas>
            </div>

            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">📊</span>Evolução do IMC
                    </h5>
                </div>
                <canvas id="pab-chart-imc" style="max-height: 200px;"></canvas>
            </div>

            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">🔥</span>Gordura Corporal
                    </h5>
                </div>
                <canvas id="pab-chart-gc" style="max-height: 200px;"></canvas>
            </div>

            <div style="background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 12px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">
                        <span style="font-size: 18px; margin-right: 6px;">💪</span>Músculo Esquelético
                    </h5>
                </div>
                <canvas id="pab-chart-me" style="max-height: 200px;"></canvas>
            </div>

        </div>

        <div style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
                <span style="font-size: 20px; margin-right: 8px;">📋</span>Comparativo das Avaliações
            </h4>

            <?php
            // --- INÍCIO DA LÓGICA CORRIGIDA ---

            // 1. Obter a avaliação ATUAL (sendo editada)
            $atual_post = $post;
            $atual_id = $atual_post->ID;

            // 2. Obter a PRIMEIRA avaliação
            $primeira_post = $query->posts[0];
            $primeira_id = $primeira_post->ID;

            // 3. Obter a ÚLTIMA (ANTERIOR) avaliação
            $anterior_post = null; // Default
            $current_index = -1;
            foreach ($query->posts as $index => $bio) {
                if ($bio->ID == $atual_id) {
                    $current_index = $index;
                    break;
                }
            }

            if ($current_index > 0) {
                // Se a avaliação atual não for a primeira, pegue a anterior
                $anterior_post = $query->posts[$current_index - 1];
            } else {
                // Se a avaliação atual FOR a primeira, comparamos ela com ela mesma
                // (para a coluna "Última" ter diferença 0)
                $anterior_post = $primeira_post; 
            }
            $anterior_id = $anterior_post->ID;


            // --- DADOS DA AVALIAÇÃO ATUAL (sendo editada) ---
            $atual_peso = (float) pab_get($atual_id, "pab_bi_peso");
            $atual_gc = (float) pab_get($atual_id, "pab_bi_gordura_corporal");
            $atual_me = (float) pab_get($atual_id, "pab_bi_musculo_esq");
            $atual_gv = (float) pab_get($atual_id, "pab_bi_gordura_visc");
            $atual_mb = (float) pab_get($atual_id, "pab_bi_metab_basal");
            $atual_idade_corporal = (int) pab_get($atual_id, "pab_bi_idade_corporal");
            $atual_imc =
                $altura_m && $atual_peso
                    ? round($atual_peso / ($altura_m * $altura_m), 1)
                    : null;

            // --- DADOS DA PRIMEIRA AVALIAÇÃO ---
            $p1_peso = (float) pab_get($primeira_id, "pab_bi_peso");
            $p1_gc = (float) pab_get($primeira_id, "pab_bi_gordura_corporal");
            $p1_me = (float) pab_get($primeira_id, "pab_bi_musculo_esq");
            $p1_gv = (float) pab_get($primeira_id, "pab_bi_gordura_visc");
            $p1_mb = (float) pab_get($primeira_id, "pab_bi_metab_basal");
            $p1_idade_corporal = (int) pab_get($primeira_id, "pab_bi_idade_corporal");
            $p1_imc =
                $altura_m && $p1_peso
                    ? round($p1_peso / ($altura_m * $altura_m), 1)
                    : null;

            // --- DADOS DA AVALIAÇÃO ANTERIOR ("ÚLTIMA" no comparativo) ---
            $p_ant_peso = (float) pab_get($anterior_id, "pab_bi_peso");
            $p_ant_gc = (float) pab_get($anterior_id, "pab_bi_gordura_corporal");
            $p_ant_me = (float) pab_get($anterior_id, "pab_bi_musculo_esq");
            $p_ant_gv = (float) pab_get($anterior_id, "pab_bi_gordura_visc");
            $p_ant_mb = (float) pab_get($anterior_id, "pab_bi_metab_basal");
            $p_ant_idade_corporal = (int) pab_get($anterior_id, "pab_bi_idade_corporal");
            $p_ant_imc =
                $altura_m && $p_ant_peso
                    ? round($p_ant_peso / ($altura_m * $altura_m), 1)
                    : null;

            // --- CALCULAR DIFERENÇAS (DUAS VEZES) ---

            // 1. Diferenças vs. PRIMEIRA (Atual - Primeira)
            $diff_p1_peso = $atual_peso - $p1_peso;
            $diff_p1_gc = $atual_gc - $p1_gc;
            $diff_p1_me = $atual_me - $p1_me;
            $diff_p1_gv = $atual_gv - $p1_gv;
            $diff_p1_mb = $atual_mb - $p1_mb;
            $diff_p1_idade_corporal = $atual_idade_corporal - $p1_idade_corporal;
            $diff_p1_imc = $atual_imc - $p1_imc;

            // 2. Diferenças vs. ANTERIOR (Atual - Anterior) -> Coluna "Última"
            $diff_ant_peso = $atual_peso - $p_ant_peso;
            $diff_ant_gc = $atual_gc - $p_ant_gc;
            $diff_ant_me = $atual_me - $p_ant_me;
            $diff_ant_gv = $atual_gv - $p_ant_gv;
            $diff_ant_mb = $atual_mb - $p_ant_mb;
            $diff_ant_idade_corporal = $atual_idade_corporal - $p_ant_idade_corporal;
            $diff_ant_imc = $atual_imc - $p_ant_imc;


            // Calcular diferenças de tempo em dias
            $data_atual_post = new DateTime($atual_post->post_date); // Data da avaliação atual
            $data_primeira = new DateTime($primeira_post->post_date);
            $data_anterior = new DateTime($anterior_post->post_date);

            $diff_dias_primeira = $data_atual_post->diff($data_primeira)->days;
            $diff_dias_anterior = $data_atual_post->diff($data_anterior)->days;
            
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

            <div style="margin-bottom: 24px;">
                <h5 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #475569;">
                    🔄 Comparativo das Bioimpedâncias
                </h5>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 10px; text-align: left; color: #64748b; font-weight: 600;">Indicador</th>
                                <th style="padding: 10px; text-align: center; color: #64748b; font-weight: 600;">Última<br><small>(<?php echo get_the_date("d/m/Y", $anterior_post); ?>)</small></th>
                                <th style="padding: 10px; text-align: center; color: #64748b; font-weight: 600;">Primeira<br><small>(<?php echo get_the_date("d/m/Y", $primeira_post); ?>)</small></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">⏱️ Tempo</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600; color: #64748b;">
                                    <?php echo $diff_dias_anterior; ?> dias
                                </td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600; color: #64748b;">
                                    <?php echo $diff_dias_primeira; ?> dias
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">⚖️ Peso</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_ant_peso, false, "kg"); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_p1_peso, false, "kg"); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">📊 IMC</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_ant_imc, false, ""); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_p1_imc, false, ""); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">🔥 Gordura Corporal</td>
                                
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;">
                                    <?php if ($diff_ant_gc != 0 && $p_ant_peso && $atual_peso) {
                                        $gc_kg_p1 = pab_calc_peso_percentual($p_ant_gc, $p_ant_peso);
                                        $gc_kg_p2 = pab_calc_peso_percentual($atual_gc, $atual_peso);
                                        $diff_gc_kg = $gc_kg_p2 - $gc_kg_p1;
                                        $sign = $diff_ant_gc > 0 ? "+" : ""; $sign_kg = $diff_gc_kg > 0 ? "+" : ""; $color = $diff_ant_gc > 0 ? "#dc2626" : "#059669"; $icon = $diff_ant_gc > 0 ? "📉" : "📈";
                                        echo '<span style="color: ' . $color . '; font-weight: 600;">' . $icon . " " . $sign . number_format($diff_ant_gc, 1) . "% (" . $sign_kg . number_format($diff_gc_kg, 1) . "kg)</span>";
                                    } else { echo '<span style="color: #64748b;">0</span>'; } ?>
                                </td>
                                
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;">
                                    <?php if ($diff_p1_gc != 0 && $p1_peso && $atual_peso) {
                                        $gc_kg_p1 = pab_calc_peso_percentual($p1_gc, $p1_peso);
                                        $gc_kg_p2 = pab_calc_peso_percentual($atual_gc, $atual_peso);
                                        $diff_gc_kg = $gc_kg_p2 - $gc_kg_p1;
                                        $sign = $diff_p1_gc > 0 ? "+" : ""; $sign_kg = $diff_gc_kg > 0 ? "+" : ""; $color = $diff_p1_gc > 0 ? "#dc2626" : "#059669"; $icon = $diff_p1_gc > 0 ? "📉" : "📈";
                                        echo '<span style="color: ' . $color . '; font-weight: 600;">' . $icon . " " . $sign . number_format($diff_p1_gc, 1) . "% (" . $sign_kg . number_format($diff_gc_kg, 1) . "kg)</span>";
                                    } else { echo '<span style="color: #64748b;">0</span>'; } ?>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">💪 Músculo Esquelético</td>
                                
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;">
                                    <?php if ($diff_ant_me != 0 && $p_ant_peso && $atual_peso) {
                                        $me_kg_p1 = pab_calc_peso_percentual($p_ant_me, $p_ant_peso);
                                        $me_kg_p2 = pab_calc_peso_percentual($atual_me, $atual_peso);
                                        $diff_me_kg = $me_kg_p2 - $me_kg_p1;
                                        $sign = $diff_ant_me > 0 ? "+" : ""; $sign_kg = $diff_me_kg > 0 ? "+" : ""; $color = $diff_ant_me > 0 ? "#059669" : "#dc2626"; $icon = $diff_ant_me > 0 ? "📈" : "📉";
                                        echo '<span style="color: ' . $color . '; font-weight: 600;">' . $icon . " " . $sign . number_format($diff_ant_me, 1) . "% (" . $sign_kg . number_format($diff_me_kg, 1) . "kg)</span>";
                                    } else { echo '<span style="color: #64748b;">0</span>'; } ?>
                                </td>
                                
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;">
                                    <?php if ($diff_p1_me != 0 && $p1_peso && $atual_peso) {
                                        $me_kg_p1 = pab_calc_peso_percentual($p1_me, $p1_peso);
                                        $me_kg_p2 = pab_calc_peso_percentual($atual_me, $atual_peso);
                                        $diff_me_kg = $me_kg_p2 - $me_kg_p1;
                                        $sign = $diff_p1_me > 0 ? "+" : ""; $sign_kg = $diff_me_kg > 0 ? "+" : ""; $color = $diff_p1_me > 0 ? "#059669" : "#dc2626"; $icon = $diff_p1_me > 0 ? "📈" : "📉";
                                        echo '<span style="color: ' . $color . '; font-weight: 600;">' . $icon . " " . $sign . number_format($diff_p1_me, 1) . "% (" . $sign_kg . number_format($diff_me_kg, 1) . "kg)</span>";
                                    } else { echo '<span style="color: #64748b;">0</span>'; } ?>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">🫀 Gordura Visceral</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_ant_gv, false, ""); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_p1_gv, false, ""); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">⚡ Metabolismo Basal</td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_ant_mb, true, " kcal"); ?></td>
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;"><?php echo pab_format_diff($diff_p1_mb, true, " kcal"); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 500;">🕐 Idade Corporal</td>
                                
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;">
                                    <?php if ($diff_ant_idade_corporal != 0) {
                                        $sign = $diff_ant_idade_corporal > 0 ? "+" : ""; $color = $diff_ant_idade_corporal > 0 ? "#dc2626" : "#059669"; $icon = $diff_ant_idade_corporal > 0 ? "📉" : "📈";
                                        echo '<span style="color: ' . $color . '; font-weight: 600;">' . $icon . " " . $sign . $diff_ant_idade_corporal . " anos</span>";
                                    } else { echo '<span style="color: #64748b;">0</span>'; } ?>
                                </td>
                                
                                <td style="padding: 12px; text-align: center; font-size: 14px; font-weight: 600;">
                                    <?php if ($diff_p1_idade_corporal != 0) {
                                        $sign = $diff_p1_idade_corporal > 0 ? "+" : ""; $color = $diff_p1_idade_corporal > 0 ? "#dc2626" : "#059669"; $icon = $diff_p1_idade_corporal > 0 ? "📉" : "📈";
                                        echo '<span style="color: ' . $color . '; font-weight: 600;">' . $icon . " " . $sign . $diff_p1_idade_corporal . " anos</span>";
                                    } else { echo '<span style="color: #64748b;">0</span>'; } ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 8px; border-left: 4px solid #0891b2;">
                <h5 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #0c4a6e;">
                    💡 Análise de Progresso
                </h5>
                <p style="margin: 0; font-size: 13px; color: #0f172a; line-height: 1.6;">
                    <?php
                    // Gerar análise inteligente (Comparando com a PRIMEIRA)
                    $analise = [];

                    if (abs($diff_p1_peso) > 0.5) {
                        if ($diff_p1_peso < 0) {
                            $analise[] =
                                "Redução de " .
                                abs(number_format($diff_p1_peso, 1)) .
                                " kg no peso corporal";
                        } else {
                            $analise[] =
                                "Ganho de " .
                                number_format($diff_p1_peso, 1) .
                                " kg no peso corporal";
                        }
                    }

                    if (abs($diff_p1_gc) > 1) {
                        if ($diff_p1_gc < 0) {
                            $analise[] =
                                "Redução de " .
                                abs(number_format($diff_p1_gc, 1)) .
                                "% na gordura corporal 🎯";
                        } else {
                            $analise[] =
                                "Aumento de " .
                                number_format($diff_p1_gc, 1) .
                                "% na gordura corporal";
                        }
                    }

                    if (abs($diff_p1_me) > 0.5) {
                        if ($diff_p1_me > 0) {
                            $analise[] =
                                "Ganho de " .
                                number_format($diff_p1_me, 1) .
                                "% em massa muscular 💪";
                        } else {
                            $analise[] =
                                "Redução de " .
                                abs(number_format($diff_p1_me, 1)) .
                                "% em massa muscular";
                        }
                    }

                    if (empty($analise)) {
                        echo "Composição corporal mantida estável desde a primeira avaliação.";
                    } else {
                        echo "<strong>Desde a primeira avaliação:</strong><br>";
                        echo "• " . implode("<br>• ", $analise);
                    }
                    ?>
                </p>
            </div>

        </div>

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