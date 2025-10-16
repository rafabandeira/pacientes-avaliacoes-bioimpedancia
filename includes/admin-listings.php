<?php // includes/admin-listings.php - CORRIGIDO V2: Usando parse_query para evitar Fatal Error

if (!defined('ABSPATH')) exit;

/**
 * Pré-carrega todos os metadados dos posts listados para evitar consultas N+1
 * e exaustão de memória na função pab_get().
 *
 * Utiliza 'parse_query' (ou 'pre_get_posts' seria outra opção segura) para acessar
 * o objeto WP_Query e garantir que a otimização ocorra apenas na tela de listagem.
 */
add_action('parse_query', function(\WP_Query $query) {
    global $pagenow;
    
    // Verifica se estamos no painel de admin, na tela de edição (edit.php)
    // e se o post type é 'pab_paciente'.
    if (
        !is_admin() ||
        $pagenow !== 'edit.php' ||
        !$query->is_main_query() ||
        $query->get('post_type') !== 'pab_paciente'
    ) {
        return;
    }
    
    // Se a query já foi executada (o que acontece antes de 'parse_query' em certos casos)
    // podemos usar a lista de posts para pré-carregar o cache.
    // Embora 'parse_query' seja antes da busca, vamos usar 'pre_get_posts' para ser mais robusto.
    
    // **NOTA DE REVISÃO:** Usar `pre_get_posts` é mais canônico para manipular a query. 
    // Vamos usar a função `get_meta_cache` para garantir o carregamento antes da renderização das colunas.
    
    // Otimização: Garantir que o cache seja carregado antes de renderizar as colunas.
    // Usaremos `manage_posts_extra_tablenav` que é executado após a query e antes da tabela.
    
    // Desativando o hook problemático e usando o método canônico em seguida.
    // Este hook foi a causa do erro, o removeremos e usaremos o `manage_posts_extra_tablenav` no final do arquivo.
}, 10, 1);


/**
 * Define as colunas personalizadas na listagem de Pacientes.
 */
add_filter('manage_pab_paciente_posts_columns', function($cols){
    // Remove colunas que podem não ser relevantes e define a ordem
    unset($cols['date']);

    $new_cols = [
        'cb'             => $cols['cb'],             // Checkbox
        'title'          => __('Nome do Paciente', 'pab'), // Usa title, mas renomeado
        'pab_genero'     => __('Gênero', 'pab'),
        'pab_nascimento' => __('Nascimento', 'pab'),
        'pab_contato'    => __('Contato', 'pab'),
        'date'           => __('Data de Cadastro', 'pab')
    ];

    return $new_cols;
});

/**
 * Popula as colunas personalizadas.
 */
add_action('manage_pab_paciente_posts_custom_column', function($col, $post_id){
    // pab_get() agora será performático graças ao hook abaixo.

    switch ($col) {
        case 'pab_genero':
            echo esc_html(pab_get($post_id, 'pab_genero'));
            break;

        case 'pab_nascimento':
            $data = pab_get($post_id, 'pab_nascimento');
            // Formata a data para padrão brasileiro
            echo $data ? esc_html(date('d/m/Y', strtotime($data))) : '-';
            break;

        case 'pab_contato':
            $celular = pab_get($post_id, 'pab_celular');
            $email = pab_get($post_id, 'pab_email');
            
            $output = [];
            if ($celular) {
                $output[] = esc_html($celular);
            }
            if ($email) {
                $output[] = esc_html($email);
            }
            
            echo implode(' | ', $output);
            break;
    }
}, 10, 2);


/**
 * Otimização: Força o carregamento do cache de meta antes da renderização da tabela.
 * Isso garante que pab_get() não faça consultas individuais ao banco.
 */
add_action('manage_posts_extra_tablenav', function($which) {
    if (is_admin() && $which === 'top') {
        global $wp_query;
        
        // Verifica se a query principal é a do nosso CPT e tem resultados
        if ($wp_query->get('post_type') === 'pab_paciente' && $wp_query->have_posts()) {
            // Usa a função do core para pré-carregar os metadados dos posts listados
            update_meta_cache('post', $wp_query->posts);
        }
    }
}, 10, 1);