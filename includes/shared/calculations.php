<?php
/**
 * Funções de Cálculo Compartilhadas
 *
 * @package PAB
 * @subpackage Shared
 */

if (!defined("ABSPATH")) {
    exit();
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
        "min" => round($imc_min * ($altura_m * $altura_m), 1),
        "max" => round($imc_max * ($altura_m * $altura_m), 1),
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
    $nasc = pab_get($patient_id, "pab_nascimento");
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
 * CORRIGIDO: Adicionadas validações robustas na entrada
 * para Gênero, Idade e Valor.
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
    /*
    error_log(
        "PAB DEBUG: pab_oms_classificacao chamada com metric=$metric, value=$value, genero=$genero, idade=$idade",
    );
    */

    // --- 1. Validação de Entrada (Robustecida) ---

    // Retorna se o valor for nulo, vazio ou não numérico
    if ($value === "" || $value === null || !is_numeric($value)) {
        return ["nivel" => "—", "ref" => "Falta dado"];
    }

    // Validação de gênero (essencial para a maioria dos cálculos)
    if (!in_array($genero, ["M", "F"])) {
        // Não assumir um gênero padrão, pois isso leva a cálculos errados.
        return ["nivel" => "—", "ref" => "Falta gênero"];
    }

    // Validação de idade (necessária para GC e IMC)
    if ($metric === "gc" || $metric === "imc") {
        if ($idade === null || !is_numeric($idade) || $idade <= 0) {
            return ["nivel" => "—", "ref" => "Falta idade"];
        }
    }

    // Configuração de corte de idade (Adulto vs. Idoso)
    $is_elderly = $idade !== null && $idade >= 60;

    // --- 2. Cálculos ---

    // ----------------------------------------------------------------------
    // 0. PESO (baseado na faixa de IMC ideal)
    // ----------------------------------------------------------------------
    if ($metric === "peso") {
        $altura_cm = isset($context["altura_cm"])
            ? $context["altura_cm"]
            : null;
        $faixa_ideal = pab_calc_faixa_peso_ideal($altura_cm);

        if (!$faixa_ideal) {
            return ["nivel" => "—", "ref" => "Falta altura"];
        }

        $ref_text =
            "Ideal: " .
            $faixa_ideal["min"] .
            "kg - " .
            $faixa_ideal["max"] .
            "kg";

        if ($value < $faixa_ideal["min"]) {
            return ["nivel" => "abaixo", "ref" => $ref_text];
        }
        if ($value > $faixa_ideal["max"]) {
            return ["nivel" => "acima1", "ref" => $ref_text];
        }
        return ["nivel" => "normal", "ref" => $ref_text];
    }

    // ----------------------------------------------------------------------
    // 1. GORDURA CORPORAL (GC - Faixas baseadas em evidências científicas)
    // ----------------------------------------------------------------------
    if ($metric === "gc") {
        $ranges = [
            "M" => [
                // Masculino - Baseado em estudos de síndrome metabólica
                "jovem" => [
                    "normal" => [8, 19], // Faixa saudável padrão
                    "acima1" => [20, 24], // Sobrepeso
                    "acima2" => [25, 29], // Obesidade leve
                    "alto1" => [30, 100], // Obesidade moderada/severa
                ],
                "idoso" => [
                    "normal" => [11, 21], // Ajuste para idade avançada
                    "acima1" => [22, 26], // Sobrepeso
                    "acima2" => [27, 31], // Obesidade leve
                    "alto1" => [32, 100], // Obesidade moderada/severa
                ],
            ],
            "F" => [
                // Feminino - Baseado em estudos clínicos validados
                "jovem" => [
                    "normal" => [21, 32], // Faixa saudável considerando função reprodutiva
                    "acima1" => [33, 37], // Sobrepeso
                    "acima2" => [38, 42], // Obesidade leve
                    "alto1" => [43, 100], // Obesidade moderada/severa
                ],
                "idoso" => [
                    "normal" => [23, 35], // Ajuste para idade avançada
                    "acima1" => [36, 40], // Sobrepeso
                    "acima2" => [41, 45], // Obesidade leve
                    "alto1" => [46, 100], // Obesidade moderada/severa
                ],
            ],
        ];

        $age_group = $is_elderly ? "idoso" : "jovem";

        // Verificação de segurança dos ranges (embora gênero já tenha sido validado)
        if (!isset($ranges[$genero][$age_group])) {
            return ["nivel" => "—", "ref" => "Erro de configuração"];
        }

        $current_ranges = $ranges[$genero][$age_group];

        if ($value < $current_ranges["normal"][0]) {
            return ["nivel" => "abaixo", "ref" => "Baixa/Essencial"];
        }
        if ($value <= $current_ranges["normal"][1]) {
            return ["nivel" => "normal", "ref" => "Normal"];
        }
        if ($value <= $current_ranges["acima1"][1]) {
            return ["nivel" => "acima1", "ref" => "Sobrepeso"];
        }
        if ($value <= $current_ranges["acima2"][1]) {
            return ["nivel" => "acima2", "ref" => "Obesidade Leve"];
        }
        return ["nivel" => "alto1", "ref" => "Obesidade Moderada/Severa"];
    }

    // ----------------------------------------------------------------------
    // 2. MÚSCULO ESQUELÉTICO (ME - Faixas por Gênero)
    // ----------------------------------------------------------------------
    if ($metric === "musculo") {
        $ranges = [
            "M" => ["abaixo" => 33.3, "normal" => 39.4, "acima1" => 100],
            "F" => ["abaixo" => 24.4, "normal" => 32.8, "acima1" => 100],
        ];

        $current_ranges = $ranges[$genero];

        if ($value < $current_ranges["abaixo"]) {
            return ["nivel" => "abaixo", "ref" => "Baixo"];
        }
        if ($value <= $current_ranges["normal"]) {
            return ["nivel" => "normal", "ref" => "Normal"];
        }
        return ["nivel" => "acima1", "ref" => "Alto"];
    }

    // ----------------------------------------------------------------------
    // 3. IMC (Índice de Massa Corporal - Padrão OMS)
    // ----------------------------------------------------------------------
    if ($metric === "imc") {
        if ($is_elderly) {
            // Faixas Sugeridas para Idosos
            if ($value < 22) {
                return ["nivel" => "abaixo", "ref" => "Baixo Peso (Idoso)"];
            }
            if ($value < 27) {
                return ["nivel" => "normal", "ref" => "Normal (Idoso)"];
            }
            return [
                "nivel" => "acima1",
                "ref" => "Sobrepeso/Obesidade (Idoso)",
            ];
        } else {
            // Faixas Padrão Adulto
            if ($value < 18.5) {
                return ["nivel" => "abaixo", "ref" => "Baixo Peso"];
            }
            if ($value < 25) {
                return ["nivel" => "normal", "ref" => "Normal"];
            }
            if ($value < 30) {
                return ["nivel" => "acima1", "ref" => "Sobrepeso"];
            }
            if ($value < 35) {
                return ["nivel" => "acima2", "ref" => "Obesidade Grau I"];
            }
            if ($value < 40) {
                return ["nivel" => "acima3", "ref" => "Obesidade Grau II"];
            }
            return ["nivel" => "alto1", "ref" => "Obesidade Grau III"];
        }
    }

    // ----------------------------------------------------------------------
    // 4. GORDURA VISCERAL (GV - Nível 1-59)
    // ----------------------------------------------------------------------
    if ($metric === "gv") {
        if ($value <= 9) {
            return ["nivel" => "normal", "ref" => "Normal"];
        }
        if ($value <= 14) {
            return ["nivel" => "alto1", "ref" => "Alto"];
        }
        return ["nivel" => "alto2", "ref" => "Muito Alto"];
    }

    // Default (e.g., mb sem referência específica)
    return ["nivel" => "normal", "ref" => "—"];
}
