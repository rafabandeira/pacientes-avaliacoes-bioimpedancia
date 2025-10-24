<?php
/**
 * Template para visualização pública de Medidas
 *
 * @package PAB
 * @subpackage Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obter dados do post atual
global $post;
$patient_id = (int) pab_get($post->ID, 'pab_paciente_id');
$patient_name = $patient_id ? get_the_title($patient_id) : 'Paciente não vinculado';

// Obter dados das medidas
$medidas = [
    'pescoco' => pab_get($post->ID, 'pab_med_pescoco'),
    'torax' => pab_get($post->ID, 'pab_med_torax'),
    'braco_direito' => pab_get($post->ID, 'pab_med_braco_direito'),
    'braco_esquerdo' => pab_get($post->ID, 'pab_med_braco_esquerdo'),
    'abd_superior' => pab_get($post->ID, 'pab_med_abd_superior'),
    'cintura' => pab_get($post->ID, 'pab_med_cintura'),
    'abd_inferior' => pab_get($post->ID, 'pab_med_abd_inferior'),
    'quadril' => pab_get($post->ID, 'pab_med_quadril'),
    'coxa_direita' => pab_get($post->ID, 'pab_med_coxa_direita'),
    'coxa_esquerda' => pab_get($post->ID, 'pab_med_coxa_esquerda'),
    'panturrilha_direita' => pab_get($post->ID, 'pab_med_panturrilha_direita'),
    'panturrilha_esquerda' => pab_get($post->ID, 'pab_med_panturrilha_esquerda'),
];

// Meta tags para compartilhamento
function pab_add_medidas_opengraph() {
    global $post;
    if (is_single() && $post->post_type === 'pab_medidas') {
        $patient_id = (int) pab_get($post->ID, 'pab_paciente_id');
        $patient_name = $patient_id ? get_the_title($patient_id) : 'Paciente';
        $title = "Medidas Corporais - {$patient_name}";
        $description = "Relatório de medidas corporais registradas em " . get_the_date('d/m/Y');
        $url = get_permalink();

        echo "\n<!-- PAB Medidas Open Graph -->\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        // Twitter Card
        echo '<meta name="twitter:card" content="summary">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        echo "<!-- /PAB Medidas Open Graph -->\n\n";
    }
}
add_action('wp_head', 'pab_add_medidas_opengraph');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($patient_name . ' - Medidas Corporais - ' . get_the_date('d/m/Y')); ?></title>

    <!-- Favicon básico -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📏</text></svg>">

    <?php wp_head(); ?>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .pab-public-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .pab-public-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }

        .pab-public-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .pab-public-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .pab-public-header .meta {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .pab-metabox {
            margin: 40px;
            background: #f8fafc;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .pab-metabox-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 20px 30px;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pab-metabox-content {
            padding: 30px;
        }

        .pab-medidas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .pab-medidas-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .pab-medidas-section:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .pab-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .pab-section-icon {
            font-size: 2rem;
        }

        .pab-section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1e40af;
        }

        .pab-medida-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .pab-medida-item:last-child {
            border-bottom: none;
        }

        .pab-medida-label {
            font-weight: 500;
            color: #374151;
        }

        .pab-medida-valor {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1f2937;
            background: #f3f4f6;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .pab-no-data {
            color: #9ca3af;
            font-style: italic;
        }

        .pab-resumo-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #bae6fd;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .pab-resumo-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pab-resumo-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .pab-stat-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e0f2fe;
        }

        .pab-stat-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .pab-stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0c4a6e;
        }

        .pab-footer {
            background: #1f2937;
            color: #9ca3af;
            text-align: center;
            padding: 25px;
            font-size: 0.9rem;
        }

        .pab-footer strong {
            color: #f3f4f6;
        }

        @media (max-width: 768px) {
            .pab-public-container {
                margin: 10px;
                border-radius: 12px;
            }

            .pab-public-header {
                padding: 25px 20px;
            }

            .pab-public-header h1 {
                font-size: 1.8rem;
            }

            .pab-metabox {
                margin: 20px;
            }

            .pab-medidas-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .pab-resumo-stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .pab-public-container {
                box-shadow: none;
                border-radius: 0;
            }

            .pab-public-header {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
            }

            .pab-metabox {
                break-inside: avoid;
                margin: 20px 0;
            }

            .pab-footer {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="pab-public-container">
        <!-- Cabeçalho -->
        <div class="pab-public-header">
            <h1>📏 Medidas Corporais</h1>
            <div class="meta">
                <strong><?php echo esc_html($patient_name); ?></strong><br>
                Registrado em <?php echo get_the_date('d/m/Y \à\s H:i'); ?>
            </div>
        </div>

        <!-- Resumo -->
        <?php if (array_filter($medidas)): ?>
            <div class="pab-resumo-section">
                <div class="pab-resumo-title">
                    📊 Resumo das Medidas
                </div>
                <div class="pab-resumo-stats">
                    <?php if ($medidas['pescoco']): ?>
                        <div class="pab-stat-item">
                            <div class="pab-stat-label">Pescoço</div>
                            <div class="pab-stat-value"><?php echo esc_html($medidas['pescoco']); ?> cm</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($medidas['cintura']): ?>
                        <div class="pab-stat-item">
                            <div class="pab-stat-label">Cintura</div>
                            <div class="pab-stat-value"><?php echo esc_html($medidas['cintura']); ?> cm</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($medidas['quadril']): ?>
                        <div class="pab-stat-item">
                            <div class="pab-stat-label">Quadril</div>
                            <div class="pab-stat-value"><?php echo esc_html($medidas['quadril']); ?> cm</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($medidas['torax']): ?>
                        <div class="pab-stat-item">
                            <div class="pab-stat-label">Tórax</div>
                            <div class="pab-stat-value"><?php echo esc_html($medidas['torax']); ?> cm</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Medidas Detalhadas -->
        <div class="pab-metabox">
            <div class="pab-metabox-header">
                📏 Medidas Corporais Completas
            </div>
            <div class="pab-metabox-content">
                <div class="pab-medidas-grid">
                    <!-- Região Superior -->
                    <div class="pab-medidas-section">
                        <div class="pab-section-header">
                            <span class="pab-section-icon">👆</span>
                            <h3 class="pab-section-title">Região Superior</h3>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Pescoço</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['pescoco'] ? esc_html($medidas['pescoco']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Tórax</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['torax'] ? esc_html($medidas['torax']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Braços -->
                    <div class="pab-medidas-section">
                        <div class="pab-section-header">
                            <span class="pab-section-icon">💪</span>
                            <h3 class="pab-section-title">Braços</h3>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Braço Direito</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['braco_direito'] ? esc_html($medidas['braco_direito']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Braço Esquerdo</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['braco_esquerdo'] ? esc_html($medidas['braco_esquerdo']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Região Abdominal -->
                    <div class="pab-medidas-section">
                        <div class="pab-section-header">
                            <span class="pab-section-icon">🏃</span>
                            <h3 class="pab-section-title">Região Abdominal</h3>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Abdomen Superior</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['abd_superior'] ? esc_html($medidas['abd_superior']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Cintura</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['cintura'] ? esc_html($medidas['cintura']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Abdomen Inferior</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['abd_inferior'] ? esc_html($medidas['abd_inferior']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Quadril</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['quadril'] ? esc_html($medidas['quadril']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Pernas -->
                    <div class="pab-medidas-section">
                        <div class="pab-section-header">
                            <span class="pab-section-icon">🦵</span>
                            <h3 class="pab-section-title">Pernas</h3>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Coxa Direita</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['coxa_direita'] ? esc_html($medidas['coxa_direita']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Coxa Esquerda</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['coxa_esquerda'] ? esc_html($medidas['coxa_esquerda']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Panturrilha Direita</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['panturrilha_direita'] ? esc_html($medidas['panturrilha_direita']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                        <div class="pab-medida-item">
                            <span class="pab-medida-label">Panturrilha Esquerda</span>
                            <span class="pab-medida-valor">
                                <?php echo $medidas['panturrilha_esquerda'] ? esc_html($medidas['panturrilha_esquerda']) . ' cm' : '<span class="pab-no-data">Não informado</span>'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="pab-footer">
            <p>
                <strong>Sistema de Medidas Corporais</strong><br>
                Relatório gerado em <?php echo date('d/m/Y \à\s H:i'); ?><br>
                <small>Este documento contém informações confidenciais do paciente.</small>
            </p>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
