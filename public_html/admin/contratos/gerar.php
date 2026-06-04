<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/functions.php';

requerAutenticacao();

$tipo     = sanitizar($_GET['tipo']      ?? '');
$venda_id = (int)($_GET['venda_id']     ?? 0);
$vei_id   = (int)($_GET['veiculo_id']   ?? 0);

$tipos_validos = ['compra_venda', 'consignacao', 'procuracao'];
if (!in_array($tipo, $tipos_validos, true)) {
    die('Tipo de contrato inválido.');
}

// ── Compra e Venda / Procuração ───────────────────────────────────────────
$venda = $veiculo = $cliente = $vendedor = $troca = null;
if ($venda_id > 0) {
    $venda = obterUmaLinha(
        "SELECT ve.*,
                v.marca, v.modelo, v.ano, v.cor, v.quilometragem, v.combustivel,
                v.cambio, v.numero_chassi, v.placa, v.renavam, v.tipo_propriedade,
                v.consignado_proprietario_nome, v.consignado_proprietario_telefone,
                c.nome AS cliente_nome, c.cpf AS cliente_cpf, c.rg AS cliente_rg,
                c.endereco AS cliente_endereco, c.numero AS cliente_numero,
                c.bairro AS cliente_bairro, c.cidade AS cliente_cidade,
                c.estado AS cliente_estado, c.cep AS cliente_cep,
                c.telefone AS cliente_telefone, c.profissao AS cliente_profissao,
                u.nome AS vendedor_nome
         FROM vendas ve
         JOIN veiculos v ON v.id = ve.veiculo_id
         JOIN clientes c ON c.id = ve.cliente_id
         JOIN usuarios u ON u.id = ve.vendedor_id
         WHERE ve.id = ?",
        [$venda_id]
    );
    if (!$venda) die('Venda não encontrada.');
    $troca = obterUmaLinha("SELECT * FROM carros_troca WHERE venda_id = ?", [$venda_id]);
}

// ── Consignação ────────────────────────────────────────────────────────────
if ($tipo === 'consignacao' && $vei_id > 0) {
    $veiculo = obterUmaLinha("SELECT * FROM veiculos WHERE id = ?", [$vei_id]);
    if (!$veiculo) die('Veículo não encontrado.');
} elseif ($tipo === 'consignacao' && $venda) {
    $veiculo = obterUmaLinha("SELECT * FROM veiculos WHERE id = ?", [$venda['veiculo_id']]);
}

$loja_nome     = LOJA_NOME;
$loja_endereco = LOJA_ENDERECO;
$loja_cnpj     = '00.000.000/0001-00'; // substitua quando tiver CNPJ real
$hoje          = date('d/m/Y');

// ── Helpers ────────────────────────────────────────────────────────────────
function campo($v) {
    return $v ? htmlspecialchars($v) : '______________________________';
}
function campoMoeda($v) {
    return $v ? 'R$ ' . number_format((float)$v, 2, ',', '.') : 'R$ ____________';
}
function formatData($d) {
    if (!$d) return '__/__/____';
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : $d;
}
function extenso($v) {
    // conversão simples de reais por extenso (sem centavos)
    $v = (int)round((float)$v);
    if ($v <= 0) return '_______________';
    $unidades   = ['','um','dois','três','quatro','cinco','seis','sete','oito','nove','dez',
                   'onze','doze','treze','quatorze','quinze','dezesseis','dezessete','dezoito','dezenove'];
    $dezenas    = ['','','vinte','trinta','quarenta','cinquenta','sessenta','setenta','oitenta','noventa'];
    $centenas   = ['','cento','duzentos','trezentos','quatrocentos','quinhentos','seiscentos',
                   'setecentos','oitocentos','novecentos'];
    $numToStr = function($n) use ($unidades, $dezenas, $centenas, &$numToStr) {
        if ($n === 0) return 'zero';
        if ($n < 0)   return 'menos '.$numToStr(-$n);
        $str = '';
        if ($n >= 1000000) {
            $m = (int)($n / 1000000);
            $str .= $numToStr($m) . ($m === 1 ? ' milhão' : ' milhões');
            $n %= 1000000;
            if ($n > 0) $str .= ($n < 100 ? ' e ' : ', ');
        }
        if ($n >= 1000) {
            $k = (int)($n / 1000);
            $str .= ($k === 1 ? 'mil' : $numToStr($k) . ' mil');
            $n %= 1000;
            if ($n > 0) $str .= ($n < 100 ? ' e ' : ', ');
        }
        if ($n >= 100) {
            $c = (int)($n / 100);
            $str .= ($n === 100 ? 'cem' : $centenas[$c]);
            $n %= 100;
            if ($n > 0) $str .= ' e ';
        }
        if ($n > 0) {
            if ($n < 20) {
                $str .= $unidades[$n];
            } else {
                $d = (int)($n / 10);
                $u = $n % 10;
                $str .= $dezenas[$d];
                if ($u > 0) $str .= ' e ' . $unidades[$u];
            }
        }
        return $str;
    };
    return ucfirst($numToStr($v)) . ' reais';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Contrato — <?= ucwords(str_replace('_', ' ', $tipo)) ?></title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 12pt;
      color: #000;
      background: #fff;
      padding: 20px;
  }
  .page {
      max-width: 800px;
      margin: 0 auto;
      padding: 40px;
      border: 1px solid #ccc;
  }
  h1 { font-size: 15pt; text-align: center; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; }
  h2 { font-size: 11pt; text-transform: uppercase; margin: 22px 0 6px; border-bottom: 1px solid #000; padding-bottom: 3px; }
  p { margin-bottom: 8px; line-height: 1.7; text-align: justify; }
  .sub { font-size: 10pt; text-align: center; margin-bottom: 20px; color: #333; }
  .logo-area { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #000; padding-bottom: 16px; }
  .logo-area .nome-loja { font-size: 20pt; font-weight: bold; letter-spacing: 3px; }
  .logo-area .cnpj { font-size: 9pt; color: #555; }
  table.dados { width: 100%; border-collapse: collapse; margin: 10px 0; }
  table.dados td { padding: 4px 8px; border: 1px solid #999; font-size: 11pt; vertical-align: top; }
  table.dados td.label { background: #f5f5f5; font-weight: bold; width: 35%; }
  .assinaturas { margin-top: 60px; display: flex; gap: 40px; }
  .assinatura { flex: 1; text-align: center; }
  .assinatura .linha { border-top: 1px solid #000; padding-top: 6px; margin-top: 50px; font-size: 10pt; }
  .valor-destaque { font-size: 13pt; font-weight: bold; }
  .clausulas { counter-reset: clausula; }
  .clausula { margin-bottom: 12px; }
  .clausula::before { counter-increment: clausula; content: "Cláusula " counter(clausula, upper-roman) " — "; font-weight: bold; }
  .rodape { margin-top: 30px; font-size: 9pt; color: #555; text-align: center; border-top: 1px solid #ccc; padding-top: 10px; }
  .btn-print {
      display: block; margin: 0 auto 20px; padding: 10px 30px;
      background: #D4AF37; color: #000; border: none; font-size: 13pt;
      cursor: pointer; border-radius: 6px; font-weight: bold;
  }
  @media print {
      .btn-print { display: none; }
      body { padding: 0; }
      .page { border: none; padding: 20px; }
  }
</style>
</head>
<body>
<button class="btn-print" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>

<div class="page">

<?php /* =========================================================
 * CONTRATO DE COMPRA E VENDA
 * ========================================================= */
if ($tipo === 'compra_venda' && $venda): ?>

<div class="logo-area">
    <div class="nome-loja"><?= htmlspecialchars($loja_nome) ?></div>
    <div class="cnpj">CNPJ: <?= $loja_cnpj ?> &nbsp;|&nbsp; <?= htmlspecialchars($loja_endereco) ?></div>
</div>

<h1>Contrato Particular de Compra e Venda de Veículo</h1>
<p class="sub">Nº <?= campo($venda['numero_contrato'] ?: 'REI-'.$venda_id.'-'.date('Y')) ?> &nbsp;|&nbsp; <?= $hoje ?></p>

<h2>1. Das Partes</h2>
<table class="dados">
<tr><td class="label">Vendedor (Loja)</td><td><?= htmlspecialchars($loja_nome) ?>, CNPJ <?= $loja_cnpj ?>, com sede em <?= htmlspecialchars($loja_endereco) ?>.</td></tr>
<tr><td class="label">Comprador</td><td><?= campo($venda['cliente_nome']) ?></td></tr>
<tr><td class="label">CPF/RG</td><td>CPF: <?= campo($venda['cliente_cpf'] ? formatarCPF($venda['cliente_cpf']) : null) ?> &nbsp;|&nbsp; RG: <?= campo($venda['cliente_rg'] ?? null) ?></td></tr>
<tr><td class="label">Endereço Comprador</td><td><?= campo($venda['cliente_endereco']) ?><?= $venda['cliente_numero'] ? ', '.$venda['cliente_numero'] : '' ?><?= $venda['cliente_bairro'] ? ' — '.$venda['cliente_bairro'] : '' ?><?= $venda['cliente_cidade'] ? ', '.$venda['cliente_cidade'].'/'.$venda['cliente_estado'] : '' ?></td></tr>
<tr><td class="label">Telefone Comprador</td><td><?= campo($venda['cliente_telefone'] ? formatarTelefone($venda['cliente_telefone']) : null) ?></td></tr>
</table>

<h2>2. Do Veículo</h2>
<table class="dados">
<tr><td class="label">Marca / Modelo</td><td><?= campo($venda['marca'].' '.$venda['modelo']) ?></td></tr>
<tr><td class="label">Ano Fabricação/Modelo</td><td><?= campo($venda['ano']) ?></td></tr>
<tr><td class="label">Cor</td><td><?= campo($venda['cor'] ?? null) ?></td></tr>
<tr><td class="label">Quilometragem</td><td><?= $venda['quilometragem'] ? number_format((int)$venda['quilometragem'], 0, ',', '.') . ' km' : '__________ km' ?></td></tr>
<tr><td class="label">Combustível / Câmbio</td><td><?= campo(ucfirst($venda['combustivel'])) ?> / <?= campo(ucfirst($venda['cambio'])) ?></td></tr>
<tr><td class="label">Chassi</td><td><?= campo($venda['numero_chassi'] ?? null) ?></td></tr>
<tr><td class="label">Placa</td><td><?= campo($venda['placa'] ?? null) ?></td></tr>
<tr><td class="label">RENAVAM</td><td><?= campo($venda['renavam'] ?? null) ?></td></tr>
</table>

<h2>3. Do Preço e Condições</h2>
<table class="dados">
<tr><td class="label">Preço de Venda</td><td><span class="valor-destaque"><?= campoMoeda($venda['preco_venda']) ?></span></td></tr>
<tr><td class="label">Por Extenso</td><td><?= extenso($venda['preco_venda']) ?></td></tr>
<tr><td class="label">Desconto</td><td><?= campoMoeda($venda['desconto_aplicado']) ?></td></tr>
<tr><td class="label">Valor Líquido</td><td><?= campoMoeda((float)$venda['preco_venda'] - (float)$venda['desconto_aplicado']) ?></td></tr>
<tr><td class="label">Forma de Pagamento</td><td><?= campo($formas_pagamento[$venda['forma_pagamento']] ?? $venda['forma_pagamento']) ?></td></tr>
<tr><td class="label">Data da Venda</td><td><?= formatData($venda['data_venda']) ?></td></tr>
<?php if ($venda['data_entrega']): ?>
<tr><td class="label">Previsão de Entrega</td><td><?= formatData($venda['data_entrega']) ?></td></tr>
<?php endif; ?>
<?php if ($troca): ?>
<tr><td class="label">Veículo de Troca</td><td><?= campo($troca['marca'].' '.$troca['modelo'].' '.$troca['ano']) ?> — Avaliado em <?= campoMoeda($troca['valor_estimado']) ?></td></tr>
<?php endif; ?>
</table>

<h2>4. Cláusulas</h2>
<div class="clausulas">
<p class="clausula">O VENDEDOR declara ser o legítimo proprietário do veículo descrito, ou estar devidamente autorizado a vendê-lo, livre e desembaraçado de quaisquer ônus, dívidas, multas ou restrições, excetuando-se as que constarem expressamente neste contrato.</p>
<p class="clausula">O COMPRADOR declara ter vistoriado e aceito o veículo no estado em que se encontra no ato da assinatura deste instrumento, estando ciente das condições de conservação do bem.</p>
<p class="clausula">A transferência da propriedade somente se dará após a compensação integral do pagamento e entrega de toda a documentação exigida por lei. O VENDEDOR se compromete a entregar o documento de transferência devidamente assinado e em condições de ser transferido no DETRAN.</p>
<p class="clausula">O veículo objeto deste contrato possui garantia de <strong><?= (int)($venda['prazo_garantia_dias'] ?? 90) ?> dias</strong> a contar da data de entrega, limitada a defeitos ocultos preexistentes à venda, não cobrindo danos decorrentes de mau uso, acidentes, desgaste natural ou falta de manutenção.</p>
<p class="clausula">As partes elegem o Foro da Comarca de <?= $venda['cliente_cidade'] ? htmlspecialchars($venda['cliente_cidade']) : '____________________' ?> para dirimir eventuais conflitos oriundos deste instrumento, renunciando expressamente a qualquer outro, por mais privilegiado que seja.</p>
</div>

<?php if ($venda['observacoes']): ?>
<h2>5. Observações</h2>
<p><?= nl2br(htmlspecialchars($venda['observacoes'])) ?></p>
<?php endif; ?>

<div class="assinaturas">
    <div class="assinatura">
        <div class="linha">
            <strong><?= htmlspecialchars($loja_nome) ?></strong><br>
            CNPJ: <?= $loja_cnpj ?>
        </div>
    </div>
    <div class="assinatura">
        <div class="linha">
            <strong><?= campo($venda['cliente_nome']) ?></strong><br>
            CPF: <?= campo($venda['cliente_cpf'] ? formatarCPF($venda['cliente_cpf']) : null) ?>
        </div>
    </div>
</div>

<div class="assinaturas" style="margin-top:40px">
    <div class="assinatura">
        <div class="linha">1ª Testemunha — Nome / CPF</div>
    </div>
    <div class="assinatura">
        <div class="linha">2ª Testemunha — Nome / CPF</div>
    </div>
</div>

<div class="rodape">
    <?= htmlspecialchars($loja_nome) ?> &nbsp;|&nbsp; <?= htmlspecialchars($loja_endereco) ?> &nbsp;|&nbsp; Documento gerado em <?= $hoje ?>
</div>

<?php endif; ?>


<?php /* =========================================================
 * CONTRATO DE CONSIGNAÇÃO
 * ========================================================= */
if ($tipo === 'consignacao' && $veiculo): ?>

<div class="logo-area">
    <div class="nome-loja"><?= htmlspecialchars($loja_nome) ?></div>
    <div class="cnpj">CNPJ: <?= $loja_cnpj ?> &nbsp;|&nbsp; <?= htmlspecialchars($loja_endereco) ?></div>
</div>

<h1>Contrato de Consignação de Veículo</h1>
<p class="sub"><?= $hoje ?></p>

<h2>1. Das Partes</h2>
<table class="dados">
<tr><td class="label">Consignatário (Loja)</td><td><?= htmlspecialchars($loja_nome) ?>, CNPJ <?= $loja_cnpj ?>, <?= htmlspecialchars($loja_endereco) ?>.</td></tr>
<tr><td class="label">Consignante (Proprietário)</td><td><?= campo($veiculo['consignado_proprietario_nome'] ?? null) ?></td></tr>
<tr><td class="label">Telefone Proprietário</td><td><?= campo($veiculo['consignado_proprietario_telefone'] ?? null) ?></td></tr>
<tr><td class="label">CPF / RG Proprietário</td><td>______________________________</td></tr>
<tr><td class="label">Endereço Proprietário</td><td>______________________________</td></tr>
</table>

<h2>2. Do Veículo</h2>
<table class="dados">
<tr><td class="label">Marca / Modelo</td><td><?= campo($veiculo['marca'].' '.$veiculo['modelo']) ?></td></tr>
<tr><td class="label">Ano Fabricação/Modelo</td><td><?= campo($veiculo['ano']) ?></td></tr>
<tr><td class="label">Cor</td><td><?= campo($veiculo['cor'] ?? null) ?></td></tr>
<tr><td class="label">Quilometragem</td><td><?= $veiculo['quilometragem'] ? number_format((int)$veiculo['quilometragem'], 0, ',', '.') . ' km' : '__________ km' ?></td></tr>
<tr><td class="label">Chassi</td><td><?= campo($veiculo['numero_chassi'] ?? null) ?></td></tr>
<tr><td class="label">Placa</td><td><?= campo($veiculo['placa'] ?? null) ?></td></tr>
<tr><td class="label">RENAVAM</td><td><?= campo($veiculo['renavam'] ?? null) ?></td></tr>
</table>

<h2>3. Das Condições</h2>
<table class="dados">
<tr><td class="label">Valor Mínimo de Venda</td><td><span class="valor-destaque"><?= campoMoeda($veiculo['consignado_valor_minimo'] ?? null) ?></span></td></tr>
<tr><td class="label">Por Extenso</td><td><?= extenso($veiculo['consignado_valor_minimo'] ?? 0) ?></td></tr>
<tr><td class="label">Comissão da Loja</td><td><?= $veiculo['consignado_percentual'] ? number_format((float)$veiculo['consignado_percentual'], 1) . '%' : '_______%' ?> sobre o valor de venda</td></tr>
<tr><td class="label">Prazo do Contrato</td><td>______ dias a partir de <?= $hoje ?></td></tr>
</table>

<h2>4. Cláusulas</h2>
<div class="clausulas">
<p class="clausula">O CONSIGNANTE entrega o veículo descrito ao CONSIGNATÁRIO para que este promova sua venda, pelo prazo e condições estipulados, recebendo como contraprestação a comissão fixada acima.</p>
<p class="clausula">O CONSIGNATÁRIO se compromete a zelar pelo veículo enquanto estiver sob sua guarda, respondendo por danos decorrentes de negligência comprovada. Não responde por danos decorrentes de furto ou roubo, desde que comprove boletim de ocorrência.</p>
<p class="clausula">Após a venda, o CONSIGNATÁRIO repassará ao CONSIGNANTE o valor líquido (preço de venda menos a comissão) no prazo de até 5 (cinco) dias úteis após o recebimento integral do pagamento do comprador.</p>
<p class="clausula">Findo o prazo sem que a venda se efetue, o CONSIGNANTE poderá retirar o veículo sem qualquer ônus, ou prorrogar o contrato por igual período mediante novo instrumento escrito.</p>
<p class="clausula">As partes elegem o Foro da Comarca de Botucatu/SP para dirimir eventuais conflitos oriundos deste instrumento.</p>
</div>

<div class="assinaturas">
    <div class="assinatura">
        <div class="linha">
            <strong><?= htmlspecialchars($loja_nome) ?></strong><br>
            CNPJ: <?= $loja_cnpj ?>
        </div>
    </div>
    <div class="assinatura">
        <div class="linha">
            <strong><?= campo($veiculo['consignado_proprietario_nome'] ?? null) ?></strong><br>
            CPF / RG: ______________________________
        </div>
    </div>
</div>

<div class="assinaturas" style="margin-top:40px">
    <div class="assinatura"><div class="linha">1ª Testemunha — Nome / CPF</div></div>
    <div class="assinatura"><div class="linha">2ª Testemunha — Nome / CPF</div></div>
</div>

<div class="rodape">
    <?= htmlspecialchars($loja_nome) ?> &nbsp;|&nbsp; <?= htmlspecialchars($loja_endereco) ?> &nbsp;|&nbsp; Documento gerado em <?= $hoje ?>
</div>

<?php endif; ?>


<?php /* =========================================================
 * PROCURAÇÃO AD NEGOTIA
 * ========================================================= */
if ($tipo === 'procuracao' && $venda): ?>

<div class="logo-area">
    <div class="nome-loja"><?= htmlspecialchars($loja_nome) ?></div>
    <div class="cnpj">CNPJ: <?= $loja_cnpj ?> &nbsp;|&nbsp; <?= htmlspecialchars($loja_endereco) ?></div>
</div>

<h1>Procuração Ad Negotia</h1>
<p class="sub">Transferência de Veículo — <?= $hoje ?></p>

<p>
Pelo presente instrumento particular, <strong><?= campo($venda['cliente_nome']) ?></strong>,
<?= $venda['cliente_profissao'] ? htmlspecialchars($venda['cliente_profissao']) . ',' : '' ?>
portador(a) do CPF nº <?= campo($venda['cliente_cpf'] ? formatarCPF($venda['cliente_cpf']) : null) ?>,
RG nº <?= campo($venda['cliente_rg'] ?? null) ?>,
residente e domiciliado(a) em
<?= campo($venda['cliente_endereco']) ?><?= $venda['cliente_numero'] ? ', nº '.$venda['cliente_numero'] : '' ?>,
<?= $venda['cliente_bairro'] ? htmlspecialchars($venda['cliente_bairro']).',' : '' ?>
<?= $venda['cliente_cidade'] ? htmlspecialchars($venda['cliente_cidade']).'/' : '' ?><?= campo($venda['cliente_estado'] ?? null) ?>,
doravante denominado(a) <strong>OUTORGANTE</strong>, nomeia e constitui como seu bastante procurador:
</p>

<p>
<strong>Nome do Procurador:</strong> _______________________________________________<br>
<strong>CPF/RG do Procurador:</strong> ________________________________________________<br>
<strong>Endereço do Procurador:</strong> _____________________________________________
</p>

<p>
ao(à) qual confere amplos poderes para, em seu nome e representação, praticar todos os atos
necessários à <strong>transferência</strong> do veículo abaixo descrito junto ao DETRAN e demais
órgãos competentes, incluindo assinar requerimentos, declarações e demais documentos que se fizerem
necessários.
</p>

<h2>Do Veículo</h2>
<table class="dados">
<tr><td class="label">Marca / Modelo</td><td><?= campo($venda['marca'].' '.$venda['modelo']) ?></td></tr>
<tr><td class="label">Ano Fabricação/Modelo</td><td><?= campo($venda['ano']) ?></td></tr>
<tr><td class="label">Cor</td><td><?= campo($venda['cor'] ?? null) ?></td></tr>
<tr><td class="label">Chassi</td><td><?= campo($venda['numero_chassi'] ?? null) ?></td></tr>
<tr><td class="label">Placa</td><td><?= campo($venda['placa'] ?? null) ?></td></tr>
<tr><td class="label">RENAVAM</td><td><?= campo($venda['renavam'] ?? null) ?></td></tr>
</table>

<p style="margin-top:16px">
A presente procuração é válida por <strong>90 (noventa) dias</strong> a contar da data de sua
assinatura, com poderes gerais para o ato acima especificado, vedada a substabelecimento.
</p>

<div class="assinaturas">
    <div class="assinatura">
        <div class="linha">
            <strong><?= campo($venda['cliente_nome']) ?></strong><br>
            CPF: <?= campo($venda['cliente_cpf'] ? formatarCPF($venda['cliente_cpf']) : null) ?><br>
            <em>Outorgante</em>
        </div>
    </div>
    <div class="assinatura">
        <div class="linha">
            Procurador(a)<br>
            CPF: ______________________________<br>
            <em>Outorgado(a)</em>
        </div>
    </div>
</div>

<div class="assinaturas" style="margin-top:40px">
    <div class="assinatura"><div class="linha">1ª Testemunha — Nome / CPF</div></div>
    <div class="assinatura"><div class="linha">2ª Testemunha — Nome / CPF</div></div>
</div>

<p style="margin-top:30px;font-size:10pt;color:#555;text-align:center">
    Local e data: <?= $loja_nome ?>, <?= $hoje ?><br>
    Reconhecer firma em cartório se necessário.
</p>

<div class="rodape">
    <?= htmlspecialchars($loja_nome) ?> &nbsp;|&nbsp; <?= htmlspecialchars($loja_endereco) ?> &nbsp;|&nbsp; Documento gerado em <?= $hoje ?>
</div>

<?php endif; ?>

</div><!-- /page -->
</body>
</html>
