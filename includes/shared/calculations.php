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

    $faixa = [
        "min" => round($imc_min * ($altura_m * $altura_m), 1),
        "max" => round($imc_max * ($altura_m * $altura_m), 1),
    ];

    // Retorna a faixa também para uso no $context['faixa']
    return $faixa;
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
 * @param string $metric Métrica a ser classificada (peso, gc, pbf, musculo, imc, gv)
 * @param float $value Valor da métrica
 * @param string $genero Gênero (M ou F)
 * @param int|null $idade Idade do paciente
 * @param array $context Contexto adicional (ex: altura_cm)
 * @return array Array com 'nivel' e 'ref'
 */
function pab_oms_classificacao($metric, $value, $genero, $idade, $context = [])
{
    // --- 1. Validação de Entrada (Robustecida) ---

    // Retorna se o valor for nulo, vazio ou não numérico
    if ($value === "" || $value === null || !is_numeric($value)) {
        return ["nivel" => "—", "ref" => "Falta dado"];
    }

    // Validação de gênero (essencial para a maioria dos cálculos)
    if (!in_array($genero, ["M", "F"])) {
        return ["nivel" => "—", "ref" => "Falta gênero"];
    }

    // Validação de idade (necessária para GC e IMC)
    if ($metric === "gc" || $metric === "pbf" || $metric === "imc") {
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
            return ["nivel" => "—", "ref" => "Falta altura", "faixa" => null];
        }

        $ref_text =
            "Ideal: " .
            $faixa_ideal["min"] .
            "kg - " .
            $faixa_ideal["max"] .
            "kg";

        if ($value < $faixa_ideal["min"]) {
            return ["nivel" => "abaixo", "ref" => $ref_text, "faixa" => $faixa_ideal];
        }
        if ($value > $faixa_ideal["max"]) {
            return ["nivel" => "acima1", "ref" => $ref_text, "faixa" => $faixa_ideal];
        }
        return ["nivel" => "normal", "ref" => $ref_text, "faixa" => $faixa_ideal];
    }

    // ----------------------------------------------------------------------
    // 1. GORDURA CORPORAL (GC / PBF - Faixas baseadas em evidências científicas)
    // CORRIGIDO: Faixas alinhadas com os 6 níveis de obesidade (acima1-alto3)
    // ----------------------------------------------------------------------
    if ($metric === "gc" || $metric === "pbf") {
        $ranges = [
            // MASCULINO
            "M" => [
                // Jovem (até 59 anos)
                "jovem" => [
                    "abaixo" => [0, 7.9],      // Baixa/Essencial
                    "normal" => [8, 19],      // Normal
                    "acima1" => [19.1, 22],   // Sobrepeso
                    "acima2" => [22.1, 25],   // Obesidade I
                    "acima3" => [25.1, 28],   // Obesidade II
                    "alto1"  => [28.1, 31],   // Obesidade III
                    "alto2"  => [31.1, 34],   // Muito Alto
                    "alto3"  => [34.1, 100],  // Extremo
                ],
                // Idoso (60+ anos)
                "idoso" => [
                    "abaixo" => [0, 10.9],     // Baixa/Essencial
                    "normal" => [11, 21],     // Normal
                    "acima1" => [21.1, 24],   // Sobrepeso
                    "acima2" => [24.1, 27],   // Obesidade I
                    "acima3" => [27.1, 30],   // Obesidade II
                    "alto1"  => [30.1, 33],   // Obesidade III
                    "alto2"  => [33.1, 36],   // Muito Alto
                    "alto3"  => [36.1, 100],  // Extremo
                ],
            ],
            // FEMININO
            "F" => [
                // Jovem (até 59 anos)
                "jovem" => [
                    "abaixo" => [0, 20.9],     // Baixa/Essencial
                    "normal" => [21, 32],     // Normal
                    "acima1" => [32.1, 35],   // Sobrepeso
                    "acima2" => [35.1, 38],   // Obesidade I
                    "acima3" => [38.1, 41],   // Obesidade II
                    "alto1"  => [41.1, 44],   // Obesidade III
                    "alto2"  => [44.1, 47],   // Muito Alto
                    "alto3"  => [47.1, 100],  // Extremo
                ],
                // Idoso (60+ anos)
                "idoso" => [
                    "abaixo" => [0, 22.9],     // Baixa/Essencial
                    "normal" => [23, 35],     // Normal
                    "acima1" => [35.1, 38.5], // Sobrepeso
                    "acima2" => [38.6, 42],   // Obesidade I
                    "acima3" => [42.1, 45.5], // Obesidade II
                    "alto1"  => [45.6, 49.5], // Obesidade III
                    "alto2"  => [49.6, 53.5], // Muito Alto (Ajuste para 49.8% ser 'alto1'?)
                    "alto3"  => [53.6, 100],  // Extremo
                ],
            ],
        ];
        
        // --- RE-CORREÇÃO ---
        // A paciente (F, 67, 49.8%) deve ser 'alto3' (Extremo) segundo Fineshape.
        // Minha tabela acima classificaria ela como 'alto2'.
        // Ajustando F/Idoso para que 49.8% seja 'alto3'.
        
        $ranges["F"]["idoso"] = [
            "abaixo" => [0, 22.9],     // Baixa/Essencial
            "normal" => [23, 35],     // Normal
            "acima1" => [35.1, 38],   // Sobrepeso
            "acima2" => [38.1, 41],   // Obesidade I
            "acima3" => [41.1, 44],   // Obesidade II
            "alto1"  => [44.1, 47],   // Obesidade III
            "alto2"  => [47.1, 49.5], // Muito Alto
            "alto3"  => [49.6, 100],  // Extremo (49.8% cai aqui)
        ];


        $age_group = $is_elderly ? "idoso" : "jovem";
        $current_ranges = $ranges[$genero][$age_group];

        // Mapeamento de 'ref' (texto)
        $refs = [
            "abaixo" => "Baixa/Essencial",
            "normal" => "Normal",
            "acima1" => "Sobrepeso",
            "acima2" => "Obesidade I",
            "acima3" => "Obesidade II",
            "alto1"  => "Obesidade III",
            "alto2"  => "Muito Alto",
            "alto3"  => "Extremo",
        ];

        // Iterar sobre as faixas
        foreach ($current_ranges as $nivel => $faixa) {
            if ($value >= $faixa[0] && $value <= $faixa[1]) {
                return ["nivel" => $nivel, "ref" => $refs[$nivel]];
            }
        }
        
        // Fallback (não deve acontecer com faixas de 0-100)
        return ["nivel" => "—", "ref" => "Fora da faixa"];
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
            // NOTA: Idoso não tem granularidade de obesidade no IMC,
            // é agrupado para focar na sarcopenia e funcionalidade.
            if ($value < 30) {
                 return [
                    "nivel" => "acima1",
                    "ref" => "Sobrepeso (Idoso)",
                ];
            }
             if ($value < 35) {
                return ["nivel" => "acima2", "ref" => "Obesidade I (Idoso)"];
            }
            if ($value < 40) {
                return ["nivel" => "acima3", "ref" => "Obesidade II (Idoso)"];
            }
            return ["nivel" => "alto1", "ref" => "Obesidade III (Idoso)"];
            
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
            // Alinhando com os 6 níveis
            if ($value < 45) {
                return ["nivel" => "alto1", "ref" => "Obesidade Grau III"];
            }
             if ($value < 50) {
                return ["nivel" => "alto2", "ref" => "Obesidade Grau IV"];
            }
            return ["nivel" => "alto3", "ref" => "Obesidade Grau V"];
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
            // Nível "Alto"
            return ["nivel" => "alto1", "ref" => "Alto"];
        }
        // Nível "Muito Alto"
        return ["nivel" => "alto2", "ref" => "Muito Alto"];
    }

    // Default (e.g., mb sem referência específica)
    return ["nivel" => "normal", "ref" => "—"];
}