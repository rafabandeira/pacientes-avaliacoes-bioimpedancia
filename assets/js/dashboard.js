jQuery(document).ready(function($) {
    // Cores personalizadas para os gráficos
    const colors = {
        blue: '#2271b1',
        green: '#00a32a',
        red: '#d63638',
        orange: '#dba617',
        purple: '#8c5e88',
        gray: '#646970'
    };

    // Gráfico de Distribuição de IMC
    if (document.getElementById('pab-chart-imc')) {
        const imcData = window.pabDashboard.imcData;
        new Chart(document.getElementById('pab-chart-imc'), {
            type: 'pie',
            data: {
                labels: imcData.map(item => item.categoria_imc),
                datasets: [{
                    data: imcData.map(item => item.total),
                    backgroundColor: [colors.green, colors.blue, colors.orange, colors.red]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    // Gráfico de Avaliações Mensais
    if (document.getElementById('pab-chart-avaliacoes')) {
        const avaliacoesData = window.pabDashboard.avaliacoesData;
        new Chart(document.getElementById('pab-chart-avaliacoes'), {
            type: 'line',
            data: {
                labels: avaliacoesData.map(item => {
                    const [year, month] = item.mes.split('-');
                    return `${month}/${year}`;
                }),
                datasets: [{
                    label: 'Total de Avaliações',
                    data: avaliacoesData.map(item => item.total),
                    borderColor: colors.blue,
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Distribuição por Gênero
    if (document.getElementById('pab-chart-genero')) {
        const generoData = window.pabDashboard.generoData;
        new Chart(document.getElementById('pab-chart-genero'), {
            type: 'doughnut',
            data: {
                labels: generoData.map(item => item.genero || 'Não informado'),
                datasets: [{
                    data: generoData.map(item => item.total),
                    backgroundColor: [colors.purple, colors.blue, colors.gray]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
});