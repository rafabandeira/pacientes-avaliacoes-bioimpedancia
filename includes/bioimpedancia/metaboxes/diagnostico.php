<?php
/**
 * Metabox: Diagnóstico de Obesidade (Bioimpedância)
 *
 * Exibe a análise detalhada da obesidade por segmento corporal
 *
 * @package PAB
 * @subpackage Bioimpedancia\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de diagnóstico de obesidade
 *
 * @param WP_Post $post O post atual
 */
function pab_bi_diag_obes_cb($post)
{
    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    if (!$pid) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Vincule um paciente para exibir o diagnóstico de obesidade.
        </div>';
        return;
    }

    $genero = pab_get($pid, "pab_genero", "M");
    $idade_real = pab_calc_idade_real($pid);
    $altura_cm = (float) pab_get($pid, "pab_altura");

    // Dados da bioimpedância
    $peso = (float) pab_get($post->ID, "pab_bi_peso");
    $gc = (float) pab_get($post->ID, "pab_bi_gordura_corporal");
    $gv = (float) pab_get($post->ID, "pab_bi_gordura_visc");

    // Cálculo do IMC
    $altura_m = $altura_cm ? $altura_cm / 100.0 : null;
    $imc =
        $altura_m && $peso ? round($peso / ($altura_m * $altura_m), 1) : null;

    // Verificar se temos dados suficientes
    if (!$gc && !$imc && !$gv) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>ℹ️ Informação:</strong> Preencha os dados de bioimpedância para ver o diagnóstico de obesidade.
        </div>';
        return;
    }

    // Classificações
    $class_gc = pab_oms_classificacao("gc", $gc, $genero, $idade_real);
    $class_imc = pab_oms_classificacao("imc", $imc, $genero, $idade_real);
    $class_gv = pab_oms_classificacao("gv", $gv, $genero, $idade_real);
    ?>

    <div class="pab-fade-in">
        <!-- Header -->
        <div style="margin-bottom: 20px; text-align: center; padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 8px;">
            <h4 style="margin: 0 0 8px 0; color: #0c4a6e; font-size: 18px; font-weight: 600;">
                📊 Análise de Obesidade por Segmento
            </h4>
            <p style="margin: 0; font-size: 13px; color: #64748b;">
                Avaliação baseada em múltiplos indicadores corporais
            </p>
        </div>

        <!-- Grid de Indicadores -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">

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
                        $class_imc["nivel"],
                    ); ?>">
                        <?php echo esc_html($class_imc["ref"]); ?>
                    </span>
                </div>
            </div>

            <!-- Card: Gordura Corporal -->
            <div style="background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <div style="font-size: 24px; margin-right: 10px;">🔥</div>
                    <div>
                        <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">Gordura Corporal</h5>
                        <p style="margin: 0; font-size: 11px; color: #94a3b8;">Percentual total (%)</p>
                    </div>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="font-size: 24px; font-weight: 700; color: #d97706;">
                        <?php echo $gc ? esc_html($gc) . "%" : "—"; ?>
                    </span>
                </div>
                <div>
                    <span class="pab-badge pab-badge-<?php echo esc_attr(
                        $class_gc["nivel"],
                    ); ?>">
                        <?php echo esc_html($class_gc["ref"]); ?>
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
                        $class_gv["nivel"],
                    ); ?>">
                        <?php echo esc_html($class_gv["ref"]); ?>
                    </span>
                </div>
            </div>

            <!-- Card: Peso -->
            <?php $class_peso = pab_oms_classificacao(
                "peso",
                $peso,
                $genero,
                $idade_real,
                [
                    "altura_cm" => $altura_cm,
                ],
            ); ?>
            <div style="background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6;">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <div style="font-size: 24px; margin-right: 10px;">⚖️</div>
                    <div>
                        <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;">Peso</h5>
                        <p style="margin: 0; font-size: 11px; color: #94a3b8;">Em relação à altura</p>
                    </div>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="font-size: 24px; font-weight: 700; color: #7c3aed;">
                        <?php echo $peso ? esc_html($peso) . " kg" : "—"; ?>
                    </span>
                </div>
                <div>
                    <span class="pab-badge pab-badge-<?php echo esc_attr(
                        $class_peso["nivel"],
                    ); ?>">
                        <?php echo esc_html($class_peso["ref"]); ?>
                    </span>
                </div>
            </div>

        </div>

        <!-- Interpretação Geral -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 10px; padding: 20px; border: 1px solid #fbbf24;">
            <h4 style="margin: 0 0 12px 0; color: #78350f; font-size: 15px; font-weight: 600;">
                <span style="font-size: 20px; margin-right: 8px;">💡</span>
                Interpretação do Diagnóstico
            </h4>

            <?php
            // Análise combinada
            $alertas = [];

            // IMC
            if (in_array($class_imc["nivel"], ["acima2", "acima3", "alto1"])) {
                $alertas[] = [
                    "icon" => "📏",
                    "title" => "IMC Elevado",
                    "desc" =>
                        "O IMC indica sobrepeso ou obesidade. Considere intervenção nutricional.",
                ];
            }

            // Gordura Corporal
            if (in_array($class_gc["nivel"], ["acima2", "alto1"])) {
                $alertas[] = [
                    "icon" => "🔥",
                    "title" => "Gordura Corporal Alta",
                    "desc" =>
                        "Percentual de gordura acima do recomendado. Atividade física é importante.",
                ];
            }

            // Gordura Visceral
            if (in_array($class_gv["nivel"], ["alto1", "alto2"])) {
                $alertas[] = [
                    "icon" => "⚠️",
                    "title" => "Atenção: Gordura Visceral",
                    "desc" =>
                        "Gordura visceral elevada aumenta riscos metabólicos. Requer acompanhamento.",
                ];
            }

            // Se não há alertas, tudo normal
            if (empty($alertas)): ?>
            <div style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.7); border-radius: 8px;">
                <span style="font-size: 32px; margin-right: 16px;">✅</span>
                <div>
                    <p style="margin: 0; font-size: 14px; color: #78350f; line-height: 1.6;">
                        <strong>Composição corporal adequada!</strong><br>
                        Os indicadores estão dentro dos parâmetros normais para gênero e idade.
                    </p>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($alertas as $alerta): ?>
                <div style="margin-bottom: 12px; padding: 12px; background: rgba(255,255,255,0.7); border-radius: 8px; border-left: 3px solid #f59e0b;">
                    <p style="margin: 0 0 4px 0; font-size: 13px; font-weight: 600; color: #78350f;">
                        <?php echo $alerta["icon"]; ?> <?php echo esc_html(
     $alerta["title"],
 ); ?>
                    </p>
                    <p style="margin: 0; font-size: 12px; color: #92400e; line-height: 1.5;">
                        <?php echo esc_html($alerta["desc"]); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            <?php endif;?>
        </div>

    </div>
    <?php
}
