<?php
/**
 * Metabox: Avaliações do Paciente
 *
 * Exibe a lista de avaliações vinculadas ao paciente
 *
 * @package PAB
 * @subpackage Paciente\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de avaliações do paciente
 *
 * @param WP_Post $post O post atual
 */
function pab_paciente_avaliacoes_cb($post)
{
    $query = new WP_Query([
        'post_type' => 'pab_avaliacao',
        'post_parent' => $post->ID,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    echo '<div class="pab-list-actions">';
    $url = admin_url(
        'post-new.php?post_type=pab_avaliacao&pab_attach=' . $post->ID
    );
    echo '<a class="button button-primary" href="' .
        esc_url($url) .
        '">Cadastrar Avaliação</a>';
    echo '</div><ul class="pab-list">';

    if ($query->have_posts()) {
        foreach ($query->posts as $p) {
            echo '<li><a href="' .
                get_edit_post_link($p->ID) .
                '">' .
                esc_html(get_the_title($p)) .
                '</a> — ' .
                esc_html(get_the_date('', $p)) .
                '</li>';
        }
    } else {
        echo '<li style="color: #666; font-style: italic;">Nenhuma avaliação cadastrada ainda.</li>';
    }

    echo '</ul>';
}
