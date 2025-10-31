<?php
/**
 * Dashboard principal do plugin
 *
 * @package PAB
 * @subpackage Admin\Dashboard
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Enfileira os scripts necessários para os gráficos
 */
function pab_enqueue_dashboard_scripts()
{
    $screen = get_current_screen();
    if ($screen->id !== "pab_paciente_page_pab-dashboard") {
        return;
    }

    wp_enqueue_script(
        "chartjs",
        "https://cdn.jsdelivr.net/npm/chart.js",
        [],
        "4.4.0",
        true,
    );

    wp_enqueue_script(
        "pab-dashboard",
        PAB_URL . "assets/js/dashboard.js",
        ["jquery", "chartjs"],
        "1.0.0",
        true,
    );

    // Dados para os gráficos
    wp_localize_script("pab-dashboard", "pabDashboard", [
        "imcData" => pab_get_imc_distribution_data(),
        "avaliacoesData" => pab_get_avaliacoes_mensais_data(),
        "generoData" => pab_get_genero_distribution_data(),
    ]);
}
add_action("admin_enqueue_scripts", "pab_enqueue_dashboard_scripts");

/**
 * Obtém dados da distribuição de IMC
 */
function pab_get_imc_distribution_data()
{
    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
        SELECT
            CASE
                WHEN CAST(pm2.meta_value AS DECIMAL(10,2))/(CAST(pm3.meta_value AS DECIMAL(10,2))/100 * CAST(pm3.meta_value AS DECIMAL(10,2))/100) < 18.5 THEN 'Abaixo do Peso'
                WHEN CAST(pm2.meta_value AS DECIMAL(10,2))/(CAST(pm3.meta_value AS DECIMAL(10,2))/100 * CAST(pm3.meta_value AS DECIMAL(10,2))/100) < 25 THEN 'Normal'
                WHEN CAST(pm2.meta_value AS DECIMAL(10,2))/(CAST(pm3.meta_value AS DECIMAL(10,2))/100 * CAST(pm3.meta_value AS DECIMAL(10,2))/100) < 30 THEN 'Sobrepeso'
                ELSE 'Obesidade'
            END as categoria_imc,
            COUNT(*) as total
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
        JOIN {$wpdb->postmeta} pm3 ON p.post_parent = pm3.post_id AND pm3.meta_key = %s
        WHERE p.post_type = %s
        AND p.post_status = %s
        GROUP BY categoria_imc
        ORDER BY MIN(CAST(pm2.meta_value AS DECIMAL(10,2))/(CAST(pm3.meta_value AS DECIMAL(10,2))/100 * CAST(pm3.meta_value AS DECIMAL(10,2))/100))
    ",
            "pab_bi_peso",
            "pab_altura",
            "pab_bioimpedancia",
            "publish",
        ),
    );

    return $results;
}

/**
 * Obtém dados de avaliações mensais
 */
function pab_get_avaliacoes_mensais_data()
{
    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
        SELECT
            DATE_FORMAT(post_date, '%%Y-%%m') as mes,
            COUNT(*) as total
        FROM {$wpdb->posts}
        WHERE post_type IN ('pab_avaliacao', 'pab_bioimpedancia', 'pab_medidas')
        AND post_status = %s
        AND post_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY mes
        ORDER BY mes ASC
    ",
            "publish",
        ),
    );

    return $results;
}

/**
 * Obtém dados da distribuição por gênero
 */
function pab_get_genero_distribution_data()
{
    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
        SELECT
            COALESCE(pm.meta_value, 'Não informado') as genero,
            COUNT(*) as total
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = %s
        AND p.post_status = %s
        AND pm.meta_key = %s
        GROUP BY pm.meta_value
    ",
            "pab_paciente",
            "publish",
            "pab_genero",
        ),
    );

    return $results;
}

/**
 * Adiciona o menu do Dashboard
 */
function pab_add_dashboard_menu()
{
    // Adiciona apenas o Dashboard no início do menu
    add_submenu_page(
        "edit.php?post_type=pab_paciente",
        __("Dashboard PAB", "pab"),
        __("Dashboard", "pab"),
        "edit_posts",
        "pab-dashboard",
        "pab_render_dashboard",
        0,
    );
}
add_action("admin_menu", "pab_add_dashboard_menu", 0);

/**
 * Renderiza o conteúdo do Dashboard
 */
function pab_render_dashboard()
{
    // Obtém estatísticas gerais
    $total_pacientes = wp_count_posts("pab_paciente")->publish;
    $total_avaliacoes = wp_count_posts("pab_avaliacao")->publish;
    $total_bioimpedancias = wp_count_posts("pab_bioimpedancia")->publish;
    $total_medidas = wp_count_posts("pab_medidas")->publish;

    // Obtém últimas bioimpedâncias
    $ultimas_bio = get_posts([
        "post_type" => "pab_bioimpedancia",
        "posts_per_page" => 5,
        "orderby" => "date",
        "order" => "DESC",
    ]);

    // Obtém últimas avaliações
    $ultimas_aval = get_posts([
        "post_type" => "pab_avaliacao",
        "posts_per_page" => 5,
        "orderby" => "date",
        "order" => "DESC",
    ]);

    // Obtém últimas medidas
    $ultimas_medidas = get_posts([
        "post_type" => "pab_medidas",
        "posts_per_page" => 5,
        "orderby" => "date",
        "order" => "DESC",
    ]);
    ?>

    <div class="wrap pab-dashboard-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="pab-dashboard-cards">
            <div class="pab-card">
                <span class="pab-card-icon dashicons dashicons-groups"></span>
                <h3><?php _e("Total de Pacientes", "pab"); ?></h3>
                <div class="pab-card-number"><?php echo esc_html(
                    $total_pacientes,
                ); ?></div>
                <a href="<?php echo esc_url(
                    admin_url("edit.php?post_type=pab_paciente"),
                ); ?>" class="pab-card-button">
                    <?php _e("Ver Todos", "pab"); ?>
                </a>
            </div>

            <div class="pab-card">
                 <span class="pab-card-icon dashicons dashicons-analytics"></span>
                <h3><?php _e("Avaliações", "pab"); ?></h3>
                <div class="pab-card-number"><?php echo esc_html(
                    $total_avaliacoes,
                ); ?></div>
                <a href="<?php echo esc_url(
                    admin_url("edit.php?post_type=pab_avaliacao"),
                ); ?>" class="pab-card-button">
                    <?php _e("Ver Todas", "pab"); ?>
                </a>
            </div>

            <div class="pab-card">
                <span class="pab-card-icon dashicons dashicons-chart-line"></span>
                <h3><?php _e("Bioimpedâncias", "pab"); ?></h3>
                <div class="pab-card-number"><?php echo esc_html(
                    $total_bioimpedancias,
                ); ?></div>
                <a href="<?php echo esc_url(
                    admin_url("edit.php?post_type=pab_bioimpedancia"),
                ); ?>" class="pab-card-button">
                    <?php _e("Ver Todas", "pab"); ?>
                </a>
            </div>
            
            <div class="pab-card">
                <span class="pab-card-icon dashicons dashicons-editor-expand"></span>
                <h3><?php _e("Medidas", "pab"); ?></h3>
                <div class="pab-card-number"><?php echo esc_html(
                    $total_medidas,
                ); ?></div>
                <a href="<?php echo esc_url(
                    admin_url("edit.php?post_type=pab_medidas"),
                ); ?>" class="pab-card-button">
                    <?php _e("Ver Todas", "pab"); ?>
                </a>
            </div>
        </div>

        <div class="pab-dashboard-grid">
            <div class="pab-grid-item">
                <h2><?php _e("Últimas Bioimpedâncias", "pab"); ?></h2>
                <?php if (!empty($ultimas_bio)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e("Paciente", "pab"); ?></th>
                                <th><?php _e("Data", "pab"); ?></th>
                                <th><?php _e("IMC", "pab"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_bio as $bio):

                                $patient_id = $bio->post_parent;
                                $patient_name = get_the_title($patient_id);
                                $peso = floatval(
                                    get_post_meta($bio->ID, "pab_bi_peso", true),
                                );
                                $altura =
                                    floatval(
                                        get_post_meta(
                                            $patient_id,
                                            "pab_altura",
                                            true,
                                        ),
                                    ) / 100;
                                $imc =
                                    $peso && $altura
                                        ? number_format(
                                            $peso / ($altura * $altura),
                                            1,
                                            ",",
                                            ".",
                                        )
                                        : "-";
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(
                                            get_edit_post_link($bio->ID),
                                        ); ?>">
                                            <?php echo esc_html(
                                                $patient_name ?: __("(Paciente não encontrado)", "pab")
                                            ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo get_the_date(
                                        "d/m/Y",
                                        $bio->ID,
                                    ); ?></td>
                                    <td><?php echo esc_html($imc); ?></td>
                                </tr>
                            <?php
                            endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e("Nenhuma bioimpedância registrada ainda.", "pab"); ?></p>
                <?php endif; ?>
            </div>

            <div class="pab-grid-item">
                <h2><?php _e("Últimas Avaliações", "pab"); ?></h2>
                 <?php if (!empty($ultimas_aval)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e("Paciente", "pab"); ?></th>
                                <th><?php _e("Data", "pab"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_aval as $aval):

                                $patient_id = $aval->post_parent;
                                $patient_name = get_the_title($patient_id);
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(
                                            get_edit_post_link($aval->ID),
                                        ); ?>">
                                            <?php echo esc_html(
                                                $patient_name ?: __("(Paciente não encontrado)", "pab")
                                            ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo get_the_date(
                                        "d/m/Y",
                                        $aval->ID,
                                    ); ?></td>
                                </tr>
                            <?php
                            endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                     <p><?php _e("Nenhuma avaliação registrada ainda.", "pab"); ?></p>
                <?php endif; ?>
            </div>

            <div class="pab-grid-item">
                <h2><?php _e("Últimas Medidas", "pab"); ?></h2>
                 <?php if (!empty($ultimas_medidas)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e("Paciente", "pab"); ?></th>
                                <th><?php _e("Data", "pab"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_medidas as $medida):

                                $patient_id = $medida->post_parent;
                                $patient_name = get_the_title($patient_id);
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(
                                            get_edit_post_link($medida->ID),
                                        ); ?>">
                                             <?php echo esc_html(
                                                $patient_name ?: __("(Paciente não encontrado)", "pab")
                                            ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo get_the_date(
                                        "d/m/Y",
                                        $medida->ID,
                                    ); ?></td>
                                </tr>
                            <?php
                            endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e("Nenhuma medida registrada ainda.", "pab"); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="pab-dashboard-charts">
            <div class="pab-chart-container" id="pab-chart-avaliacoes-container">
                <h2><?php _e("Avaliações por Mês (Últimos 12 meses)", "pab"); ?></h2>
                <canvas id="pab-chart-avaliacoes"></canvas>
            </div>

            <div class="pab-chart-container">
                <h2><?php _e("Distribuição de IMC", "pab"); ?></h2>
                <canvas id="pab-chart-imc"></canvas>
            </div>

            <div class="pab-chart-container">
                <h2><?php _e("Distribuição por Gênero", "pab"); ?></h2>
                <canvas id="pab-chart-genero"></canvas>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Adiciona os estilos do dashboard
 */
function pab_dashboard_styles()
{
    $screen = get_current_screen();
    if ($screen->id !== "pab_paciente_page_pab-dashboard") {
        return;
    }?>
    <style>
        .pab-dashboard-wrap {
            background-color: #f8fafc;
            margin-top: 20px;
            padding: 20px;
            margin-left: -20px; /* Alinha com o menu */
        }
        
        .pab-dashboard-wrap h1 {
             font-size: 28px;
             font-weight: 600;
             color: #1e293b;
             margin-bottom: 24px;
        }

        /* Estilo Base dos Cards */
        .pab-card,
        .pab-grid-item,
        .pab-chart-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .pab-card:hover,
        .pab-grid-item:hover,
        .pab-chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Cards de Resumo */
        .pab-dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin: 20px 0;
        }

        .pab-card {
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .pab-card-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 48px;
            color: #667eea;
            opacity: 0.15;
            transition: all 0.3s ease;
        }
        
        .pab-card:hover .pab-card-icon {
             opacity: 0.2;
             transform: scale(1.1);
        }

        .pab-card h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            font-weight: 600;
            color: #475569;
        }

        .pab-card-number {
            font-size: 40px;
            font-weight: 700;
            margin: 10px 0 20px 0;
            color: #1e293b;
            line-height: 1;
        }
        
        .pab-card-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px 0 rgba(102, 126, 234, 0.3);
        }
        
        .pab-card-button:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Grid de Atividades Recentes */
        .pab-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin: 24px 0;
        }
        
        .pab-grid-item {
             padding: 0; /* Padding será interno */
        }
        
        .pab-grid-item h2 {
            margin: 0;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .pab-grid-item table {
            margin: 0;
            border: none;
            border-radius: 0 0 12px 12px;
        }
        
        .pab-grid-item table th {
             color: #64748b;
             font-weight: 600;
             font-size: 13px;
        }
        
         .pab-grid-item table td a {
             font-weight: 600;
             color: #3b82f6;
             text-decoration: none;
         }
         
         .pab-grid-item table td a:hover {
             color: #1e293b;
         }
         
         .pab-grid-item p {
             padding: 20px 24px;
             margin: 0;
             color: #64748b;
         }

        /* Grid de Gráficos */
        .pab-dashboard-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin: 24px 0;
        }

        .pab-chart-container {
            padding: 24px;
        }
        
        /* Gráfico de Avaliações (largura total) */
        #pab-chart-avaliacoes-container {
            grid-column: 1 / -1;
        }

        .pab-chart-container h2 {
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Responsividade */
        @media screen and (max-width: 782px) {
             .pab-dashboard-wrap {
                margin-left: 0;
             }
             
             /* Cards 2x2 em telas menores */
             .pab-dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
             }
             .pab-card-number {
                 font-size: 32px; /* Reduz fonte em 2x2 */
             }

             .pab-dashboard-grid,
             .pab-dashboard-charts {
                 grid-template-columns: 1fr;
                 gap: 20px;
             }
             
             /* Reseta a largura total e altura em telas pequenas */
             #pab-chart-avaliacoes-container {
                grid-column: auto;
                max-height: none;
            }
        }
        
        /* Ajuste fino para telas muito pequenas (cards 2x2) */
         @media screen and (max-width: 480px) {
            .pab-dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
            }
             .pab-card h3 {
                 font-size: 13px;
             }
             .pab-card-number {
                 font-size: 28px;
             }
             .pab-card-button {
                 padding: 8px 12px;
                 font-size: 11px;
             }
         }
        
    </style>
    <?php
}
add_action("admin_head", "pab_dashboard_styles");