<?php
/**
 * Metabox: Histórico Ginecológico (Avaliação)
 *
 * Exibe o formulário de histórico ginecológico da avaliação
 *
 * @package PAB
 * @subpackage Avaliacao\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de histórico ginecológico
 *
 * @param WP_Post $post O post atual
 */
function pab_av_gineco_cb($post)
{
    $f = [
        "mens" => pab_get($post->ID, "pab_av_mens"),
        "tpm" => pab_get($post->ID, "pab_av_tpm"),
        "meno_s" => pab_get($post->ID, "pab_av_meno_sim"),
        "meno_idade" => pab_get($post->ID, "pab_av_meno_idade"),
        "gest_s" => pab_get($post->ID, "pab_av_gest_sim"),
        "gest_qt" => pab_get($post->ID, "pab_av_gest_qt"),
        "filhos" => pab_get($post->ID, "pab_av_filhos"),
        "med_s" => pab_get($post->ID, "pab_av_gine_med_sim"),
        "med_q" => pab_get($post->ID, "pab_av_gine_med_quais"),
    ]; ?>
    <div class="pab-grid">
        <label><strong>Menstruação</strong>
            <select name="pab_av_mens">
                <option value="regular" <?php selected(
                    $f["mens"],
                    "regular",
                ); ?>>Regular</option>
                <option value="irregular" <?php selected(
                    $f["mens"],
                    "irregular",
                ); ?>>Irregular</option>
            </select>
        </label>
        <label><strong>TPM</strong>
            <select name="pab_av_tpm">
                <option value="nao" <?php selected(
                    $f["tpm"],
                    "nao",
                ); ?>>Não</option>
                <option value="sim" <?php selected(
                    $f["tpm"],
                    "sim",
                ); ?>>Sim</option>
            </select>
        </label>
        <label><strong>Menopausa</strong>
            <select name="pab_av_meno_sim" class="pab-toggle" data-target="#meno_box">
                <option value="nao" <?php selected(
                    $f["meno_s"],
                    "nao",
                ); ?>>Não</option>
                <option value="sim" <?php selected(
                    $f["meno_s"],
                    "sim",
                ); ?>>Sim</option>
            </select>
        </label>
        <div id="meno_box" class="pab-conditional" data-show="sim">
            <label><strong>Idade da menopausa</strong><input type="text" name="pab_av_meno_idade" value="<?php echo esc_attr(
                $f["meno_idade"],
            ); ?>"></label>
        </div>
        <label><strong>Gestação</strong>
            <select name="pab_av_gest_sim" class="pab-toggle" data-target="#gest_box">
                <option value="nao" <?php selected(
                    $f["gest_s"],
                    "nao",
                ); ?>>Não</option>
                <option value="sim" <?php selected(
                    $f["gest_s"],
                    "sim",
                ); ?>>Sim</option>
            </select>
        </label>
        <div id="gest_box" class="pab-conditional" data-show="sim">
            <label><strong>Quantas</strong><input type="number" name="pab_av_gest_qt" value="<?php echo esc_attr(
                $f["gest_qt"],
            ); ?>"></label>
            <label><strong>Nº de filhos</strong><input type="number" name="pab_av_filhos" value="<?php echo esc_attr(
                $f["filhos"],
            ); ?>"></label>
            <label><strong>Faz uso de medicamentos</strong>
                <select name="pab_av_gine_med_sim" class="pab-toggle" data-target="#gmed_box">
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
            <div id="gmed_box" class="pab-conditional" data-show="sim">
                <label><strong>Qual(is)</strong><input type="text" name="pab_av_gine_med_quais" value="<?php echo esc_attr(
                    $f["med_q"],
                ); ?>"></label>
            </div>
        </div>
    </div>
    <?php
}
