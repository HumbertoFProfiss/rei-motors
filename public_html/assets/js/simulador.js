document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formSimulador');
    const resultado = document.getElementById('resultadoSimulador');

    if (!form || !resultado) return;

    // Taxa de juros mensal aproximada para financiamento de veículos (CDC)
    const TAXA_JUROS_MENSAL = 0.0149; // 1,49% ao mês

    function calcularParcela(valorFinanciado, prazo) {
        // Fórmula Price (sistema francês de amortização)
        const taxa = TAXA_JUROS_MENSAL;
        return valorFinanciado * (taxa * Math.pow(1 + taxa, prazo)) / (Math.pow(1 + taxa, prazo) - 1);
    }

    function formatarMoeda(valor) {
        return valor.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function animar(elementId, valor) {
        const el = document.getElementById(elementId);
        if (!el) return;

        const inicio = 0;
        const duracao = 800;
        const inicioTempo = performance.now();

        function step(tempo) {
            const progresso = Math.min((tempo - inicioTempo) / duracao, 1);
            const easing = 1 - Math.pow(1 - progresso, 3); // ease-out cubic
            el.textContent = formatarMoeda(inicio + (valor - inicio) * easing);
            if (progresso < 1) requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
    }

    let dadosSimulacao = {};

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const valorCarro = parseFloat(document.getElementById('valor').value);
        const valorEntrada = parseFloat(document.getElementById('entrada').value) || 0;
        const prazo = parseInt(document.getElementById('prazo').value);

        if (!valorCarro || valorCarro <= 0) {
            document.getElementById('valor').focus();
            return;
        }

        const valorFinanciado = valorCarro - valorEntrada;
        const valorParcela = calcularParcela(valorFinanciado, prazo);

        dadosSimulacao = {
            valor: formatarMoeda(valorCarro),
            entrada: formatarMoeda(valorEntrada),
            financiado: formatarMoeda(valorFinanciado),
            parcela: formatarMoeda(valorParcela),
            prazo: prazo + 'x'
        };

        // Exibir resultado
        resultado.style.display = 'block';
        resultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        animar('resultadoEntrada', valorEntrada);
        animar('resultadoFinanciado', valorFinanciado);
        animar('resultadoParcela', valorParcela);

        // Mostrar mini-form de captura se ainda não foi enviado
        const captura = document.getElementById('simCaptura');
        if (captura) captura.style.display = '';
    });

    // Mini-form de captura de lead
    const formCaptura = document.getElementById('formSimCaptura');
    if (formCaptura) {
        formCaptura.addEventListener('submit', function (e) {
            e.preventDefault();
            const nome     = this.querySelector('[name="sim_nome"]').value.trim();
            const telefone = this.querySelector('[name="sim_telefone"]').value.trim();
            if (!nome || !telefone) return;

            const obs = 'Simulação: ' + Object.entries(dadosSimulacao).map(([k, v]) => k + '=' + v).join(' | ');

            fetch('api/lead.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ nome, telefone, origem: 'simulador', observacoes: obs })
            }).then(function () {
                document.getElementById('simCaptura').innerHTML = '<p style="color:#D4AF37;text-align:center;font-weight:600">✓ Obrigado! Entraremos em contato.</p>';
            }).catch(function () {});
        });
    }

    // Formatar campo de valor ao digitar
    const campoValor = document.getElementById('valor');
    if (campoValor) {
        campoValor.addEventListener('input', function () {
            const val = parseFloat(this.value);
            if (val < 0) this.value = 0;
        });
    }
});
