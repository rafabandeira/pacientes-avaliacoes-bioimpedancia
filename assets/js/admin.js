// assets/js/admin.js - JavaScript aprimorado com micro-interações
jQuery(function ($) {
  "use strict";

  // ================================================================
  // INICIALIZAÇÃO E CONFIGURAÇÕES
  // ================================================================

  let isInitialized = false;

  function initializeEnhancements() {
    if (isInitialized) return;
    isInitialized = true;

    addLoadingStates();
    enhanceFormInputs();
    addCopyFunctionality();
    addTooltips();
    addSmoothAnimations();
    initializeChartEnhancements();
    addKeyboardShortcuts();

    // Adicionar classe fade-in aos elementos principais
    $(".postbox .inside").addClass("pab-fade-in");
  }

  // ================================================================
  // CONDICIONAIS E TOGGLES (FUNCIONALIDADE ORIGINAL)
  // ================================================================

  function toggleBoxes() {
    $(".pab-toggle").each(function () {
      const val = $(this).val();
      const target = $(this).data("target");
      if (!target) return;
      const showVal = $(target).data("show") || "sim";

      const $target = $(target);
      if (val === showVal) {
        $target.slideDown(300).removeClass("pab-conditional");
      } else {
        $target.slideUp(300).addClass("pab-conditional");
      }
    });
  }

  $(document).on("change", ".pab-toggle", toggleBoxes);

  // ================================================================
  // ESTADOS DE CARREGAMENTO
  // ================================================================

  function addLoadingStates() {
    // Adicionar loading state ao salvar
    $("#post").on("submit", function () {
      const $submitButton = $("#publish, #save-post");
      if ($submitButton.length) {
        $submitButton.prop("disabled", true);
        $submitButton.html('<span class="pab-loading"></span> Salvando...');
      }

      // Mostrar overlay de carregamento
      $("body").append(
        '<div class="pab-loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div class="pab-loading" style="width: 40px; height: 40px; border-width: 4px;"></div></div>',
      );
    });
  }

  // ================================================================
  // APRIMORAMENTOS DE INPUTS
  // ================================================================

  function formatPhoneNumber(value) {
    value = value.replace(/\D/g, "");
    if (value.length <= 11) {
      if (value.length > 2) {
        value = "(" + value.substring(0, 2) + ") " + value.substring(2);
      }
      if (value.length > 9) {
        value = value.substring(0, 10) + "-" + value.substring(10);
      }
      return value;
    }
    return value.substring(0, 11);
  }

  function enhanceFormInputs() {
    // Formatar números de telefone existentes
    $('input[name="pab_celular"]').each(function () {
      const $input = $(this);
      $input.val(formatPhoneNumber($input.val()));
    });

    // Formatação do telefone em tempo real
    $('input[name="pab_celular"]').on("input", function (e) {
      let value = e.target.value.replace(/\D/g, "");
      if (value.length <= 11) {
        if (value.length > 2) {
          value = "(" + value.substring(0, 2) + ") " + value.substring(2);
        }
        if (value.length > 9) {
          value = value.substring(0, 10) + "-" + value.substring(10);
        }
        $(this).val(value);
      } else {
        $(this).val(value.substring(0, 11));
      }
    });

    // Adicionar feedback visual aos inputs
    // Aplicar estilos consistentes em todos os inputs
    $(
      '.pab-grid input[type="number"], .pab-grid input[type="text"], .pab-grid input[type="email"]',
    ).each(function () {
      $(this).addClass("pab-input");
      const type = $(this).attr("type");
      if (type === "email") {
        $(this).css({
          padding: "8px 12px",
          border: "1px solid #e2e8f0",
          "border-radius": "4px",
          width: "100%",
          "box-sizing": "border-box",
          "font-size": "14px",
          "line-height": "1.5",
          transition: "border-color 0.2s ease-in-out",
        });
      }
      const $input = $(this);
      const $label = $input.closest("label");

      // Adicionar ícone de validação
      $input.on("input blur", function () {
        const value = $(this).val();
        const $existingIcon = $label.find(".pab-validation-icon");
        $existingIcon.remove();

        if (
          value &&
          value !== "" &&
          (($(this).attr("type") === "email" && value.includes("@")) ||
            ($(this).attr("type") === "number" &&
              !isNaN(value) &&
              parseFloat(value) >= 0) ||
            ($(this).attr("name") === "pab_celular" &&
              value.replace(/\D/g, "").length >= 10) ||
            $(this).attr("type") === "text")
        ) {
          $label.append(
            '<span class="pab-validation-icon" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 14px;">✓</span>',
          );
          $input.css("border-color", "#10b981");
        } else if (value === "" || value === null) {
          $input.css("border-color", "#e2e8f0");
        } else {
          $label.append(
            '<span class="pab-validation-icon" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #dc2626; font-size: 14px;">✗</span>',
          );
          $input.css("border-color", "#dc2626");
        }
      });

      // Posição relativa para o ícone
      $label.css("position", "relative");
    });

    // Auto-formatação de números
    $('.pab-grid input[type="number"]').on("blur", function () {
      const value = parseFloat($(this).val());
      if (!isNaN(value)) {
        const decimals =
          $(this).attr("step") && $(this).attr("step").includes(".") ? 1 : 0;
        $(this).val(value.toFixed(decimals));
      }
    });

    // Suporte a vírgulas decimais em campos de medidas
    $(".pab-decimal-input").on("input", function () {
      // Permitir números, vírgulas e pontos
      this.value = this.value.replace(/[^0-9.,]/g, "");

      // Substituir vírgulas por pontos para padronização
      let value = this.value.replace(/,/g, ".");

      // Garantir apenas um separador decimal
      const parts = value.split(".");
      if (parts.length > 2) {
        value = parts[0] + "." + parts.slice(1).join("");
      }

      // Limitar a 1 casa decimal
      if (parts[1] && parts[1].length > 1) {
        value = parseFloat(value).toFixed(1);
      }

      this.value = value;
    });

    $(".pab-decimal-input").on("blur", function () {
      // Formatação final ao sair do campo
      let value = this.value.replace(/,/g, ".");

      if (value && !isNaN(parseFloat(value))) {
        // Formatar com uma casa decimal
        this.value = parseFloat(value).toFixed(1);

        // Validação visual
        if (parseFloat(value) >= 0) {
          $(this).css({
            "border-color": "#10b981",
            "background-color": "#f0fdf4",
          });
        }
      } else if (value === "") {
        $(this).css({
          "border-color": "#e2e8f0",
          "background-color": "white",
        });
      } else {
        $(this).css({
          "border-color": "#dc2626",
          "background-color": "#fef2f2",
        });
      }
    });
  }

  // ================================================================
  // FUNCIONALIDADE DE CÓPIA APRIMORADA
  // ================================================================

  function addCopyFunctionality() {
    // Aprimorar o campo de link de compartilhamento
    $(document).on("click", ".pab-share-input", function () {
      const $input = $(this);
      $input.select();

      // Tentar copiar usando API moderna
      if (navigator.clipboard) {
        navigator.clipboard.writeText($input.val()).then(function () {
          showCopySuccess($input);
        });
      } else {
        // Fallback para browsers mais antigos
        try {
          document.execCommand("copy");
          showCopySuccess($input);
        } catch (err) {
          console.error("Erro ao copiar:", err);
        }
      }
    });
  }

  function showCopySuccess($input) {
    const originalBg = $input.css("background-color");
    const originalColor = $input.css("color");

    $input.css({
      background: "#10b981",
      color: "white",
      transition: "all 0.3s ease",
    });

    // Mostrar tooltip de sucesso
    const $tooltip = $('<div class="pab-copy-tooltip">Link copiado! 📋</div>');
    $tooltip.css({
      position: "absolute",
      top: "-40px",
      left: "50%",
      transform: "translateX(-50%)",
      background: "#1f2937",
      color: "white",
      padding: "8px 12px",
      borderRadius: "6px",
      fontSize: "12px",
      whiteSpace: "nowrap",
      zIndex: 1000,
      animation: "fadeInUp 0.3s ease",
    });

    $input.parent().css("position", "relative").append($tooltip);

    setTimeout(function () {
      $input.css({
        background: originalBg,
        color: originalColor,
      });
      $tooltip.fadeOut(300, function () {
        $(this).remove();
      });
    }, 2000);
  }

  // ================================================================
  // TOOLTIPS INFORMATIVOS
  // ================================================================

  function addTooltips() {
    // Adicionar tooltips aos badges de classificação
    $(".pab-badge").each(function () {
      const $badge = $(this);
      const text = $badge.text();

      $badge
        .on("mouseenter", function (e) {
          const tooltip = getClassificationTooltip(text);
          if (!tooltip) return;

          const $tooltip = $('<div class="pab-tooltip">' + tooltip + "</div>");
          $tooltip.css({
            position: "absolute",
            background: "#1f2937",
            color: "white",
            padding: "8px 12px",
            borderRadius: "6px",
            fontSize: "12px",
            maxWidth: "200px",
            zIndex: 1000,
            boxShadow: "0 4px 6px -1px rgba(0, 0, 0, 0.1)",
            animation: "fadeIn 0.2s ease",
          });

          $("body").append($tooltip);

          const updatePosition = function (e) {
            $tooltip.css({
              left: e.pageX + 10 + "px",
              top: e.pageY - 40 + "px",
            });
          };

          updatePosition(e);
          $(document).on("mousemove.tooltip", updatePosition);
        })
        .on("mouseleave", function () {
          $(".pab-tooltip").fadeOut(200, function () {
            $(this).remove();
          });
          $(document).off("mousemove.tooltip");
        });
    });
  }

  function getClassificationTooltip(classification) {
    const tooltips = {
      Normal: "Dentro dos parâmetros ideais para idade e gênero",
      normal: "Dentro dos parâmetros ideais para idade e gênero",
      Baixo: "Abaixo do recomendado - considere avaliação nutricional",
      abaixo: "Abaixo do recomendado - considere avaliação nutricional",
      Alto: "Acima do recomendado - monitoramento necessário",
      Sobrepeso: "IMC entre 25-29.9 - risco moderado para saúde",
      "Obesidade I": "IMC 30-34.9 - risco aumentado, intervenção recomendada",
      "Obesidade II": "IMC 35-39.9 - risco alto, acompanhamento médico",
      "Obesidade III": "IMC ≥40 - risco muito alto, intervenção urgente",
    };
    return tooltips[classification] || null;
  }

  // ================================================================
  // ANIMAÇÕES SUAVES
  // ================================================================

  function addSmoothAnimations() {
    // Animação de entrada para metaboxes
    $(".postbox").each(function (index) {
      $(this)
        .css({
          opacity: 0,
          transform: "translateY(20px)",
        })
        .delay(index * 100)
        .animate(
          {
            opacity: 1,
          },
          500,
        )
        .css("transform", "translateY(0)");
    });

    // Hover effect para avatares
    $(document)
      .on("mouseenter", ".pab-avatar", function () {
        $(this).css("transform", "translateY(-4px) scale(1.05)");
      })
      .on("mouseleave", ".pab-avatar", function () {
        if (!$(this).hasClass("active")) {
          $(this).css("transform", "");
        }
      });

    // Animação para alerts
    $(".pab-alert").hide().slideDown(400);
  }

  // ================================================================
  // APRIMORAMENTOS DE GRÁFICOS
  // ================================================================

  function initializeChartEnhancements() {
    // Aguardar carregamento dos gráficos
    if (typeof window.PAB_CHART_DATA !== "undefined") {
      setTimeout(function () {
        addChartInteractivity();
      }, 1000);
    }
  }

  function addChartInteractivity() {
    // Adicionar controles de zoom aos gráficos
    $("canvas").each(function () {
      const $canvas = $(this);
      const $container = $canvas.closest(".pab-charts");

      if ($container.length) {
        const $controls = $(
          '<div class="pab-chart-controls" style="text-align: center; margin-top: 12px;"></div>',
        );
        const $zoomIn = $(
          '<button type="button" class="button button-small" style="margin: 0 4px;">🔍 Zoom +</button>',
        );
        const $zoomOut = $(
          '<button type="button" class="button button-small" style="margin: 0 4px;">🔍 Zoom -</button>',
        );
        const $reset = $(
          '<button type="button" class="button button-small" style="margin: 0 4px;">↻ Reset</button>',
        );

        $controls.append($zoomIn, $zoomOut, $reset);
        $canvas.after($controls);

        // Funcionalidade dos botões (placeholder)
        $zoomIn.on("click", function () {
          $canvas.css("transform", "scale(1.1)");
        });
        $zoomOut.on("click", function () {
          $canvas.css("transform", "scale(0.9)");
        });
        $reset.on("click", function () {
          $canvas.css("transform", "");
        });
      }
    });
  }

  // ================================================================
  // ATALHOS DE TECLADO
  // ================================================================

  function addKeyboardShortcuts() {
    $(document).on("keydown", function (e) {
      // Ctrl/Cmd + S para salvar
      if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
        e.preventDefault();
        $("#publish").click();
      }

      // Esc para fechar tooltips
      if (e.keyCode === 27) {
        $(".pab-tooltip, .pab-copy-tooltip").fadeOut(200, function () {
          $(this).remove();
        });
      }
    });
  }

  // ================================================================
  // VALIDAÇÃO DE FORMULÁRIO AVANÇADA
  // ================================================================

  function validateBioimpedanceData() {
    let isValid = true;
    const errors = [];

    // Validar peso
    const peso = parseFloat($('input[name="pab_bi_peso"]').val());
    if (peso && (peso < 20 || peso > 300)) {
      errors.push("Peso deve estar entre 20kg e 300kg");
      isValid = false;
    }

    // Validar gordura corporal
    const gc = parseFloat($('input[name="pab_bi_gordura_corporal"]').val());
    if (gc && (gc < 1 || gc > 70)) {
      errors.push("Gordura corporal deve estar entre 1% e 70%");
      isValid = false;
    }

    // Validar músculo esquelético
    const me = parseFloat($('input[name="pab_bi_musculo_esq"]').val());
    if (me && (me < 10 || me > 70)) {
      errors.push("Músculo esquelético deve estar entre 10% e 70%");
      isValid = false;
    }

    // Validar gordura visceral
    const gv = parseFloat($('input[name="pab_bi_gordura_visc"]').val());
    if (gv && (gv < 1 || gv > 59)) {
      errors.push("Gordura visceral deve estar entre 1 e 59");
      isValid = false;
    }

    if (!isValid) {
      showValidationErrors(errors);
    }

    return isValid;
  }

  function showValidationErrors(errors) {
    const $errorContainer = $('<div class="pab-validation-errors"></div>');
    $errorContainer.css({
      position: "fixed",
      top: "20px",
      right: "20px",
      background: "#fecaca",
      border: "1px solid #dc2626",
      borderRadius: "8px",
      padding: "16px",
      maxWidth: "300px",
      zIndex: 10000,
      boxShadow: "0 4px 6px -1px rgba(0, 0, 0, 0.1)",
    });

    let errorHtml =
      '<h4 style="margin: 0 0 8px 0; color: #b91c1c;">⚠️ Erros de Validação</h4>';
    errors.forEach(function (error) {
      errorHtml +=
        '<p style="margin: 4px 0; font-size: 13px; color: #7f1d1d;">• ' +
        error +
        "</p>";
    });

    const $closeBtn = $(
      '<button type="button" style="position: absolute; top: 8px; right: 8px; background: none; border: none; font-size: 16px; cursor: pointer; color: #b91c1c;">×</button>',
    );

    $errorContainer.html(errorHtml).append($closeBtn);
    $("body").append($errorContainer);

    $closeBtn.on("click", function () {
      $errorContainer.fadeOut(300, function () {
        $(this).remove();
      });
    });

    setTimeout(function () {
      $errorContainer.fadeOut(300, function () {
        $(this).remove();
      });
    }, 8000);
  }

  // ================================================================
  // INICIALIZAÇÃO E EVENT HANDLERS
  // ================================================================

  // Inicializar na carga da página
  $(document).ready(function () {
    initializeEnhancements();
    toggleBoxes();
  });

  // Validar antes de enviar formulário
  $("#post").on("submit", function (e) {
    if ($("body").hasClass("post-type-pab_bioimpedancia")) {
      if (!validateBioimpedanceData()) {
        e.preventDefault();
        return false;
      }
    }
  });

  // Re-inicializar após mudanças AJAX
  $(document).on("ajaxComplete", function () {
    setTimeout(initializeEnhancements, 500);
  });

  // Handler para mudanças de toggle
  $(document).on("change", ".pab-toggle", toggleBoxes);

  // Auto-save draft periodicamente (se habilitado)
  if (typeof autosave !== "undefined") {
    setInterval(function () {
      if (
        $(".pab-grid input").filter(function () {
          return $(this).val() !== "";
        }).length > 0
      ) {
        // Trigger autosave se houver dados
        $(document).trigger("heartbeat-send.autosave");
      }
    }, 30000); // A cada 30 segundos
  }
});

// ================================================================
// ESTILOS CSS DINÂMICOS
// ================================================================

// Adicionar estilos dinâmicos que complementam o CSS
jQuery(function ($) {
  if ($("#pab-dynamic-styles").length === 0) {
    const dynamicStyles = `
            <style id="pab-dynamic-styles">
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .pab-validation-icon {
                    animation: fadeIn 0.3s ease;
                }

                .pab-avatar {
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                }

                .pab-loading-overlay {
                    animation: fadeIn 0.3s ease;
                }

                .button:active {
                    transform: translateY(1px);
                }

                .postbox:hover {
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
                    transition: box-shadow 0.3s ease;
                }
            </style>
        `;
    $("head").append(dynamicStyles);
  }

  // ================================================================
  // CPTs PAB - EDITOR E TÍTULO REMOVIDOS VIA CSS
  // ================================================================
  // Editor e título são escondidos via CSS no includes/assets.php
  // Não há necessidade de manipulação JavaScript adicional
});