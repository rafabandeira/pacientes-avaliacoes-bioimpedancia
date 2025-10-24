<?php
/**
 * Metabox: Medidas Corporais (Medidas)
 *
 * Exibe campos para inserção das medidas corporais em centímetros
 *
 * @package PAB
 * @subpackage Medidas\Metaboxes
 */

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Renderiza a metabox de medidas corporais
 *
 * @param WP_Post $post O post atual
 */
function pab_med_corporais_cb($post)
{
    // Obter valores salvos
    $pescoco = pab_get($post->ID, "pab_med_pescoco");
    $torax = pab_get($post->ID, "pab_med_torax");
    $braco_direito = pab_get($post->ID, "pab_med_braco_direito");
    $braco_esquerdo = pab_get($post->ID, "pab_med_braco_esquerdo");
    $abd_superior = pab_get($post->ID, "pab_med_abd_superior");
    $cintura = pab_get($post->ID, "pab_med_cintura");
    $abd_inferior = pab_get($post->ID, "pab_med_abd_inferior");
    $quadril = pab_get($post->ID, "pab_med_quadril");
    $coxa_direita = pab_get($post->ID, "pab_med_coxa_direita");
    $coxa_esquerda = pab_get($post->ID, "pab_med_coxa_esquerda");
    $panturrilha_direita = pab_get($post->ID, "pab_med_panturrilha_direita");
    $panturrilha_esquerda = pab_get($post->ID, "pab_med_panturrilha_esquerda");
    ?>
    <style>
        .pab-medidas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .pab-medidas-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .pab-medidas-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .pab-medidas-section {
            margin-bottom: 24px;
        }

        .pab-medidas-section:last-child {
            margin-bottom: 0;
        }

        .pab-medidas-section h4 {
            margin: 0 0 16px 0;
            color: #1e40af;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .pab-field-row {
            margin-bottom: 16px;
        }

        .pab-field-row:last-child {
            margin-bottom: 0;
        }

        .pab-field-row label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        .pab-field-row input[type="text"],
        .pab-field-row input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: white;
        }

        .pab-field-row input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .pab-unit {
            color: #6b7280;
            font-size: 13px;
            margin-top: 4px;
        }

        .pab-icon {
            display: inline-block;
            margin-right: 8px;
            font-size: 16px;
        }
    </style>

    <div class="pab-medidas-grid">
        <!-- Região Superior -->
        <div class="pab-medidas-card">
            <div class="pab-medidas-section">
                <h4><span class="pab-icon">👆</span>Região Superior</h4>

                <div class="pab-field-row">
                    <label for="pab_med_pescoco">Pescoço</label>
                    <input type="text"
                           id="pab_med_pescoco"
                           name="pab_med_pescoco"
                           value="<?php echo esc_attr($pescoco); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 35,5 ou 35.5">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_torax">Tórax</label>
                    <input type="text"
                           id="pab_med_torax"
                           name="pab_med_torax"
                           value="<?php echo esc_attr($torax); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 95,0 ou 95.0">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>
            </div>
        </div>

        <!-- Braços -->
        <div class="pab-medidas-card">
            <div class="pab-medidas-section">
                <h4><span class="pab-icon">💪</span>Braços</h4>

                <div class="pab-field-row">
                    <label for="pab_med_braco_direito">Braço Direito</label>
                    <input type="text"
                           id="pab_med_braco_direito"
                           name="pab_med_braco_direito"
                           value="<?php echo esc_attr($braco_direito); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 28,5 ou 28.5">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_braco_esquerdo">Braço Esquerdo</label>
                    <input type="text"
                           id="pab_med_braco_esquerdo"
                           name="pab_med_braco_esquerdo"
                           value="<?php echo esc_attr($braco_esquerdo); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 28,0 ou 28.0">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>
            </div>
        </div>

        <!-- Região Abdominal -->
        <div class="pab-medidas-card">
            <div class="pab-medidas-section">
                <h4><span class="pab-icon">🏃</span>Região Abdominal</h4>

                <div class="pab-field-row">
                    <label for="pab_med_abd_superior">Abdomen Superior</label>
                    <input type="text"
                           id="pab_med_abd_superior"
                           name="pab_med_abd_superior"
                           value="<?php echo esc_attr($abd_superior); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 85,0 ou 85.0">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_cintura">Cintura</label>
                    <input type="text"
                           id="pab_med_cintura"
                           name="pab_med_cintura"
                           value="<?php echo esc_attr($cintura); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 80,5 ou 80.5">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_abd_inferior">Abdomen Inferior</label>
                    <input type="text"
                           id="pab_med_abd_inferior"
                           name="pab_med_abd_inferior"
                           value="<?php echo esc_attr($abd_inferior); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 88,0 ou 88.0">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_quadril">Quadril</label>
                    <input type="text"
                           id="pab_med_quadril"
                           name="pab_med_quadril"
                           value="<?php echo esc_attr($quadril); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 95,5 ou 95.5">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>
            </div>
        </div>

        <!-- Pernas -->
        <div class="pab-medidas-card">
            <div class="pab-medidas-section">
                <h4><span class="pab-icon">🦵</span>Pernas</h4>

                <div class="pab-field-row">
                    <label for="pab_med_coxa_direita">Coxa Direita</label>
                    <input type="text"
                           id="pab_med_coxa_direita"
                           name="pab_med_coxa_direita"
                           value="<?php echo esc_attr($coxa_direita); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 55,0 ou 55.0">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_coxa_esquerda">Coxa Esquerda</label>
                    <input type="text"
                           id="pab_med_coxa_esquerda"
                           name="pab_med_coxa_esquerda"
                           value="<?php echo esc_attr($coxa_esquerda); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 54,5 ou 54.5">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_panturrilha_direita">Panturrilha Direita</label>
                    <input type="text"
                           id="pab_med_panturrilha_direita"
                           name="pab_med_panturrilha_direita"
                           value="<?php echo esc_attr($panturrilha_direita); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 35,0 ou 35.0">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>

                <div class="pab-field-row">
                    <label for="pab_med_panturrilha_esquerda">Panturrilha Esquerda</label>
                    <input type="text"
                           id="pab_med_panturrilha_esquerda"
                           name="pab_med_panturrilha_esquerda"
                           value="<?php echo esc_attr(
                               $panturrilha_esquerda,
                           ); ?>"
                           class="pab-decimal-input"
                           placeholder="Ex: 34,8 ou 34.8">
                    <div class="pab-unit">Valor em centímetros (cm)</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Adicionar validação em tempo real e formatação com suporte a vírgulas
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.pab-decimal-input');

        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                // Permitir números, vírgulas e pontos
                this.value = this.value.replace(/[^0-9.,]/g, '');

                // Substituir vírgulas por pontos para padronização
                let value = this.value.replace(/,/g, '.');

                // Garantir apenas um separador decimal
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }

                // Limitar a 1 casa decimal
                if (parts[1] && parts[1].length > 1) {
                    value = parseFloat(value).toFixed(1);
                }

                this.value = value;
            });

            input.addEventListener('blur', function() {
                // Formatação final ao sair do campo
                let value = this.value.replace(/,/g, '.');

                if (value && !isNaN(parseFloat(value))) {
                    // Formatar com uma casa decimal
                    this.value = parseFloat(value).toFixed(1);

                    // Validação visual
                    if (parseFloat(value) >= 0) {
                        this.style.borderColor = '#10b981';
                        this.style.backgroundColor = '#f0fdf4';
                    }
                } else if (value === '') {
                    this.style.borderColor = '#e2e8f0';
                    this.style.backgroundColor = 'white';
                } else {
                    this.style.borderColor = '#dc2626';
                    this.style.backgroundColor = '#fef2f2';
                }
            });

            // Adicionar efeito visual ao focar
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.2s ease';
                this.style.backgroundColor = 'white';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });

            // Permitir teclas de navegação
            input.addEventListener('keydown', function(e) {
                // Permitir: backspace, delete, tab, escape, enter
                if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                    // Permitir: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true) ||
                    // Permitir: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    return;
                }

                // Garantir que seja número, vírgula ou ponto
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) &&
                    (e.keyCode < 96 || e.keyCode > 105) &&
                    e.keyCode !== 188 && e.keyCode !== 190) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
    <?php
}
