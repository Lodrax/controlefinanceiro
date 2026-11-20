<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../conexao.php";

$resultado = mysqli_query(
    $conexao,
    "SELECT num_doc, descricao, data_vencimento, valor FROM tb_contas WHERE status = 'D' ORDER BY data_vencimento ASC, num_doc ASC"
);

if (!$resultado) {
    error_log("Erro ao consultar contas pendentes.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar as contas pendentes.");
}

$contas = [];
$valorTotal = 0.0;
$contasVencidas = 0;
$hoje = date("Y-m-d");

while ($conta = mysqli_fetch_assoc($resultado)) {
    $contas[] = $conta;
    $valorTotal += (float) ($conta["valor"] ?? 0);

    $dataVencimento = $conta["data_vencimento"] ?? null;

    if (
        is_string($dataVencimento) &&
        $dataVencimento !== "" &&
        $dataVencimento !== "0000-00-00" &&
        $dataVencimento < $hoje
    ) {
        $contasVencidas++;
    }
}

$formatarData = static function (?string $data): string {
    if ($data === null || $data === "" || $data === "0000-00-00") {
        return "—";
    }

    $timestamp = strtotime($data);

    return $timestamp === false ? "—" : date("d/m/Y", $timestamp);
};

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar Conta</title>
    <link
        rel="stylesheet"
        href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>"
    >
</head>

<body>
    <main class="container pagina-contas-listagem">
        <a href="painel.php" class="voltar" aria-label="Voltar às opções de contas">←</a>
        <a href="../index.html" class="inicio" aria-label="Ir para a página inicial" title="Página inicial">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m3 10 9-7 9 7"></path>
                <path d="M5 9v12h14V9"></path>
                <path d="M9 21v-7h6v7"></path>
            </svg>
        </a>

        <section class="cartao cartao-contas">
            <div class="identificacao">
                <span class="linha-dourada"></span>
                <p>SISTEMA FINANCEIRO</p>
            </div>

            <h1>Pagar <span>Conta</span></h1>
            <p class="descricao">
                Confira as contas pendentes e confirme o pagamento para atualizar o status e registrar a data de hoje.
            </p>

            <?php if (isset($_GET["sucesso"])): ?>
                <div class="mensagem sucesso" role="status">
                    Pagamento registrado com sucesso.
                </div>
            <?php elseif (isset($_GET["erro"]) && $_GET["erro"] === "indisponivel"): ?>
                <div class="mensagem erro" role="alert">
                    Essa conta não está mais devendo. Atualize a lista e tente novamente.
                </div>
            <?php elseif (isset($_GET["erro"])): ?>
                <div class="mensagem erro" role="alert">
                    Não foi possível registrar o pagamento. Tente novamente.
                </div>
            <?php endif; ?>

            <section class="resumo-contas" aria-label="Resumo das contas pendentes">
                <div class="indicador-contas">
                    <span class="indicador-rotulo">Contas pendentes</span>
                    <strong><?= count($contas) ?></strong>
                </div>
                <div class="indicador-contas">
                    <span class="indicador-rotulo">Total em aberto</span>
                    <strong>R$ <?= number_format($valorTotal, 2, ",", ".") ?></strong>
                </div>
                <div class="indicador-contas indicador-vencidas">
                    <span class="indicador-rotulo">Contas vencidas</span>
                    <strong><?= $contasVencidas ?></strong>
                </div>
            </section>

            <h2 class="titulo-secao">Contas para pagar</h2>

            <div class="tabela-contas tabela-mobile">
                <table>
                    <thead>
                        <tr>
                            <th>Nº documento</th>
                            <th>Descrição</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contas) > 0): ?>
                            <?php foreach ($contas as $conta): ?>
                                <?php
                                $dataVencimento = $conta["data_vencimento"] ?? null;
                                $vencida = is_string($dataVencimento) &&
                                    $dataVencimento !== "" &&
                                    $dataVencimento !== "0000-00-00" &&
                                    $dataVencimento < $hoje;
                                ?>
                                <tr>
                                    <td data-label="Nº documento"><?= htmlspecialchars((string) $conta["num_doc"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td data-label="Descrição"><?= htmlspecialchars((string) ($conta["descricao"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                    <td data-label="Vencimento">
                                        <span class="<?= $vencida ? "vencimento-atrasado" : "" ?>">
                                            <?= htmlspecialchars($formatarData($dataVencimento), ENT_QUOTES, "UTF-8") ?>
                                        </span>
                                    </td>
                                    <td data-label="Valor">
                                        <?php if ($conta["valor"] !== null): ?>
                                            R$ <?= number_format((float) $conta["valor"], 2, ",", ".") ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Status">
                                        <?php if ($vencida): ?>
                                            <span class="status-conta status-vencida">Vencida (D)</span>
                                        <?php else: ?>
                                            <span class="status-conta status-aberta">Devendo (D)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Ação">
                                        <form
                                            action="confirmar_pagamento.php"
                                            method="POST"
                                            autocomplete="off"
                                        >
                                            <input
                                                type="hidden"
                                                name="token_csrf"
                                                value="<?= htmlspecialchars(
                                                    $_SESSION["csrf_contas"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="num_doc"
                                                value="<?= htmlspecialchars(
                                                    (string) $conta["num_doc"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                            >
                                            <button
                                                type="submit"
                                                class="botao-marcar-pago"
                                                data-confirmar-pagamento
                                                data-num-doc="<?= htmlspecialchars((string) $conta["num_doc"], ENT_QUOTES, "UTF-8") ?>"
                                                data-descricao="<?= htmlspecialchars((string) ($conta["descricao"] ?? "—"), ENT_QUOTES, "UTF-8") ?>"
                                                data-valor="<?= $conta["valor"] !== null ? "R$ " . number_format((float) $conta["valor"], 2, ",", ".") : "—" ?>"
                                            >
                                                Confirmar pagamento
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="sem-contas">
                                    <span class="icone-sem-contas" aria-hidden="true">✓</span>
                                    <strong>Tudo em dia!</strong>
                                    <span>Não há contas pendentes para pagar.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <dialog class="modal-pagamento" id="modalPagamento" aria-labelledby="tituloModalPagamento">
            <div class="modal-pagamento-conteudo">
                <span class="modal-pagamento-icone" aria-hidden="true">R$</span>
                <p class="modal-pagamento-etiqueta">CONFIRMAÇÃO DE PAGAMENTO</p>
                <h2 id="tituloModalPagamento">Confirmar pagamento?</h2>
                <p class="modal-pagamento-descricao">
                    Confira os dados da conta antes de registrar o pagamento.
                </p>
                <dl class="modal-pagamento-dados">
                    <div>
                        <dt>Nº documento</dt>
                        <dd id="modalNumeroDocumento"></dd>
                    </div>
                    <div>
                        <dt>Descrição</dt>
                        <dd id="modalDescricao"></dd>
                    </div>
                    <div>
                        <dt>Valor</dt>
                        <dd id="modalValor"></dd>
                    </div>
                </dl>
                <div class="modal-pagamento-acoes">
                    <button type="button" class="modal-pagamento-cancelar" id="cancelarPagamento">
                        Voltar
                    </button>
                    <button type="button" class="modal-pagamento-confirmar" id="confirmarPagamento">
                        Sim, confirmar
                    </button>
                </div>
            </div>
        </dialog>

        <footer>Sistema Financeiro &copy; 2026</footer>
    </main>
    <script src="../js/pagamento.js" defer></script>
</body>

</html>
<?php

mysqli_free_result($resultado);
mysqli_close($conexao);
