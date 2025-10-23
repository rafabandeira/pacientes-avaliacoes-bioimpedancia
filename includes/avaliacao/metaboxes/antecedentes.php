<?php
/**
 * Metabox: Antecedentes Patológicos e Familiares (Avaliação)
 *
 * Exibe o formulário de antecedentes patológicos e familiares da avaliação
 *
 * @package PAB
 * @subpackage Avaliacao\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de antecedentes patológicos e familiares
 *
 * @param WP_Post $post O post atual
 */
function pab_av_antecedentes_cb($post)
{
    $f = [
        "patol" => pab_get($post->ID, "pab_av_patol"),
        "circ_s" => pab_get($post->ID, "pab_av_circ_sim"),
        "circ_q" => pab_get($post->ID, "pab_av_circ_quais"),
        "circ_fam" => pab_get($post->ID, "pab_av_circ_fam"),
        "end_s" => pab_get($post->ID, "pab_av_end_sim"),
        "end_q" => pab_get($post->ID, "pab_av_end_quais"),
        "end_fam" => pab_get($post->ID, "pab_av_end_fam"),
        "med_s" => pab_get($post->ID, "pab_av_med_sim"),
        "med_t" => pab_get($post->ID, "pab_av_med_tempo"),
        "med_q" => pab_get($post->ID, "pab_av_med_quais"),
    ]; ?>
    <div class="pab-grid">
        <label><strong>Antecedentes patológicos</strong><textarea rows="2" name="pab_av_patol"><?php echo esc_textarea(
            $f["patol"],
        ); ?></textarea></label>

        <label><strong>Distúrbios circulatórios</strong>
            <select name="pab_av_circ_sim" class="pab-toggle" data-target="#circ_box">
                <option value="nao" <?php selected(
                    $f["circ_s"],
                    "nao",
                ); ?>>Não</option>
                <option value="sim" <?php selected(
                    $f["circ_s"],
                    "sim",
                ); ?>>Sim</option>
            </select>
        </label>
        <div id="circ_box" class="pab-conditional" data-show="sim">
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_circ_quais" value="<?php echo esc_attr(
                $f["circ_q"],
            ); ?>"></label>
            <label><strong>Antecedentes familiares</strong>
                <select name="pab_av_circ_fam">
                    <option value="nao" <?php selected(
                        $f["circ_fam"],
                        "nao",
                    ); ?>>Não</option>
                    <option value="sim" <?php selected(
                        $f["circ_fam"],
                        "sim",
                    ); ?>>Sim</option>
                </select>
            </label>
        </div>

        <label><strong>Distúrbios endócrino-metabólicos</strong>
            <select name="pab_av_end_sim" class="pab-toggle" data-target="#end_box">
                <option value="nao" <?php selected(
                    $f["end_s"],
                    "nao",
                ); ?>>Não</option>
                <option value="sim" <?php selected(
                    $f["end_s"],
                    "sim",
                ); ?>>Sim</option>
            </select>
        </label>
        <div id="end_box" class="pab-conditional" data-show="sim">
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_end_quais" value="<?php echo esc_attr(
                $f["end_q"],
            ); ?>"></label>
            <label><strong>Antecedentes familiares</strong>
                <select name="pab_av_end_fam">
                    <option value="nao" <?php selected(
                        $f["end_fam"],
                        "nao",
                    ); ?>>Não</option>
                    <option value="sim" <?php selected(
                        $f["end_fam"],
                        "sim",
                    ); ?>>Sim</option>
                </select>
            </label>
        </div>

        <label><strong>Uso de medicamentos</strong>
            <select name="pab_av_med_sim" class="pab-toggle" data-target="#med_box">
                <option value="nao" <?php selected(
                    $f["med_s"],
                    "nao",
                ); ?>>Não</option>
                <option value="sim" <?php selected(
                    $f["med_s"],
                    "sim",
                ); ?>>Sim</option>
            </select>
        </label>
        <div id="med_box" class="pab-conditional" data-show="sim">
            <label><strong>Quanto tempo</strong><input type="text" name="pab_av_med_tempo" value="<?php echo esc_attr(
                $f["med_t"],
            ); ?>"></label>
            <label><strong>Qual(is)</strong><input type="text" name="pab_av_med_quais" value="<?php echo esc_attr(
                $f["med_q"],
            ); ?>"></label>
        </div>
    </div>
    <?php
}
