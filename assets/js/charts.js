// assets/js/charts.js
(function(){
    if (!window.PAB_CHART_DATA) return;
    const d = window.PAB_CHART_DATA;

    // Peso corporal (linha) + referência (linha) + área verde entre
    const ctxPeso = document.getElementById('pabChartPeso');
    if (ctxPeso) {
        new Chart(ctxPeso, {
            type: 'line',
            data: {
                labels: d.datas,
                datasets: [
                    { label:'Peso', data:d.pesos, borderColor:'red', backgroundColor:'transparent' },
                    { label:'Ref.', data:d.refPeso, borderColor:'green', backgroundColor:'rgba(0,128,0,0.08)', fill: '+1' }
                ]
            },
            options: { responsive:true }
        });
    }

    // Bi-compartimental (pizza): massa gorda e massa magra do último registro
    const ctxBi = document.getElementById('pabChartBiComp');
    if (ctxBi && d.gorduras.length && d.musculos.length) {
        const lastG = d.gorduras[d.gorduras.length-1];
        const lastM = d.musculos[d.musculos.length-1];
        const massaMagra = Math.max(0, 100 - lastG); // simplificação
        new Chart(ctxBi, {
            type: 'pie',
            data: { labels:['Massa gorda','Massa magra'], datasets:[{ data:[lastG, massaMagra], backgroundColor:['#f66','#66f'] }] },
            options: { responsive:true }
        });
    }

    // Composição corporal (barras gordura + linha músculo)
    const ctxComp = document.getElementById('pabChartCompLineBar');
    if (ctxComp) {
        new Chart(ctxComp, {
            data: {
                labels: d.datas,
                datasets: [
                    { type:'bar', label:'Gordura (%)', data:d.gorduras, backgroundColor:'#f66' },
                    { type:'line', label:'Músculo (%)', data:d.musculos, borderColor:'#66f' }
                ]
            },
            options: { responsive:true }
        });
    }

    // Idade corporal (barras): idade real vs idade corporal ao longo do tempo
    const ctxIdade = document.getElementById('pabChartIdadeCorporal');
    if (ctxIdade) {
        new Chart(ctxIdade, {
            type: 'bar',
            data: {
                labels: d.datas,
                datasets: [
                    { label:'Idade real', data: d.datas.map(()=>d.idadeReal), backgroundColor:'rgba(0,200,0,0.6)' },
                    { label:'Idade corporal', data: d.idadesCorp, backgroundColor:'rgba(200,0,0,0.6)' },
                ]
            },
            options: { responsive:true }
        });
    }
})();
