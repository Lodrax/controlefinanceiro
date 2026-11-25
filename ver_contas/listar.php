<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../conexao.php";

$resultadoFornecedores = mysqli_query(
    $conexao,
    "SELECT codigo, nome FROM tb_fornecedor ORDER BY nome ASC"
);

if (!$resultadoFornecedores) {
    error_log("Erro ao consultar fornecedores para os relatórios de contas.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar os fornecedores.");
}

$fornecedores = [];
$codigosFornecedores = [];

while ($fornecedor = mysqli_fetch_assoc($resultadoFornecedores)) {
    $fornecedores[] = $fornecedor;
    $codigosFornecedores[(string) $fornecedor["codigo"]] = true;
}

mysqli_free_result($resultadoFornecedores);

$errosFiltros = [];

$lerDataFiltro = static function (string $campo, string $rotulo) use (&$errosFiltros): ?string {
    $valor = $_GET[$campo] ?? "";

    if (!is_string($valor)) {
        $errosFiltros[$campo] = "Informe uma data válida para {$rotulo}.";
        return null;
    }

    if ($valor === "") {
        return null;
    }

    if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $valor) !== 1) {
        $errosFiltros[$campo] = "Informe uma data válida para {$rotulo}.";
        return null;
    }

    [$ano, $mes, $dia] = array_map("intval", explode("-", $valor));

    if (!checkdate($mes, $dia, $ano)) {
        $errosFiltros[$campo] = "Informe uma data válida para {$rotulo}.";
        return null;
    }

    return $valor;
};

$lerFornecedorFiltro = static function (string $campo, string $rotulo) use (
    &$errosFiltros,
    $codigosFornecedores
): ?string {
    $valor = $_GET[$campo] ?? "";

    if (!is_string($valor)) {
        $errosFiltros[$campo] = "Selecione um fornecedor válido para {$rotulo}.";
        return null;
    }

    if ($valor === "") {
        return null;
    }

    if ($valor === "sem") {
        return "sem";
    }

    $codigo = filter_var(
        $valor,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 2147483647]]
    );

    if ($codigo === false || !isset($codigosFornecedores[(string) $codigo])) {
        $errosFiltros[$campo] = "O fornecedor selecionado para {$rotulo} não existe.";
        return null;
    }

    return (string) $codigo;
};

$dataPagarInicio = $lerDataFiltro("pagar_de", "contas a pagar");
$dataPagarFim = $lerDataFiltro("pagar_ate", "contas a pagar");
$dataPagaInicio = $lerDataFiltro("paga_de", "contas pagas");
$dataPagaFim = $lerDataFiltro("paga_ate", "contas pagas");
$fornecedorPagar = $lerFornecedorFiltro("fornecedor_pagar", "contas a pagar");
$fornecedorPaga = $lerFornecedorFiltro("fornecedor_paga", "contas pagas");

if ($dataPagarInicio !== null && $dataPagarFim !== null && $dataPagarInicio > $dataPagarFim) {
    $errosFiltros["periodo_pagar"] = "O início do período das contas a pagar deve ser anterior ao fim.";
}

if ($dataPagaInicio !== null && $dataPagaFim !== null && $dataPagaInicio > $dataPagaFim) {
    $errosFiltros["periodo_paga"] = "O início do período das contas pagas deve ser anterior ao fim.";
}

$erroPeriodoPagar =
    isset($errosFiltros["pagar_de"]) ||
    isset($errosFiltros["pagar_ate"]) ||
    isset($errosFiltros["periodo_pagar"]);
$erroPeriodoPaga =
    isset($errosFiltros["paga_de"]) ||
    isset($errosFiltros["paga_ate"]) ||
    isset($errosFiltros["periodo_paga"]);
$erroFornecedorPagar = isset($errosFiltros["fornecedor_pagar"]);
$erroFornecedorPaga = isset($errosFiltros["fornecedor_paga"]);

$resultado = mysqli_query(
    $conexao,
    "SELECT c.num_doc, c.codigo, f.nome AS nome_fornecedor, c.descricao, c.data_vencimento, c.data_pagamento, c.valor, c.status
     FROM tb_contas c
     LEFT JOIN tb_fornecedor f ON f.codigo = c.codigo
     ORDER BY c.num_doc ASC"
);

if (!$resultado) {
    error_log("Erro ao consultar contas para os relatórios.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar as contas.");
}

$contas = [];
$contasPendentes = 0;
$contasPagas = 0;
$valorPendente = 0.0;

while ($conta = mysqli_fetch_assoc($resultado)) {
    $contas[] = $conta;

    if ($conta["status"] === "D") {
        $contasPendentes++;
        $valorPendente += (float) ($conta["valor"] ?? 0);
    } elseif ($conta["status"] === "P") {
        $contasPagas++;
    }
}

mysqli_free_result($resultado);
mysqli_close($conexao);

$nomeFornecedorConta = static function (array $conta): string {
    $nome = $conta["nome_fornecedor"] ?? null;

    if (is_string($nome) && trim($nome) !== "") {
        return trim($nome);
    }

    $codigo = $conta["codigo"] ?? null;

    return $codigo === null
        ? "Sem fornecedor"
        : "Fornecedor #" . (string) $codigo . " (não cadastrado)";
};

$correspondePeriodo = static function (?string $data, ?string $inicio, ?string $fim): bool {
    if ($inicio === null && $fim === null) {
        return true;
    }

    if ($data === null || $data === "" || $data === "0000-00-00") {
        return false;
    }

    return ($inicio === null || $data >= $inicio) &&
        ($fim === null || $data <= $fim);
};

$correspondeFornecedor = static function (array $conta, ?string $filtro): bool {
    if ($filtro === null) {
        return true;
    }

    $codigo = $conta["codigo"] ?? null;

    if ($filtro === "sem") {
        return $codigo === null || $codigo === "";
    }

    return $codigo !== null && (string) $codigo === $filtro;
};

$ordenarContas = static function (
    array &$registros,
    string $campoData,
    bool $agruparFornecedor = false,
    bool $maisRecentesPrimeiro = false
) use ($nomeFornecedorConta): void {
    usort($registros, static function (array $a, array $b) use (
        $campoData,
        $agruparFornecedor,
        $maisRecentesPrimeiro,
        $nomeFornecedorConta
    ): int {
        if ($agruparFornecedor) {
            $comparacaoFornecedor = strnatcasecmp(
                $nomeFornecedorConta($a),
                $nomeFornecedorConta($b)
            );

            if ($comparacaoFornecedor !== 0) {
                return $comparacaoFornecedor;
            }
        }

        $dataA = $a[$campoData] ?? null;
        $dataB = $b[$campoData] ?? null;
        $semDataA = $dataA === null || $dataA === "" || $dataA === "0000-00-00";
        $semDataB = $dataB === null || $dataB === "" || $dataB === "0000-00-00";

        if ($semDataA !== $semDataB) {
            return $semDataA ? 1 : -1;
        }

        $comparacaoData = strcmp((string) $dataA, (string) $dataB);

        if ($maisRecentesPrimeiro) {
            $comparacaoData *= -1;
        }

        if ($comparacaoData !== 0) {
            return $comparacaoData;
        }

        return (int) $a["num_doc"] <=> (int) $b["num_doc"];
    });
};

$contasPagarPorData = [];
$contasPagarPorFornecedor = [];
$contasAPagar = [];
$contasPagasLista = [];
$contasPagasPorFornecedor = [];
$contasPagasPorData = [];

foreach ($contas as $conta) {
    $status = (string) ($conta["status"] ?? "");

    if ($status === "D") {
        $contasAPagar[] = $conta;

        if (
            !$erroPeriodoPagar &&
            $correspondePeriodo($conta["data_vencimento"] ?? null, $dataPagarInicio, $dataPagarFim)
        ) {
            $contasPagarPorData[] = $conta;
        }

        if (!$erroFornecedorPagar && $correspondeFornecedor($conta, $fornecedorPagar)) {
            $contasPagarPorFornecedor[] = $conta;
        }
    } elseif ($status === "P") {
        $contasPagasLista[] = $conta;

        if (!$erroFornecedorPaga && $correspondeFornecedor($conta, $fornecedorPaga)) {
            $contasPagasPorFornecedor[] = $conta;
        }

        if (
            !$erroPeriodoPaga &&
            $correspondePeriodo($conta["data_pagamento"] ?? null, $dataPagaInicio, $dataPagaFim)
        ) {
            $contasPagasPorData[] = $conta;
        }
    }
}

$ordenarContas($contasPagarPorData, "data_vencimento");
$ordenarContas($contasPagarPorFornecedor, "data_vencimento", true);
$ordenarContas($contasAPagar, "data_vencimento");
$ordenarContas($contasPagasLista, "data_pagamento", false, true);
$ordenarContas($contasPagasPorFornecedor, "data_pagamento", true, true);
$ordenarContas($contasPagasPorData, "data_pagamento", false, true);

$valorFiltro = static function (string $campo): string {
    $valor = $_GET[$campo] ?? "";

    return is_string($valor) ? $valor : "";
};

$consultaAtual = http_build_query(array_filter(
    [
        "pagar_de" => $valorFiltro("pagar_de"),
        "pagar_ate" => $valorFiltro("pagar_ate"),
        "paga_de" => $valorFiltro("paga_de"),
        "paga_ate" => $valorFiltro("paga_ate"),
        "fornecedor_pagar" => $valorFiltro("fornecedor_pagar"),
        "fornecedor_paga" => $valorFiltro("fornecedor_paga"),
    ],
    static fn (string $valor): bool => $valor !== ""
));

$documentoSenhaInvalida = $_GET["num_doc"] ?? "";
$documentoSenhaInvalida = is_string($documentoSenhaInvalida)
    ? filter_var(
        $documentoSenhaInvalida,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 2147483647]]
    )
    : false;
$reabrirModalSenhaInvalida =
    ($_GET["exclusao"] ?? "") === "senha" &&
    $documentoSenhaInvalida !== false;

$formatarData = static function (?string $data): string {
    if ($data === null || $data === "" || $data === "0000-00-00") {
        return "—";
    }

    $timestamp = strtotime($data);

    return $timestamp === false ? "—" : date("d/m/Y", $timestamp);
};

$relatorios = [
    [
        "id" => "contas-pagar-data",
        "titulo" => "Contas a pagar por data",
        "contas" => $contasPagarPorData,
        "erro" => $erroPeriodoPagar ? "Corrija o período informado para exibir este relatório." : "",
        "vazio" => "Não há contas a pagar neste período.",
    ],
    [
        "id" => "contas-pagar-fornecedor",
        "titulo" => "Contas a pagar por fornecedor",
        "contas" => $contasPagarPorFornecedor,
        "erro" => $erroFornecedorPagar ? "Selecione um fornecedor válido para exibir este relatório." : "",
        "vazio" => "Não há contas a pagar para o fornecedor selecionado.",
    ],
    [
        "id" => "contas-a-pagar",
        "titulo" => "Contas a pagar",
        "contas" => $contasAPagar,
        "erro" => "",
        "vazio" => "Não há contas pendentes.",
    ],
    [
        "id" => "contas-pagas",
        "titulo" => "Contas pagas",
        "contas" => $contasPagasLista,
        "erro" => "",
        "vazio" => "Não há contas pagas.",
    ],
    [
        "id" => "contas-pagas-fornecedor",
        "titulo" => "Contas pagas por fornecedor",
        "contas" => $contasPagasPorFornecedor,
        "erro" => $erroFornecedorPaga ? "Selecione um fornecedor válido para exibir este relatório." : "",
        "vazio" => "Não há contas pagas para o fornecedor selecionado.",
    ],
    [
        "id" => "contas-pagas-data",
        "titulo" => "Contas pagas por data",
        "contas" => $contasPagasPorData,
        "erro" => $erroPeriodoPaga ? "Corrija o período informado para exibir este relatório." : "",
        "vazio" => "Não há contas pagas neste período.",
    ],
];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver contas</title>
    <link
        rel="stylesheet"
        href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>"
    >
</head>

<body>
    <main class="container pagina-contas-listagem pagina-ver-contas">
        <a href="sair.php" class="voltar" aria-label="Sair da consulta" title="Sair da consulta">←</a>
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
                <p>CONSULTA FINANCEIRA</p>
            </div>

            <h1>Ver <span>Contas</span></h1>
            <p class="descricao">
                Acesso de
                <strong><?= htmlspecialchars(
                    (string) ($_SESSION["ver_contas_usuario"] ?? ""),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></strong>.
                Consulte os seis relatórios. Selecione uma conta para exibir a opção de exclusão, autorizada pela senha de um gerente.
            </p>

            <?php if (($_GET["exclusao"] ?? "") === "sucesso"): ?>
                <div class="mensagem sucesso" role="status">
                    Conta excluída com sucesso.
                </div>
            <?php elseif (($_GET["exclusao"] ?? "") === "indisponivel"): ?>
                <div class="mensagem erro" role="alert">
                    Essa conta não foi encontrada ou já foi excluída.
                </div>
            <?php elseif (($_GET["exclusao"] ?? "") === "erro"): ?>
                <div class="mensagem erro" role="alert">
                    Não foi possível excluir a conta. Tente novamente.
                </div>
            <?php endif; ?>

            <section class="resumo-contas" aria-label="Resumo das contas">
                <div class="indicador-contas">
                    <span class="indicador-rotulo">Contas cadastradas</span>
                    <strong><?= count($contas) ?></strong>
                </div>
                <div class="indicador-contas">
                    <span class="indicador-rotulo">Contas a pagar</span>
                    <strong><?= $contasPendentes ?></strong>
                </div>
                <div class="indicador-contas">
                    <span class="indicador-rotulo">Valor pendente</span>
                    <strong>R$ <?= number_format($valorPendente, 2, ",", ".") ?></strong>
                </div>
                <div class="indicador-contas">
                    <span class="indicador-rotulo">Contas pagas</span>
                    <strong><?= $contasPagas ?></strong>
                </div>
            </section>

            <section class="filtros-relatorios" aria-labelledby="titulo-filtros">
                <h2 id="titulo-filtros">Filtros dos relatórios</h2>
                <p>Os relatórios por data usam vencimento para contas a pagar e data de pagamento para contas pagas.</p>

                <?php if ($errosFiltros !== []): ?>
                    <div class="mensagem erro" role="alert">
                        <?php foreach ($errosFiltros as $erroFiltro): ?>
                            <span><?= htmlspecialchars($erroFiltro, ENT_QUOTES, "UTF-8") ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="GET" action="listar.php" autocomplete="off">
                    <div class="grade-filtros">
                        <fieldset class="grupo-filtro">
                            <legend>Contas a pagar por data</legend>
                            <div class="campos-filtro">
                                <div class="campo-filtro">
                                    <label for="pagar_de">De</label>
                                    <input type="date" id="pagar_de" name="pagar_de" value="<?= htmlspecialchars($valorFiltro("pagar_de"), ENT_QUOTES, "UTF-8") ?>">
                                </div>
                                <div class="campo-filtro">
                                    <label for="pagar_ate">Até</label>
                                    <input type="date" id="pagar_ate" name="pagar_ate" value="<?= htmlspecialchars($valorFiltro("pagar_ate"), ENT_QUOTES, "UTF-8") ?>">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="grupo-filtro">
                            <legend>Contas a pagar por fornecedor</legend>
                            <div class="campo-filtro">
                                <label for="fornecedor_pagar">Fornecedor</label>
                                <select id="fornecedor_pagar" name="fornecedor_pagar">
                                    <option value="">Todos os fornecedores</option>
                                    <option value="sem" <?= $valorFiltro("fornecedor_pagar") === "sem" ? "selected" : "" ?>>Sem fornecedor vinculado</option>
                                    <?php foreach ($fornecedores as $fornecedor): ?>
                                        <?php $codigoFornecedor = (string) $fornecedor["codigo"]; ?>
                                        <option
                                            value="<?= htmlspecialchars($codigoFornecedor, ENT_QUOTES, "UTF-8") ?>"
                                            <?= $valorFiltro("fornecedor_pagar") === $codigoFornecedor ? "selected" : "" ?>
                                        >
                                            <?= htmlspecialchars(
                                                (string) $fornecedor["nome"] . " (#" . $codigoFornecedor . ")",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </fieldset>

                        <fieldset class="grupo-filtro">
                            <legend>Contas pagas por fornecedor</legend>
                            <div class="campo-filtro">
                                <label for="fornecedor_paga">Fornecedor</label>
                                <select id="fornecedor_paga" name="fornecedor_paga">
                                    <option value="">Todos os fornecedores</option>
                                    <option value="sem" <?= $valorFiltro("fornecedor_paga") === "sem" ? "selected" : "" ?>>Sem fornecedor vinculado</option>
                                    <?php foreach ($fornecedores as $fornecedor): ?>
                                        <?php $codigoFornecedor = (string) $fornecedor["codigo"]; ?>
                                        <option
                                            value="<?= htmlspecialchars($codigoFornecedor, ENT_QUOTES, "UTF-8") ?>"
                                            <?= $valorFiltro("fornecedor_paga") === $codigoFornecedor ? "selected" : "" ?>
                                        >
                                            <?= htmlspecialchars(
                                                (string) $fornecedor["nome"] . " (#" . $codigoFornecedor . ")",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </fieldset>

                        <fieldset class="grupo-filtro">
                            <legend>Contas pagas por data</legend>
                            <div class="campos-filtro">
                                <div class="campo-filtro">
                                    <label for="paga_de">De</label>
                                    <input type="date" id="paga_de" name="paga_de" value="<?= htmlspecialchars($valorFiltro("paga_de"), ENT_QUOTES, "UTF-8") ?>">
                                </div>
                                <div class="campo-filtro">
                                    <label for="paga_ate">Até</label>
                                    <input type="date" id="paga_ate" name="paga_ate" value="<?= htmlspecialchars($valorFiltro("paga_ate"), ENT_QUOTES, "UTF-8") ?>">
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="acoes-filtros">
                        <button type="submit" class="botao-cadastrar botao-aplicar-filtros">Aplicar filtros</button>
                        <a href="listar.php" class="limpar-filtros">Limpar filtros</a>
                    </div>
                </form>
            </section>

            <div class="relatorios-contas" id="relatorios-contas">
                <?php foreach ($relatorios as $relatorio): ?>
                    <section class="relatorio-conta" aria-labelledby="<?= htmlspecialchars($relatorio["id"], ENT_QUOTES, "UTF-8") ?>">
                        <h2 id="<?= htmlspecialchars($relatorio["id"], ENT_QUOTES, "UTF-8") ?>">
                            <?= htmlspecialchars($relatorio["titulo"], ENT_QUOTES, "UTF-8") ?>
                            <span><?= count($relatorio["contas"]) ?></span>
                        </h2>

                        <?php if ($relatorio["erro"] !== ""): ?>
                            <p class="relatorio-vazio" role="alert"><?= htmlspecialchars($relatorio["erro"], ENT_QUOTES, "UTF-8") ?></p>
                        <?php elseif ($relatorio["contas"] === []): ?>
                            <p class="relatorio-vazio" role="status"><?= htmlspecialchars($relatorio["vazio"], ENT_QUOTES, "UTF-8") ?></p>
                        <?php else: ?>
                            <div class="tabela-contas tabela-mobile">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nº documento</th>
                                            <th>Fornecedor</th>
                                            <th>Descrição</th>
                                            <th>Vencimento</th>
                                            <th>Pagamento</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($relatorio["contas"] as $conta): ?>
                                            <?php $statusPago = $conta["status"] === "P"; ?>
                                            <tr
                                                data-conta-linha
                                                data-num-doc="<?= htmlspecialchars((string) $conta["num_doc"], ENT_QUOTES, "UTF-8") ?>"
                                                data-descricao="<?= htmlspecialchars((string) ($conta["descricao"] ?? "—"), ENT_QUOTES, "UTF-8") ?>"
                                                tabindex="0"
                                                aria-label="Selecionar conta documento <?= htmlspecialchars((string) $conta["num_doc"], ENT_QUOTES, "UTF-8") ?>"
                                            >
                                                <td data-label="Nº documento"><?= htmlspecialchars((string) $conta["num_doc"], ENT_QUOTES, "UTF-8") ?></td>
                                                <td data-label="Fornecedor"><?= htmlspecialchars($nomeFornecedorConta($conta), ENT_QUOTES, "UTF-8") ?></td>
                                                <td data-label="Descrição"><?= htmlspecialchars((string) ($conta["descricao"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                                                <td data-label="Vencimento"><?= htmlspecialchars($formatarData($conta["data_vencimento"] ?? null), ENT_QUOTES, "UTF-8") ?></td>
                                                <td data-label="Pagamento"><?= htmlspecialchars($formatarData($conta["data_pagamento"] ?? null), ENT_QUOTES, "UTF-8") ?></td>
                                                <td data-label="Valor">
                                                    <?php if ($conta["valor"] !== null): ?>
                                                        R$ <?= number_format((float) $conta["valor"], 2, ",", ".") ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Status">
                                                    <?php if ($statusPago): ?>
                                                        <span class="status-conta status-paga">Paga (P)</span>
                                                    <?php else: ?>
                                                        <span class="status-conta status-aberta">Devendo (D)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Ação" class="celula-acao-conta" hidden>
                                                    <button
                                                        type="button"
                                                        class="botao-excluir-conta"
                                                        data-excluir-conta
                                                        aria-label="Excluir conta documento <?= htmlspecialchars((string) $conta["num_doc"], ENT_QUOTES, "UTF-8") ?>"
                                                        title="Excluir conta"
                                                    >
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M3 6h18"></path>
                                                            <path d="M8 6V4h8v2"></path>
                                                            <path d="m19 6-1 14H6L5 6"></path>
                                                            <path d="M10 11v5M14 11v5"></path>
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>

        <dialog
            class="modal-exclusao-conta"
            id="modalExclusaoConta"
            aria-labelledby="tituloModalExclusao"
            data-reabrir-senha="<?= $reabrirModalSenhaInvalida ? "true" : "false" ?>"
            data-num-doc="<?= $reabrirModalSenhaInvalida ? (int) $documentoSenhaInvalida : "" ?>"
        >
            <div class="modal-exclusao-conteudo">
                <span class="modal-exclusao-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"></path>
                        <path d="M8 6V4h8v2"></path>
                        <path d="m19 6-1 14H6L5 6"></path>
                        <path d="M10 11v5M14 11v5"></path>
                    </svg>
                </span>
                <p class="modal-exclusao-etiqueta">EXCLUSÃO DE CONTA</p>
                <h2 id="tituloModalExclusao">Confirmar exclusão?</h2>
                <p class="modal-exclusao-descricao">
                    A conta <strong id="modalNumeroConta"></strong> será removida permanentemente.
                    Para autorizar, informe a senha de um gerente cadastrado.
                </p>
                <?php if ($reabrirModalSenhaInvalida): ?>
                    <div class="mensagem erro" role="alert">
                        Senha incorreta. Tente novamente com a senha de um gerente cadastrado.
                    </div>
                <?php endif; ?>
                <form action="excluir_conta.php" method="POST" id="formularioExclusaoConta" autocomplete="off">
                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($_SESSION["csrf_ver_contas"], ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="num_doc" id="numeroContaExclusao">
                    <input type="hidden" name="consulta" value="<?= htmlspecialchars($consultaAtual, ENT_QUOTES, "UTF-8") ?>">
                    <div class="campo modal-exclusao-campo">
                        <label for="senhaGerente">Senha de um gerente</label>
                        <input
                            type="password"
                            id="senhaGerente"
                            name="senha_gerente"
                            placeholder="Digite a senha do gerente"
                            autocomplete="off"
                            required
                        >
                    </div>
                    <div class="modal-exclusao-acoes">
                        <button type="button" class="modal-exclusao-cancelar" id="cancelarExclusaoConta">
                            Cancelar
                        </button>
                        <button type="submit" class="modal-exclusao-confirmar">
                            Excluir conta
                        </button>
                    </div>
                </form>
            </div>
        </dialog>

        <footer>Sistema Financeiro © 2026</footer>
    </main>
    <script src="../js/exclusao-conta.js?v=2" defer></script>
</body>

</html>
