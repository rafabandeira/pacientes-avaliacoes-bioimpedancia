<?php
/**
 * Template personalizado para visualização de Bioimpedância
 * Este template é carregado pelo plugin, não pelo tema
 */

if (!defined('ABSPATH')) exit;

// Obter dados do post atual
$post_id = get_the_ID();
$patient_id = (int) pab_get($post_id, 'pab_paciente_id');

// Dados do paciente
$patient_name = pab_get($patient_id, 'pab_nome', 'Paciente não identificado');
$patient_gender = pab_get($patient_id, 'pab_genero', 'M');
$patient_birth = pab_get($patient_id, 'pab_nascimento');
$patient_height = pab_get($patient_id, 'pab_altura');

// Dados da bioimpedância
$peso = pab_get($post_id, 'pab_bi_peso');
$gordura = pab_get($post_id, 'pab_bi_gordura_corporal');
$musculo = pab_get($post_id, 'pab_bi_musculo_esq');
$gordura_visc = pab_get($post_id, 'pab_bi_gordura_visc');
$metab_basal = pab_get($post_id, 'pab_bi_metab_basal');
$idade_corp = pab_get($post_id, 'pab_bi_idade_corporal');

// Calcular idade real
$idade_real = pab_calc_idade_real($patient_id);

// Calcular IMC
$altura_m = $patient_height ? ($patient_height / 100.0) : null;
$imc = ($altura_m && $peso) ? round($peso / ($altura_m * $altura_m), 1) : null;

// Classificações OMS
$c_imc = pab_oms_classificacao('imc', $imc, $patient_gender, $idade_real);
$c_gc = pab_oms_classificacao('gc', $gordura, $patient_gender, $idade_real);
$c_gv = pab_oms_classificacao('gv', $gordura_visc, $patient_gender, $idade_real);
$c_musculo = pab_oms_classificacao('musculo', $musculo, $patient_gender, $idade_real);
// CORREÇÃO: Passar a altura do paciente como contexto para calcular a faixa de peso ideal
$c_peso = pab_oms_classificacao('peso', (float)$peso, $patient_gender, $idade_real, ['altura_cm' => $patient_height]);

get_header();
?>

<style>
    body {
        background-color: #f8f9fa; /* Fundo mais suave */
    }
    .pab-single-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }
    .pab-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .pab-header h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
        font-weight: 600;
    }
    .pab-header .meta {
        opacity: 0.9;
        font-size: 14px;
    }
    
/* AVATARES */
    .pab-avatar-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .pab-avatar-section h2 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #333;
        font-weight: 600;
        text-align: center;
    }
    
    .pab-avatars-container {
        display: flex;
        flex-wrap: nowrap;
        gap: 0;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 15px; /* Espaço para a scrollbar */
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
        margin: 30px 0;
        border-radius: 0;
        background: #f8f9fa;
        transition: all 0.3s ease;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        min-height: 120px;
        width: 100%;
    }
    
    .pab-avatar-wrapper:first-child .pab-avatar {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
    
    .pab-avatar-wrapper:last-child .pab-avatar {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    
    .pab-avatar.active {
        border-color: #228be6;
        background: rgba(34, 139, 230, 0.15);
        transform: scale(1.1);
        box-shadow: 0 4px 16px rgba(34, 139, 230, 0.4);
        z-index: 10;
        border-radius: 12px !important;
    }
    
    .pab-avatar img {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Alterado para contain para não cortar a imagem */
        display: block;
    }
    
    .pab-avatar.active::after {
        content: "✓";
        position: absolute;
        top: -10px;
        right: -10px;
        background: #228be6;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    }
    
    .pab-avatar-label {
        margin-top: 8px;
        font-size: 11px;
        color: #999;
        text-align: center;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }
    
    .pab-avatar-label.active {
        color: #228be6;
        font-weight: 700;
        font-size: 13px;
        transform: scale(1.05);
    }
    
    .pab-avatars-container::-webkit-scrollbar { height: 8px; }
    .pab-avatars-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .pab-avatars-container::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
    .pab-avatars-container::-webkit-scrollbar-thumb:hover { background: #555; }
    
    .pab-grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); /* Aumentado o minmax */
        gap: 20px;
        margin-bottom: 30px;
    }
    .pab-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-left: 4px solid #667eea;
    }
    .pab-card h2 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #333;
        font-weight: 600;
    }
    .pab-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .pab-info-row:last-child {
        border-bottom: none;
    }
    .pab-info-label {
        color: #666;
        font-size: 14px;
    }
    .pab-info-value {
        font-weight: 600;
        color: #333;
        font-size: 14px;
        text-align: right;
    }
    .pab-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-left: 8px;
    }
    .pab-badge.normal { background: #d4edda; color: #155724; }
    .pab-badge.abaixo { background: #fff3cd; color: #856404; }
    .pab-badge.acima1 { background: #f8d7da; color: #721c24; }
    .pab-badge.acima2 { background: #f8d7da; color: #721c24; }
    .pab-badge.acima3 { background: #f8d7da; color: #721c24; }
    .pab-badge.alto1, .pab-badge.alto2, .pab-badge.alto3 { background: #dc3545; color: white; }
    
    .pab-footer {
        text-align: center;
        padding: 30px;
        color: #999;
        font-size: 13px;
    }
    
    @media (max-width: 768px) {
        .pab-header { padding: 30px; }
        .pab-avatar { min-height: 80px; }
        .pab-avatar.active::after { width: 24px; height: 24px; font-size: 12px; top: -8px; right: -8px; }
    }

    @media print {
        body { background-color: #fff; }
        .pab-single-container { max-width: 100%; margin: 0; padding: 0; box-shadow: none; }
        .pab-header { background: #667eea !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pab-card, .pab-avatar-section { box-shadow: none; border: 1px solid #ddd; }
        .pab-footer { display: none; }
    }
</style>

<div class="pab-single-container">
    
    <div class="pab-header">
        <h1><?php echo esc_html($patient_name); ?></h1>
        <div class="meta">
            <strong>Data da Avaliação:</strong> <?php echo get_the_date('d/m/Y'); ?> às <?php echo get_the_time('H:i'); ?>
            <br>
            <strong>Idade:</strong> <?php echo $idade_real ? esc_html($idade_real) . ' anos' : '—'; ?>
            <?php if ($patient_height): ?>
                | <strong>Altura:</strong> <?php echo esc_html($patient_height); ?> cm
            <?php endif; ?>
        </div>
    </div>

    <?php 
    // CORREÇÃO: Avatares agora são baseados na classificação de IMC
    $nivel = $c_imc['nivel'];
    $prefix = $patient_gender === 'F' ? 'f' : 'm';
    $levels = ['abaixo','normal','acima1','acima2','acima3','alto1','alto2','alto3'];
    
    // CORREÇÃO: Legendas mais claras baseadas na classificação de IMC
    $labels_imc = [
        'abaixo' => 'Baixo Peso',
        'normal' => 'Normal',
        'acima1' => 'Sobrepeso',
        'acima2' => 'Obesidade I',
        'acima3' => 'Obesidade II',
        'alto1'  => 'Obesidade III',
        'alto2'  => 'Obesidade III', // fallback
        'alto3'  => 'Obesidade III', // fallback
    ];
    ?>
    
    <div class="pab-avatar-section">
        <h2>📊 Representação Visual da Composição Corporal (IMC)</h2>
        
        <div class="pab-avatars-container">
            <?php foreach ($levels as $lvl): ?>
                <?php 
                $active = ($lvl === $nivel) ? 'active' : '';
                $img = PAB_URL . "assets/img/avatars/{$prefix}-{$lvl}.png";
                ?>
                <div class="pab-avatar-wrapper">
                    <div class="pab-avatar <?php echo $active; ?>" title="<?php echo esc_attr($labels_imc[$lvl]); ?>">
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($lvl); ?>">
                    </div>
                    <div class="pab-avatar-label <?php echo $active; ?>">
                        <?php echo esc_html($labels_imc[$lvl]); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="pab-grid-2">
        
        <div class="pab-card">
            <h2>📊 Composição Corporal</h2>
            <div class="pab-info-row">
                <span class="pab-info-label">Peso <small style="display: block;"><?php echo esc_html($c_peso['ref']); ?></small></span>
                <span class="pab-info-value">
                    <?php echo esc_html($peso); ?> kg
                    <span class="pab-badge <?php echo esc_attr($c_peso['nivel']); ?>">
                        <?php echo esc_html(ucfirst($c_peso['nivel'])); ?>
                    </span>
                </span>
            </div>
            <div class="pab-info-row">
                <span class="pab-info-label">Gordura Corporal</span>
                <span class="pab-info-value">
                    <?php echo esc_html($gordura); ?>% 
                    <span class="pab-badge <?php echo esc_attr($c_gc['nivel']); ?>">
                        <?php echo esc_html($c_gc['ref']); ?>
                    </span>
                </span>
            </div>
            <div class="pab-info-row">
                <span class="pab-info-label">Músculo Esquelético</span>
                <span class="pab-info-value">
                    <?php echo esc_html($musculo); ?>% 
                    <span class="pab-badge <?php echo esc_attr($c_musculo['nivel']); ?>">
                        <?php echo esc_html($c_musculo['ref']); ?>
                    </span>
                </span>
            </div>
        </div>

        <div class="pab-card">
            <h2>🎯 Indicadores de Saúde</h2>
            <div class="pab-info-row">
                <span class="pab-info-label">IMC</span>
                <span class="pab-info-value">
                    <?php echo $imc ? esc_html($imc) : '—'; ?> 
                    <?php if ($imc): ?>
                    <span class="pab-badge <?php echo esc_attr($c_imc['nivel']); ?>">
                        <?php echo esc_html($c_imc['ref']); ?>
                    </span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="pab-info-row">
                <span class="pab-info-label">Gordura Visceral</span>
                <span class="pab-info-value">
                    Nível <?php echo esc_html($gordura_visc); ?> 
                    <span class="pab-badge <?php echo esc_attr($c_gv['nivel']); ?>">
                        <?php echo esc_html($c_gv['ref']); ?>
                    </span>
                </span>
            </div>
            <div class="pab-info-row">
                <span class="pab-info-label">Metabolismo Basal</span>
                <span class="pab-info-value"><?php echo esc_html($metab_basal); ?> kcal/dia</span>
            </div>
        </div>

        <div class="pab-card">
            <h2>⏱️ Idade Corporal</h2>
            <div class="pab-info-row">
                <span class="pab-info-label">Idade Real</span>
                <span class="pab-info-value"><?php echo $idade_real ? esc_html($idade_real) . ' anos' : '—'; ?></span>
            </div>
            <div class="pab-info-row">
                <span class="pab-info-label">Idade Corporal</span>
                <span class="pab-info-value"><?php echo esc_html($idade_corp); ?> anos</span>
            </div>
            <?php 
            $delta = ($idade_real && $idade_corp) ? ((int)$idade_real - (int)$idade_corp) : null;
            if ($delta !== null):
            ?>
            <div class="pab-info-row">
                <span class="pab-info-label">Diferença</span>
                <span class="pab-info-value" style="color: <?php echo $delta < 0 ? '#dc3545' : '#28a745'; ?>; font-weight: 700;">
                    <?php echo $delta > 0 ? '+' : ''; ?><?php echo esc_html($delta); ?> anos
                    <?php if ($delta < 0): ?>
                        <small style="display: block; font-size: 11px; font-weight: 400;">(corpo mais velho que a idade real)</small>
                    <?php else: ?>
                        <small style="display: block; font-size: 11px; font-weight: 400;">(corpo mais jovem que a idade real)</small>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="pab-footer">
        <p>Relatório gerado em <?php echo current_time('d/m/Y'); ?> às <?php echo current_time('H:i'); ?></p>
        <p><small>Este documento é confidencial e destinado exclusivamente ao paciente identificado.</small></p>
    </div>

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

<?php get_footer(); ?>