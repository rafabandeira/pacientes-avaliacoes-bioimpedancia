<?php
/**
 * Metabox: Histórico (Medidas)
 *
 * Exibe o histórico de medidas corporais do paciente
 *
 * @package PAB
 * @subpackage Medidas\Metaboxes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza a metabox de histórico de medidas
 *
 * @param WP_Post $post O post atual
 */
function pab_med_historico_cb($post)
{
    $patient_id = (int) pab_get($post->ID, 'pab_paciente_id');

    if (!$patient_id) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>📊 Histórico:</strong> O histórico será exibido após vincular a um paciente e salvar as medidas.
        </div>';
        return;
    }

    // Buscar todas as medidas do paciente, excluindo a atual se estiver sendo editada
    $args = [
        'post_type' => 'pab_medidas',
        'post_parent' => $patient_id,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => 'pab_paciente_id',
                'value' => $patient_id,
                'compare' => '='
            ]
        ]
    ];

    // Se estamos editando um post existente, excluí-lo dos resultados
    if ($post->ID) {
        $args['post__not_in'] = [$post->ID];
    }

    $medidas_query = new WP_Query($args);
    $medidas = $medidas_query->posts;

    if (empty($medidas)) {
        echo '<div class="pab-alert pab-alert-info">
            <strong>📊 Histórico:</strong> Este é o primeiro registro de medidas para este paciente.
        </div>';
        return;
    }

    ?>
    <style>
        .pab-historico-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
        }

        .pab-historico-item {
            border-bottom: 1px solid #f1f5f9;
            padding: 16px;
            transition: background-color 0.2s ease;
        }

        .pab-historico-item:last-child {
            border-bottom: none;
        }

        .pab-historico-item:hover {
            background-color: #f8fafc;
        }

        .pab-historico-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .pab-historico-data {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
        }

        .pab-historico-actions {
            display: flex;
            gap: 8px;
        }

        .pab-btn-small {
            padding: 4px 8px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .pab-btn-view {
            background: #e0f2fe;
            color: #0369a1;
        }

        .pab-btn-view:hover {
            background: #0369a1;
            color: white;
        }

        .pab-btn-edit {
            background: #fef3c7;
            color: #d97706;
        }

        .pab-btn-edit:hover {
            background: #d97706;
            color: white;
        }

        .pab-medidas-grid-small {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .pab-medida-item {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 4px;
            border-left: 3px solid #3b82f6;
        }

        .pab-medida-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .pab-medida-valor {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }

        .pab-no-data {
            color: #9ca3af;
            font-style: italic;
            font-size: 13px;
        }

        .pab-comparacao {
            margin-top: 12px;
            padding: 12px;
            background: #f0f9ff;
            border-radius: 6px;
            border: 1px solid #bae6fd;
        }

        .pab-comparacao-title {
            font-size: 13px;
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 8px;
        }

        .pab-diferenca {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        .pab-diferenca.positiva {
            background: #fecaca;
            color: #dc2626;
        }

        .pab-diferenca.negativa {
            background: #bbf7d0;
            color: #16a34a;
        }

        .pab-diferenca.neutra {
            background: #e5e7eb;
            color: #6b7280;
        }
    </style>

    <div class="pab-historico-container">
        <?php foreach ($medidas as $index => $medida_post): ?>
            <?php
            $medida_data = get_the_date('d/m/Y H:i', $medida_post->ID);
            $medida_link_edit = get_edit_post_link($medida_post->ID);
            $medida_link_view = get_permalink($medida_post->ID);

            // Obter os dados das medidas
            $dados_medida = [
                'Pescoço' => pab_get($medida_post->ID, 'pab_med_pescoco'),
                'Tórax' => pab_get($medida_post->ID, 'pab_med_torax'),
                'Braço Dir.' => pab_get($medida_post->ID, 'pab_med_braco_direito'),
                'Braço Esq.' => pab_get($medida_post->ID, 'pab_med_braco_esquerdo'),
                'Abd. Superior' => pab_get($medida_post->ID, 'pab_med_abd_superior'),
                'Cintura' => pab_get($medida_post->ID, 'pab_med_cintura'),
                'Abd. Inferior' => pab_get($medida_post->ID, 'pab_med_abd_inferior'),
                'Quadril' => pab_get($medida_post->ID, 'pab_med_quadril'),
                'Coxa Dir.' => pab_get($medida_post->ID, 'pab_med_coxa_direita'),
                'Coxa Esq.' => pab_get($medida_post->ID, 'pab_med_coxa_esquerda'),
                'Panturr. Dir.' => pab_get($medida_post->ID, 'pab_med_panturrilha_direita'),
                'Panturr. Esq.' => pab_get($medida_post->ID, 'pab_med_panturrilha_esquerda'),
            ];

            // Dados atuais para comparação (se estamos editando)
            $dados_atuais = [];
            if ($post->ID) {
                $dados_atuais = [
                    'Pescoço' => pab_get($post->ID, 'pab_med_pescoco'),
                    'Tórax' => pab_get($post->ID, 'pab_med_torax'),
                    'Braço Dir.' => pab_get($post->ID, 'pab_med_braco_direito'),
                    'Braço Esq.' => pab_get($post->ID, 'pab_med_braco_esquerdo'),
                    'Abd. Superior' => pab_get($post->ID, 'pab_med_abd_superior'),
                    'Cintura' => pab_get($post->ID, 'pab_med_cintura'),
                    'Abd. Inferior' => pab_get($post->ID, 'pab_med_abd_inferior'),
                    'Quadril' => pab_get($post->ID, 'pab_med_quadril'),
                    'Coxa Dir.' => pab_get($post->ID, 'pab_med_coxa_direita'),
                    'Coxa Esq.' => pab_get($post->ID, 'pab_med_coxa_esquerda'),
                    'Panturr. Dir.' => pab_get($post->ID, 'pab_med_panturrilha_direita'),
                    'Panturr. Esq.' => pab_get($post->ID, 'pab_med_panturrilha_esquerda'),
                ];
            }
            ?>

            <div class="pab-historico-item">
                <div class="pab-historico-header">
                    <span class="pab-historico-data">📏 <?php echo esc_html($medida_data); ?></span>
                    <div class="pab-historico-actions">
                        <?php if ($medida_link_view): ?>
                            <a href="<?php echo esc_url($medida_link_view); ?>"
                               class="pab-btn-small pab-btn-view"
                               target="_blank">Ver</a>
                        <?php endif; ?>
                        <?php if ($medida_link_edit): ?>
                            <a href="<?php echo esc_url($medida_link_edit); ?>"
                               class="pab-btn-small pab-btn-edit"
                               target="_blank">Editar</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pab-medidas-grid-small">
                    <?php foreach ($dados_medida as $label => $valor): ?>
                        <div class="pab-medida-item">
                            <div class="pab-medida-label"><?php echo esc_html($label); ?></div>
                            <div class="pab-medida-valor">
                                <?php if ($valor): ?>
                                    <?php echo esc_html($valor); ?> cm
                                    <?php
                                    // Mostrar comparação se estivermos editando e houver dados atuais
                                    if ($post->ID && isset($dados_atuais[$label]) && $dados_atuais[$label] && $valor) {
                                        $diferenca = floatval($dados_atuais[$label]) - floatval($valor);
                                        if (abs($diferenca) > 0.1) {
                                            $classe = $diferenca > 0 ? 'positiva' : 'negativa';
                                            $sinal = $diferenca > 0 ? '+' : '';
                                            echo '<span class="pab-diferenca ' . $classe . '">' .
                                                 $sinal . number_format($diferenca, 1) . '</span>';
                                        }
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="pab-no-data">Não informado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($index === 0 && $post->ID && array_filter($dados_atuais)): ?>
                    <div class="pab-comparacao">
                        <div class="pab-comparacao-title">
                            📊 Comparação com registro mais recente
                        </div>
                        <div style="font-size: 12px; color: #0c4a6e;">
                            <span class="pab-diferenca positiva">+1.2</span> = Aumento de 1.2cm
                            <span class="pab-diferenca negativa">-0.8</span> = Diminuição de 0.8cm
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll para o container do histórico
        const container = document.querySelector('.pab-historico-container');
        if (container) {
            container.style.scrollBehavior = 'smooth';
        }

        // Highlight do item mais recente
        const firstItem = document.querySelector('.pab-historico-item');
        if (firstItem) {
            firstItem.style.borderLeft = '4px solid #3b82f6';
            firstItem.style.backgroundColor = '#f8fafc';
        }
    });
    </script>
    <?php
}