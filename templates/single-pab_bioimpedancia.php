<?php
/**
 * Template personalizado para visualização de Bioimpedância
 * Este template é carregado pelo plugin, não pelo tema
 * Atualizado para ser mais semelhante à página de edição
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Função para adicionar OpenGraph tags no head
 * CORREÇÃO: Agora usa a classificação PBF (c_gc) para o avatar
 */
function pab_add_bioimpedancia_opengraph(
    $post_id,
    $patient_id,
    $patient_name,
    $patient_gender,
    $c_gc // CORREÇÃO: Recebe a classificação de Gordura Corporal
) {
    if (is_admin()) {
        return;
    }

    // Determinar avatar baseado no PBF (Gordura Corporal)
    $nivel = $c_gc["nivel"]; // CORREÇÃO: Usar o nível da gordura
    $prefix = $patient_gender === "F" ? "f" : "m";

    // Usar avatar cropped focado na cabeça para OpenGraph
    // Primeira opção: arquivo gerado em cache
    $avatar_img_cropped = pab_generate_avatar_head_crop(
        $patient_gender,
        $nivel,
    );

    // Segunda opção: endpoint dinâmico
    if (!$avatar_img_cropped) {
        $avatar_img_cropped = add_query_arg(
            [
                "pab_avatar_head" => "1",
                "gender" => $patient_gender,
                "level" => $nivel,
            ],
            home_url(),
        );
    }

    // Fallback para imagem original se cropped falhar
    $avatar_img =
        $avatar_img_cropped ?:
        PAB_URL . "assets/img/avatars/{$prefix}-{$nivel}.png";

    // Dados básicos
    $title = get_the_title() . " - Relatório de Bioimpedância";
    $description = "Relatório completo de bioimpedância de {$patient_name}. Análise detalhada da composição corporal com base nos padrões da OMS.";
    $url = get_permalink();

    // Labels para classificação (usando PBF)
    $labels_gc = [
        "abaixo" => "Baixo Peso",
        "normal" => "Normal",
        "acima1" => "Sobrepeso",
        "acima2" => "Obesidade I",
        "acima3" => "Obesidade II",
        "alto1" => "Obesidade III",
        "alto2" => "Muito Alto",
        "alto3" => "Extremo",
    ];

    $classificacao = $labels_gc[$nivel] ?? "Normal";
    $description .= " Classificação de Gordura: {$classificacao}.";
    ?>
    <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
    <meta property="og:description" content="<?php echo esc_attr(
        $description,
    ); ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="<?php echo esc_url($url); ?>" />
    <meta property="og:image" content="<?php echo esc_url($avatar_img); ?>" />
    <meta property="og:image:width" content="400" />
    <meta property="og:image:height" content="400" />
    <meta property="og:image:alt" content="Avatar representativo da composição corporal - <?php echo esc_attr(
        $classificacao,
    ); ?>" />
    <meta property="og:site_name" content="<?php echo esc_attr(
        get_bloginfo("name"),
    ); ?>" />

    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
    <meta name="twitter:description" content="<?php echo esc_attr(
        $description,
    ); ?>" />
    <meta name="twitter:image" content="<?php echo esc_url($avatar_img); ?>" />

    <meta name="description" content="<?php echo esc_attr($description); ?>" />

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "MedicalTest",
        "name": "<?php echo esc_js($title); ?>",
        "description": "<?php echo esc_js($description); ?>",
        "url": "<?php echo esc_url($url); ?>",
        "image": "<?php echo esc_url($avatar_img); ?>",
        "about": {
            "@type": "Person",
            "name": "<?php echo esc_js($patient_name); ?>",
            "gender": "<?php echo $patient_gender === "F"
                ? "Female"
                : "Male"; ?>"
        },
        "provider": {
            "@type": "Organization",
            "name": "<?php echo esc_js(get_bloginfo("name")); ?>",
            "url": "<?php echo esc_url(home_url()); ?>"
        },
        "dateCreated": "<?php echo get_the_date("c"); ?>",
        "dateModified": "<?php echo get_the_modified_date("c"); ?>"
    }
    </script>
    <?php
}

// Obter dados do post atual
$post_id = get_the_ID();
$patient_id = (int) pab_get($post_id, "pab_paciente_id");

// Dados do paciente
$patient_name = pab_get($patient_id, "pab_nome", "Paciente não identificado");
$patient_gender = pab_get($patient_id, "pab_genero", "M");
$patient_birth = pab_get($patient_id, "pab_nascimento");
$patient_height = pab_get($patient_id, "pab_altura");

// Dados da bioimpedância
$peso = pab_get($post_id, "pab_bi_peso");
$gordura = pab_get($post_id, "pab_bi_gordura_corporal"); // PBF (%)
$musculo = pab_get($post_id, "pab_bi_musculo_esq");
$gordura_visc = pab_get($post_id, "pab_bi_gordura_visc");
$metab_basal = pab_get($post_id, "pab_bi_metab_basal");
$idade_corp = pab_get($post_id, "pab_bi_idade_corporal");

// Calcular idade real
$idade_real = pab_calc_idade_real($patient_id);

// Calcular IMC
$altura_m = $patient_height ? $patient_height / 100.0 : null;
$imc = $altura_m && $peso ? round($peso / ($altura_m * $altura_m), 1) : null;

// Classificações OMS
$c_imc = pab_oms_classificacao("imc", $imc, $patient_gender, $idade_real);
// CORREÇÃO: Usar 'pbf' ou 'gc' (assumindo que calculations.php aceita ambos)
$c_gc = pab_oms_classificacao("gc", $gordura, $patient_gender, $idade_real);
$c_gv = pab_oms_classificacao(
    "gv",
    $gordura_visc,
    $patient_gender,
    $idade_real,
);
$c_musculo = pab_oms_classificacao(
    "musculo",
    $musculo,
    $patient_gender,
    $idade_real,
);
$c_peso = pab_oms_classificacao(
    "peso",
    (float) $peso,
    $patient_gender,
    $idade_real,
    ["altura_cm" => $patient_height],
);

// Adicionar OpenGraph tags no wp_head
add_action(
    "wp_head",
    function () use (
        $post_id,
        $patient_id,
        $patient_name,
        $patient_gender,
        $c_gc // CORREÇÃO: Passar $c_gc (Gordura) em vez de $c_imc
    ) {
        pab_add_bioimpedancia_opengraph(
            $post_id,
            $patient_id,
            $patient_name,
            $patient_gender,
            $c_gc // CORREÇÃO: Passar $c_gc
        );
    },
    1,
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo("charset"); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html(
        $patient_name . " - Bioimpedância - " . get_the_date("d/m/Y"),
    ); ?></title>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">

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
            padding: 5px;
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
            font-size: 0.8rem;
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
            padding: 10px;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .pab-metabox-header.avatars {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .pab-metabox-content {
            background: white;
        }

        /* Grid de dados similar ao padrão medidas */
        .pab-data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 30px;
            padding: 30px;
        }

        .pab-data-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .pab-data-section:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .pab-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .pab-section-icon {
            font-size: 2rem;
        }

        .pab-section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1e40af;
        }

        .pab-data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .pab-data-item:last-child {
            border-bottom: none;
        }

        .pab-data-label {
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .pab-data-value {
            font-weight: 600;
            color: #1e40af;
            font-size: 14px;
            padding: 8px 12px;
            background: #f0f9ff;
            border-radius: 6px;
            border: 1px solid #dbeafe;
        }

        .pab-data-classification {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .pab-data-classification.normal { background: #d4edda; color: #155724; }
        .pab-data-classification.abaixo { background: #fff3cd; color: #856404; }
        .pab-data-classification.acima1 { background: #f8d7da; color: #721c24; }
        .pab-data-classification.acima2 { background: #f8d7da; color: #721c24; }
        .pab-data-classification.acima3 { background: #f8d7da; color: #721c24; }
        .pab-data-classification.alto1, .pab-data-classification.alto2, .pab-data-classification.alto3 {
            background: #dc3545; color: white;
        }

        /* Avatares estilizados */
        .pab-avatars-container {
            display: flex;
            flex-wrap: nowrap;
            gap: 0;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 20px 0 15px 0;
            margin: 0;
        }

        .pab-avatar-wrapper {
            flex-shrink: 0;
            flex-grow: 1;
            flex-basis: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pab-avatar {
            border: 3px solid transparent;
            padding: 0;
            margin: 0;
            border-radius: 0;
            background: #f8f9fa;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 100px;
            width: 100%;
        }

        .pab-avatar-wrapper:first-child .pab-avatar {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .pab-avatar-wrapper:last-child .pab-avatar {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .pab-avatar.active {
            border-color: #228be6;
            background: rgba(34, 139, 230, 0.15);
            transform: scale(1.05);
            box-shadow: 0 4px 16px rgba(34, 139, 230, 0.4);
            z-index: 10;
            border-radius: 8px !important;
        }

        .pab-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }


        .pab-avatar-label {
            margin-top: 8px;
            font-size: 10px;
            color: #999;
            text-align: center;
            font-weight: 500;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .pab-avatar-label.active {
            color: #228be6;
        }

        /* Cards de composição corporal - padrão medidas */
        .pab-comp-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
        }

        .pab-comp-cards .pab-data-grid {
            width: 100%;
        }

        .pab-comp-card {
            background: white;
            border-radius: 12px;
            padding: 10px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            width: calc(50% - 10px);
            margin: 0;
        }

        .pab-comp-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .pab-comp-cards .pab-comp-card h4 {
                margin: 0 0 10px 0;
                font-size: 1.4rem;
                font-weight: 600;
                color: #1e40af;
                display: flex;
                align-items: center;
                gap: 12px;
                padding-bottom: 15px;
                border-bottom: 2px solid #f1f5f9;
            }

        .pab-comp-cards .pab-comp-card .icon {
            font-size: 2rem;
        }

        .pab-comp-cards .pab-comp-value {
                font-size: 1.8rem;
                font-weight: 700;
                color: #1e40af;
                margin-bottom: 10px;
                padding: 10px;
                background: #f0f9ff;
                border-radius: 8px;
                border: 1px solid #dbeafe;
                text-align: center;
            }

        .pab-comp-cards .pab-comp-ref {
            font-size: 13px;
            color: #64748b;
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 12px;
            border: 1px solid #e2e8f0;
        }

        /* Alertas */
        .pab-alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin: 16px 0;
            font-size: 14px;
            line-height: 1.6;
            position: relative;
            overflow: hidden;
        }

        .pab-alert::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
        }

        .pab-alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .pab-alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        /* Footer - padrão medidas */
        .pab-footer {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-size: 14px;
            margin-top: 40px;
            border-top: 2px solid #f1f5f9;
            background: #f8fafc;
        }

        .pab-footer strong {
            color: #1e40af;
        }

        /* Responsive - padrão medidas */
        @media (max-width: 768px) {
            .pab-public-container {
                margin: 10px;
                border-radius: 12px;
            }

            .pab-public-header {
                padding: 30px 20px;
            }

            .pab-public-header h1 {
                font-size: 2rem;
            }

            .pab-metabox {
                margin: 20px;
            }

            .pab-comp-cards {
                padding: 15px;
                gap: 15px;
            }
            .pab-comp-card {
                width: 100%;
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
                background: #1e40af !important;
                -webkit-print-color-adjust: exact;
            }

            .pab-metabox {
                break-inside: avoid;
                margin: 10px 0;
            }

            .pab-footer {
                margin-top: 20px;
                padding: 20px;
            }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .pab-public-container {
                margin: 10px;
            }

            .pab-public-header {
                padding: 20px;
            }

            .pab-data-grid {
                grid-template-columns: 1fr;
            }

            .pab-comp-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Print styles */
        @media print {
            body { background-color: #fff; }
            .pab-public-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .pab-public-header {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .pab-metabox {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .pab-footer { display: none; }
        }
    </style>
</head>

<body>

<div class="pab-public-container">

    <div class="pab-public-header">
        <h1><?php echo esc_html($patient_name); ?></h1>
        <div class="meta">
            <strong>Data da Avaliação:</strong> <?php echo get_the_date(
                "d/m/Y",
            ); ?> às <?php echo get_the_time("H:i"); ?>
            <br>
            <strong>Idade:</strong> <?php echo $idade_real
                ? esc_html($idade_real) . " anos"
                : "—"; ?>
            <?php if ($patient_height): ?>
                | <strong>Altura:</strong> <?php echo esc_html(
                    $patient_height,
                ); ?> cm
            <?php endif; ?>
        </div>
    </div>

    <div class="pab-metabox">
        <div class="pab-metabox-header avatars">
            <span>Avatares (OMS)</span>
        </div>
        <div class="pab-metabox-content">
            <div style="text-align: center; margin-bottom: 20px;">
                <h4 style="margin: 10px 0; color: #333; font-size: 16px; font-weight: 600;">
                    🔥 Representação Visual da Composição Corporal (Gordura)
                </h4>
                <p style="margin: 0; font-size: 12px; color: #666;">
                    Baseado na classificação da Organização Mundial da Saúde
                </p>

                <?php if ($gordura): ?>
                    <div style="margin: 10px 0; padding: 10px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; display: inline-block;">
                        <div style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                            Gordura: <?php echo esc_html($gordura); ?>%
                        </div>
                        <div style="font-size: 14px; font-weight: 600; color: <?php echo $c_gc[
                            "nivel"
                        ] == "normal"
                            ? "#059669"
                            : ($c_gc["nivel"] == "abaixo"
                                ? "#0891b2"
                                : "#dc2626"); ?>;">
                            <?php echo esc_html($c_gc["ref"]); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin: 10px 0; padding: 10px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; display: inline-block;">
                        <div style="font-size: 14px; color: #92400e; font-weight: 600;">
                            ⚠️ Gordura Corporal não calculada - Dados insuficientes
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            // Avatares baseados na classificação de Gordura Corporal (PBF)
            $nivel = $c_gc["nivel"]; // CORREÇÃO: Usar $c_gc (Gordura)
            $prefix = $patient_gender === "F" ? "f" : "m";
            $levels = [
                "abaixo",
                "normal",
                "acima1",
                "acima2",
                "acima3",
                "alto1",
                "alto2",
                "alto3",
            ];

            // Labels para os avatares (mantido genérico, pode ser ajustado)
            $labels_avatar = [
                "abaixo" => "Abaixo",
                "normal" => "Normal",
                "acima1" => "Acima I",
                "acima2" => "Acima II",
                "acima3" => "Acima III",
                "alto1" => "Alto I",
                "alto2" => "Alto II",
                "alto3" => "Alto III",
            ];
            ?>

            <?php if ($gordura && $nivel): ?>
                <div style="text-align: center; margin: 5px 0; padding: 10px; background: #e0f2fe; border-radius: 6px;">
                    <div style="font-size: 13px; color: #0369a1; font-weight: 600;">
                        👆 Seu avatar atual: <strong><?php echo esc_html(
                            $labels_avatar[$nivel] ?? "N/A",
                        ); ?></strong>
                    </div>
                </div>
            <?php endif; ?>


            <div class="pab-avatars-container">
                <?php foreach ($levels as $lvl): ?>
                    <?php
                    $active = $lvl === $nivel ? "active" : ""; // $nivel agora é de $c_gc
                    $img = PAB_URL . "assets/img/avatars/{$prefix}-{$lvl}.png";
                    ?>
                    <div class="pab-avatar-wrapper">
                        <div class="pab-avatar <?php echo $active; ?>" title="<?php echo esc_attr($labels_avatar[$lvl], ); ?>">
                            <img src="<?php echo esc_url( $img, ); ?>" alt="<?php echo esc_attr($lvl); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="pab-metabox">
        <div class="pab-metabox-header">
            <span>Dados de Bioimpedância</span>
        </div>
        <div class="pab-metabox-content">

            <?php if (!$peso && !$musculo && !$idade_corp): ?>
                <div class="pab-alert pab-alert-info">
                    <strong>ℹ️ Informação:</strong> Dados de bioimpedância não disponíveis para análise detalhada.
                </div>
            <?php else: ?>
                <div class="pab-comp-cards">
                    <?php if ($peso): ?>
                    <div class="pab-comp-card">
                        <h4><span class="icon">⚖️</span> Peso Corporal</h4>
                        <div class="pab-comp-value"><?php echo esc_html(                     $peso,
                        ); ?> kg</div>

                        <?php if ($patient_height): ?>
                            <?php
                            // Recalculando $c_peso (já feito acima)
                            $peso_ideal_min = $c_peso["faixa"]["min"] ?? null;
                            $peso_ideal_max = $c_peso["faixa"]["max"] ?? null;

                            if ($peso_ideal_min && $peso_ideal_max) {
                                if ($peso < $peso_ideal_min) {
                                    $delta_peso = $peso - $peso_ideal_min;
                                } elseif ($peso > $peso_ideal_max) {
                                    $delta_peso = $peso - $peso_ideal_max;
                                } else {
                                    $peso_medio_ideal =
                                        ($peso_ideal_min + $peso_ideal_max) / 2;
                                    $delta_peso = $peso - $peso_medio_ideal;
                                }
                            } else {
                                $delta_peso = 0;
                            }
                            ?>
                            <div style="margin: 8px 0; padding: 8px; background: #f1f5f9; border-radius: 6px;">
                                <strong style="color: <?php echo $delta_peso > 0
                                    ? "#dc2626"
                                    : ($delta_peso < 0
                                        ? "#0891b2"
                                        : "#059669"); ?>;">
                                    <?php echo ($delta_peso > 0 ? "+" : "") .
                                        number_format(
                                            $delta_peso,
                                            1,
                                        ); ?> kg do peso ideal
                                </strong>
                                <br>
                                <small>Faixa ideal: <?php echo $peso_ideal_min; ?>kg - <?php echo $peso_ideal_max; ?>kg</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($gordura): ?>
                    <div class="pab-comp-card" style="border-left-color: #f59e0b;">
                        <h4><span class="icon">🔥</span> Gordura Corporal</h4>
                        <div class="pab-comp-value"><?php echo esc_html(
                            $gordura,
                        ); ?>%</div>

                        <?php if ($peso): ?>
                            <?php $massa_gordura_kg =
                                ($gordura / 100) * $peso; ?>
                            <div style="margin: 8px 0; padding: 8px; background: #fef3c7; border-radius: 6px;">
                                <strong style="color: #f59e0b;">
                                    <?php echo number_format(
                                        $massa_gordura_kg,
                                        1,
                                    ); ?> kg de gordura
                                </strong>
                                <br>
                                <small>Em relação ao peso total de <?php echo $peso; ?>kg</small>
                            </div>
                        <?php endif; ?>

                        <div class="pab-comp-ref">
                            Classificação:
                            <strong><?php echo esc_html(
                                $c_gc["ref"],
                            ); ?></strong>
                            (<?php echo $patient_gender === "F"
                                ? "Mulher"
                                : "Homem"; ?>, <?php echo $idade_real; ?> anos)
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($musculo): ?>
                    <div class="pab-comp-card" style="border-left-color: #10b981;">
                        <h4><span class="icon">💪</span> Músculo Esquelético</h4>
                        <div class="pab-comp-value"><?php echo esc_html(
                            $musculo,
                        ); ?>%</div>

                        <?php if ($peso): ?>
                            <?php $massa_musculo_kg =
                                ($musculo / 100) * $peso; ?>
                            <div style="margin: 8px 0; padding: 8px; background: #d1fae5; border-radius: 6px;">
                                <strong style="color: #10b981;">
                                    <?php echo number_format(
                                        $massa_musculo_kg,
                                        1,
                                    ); ?> kg de músculo
                                </strong>
                                <br>
                                <small>Massa muscular ativa</small>
                            </div>
                        <?php endif; ?>

                        <div class="pab-comp-ref">
                             Classificação:
                            <strong><?php echo esc_html(
                                $c_musculo["ref"],
                            ); ?></strong>
                             (<?php echo $patient_gender === "F"
                                 ? "Mulher"
                                 : "Homem"; ?>)
                        </div>
                    </div>
                    <?php endif; ?>



                    <?php if ($gordura_visc): ?>
                    <div class="pab-comp-card" style="border-left-color: #ef4444;">
                        <h4><span class="icon">🫀</span> Gordura Visceral</h4>
                        <div class="pab-comp-value">Nível <?php echo esc_html(
                            $gordura_visc,
                        ); ?></div>
                        <div class="pab-comp-ref">
                            Classificação:
                            <strong><?php echo esc_html(
                                $c_gv["ref"],
                            ); ?></strong>
                             (Referência: Normal ≤ 9)
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($metab_basal): ?>
                    <div class="pab-comp-card" style="border-left-color: #8b5cf6;">
                        <h4><span class="icon">⚡</span> Metabolismo Basal</h4>
                        <div class="pab-comp-value"><?php echo esc_html(
                            $metab_basal,
                        ); ?> kcal</div>

                        <div style="margin: 8px 0; padding: 8px; background: #f3e8ff; border-radius: 6px;">
                            <strong style="color: #8b5cf6;">
                                ~<?php echo round(
                                    $metab_basal / 24,
                                ); ?> kcal/hora
                            </strong>
                            <br>
                            <small>Energia gasta em repouso</small>
                        </div>

                        <div class="pab-comp-ref">
                            Referência (Mifflin-St Jeor):
                            <?php if ($peso && $patient_height && $idade_real) {
                                if ($patient_gender === "F") {
                                    $tmb_calculado =
                                        10 * $peso +
                                        6.25 * $patient_height -
                                        5 * $idade_real -
                                        161;
                                } else {
                                    $tmb_calculado =
                                        10 * $peso +
                                        6.25 * $patient_height -
                                        5 * $idade_real +
                                        5;
                                }
                                echo round($tmb_calculado) . " kcal/dia";
                            } else {
                                echo "Dados insuficientes para cálculo";
                            } ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($idade_corp): ?>
                    <div class="pab-comp-card" style="border-left-color: #06b6d4;">
                        <h4><span class="icon">🕐</span> Idade Corporal</h4>
                        <div class="pab-comp-value"><?php echo esc_html(
                            $idade_corp,
                        ); ?> anos</div>

                        <?php if ($idade_real): ?>
                            <?php
                            $delta_idade =
                                (int) $idade_corp - (int) $idade_real;
                            $badge_color =
                                $delta_idade <= 0
                                    ? "#10b981"
                                    : ($delta_idade > 5
                                        ? "#dc2626"
                                        : "#f59e0b");
                            ?>
                            <div style="margin: 8px 0; padding: 8px; background: <?php echo $delta_idade <=
                            0
                                ? "#d1fae5"
                                : ($delta_idade <= 5
                                    ? "#fef3c7"
                                    : "#fee2e2"); ?>; border-radius: 6px;">
                                <strong style="color: <?php echo $badge_color; ?>;">
                                    <?php if ($delta_idade == 0): ?>
                                        Idade ideal (igual à cronológica)
                                    <?php elseif ($delta_idade > 0): ?>
                                        +<?php echo $delta_idade; ?> anos (Mais Velha)
                                    <?php else: ?>
                                        <?php echo $delta_idade; ?> anos (Mais Jovem)
                                    <?php endif; ?>
                                </strong>
                                <br>
                                <small>
                                    <?php if ($delta_idade <= 0): ?>
                                        Excelente condição física
                                    <?php elseif ($delta_idade <= 5): ?>
                                        Condição razoável, pode melhorar
                                    <?php else: ?>
                                        Recomenda-se atividade física regular
                                    <?php endif; ?>
                                </small>
                            </div>

                            <div class="pab-comp-ref">
                                Idade cronológica: <?php echo esc_html(
                                    $idade_real,
                                ); ?> anos
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($peso && $gordura && $musculo && $idade_corp): ?>
    <div class="pab-metabox">
        <div class="pab-metabox-header" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); color: #0c4a6e;">
            <span>📊 Resumo da Composição Corporal</span>
        </div>
        <div class="pab-metabox-content">
            <div style="padding: 20px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border: 1px solid #0ea5e9;">
                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #0f172a;">
                    <?php
                    $resumo = "O paciente apresenta ";

                    // Análise do peso
                    if ($c_peso["nivel"] == "normal") {
                        $resumo .= "peso adequado ";
                    } elseif ($c_peso["nivel"] == "abaixo") {
                        $resumo .= "peso abaixo do ideal ";
                    } else {
                        $resumo .= "peso acima do ideal ";
                    }

                    // Análise da composição
                    if ($c_musculo["nivel"] == "normal") {
                        $resumo .= "e massa muscular adequada. ";
                    } elseif ($c_musculo["nivel"] == "abaixo") {
                        $resumo .= "e massa muscular abaixo do recomendado. ";
                    } else {
                        $resumo .= "e boa massa muscular. ";
                    }

                    // Análise da gordura corporal
                    if ($c_gc["nivel"] == "normal") {
                        $resumo .=
                            "O percentual de gordura corporal está dentro da faixa saudável. ";
                    } elseif ($c_gc["nivel"] == "abaixo") {
                        $resumo .=
                            "O percentual de gordura corporal está baixo. ";
                    } else {
                        $resumo .=
                            "O percentual de gordura corporal está elevado. ";
                    }

                    // Análise da idade corporal
                    $delta_idade = (int) $idade_corp - (int) $idade_real;
                    if ($delta_idade <= 0) {
                        $resumo .=
                            "A idade corporal indica excelente condição física, sendo ";
                        if (abs($delta_idade) > 0) {
                            $resumo .=
                                abs($delta_idade) .
                                " anos mais jovem que a idade cronológica.";
                        } else {
                            $resumo .= "equivalente à idade cronológica.";
                        }
                    } elseif ($delta_idade <= 5) {
                        $resumo .=
                            "A idade corporal sugere condição física razoável, sendo ";
                        $resumo .=
                            $delta_idade .
                            " anos mais elevada que a cronológica. Recomenda-se manutenção ou melhoria dos hábitos de vida.";
                    } else {
                        $resumo .=
                            "A idade corporal indica necessidade de melhoria da condição física, sendo ";
                        $resumo .=
                            $delta_idade .
                            " anos mais elevada que a cronológica. Recomenda-se atividade física regular e acompanhamento profissional.";
                    }

                    // Conversões em massa corporal
                    if ($peso) {
                        $massa_gordura_kg = ($gordura / 100) * $peso;
                        $massa_musculo_kg = ($musculo / 100) * $peso;
                        $resumo .= " Em termos absolutos, o paciente possui ";
                        $resumo .=
                            number_format($massa_musculo_kg, 1) .
                            "kg de massa muscular e ";
                        $resumo .=
                            number_format($massa_gordura_kg, 1) .
                            "kg de gordura corporal.";
                    }

                    echo esc_html($resumo);
                    ?>
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 20px;">
                <div style="text-align: center; padding: 12px; background: <?php echo $c_peso[
                    "nivel"
                ] == "normal"
                    ? "#d1fae5"
                    : ($c_peso["nivel"] == "abaixo"
                        ? "#fef3c7"
                        : "#fee2e2"); ?>; border-radius: 8px;">
                    <div style="font-size: 18px; margin-bottom: 4px;">
                        <?php echo $c_peso["nivel"] == "normal"
                            ? "✅"
                            : ($c_peso["nivel"] == "abaixo"
                                ? "⚠️"
                                : "🔴"); ?>
                    </div>
                    <div style="font-size: 12px; font-weight: 600; color: #374151;">PESO</div>
                    <div style="font-size: 11px; color: #6b7280;"><?php echo ucfirst(
                        $c_peso["nivel"],
                    ); ?></div>
                </div>

                <div style="text-align: center; padding: 12px; background: <?php echo $c_gc[
                    "nivel"
                ] == "normal"
                    ? "#d1fae5"
                    : "#fee2e2"; ?>; border-radius: 8px;">
                    <div style="font-size: 18px; margin-bottom: 4px;">
                        <?php echo $c_gc["nivel"] == "normal" ? "✅" : "🔴"; ?>
                    </div>
                    <div style="font-size: 12px; font-weight: 600; color: #374151;">GORDURA</div>
                    <div style="font-size: 11px; color: #6b7280;"><?php echo $gordura; ?>%</div>
                </div>

                <div style="text-align: center; padding: 12px; background: <?php echo $c_musculo[
                    "nivel"
                ] == "normal"
                    ? "#d1fae5"
                    : ($c_musculo["nivel"] == "abaixo"
                        ? "#fee2e2"
                        : "#d1fae5"); ?>; border-radius: 8px;">
                    <div style="font-size: 18px; margin-bottom: 4px;">
                        <?php echo $c_musculo["nivel"] == "normal"
                            ? "✅"
                            : ($c_musculo["nivel"] == "abaixo"
                                ? "🔴"
                                : "✅"); ?>
                    </div>
                    <div style="font-size: 12px; font-weight: 600; color: #374151;">MÚSCULO</div>
                    <div style="font-size: 11px; color: #6b7280;"><?php echo $musculo; ?>%</div>
                </div>

                <div style="text-align: center; padding: 12px; background: <?php echo $delta_idade <=
                0
                    ? "#d1fae5"
                    : ($delta_idade <= 5
                        ? "#fef3c7"
                        : "#fee2e2"); ?>; border-radius: 8px;">
                    <div style="font-size: 18px; margin-bottom: 4px;">
                        <?php echo $delta_idade <= 0
                            ? "✅"
                            : ($delta_idade <= 5
                                ? "⚠️"
                                : "🔴"); ?>
                    </div>
                    <div style="font-size: 12px; font-weight: 600; color: #374151;">IDADE</div>
                    <div style="font-size: 11px; color: #6b7280;">
                        <?php echo $delta_idade <= 0
                            ? "Ideal"
                            : ($delta_idade <= 5
                                ? "Razoável"
                                : "Atenção"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="pab-footer">
        <p>
            <strong>Sistema de Bioimpedância</strong><br>
            Relatório gerado em <?php echo date("d/m/Y \à\s H:i"); ?><br>
            <small>Este documento contém informações confidenciais do paciente.</small>
        </p>
    </div>

    <?php wp_footer(); ?>

</div>

<script>
// Auto-scroll para o avatar ativo
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.pab-avatars-container');
    const activeAvatar = document.querySelector('.pab-avatar.active');

    if (container && activeAvatar) {
        const parentWrapper = activeAvatar.closest('.pab-avatar-wrapper');
        const containerWidth = container.offsetWidth;
        const activeOffset = parentWrapper.offsetLeft;
        const activeWidth = parentWrapper.offsetWidth;
        const scrollTarget = activeOffset - (containerWidth / 2) + (activeWidth / 2);

        container.scrollTo({
            left: scrollTarget,
            behavior: 'smooth'
        });
    }
});
</script>

</body>
</html>