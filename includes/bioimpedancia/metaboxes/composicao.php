<?php
/**
 * Metabox: Composição Corporal (Bioimpedância)
 *
 * Exibe a tabela detalhada de composição corporal com classificações OMS
 *
 * @package PAB
 * @subpackage Bioimpedancia\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de composição corporal
 *
 * @param WP_Post $post O post atual
 */
function pab_bi_comp_tab_cb($post)
{
    $pid = (int) pab_get($post->ID, "pab_paciente_id");
    if (!$pid) {
        echo '<div class="pab-alert pab-alert-warning">
            <strong>⚠️ Atenção:</strong> Vincule um paciente para exibir a composição corporal.
        </div>';
        return;
    }

    // Dados do paciente
    $genero = pab_get($pid, "pab_genero", "M");
    $altura_cm = (float) pab_get($pid, "pab_altura");
    $idade_real = pab_calc_idade_real($pid);

    // Dados da bioimpedância
    $peso = (float) pab_get($post->ID, "pab_bi_peso");
    $gc = (float) pab_get($post->ID, "pab_bi_gordura_corporal");
    $me = (float) pab_get($post->ID, "pab_bi_musculo_esq");
    $gv = (float) pab_get($post->ID, "pab_bi_gordura_visc");
    $mb = (float) pab_get($post->ID, "pab_bi_metab_basal");
    $idade_corp = (float) pab_get($post->ID, "pab_bi_idade_corporal");

    // Cálculos
    $altura_m = $altura_cm ? $altura_cm / 100.0 : null;
    $imc =
        $altura_m && $peso ? round($peso / ($altura_m * $altura_m), 1) : null;

    // Classificações OMS
    $class_peso = pab_oms_classificacao("peso", $peso, $genero, $idade_real, [
        "altura_cm" => $altura_cm,
    ]);
    $class_gc = pab_oms_classificacao("gc", $gc, $genero, $idade_real);
    $class_me = pab_oms_classificacao("musculo", $me, $genero, $idade_real);
    $class_imc = pab_oms_classificacao("imc", $imc, $genero, $idade_real);
    $class_gv = pab_oms_classificacao("gv", $gv, $genero, $idade_real);
    ?>
    <div class="pab-fade-in">
        <div class="pab-comp-tab-wrapper">
            <!-- COLUNA: Peso e IMC -->
            <div class="pab-comp-tab-col">

                <div class="pab-comp-tab-card">
                    <div class="pab-comp-tab-header">
                        <div class="pab-comp-tab-icon">⚖️</div>
                        <h4>Peso</h4>
                    </div>

                    <div class="pab-comp-tab-body">
                        <span class="pab-comp-tab-value"><?php echo $peso
                            ? esc_html($peso)
                            : "—"; ?> <small>kg</small></span>
                        <?php
                        $faixa = pab_calc_faixa_peso_ideal($altura_cm);
                        if ($faixa) {
                            echo '<div class="pab-comp-tab-ideal">';
                            echo '<span class="pab-comp-tab-ideal-label">Ideal:</span> ';
                            echo '<span class="pab-comp-tab-ideal-value">' .
                                esc_html($faixa["min"]) .
                                " - " .
                                esc_html($faixa["max"]) .
                                " kg</span>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <div class="pab-comp-tab-footer">
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $class_peso["nivel"],
                        ); ?>">
                            <?php echo esc_html($class_peso["ref"]); ?>
                        </span>
                    </div>
                    <div class="pab-comp-tab-sep"></div>
                </div>

                <!-- IMC -->
                <div class="pab-comp-tab-card">
                    <div class="pab-comp-tab-header">
                        <div class="pab-comp-tab-icon">📊</div>
                        <h4>IMC</h4>
                    </div>

                    <div class="pab-comp-tab-body">
                        <span class="pab-comp-tab-value"><?php echo $imc
                            ? esc_html($imc)
                            : "—"; ?></span>
                        <div class="pab-comp-tab-formula">
                            <span class="pab-comp-tab-formula-text">
                                Peso (kg) / Altura² (m)
                            </span>
                        </div>
                    </div>

                    <div class="pab-comp-tab-footer">
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $class_imc["nivel"],
                        ); ?>">
                            <?php echo esc_html($class_imc["ref"]); ?>
                        </span>
                    </div>
                    <div class="pab-comp-tab-sep"></div>
                </div>

            </div>

            <!-- COLUNA: Gordura Corporal e Músculo -->
            <div class="pab-comp-tab-col">

                <div class="pab-comp-tab-card">
                    <div class="pab-comp-tab-header">
                        <div class="pab-comp-tab-icon">🔥</div>
                        <h4>Gordura Corporal</h4>
                    </div>

                    <div class="pab-comp-tab-body">
                        <span class="pab-comp-tab-value"><?php echo $gc
                            ? esc_html($gc)
                            : "—"; ?> <small>%</small></span>
                    </div>

                    <div class="pab-comp-tab-footer">
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $class_gc["nivel"],
                        ); ?>">
                            <?php echo esc_html($class_gc["ref"]); ?>
                        </span>
                    </div>
                    <div class="pab-comp-tab-sep"></div>
                </div>

                <!-- Músculo Esquelético -->
                <div class="pab-comp-tab-card">
                    <div class="pab-comp-tab-header">
                        <div class="pab-comp-tab-icon">💪</div>
                        <h4>Músculo Esquelético</h4>
                    </div>

                    <div class="pab-comp-tab-body">
                        <span class="pab-comp-tab-value"><?php echo $me
                            ? esc_html($me)
                            : "—"; ?> <small>%</small></span>
                    </div>

                    <div class="pab-comp-tab-footer">
                        <span class="pab-badge pab-badge-<?php echo esc_attr(
                            $class_me["nivel"],
                        ); ?>">
                            <?php echo esc_html($class_me["ref"]); ?>
                        </span>
                    </div>
                    <div class="pab-comp-tab-sep"></div>
                </div>

            </div>

            <!-- COLUNA: Gordura Visceral, Metab. Basal e Idade Corporal -->
            <div class="pab-comp-tab-col">

                <div class="pab-comp-tab-card">
                    <div class="pab-comp-tab-icon">🫀</div>
                    <h4>Gordura Visceral</h4>
                </div>

                <div class="pab-comp-tab-body">
                    <span class="pab-comp-tab-value"><?php echo $gv
                        ? esc_html($gv)
                        : "—"; ?></span>
                </div>

                <div class="pab-comp-tab-footer">
                    <span class="pab-badge pab-badge-<?php echo esc_attr(
                        $class_gv["nivel"],
                    ); ?>">
                        <?php echo esc_html($class_gv["ref"]); ?>
                    </span>
                </div>

                <!-- Metabolismo Basal -->
                <div class="pab-comp-tab-misc">
                    <span class="pab-comp-tab-misc-label">⚡ Metab. Basal:</span>
                    <span class="pab-comp-tab-misc-value"><?php echo $mb
                        ? esc_html($mb) . " kcal"
                        : "—"; ?></span>
                </div>

                <!-- Idade Corporal -->
                <div class="pab-comp-tab-misc">
                    <?php
                    $diff = "";
                    $diff_class = "";
                    if ($idade_corp && $idade_real) {
                        $delta = $idade_corp - $idade_real;
                        if ($delta > 0) {
                            $diff = " (+" . $delta . " anos)";
                            $diff_class = "pab-age-older";
                        } elseif ($delta < 0) {
                            $diff = " (" . $delta . " anos)";
                            $diff_class = "pab-age-younger";
                        }
                    }
                    ?>
                    <span class="pab-comp-tab-misc-label">🕐 Idade Corporal:</span>
                    <span class="pab-comp-tab-misc-value <?php echo esc_attr(
                        $diff_class,
                    ); ?>">
                        <?php echo $idade_corp
                            ? esc_html($idade_corp) . " anos"
                            : "—"; ?>
                        <?php if ($diff): ?>
                            <small class="pab-age-diff"><?php echo esc_html(
                                $diff,
                            ); ?></small>
                        <?php endif; ?>
                    </span>
                </div>

            </div>
        </div>

        <!-- Legenda de Classificações -->
        <div class="pab-comp-tab-legend">
            <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                📌 Legenda de Classificações
            </h4>
            <p style="margin: 0; font-size: 12px; color: #6b7280; line-height: 1.8;">
                <span class="pab-badge pab-badge-abaixo" style="margin-right: 8px;">Abaixo</span>
                <span class="pab-badge pab-badge-normal" style="margin-right: 8px;">Normal</span>
                <span class="pab-badge pab-badge-acima1" style="margin-right: 8px;">Limítrofe</span>
                <span class="pab-badge pab-badge-acima2" style="margin-right: 8px;">Elevado</span>
                <span class="pab-badge pab-badge-alto1" style="margin-right: 8px;">Alto</span>
                <span class="pab-badge pab-badge-alto2" style="margin-right: 8px;">Muito Alto</span>
            </p>
        </div>
    </div>
    <?php
}
