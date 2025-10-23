<?php
/**
 * Funções de Cálculo Compartilhadas
 *
 * Contém funções de cálculo utilizadas por múltiplos post types
 *
 * @package PAB
 * @subpackage Shared
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calcula a faixa de peso ideal baseado na altura
 *
 * @param float $altura_cm Altura em centímetros
 * @return array|null Array com 'min' e 'max' ou null se inválido
 */
function pab_calc_faixa_peso_ideal($altura_cm)
{
    if (!$altura_cm || $altura_cm <= 0) {
        return null;
    }
    $altura_m = $altura_cm / 100.0;
    $imc_min = 18.5;
    $imc_max = 24.9;

    return [
        'min' => round($imc_min * ($altura_m * $altura_m), 1),
        'max' => round($imc_max * ($altura_m * $altura_m), 1),
    ];
}

/**
 * Calcula a idade real do paciente baseado na data de nascimento
 *
 * @param int $patient_id ID do paciente
 * @return int|null Idade em anos ou null se inválido
 */
function pab_calc_idade_real($patient_id)
{
    $nasc = pab_get($patient_id, 'pab_nascimento');
    if (!$nasc) {
        return null;
    }
    try {
        $dt = new DateTime($nasc);
        $now = new DateTime();
        return (int) $dt->diff($now)->y;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Classificação OMS/Padrão por Métrica
 *
 * Implementa faixas por Gênero e Idade (usando 60 como corte para idoso/jovem).
 * Adicionado cálculo de Peso Ideal por IMC.
 *
 * @param string $metric Métrica a ser classificada (peso, gc, musculo, imc, gv)
 * @param float $value Valor da métrica
 * @param string $genero Gênero (M ou F)
 * @param int|null $idade Idade do paciente
 * @param array $context Contexto adicional (ex: altura_cm)
 * @return array Array com 'nivel' e 'ref'
 */
function pab_oms_classificacao($metric, $value, $genero, $idade, $context = [])
{
    // Debug log
    error_log(
        "PAB DEBUG: pab_oms_classificacao chamada com metric=$metric, value=$value, genero=$genero, idade=$idade"
    );

    // Retorna se o valor for nulo ou vazio
    if ($value === '' || $value === null || !is_numeric($value)) {
        error_log("PAB DEBUG: Valor inválido para $metric: $value");
        return ['nivel' => '—', 'ref' => 'Falta dado'];
    }

    // Validação de gênero
    if (!in_array($genero, ['M', 'F'])) {
        error_log("PAB DEBUG: Gênero inválido '$genero', usando M como padrão");
        $genero = 'M'; // Default
    }

    // Configuração de corte de idade (Adulto vs. Idoso)
    $is_elderly = $idade !== null && $idade >= 60;

    // ----------------------------------------------------------------------
    // 0. PESO (baseado na faixa de IMC ideal)
    // ----------------------------------------------------------------------
    if ($metric === 'peso') {
        $altura_cm = isset($context['altura_cm']) ? $context['altura_cm'] : null;
        $faixa_ideal = pab_calc_faixa_peso_ideal($altura_cm);

        if (!$faixa_ideal) {
            return ['nivel' => '—', 'ref' => 'Falta altura'];
        }

        $ref_text = 'Ideal: ' . $faixa_ideal['min'] . 'kg - ' . $faixa_ideal['max'] . 'kg';

        if ($value < $faixa_ideal['min']) {
            return ['nivel' => 'abaixo', 'ref' => $ref_text];
        }
        if ($value > $faixa_ideal['max']) {
            return ['nivel' => 'acima1', 'ref' => $ref_text];
        }
        return ['nivel' => 'normal', 'ref' => $ref_text];
    }

    // ----------------------------------------------------------------------
    // 1. GORDURA CORPORAL (GC - Faixas por Idade/Gênero)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === 'gc') {
        $ranges = [
            'M' => [
                // Masculino
                'jovem' => [
                    'normal' => [11, 21],
                    'acima1' => [22, 26],
                    'acima2' => [27, 30],
                    'alto1' => [31, 100],
                ],
                'idoso' => [
                    'normal' => [13, 23],
                    'acima1' => [24, 28],
                    'acima2' => [29, 32],
                    'alto1' => [33, 100],
                ],
            ],
            'F' => [
                // Feminino
                'jovem' => [
                    'normal' => [18, 28],
                    'acima1' => [29, 33],
                    'acima2' => [34, 38],
                    'alto1' => [39, 100],
                ],
                'idoso' => [
                    'normal' => [20, 30],
                    'acima1' => [31, 35],
                    'acima2' => [36, 40],
                    'alto1' => [41, 100],
                ],
            ],
        ];

        $age_group = $is_elderly ? 'idoso' : 'jovem';

        // Verificação de segurança dos ranges
        if (!isset($ranges[$genero][$age_group])) {
            return ['nivel' => '—', 'ref' => 'Erro de configuração'];
        }

        $current_ranges = $ranges[$genero][$age_group];

        if ($value < $current_ranges['normal'][0]) {
            return ['nivel' => 'abaixo', 'ref' => 'Baixa/Essencial'];
        }
        if ($value <= $current_ranges['normal'][1]) {
            return ['nivel' => 'normal', 'ref' => 'Normal'];
        }
        if ($value <= $current_ranges['acima1'][1]) {
            return ['nivel' => 'acima1', 'ref' => 'Limítrofe/Sobrepeso'];
        }
        if ($value <= $current_ranges['acima2'][1]) {
            return ['nivel' => 'acima2', 'ref' => 'Obesidade Moderada'];
        }
        return ['nivel' => 'alto1', 'ref' => 'Obesidade Elevada'];
    }

    // ----------------------------------------------------------------------
    // 2. MÚSCULO ESQUELÉTICO (ME - Faixas por Gênero)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === 'musculo') {
        $ranges = [
            'M' => ['abaixo' => 33.3, 'normal' => 39.4, 'acima1' => 100],
            'F' => ['abaixo' => 24.4, 'normal' => 32.8, 'acima1' => 100],
        ];

        // Verificação de segurança dos ranges
        if (!isset($ranges[$genero])) {
            return ['nivel' => '—', 'ref' => 'Erro de configuração'];
        }

        $current_ranges = $ranges[$genero];

        if ($value < $current_ranges['abaixo']) {
            return ['nivel' => 'abaixo', 'ref' => 'Baixo'];
        }
        if ($value <= $current_ranges['normal']) {
            return ['nivel' => 'normal', 'ref' => 'Normal'];
        }
        return ['nivel' => 'acima1', 'ref' => 'Alto'];
    }

    // ----------------------------------------------------------------------
    // 3. IMC (Índice de Massa Corporal - Padrão OMS)
    // Fonte: OMS (World Health Organization)
    // ----------------------------------------------------------------------
    if ($metric === 'imc') {
        if ($is_elderly) {
            // Faixas Sugeridas para Idosos
            if ($value < 22) {
                return ['nivel' => 'abaixo', 'ref' => 'Baixo Peso (Idoso)'];
            }
            if ($value < 27) {
                return ['nivel' => 'normal', 'ref' => 'Normal (Idoso)'];
            }
            return ['nivel' => 'acima1', 'ref' => 'Sobrepeso/Obesidade (Idoso)'];
        } else {
            // Faixas Padrão Adulto
            if ($value < 18.5) {
                return ['nivel' => 'abaixo', 'ref' => 'Baixo Peso'];
            }
            if ($value < 25) {
                return ['nivel' => 'normal', 'ref' => 'Normal'];
            }
            if ($value < 30) {
                return ['nivel' => 'acima1', 'ref' => 'Sobrepeso'];
            }
            if ($value < 35) {
                return ['nivel' => 'acima2', 'ref' => 'Obesidade Grau I'];
            }
            if ($value < 40) {
                return ['nivel' => 'acima3', 'ref' => 'Obesidade Grau II'];
            }
            return ['nivel' => 'alto1', 'ref' => 'Obesidade Grau III'];
        }
    }

    // ----------------------------------------------------------------------
    // 4. GORDURA VISCERAL (GV - Nível 1-59)
    // Fonte: Padrões comuns de Bioimpedância
    // ----------------------------------------------------------------------
    if ($metric === 'gv') {
        if ($value <= 9) {
            return ['nivel' => 'normal', 'ref' => 'Normal'];
        }
        if ($value <= 14) {
            return ['nivel' => 'alto1', 'ref' => 'Alto'];
        }
        return ['nivel' => 'alto2', 'ref' => 'Muito Alto'];
    }

    // Default (e.g., mb sem referência específica)
    return ['nivel' => 'normal', 'ref' => '—'];
}
