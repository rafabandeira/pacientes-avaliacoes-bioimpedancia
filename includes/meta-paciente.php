<?php // includes/meta-paciente.php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function() {
    add_meta_box('pab_paciente_dados', 'Dados do Paciente', 'pab_paciente_dados_cb', 'pab_paciente', 'normal', 'high');
    add_meta_box('pab_paciente_avaliacoes', 'Avaliações do Paciente', 'pab_paciente_avaliacoes_cb', 'pab_paciente', 'normal', 'default');
    add_meta_box('pab_paciente_bioimps', 'Bioimpedâncias do Paciente', 'pab_paciente_bioimps_cb', 'pab_paciente', 'normal', 'default');
});

function pab_paciente_dados_cb($post) {
    wp_nonce_field('pab_paciente_dados', 'pab_paciente_nonce');
    $f = [
        'pab_nome' => pab_get($post->ID, 'pab_nome'),
        'pab_genero' => pab_get($post->ID, 'pab_genero'),
        'pab_nascimento' => pab_get($post->ID, 'pab_nascimento'),
        'pab_altura' => pab_get($post->ID, 'pab_altura'),
        'pab_celular' => pab_get($post->ID, 'pab_celular'),
        'pab_email' => pab_get($post->ID, 'pab_email'),
    ];
    ?>
    <div class="pab-grid">
        <label><strong>Nome do Paciente</strong><input type="text" name="pab_nome" value="<?php echo esc_attr($f['pab_nome']); ?>" /></label>
        <label><strong>Gênero</strong>
            <select name="pab_genero">
                <option value="">Selecione</option>
                <option value="M" <?php selected($f['pab_genero'], 'M'); ?>>Masculino</option>
                <option value="F" <?php selected($f['pab_genero'], 'F'); ?>>Feminino</option>
            </select>
        </label>
        <label><strong>Data Nascimento</strong><input type="date" name="pab_nascimento" value="<?php echo esc_attr($f['pab_nascimento']); ?>" /></label>
        <label><strong>Altura (cm)</strong><input type="number" step="0.1" name="pab_altura" value="<?php echo esc_attr($f['pab_altura']); ?>" /></label>
        <label><strong>Celular/WhatsApp</strong><input type="text" name="pab_celular" value="<?php echo esc_attr($f['pab_celular']); ?>" /></label>
        <label><strong>E-mail</strong><input type="email" name="pab_email" value="<?php echo esc_attr($f['pab_email']); ?>" /></label>
    </div>
    <?php
}

add_action('save_post_pab_paciente', function($post_id) {
    if (!isset($_POST['pab_paciente_nonce']) || !wp_verify_nonce($_POST['pab_paciente_nonce'], 'pab_paciente_dados')) return;
    $fields = ['pab_nome','pab_genero','pab_nascimento','pab_altura','pab_celular','pab_email'];
    foreach ($fields as $k) {
        if (isset($_POST[$k])) update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
    }
}, 10, 1);

function pab_paciente_avaliacoes_cb($post) {
    $query = new WP_Query([
        'post_type' => 'pab_avaliacao',
        'post_parent' => $post->ID,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    echo '<div class="pab-list-actions">';
    $url = admin_url('post-new.php?post_type=pab_avaliacao&pab_attach=' . $post->ID);
    echo '<a class="button button-primary" href="' . esc_url($url) . '">Cadastrar Avaliação</a>';
    echo '</div><ul class="pab-list">';
    foreach ($query->posts as $p) {
        echo '<li><a href="' . get_edit_post_link($p->ID) . '">' . esc_html(get_the_title($p)) . '</a> — ' . esc_html(get_the_date('', $p)) . '</li>';
    }
    echo '</ul>';
}

function pab_paciente_bioimps_cb($post) {
    $query = new WP_Query([
        'post_type' => 'pab_bioimpedancia',
        'post_parent' => $post->ID,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    echo '<div class="pab-list-actions">';
    $url = admin_url('post-new.php?post_type=pab_bioimpedancia&pab_attach=' . $post->ID);
    echo '<a class="button button-primary" href="' . esc_url($url) . '">Cadastrar Bioimpedância</a>';
    echo '</div><ul class="pab-list">';
    foreach ($query->posts as $p) {
        echo '<li><a href="' . get_edit_post_link($p->ID) . '">' . esc_html(get_the_title($p)) . '</a> — ' . esc_html(get_the_date('', $p)) . '</li>';
    }
    echo '</ul>';
}

/**
 * Ao criar uma avaliação ou bioimpedância pelo botão, já associar o paciente.
 */
add_action('load-post-new.php', function() {
    if (!isset($_GET['pab_attach'])) return;
    $patient_id = (int) $_GET['pab_attach'];
    $pt = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
    if (!in_array($pt, ['pab_avaliacao','pab_bioimpedancia'])) return;

    add_action('admin_footer', function() use ($patient_id, $pt) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const patientId = <?php echo (int)$patient_id; ?>;
            // cria um input hidden com o paciente
            const form = document.getElementById('post');
            if (form) {
                let hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'pab_paciente_id';
                hidden.value = patientId;
                form.appendChild(hidden);
            }
        });
        </script>
        <?php
    });
});
