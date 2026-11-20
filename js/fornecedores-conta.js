const campoCodigoFornecedor = document.getElementById("codigo");
const botaoFornecedores = document.querySelector(".botao-fornecedores");
const listaFornecedores = document.getElementById("lista-fornecedores");

function definirListaFornecedoresAberta(aberta) {
  listaFornecedores.hidden = !aberta;
  botaoFornecedores.setAttribute("aria-expanded", String(aberta));
  campoCodigoFornecedor.setAttribute("aria-expanded", String(aberta));
}

botaoFornecedores.addEventListener("click", function () {
  definirListaFornecedoresAberta(listaFornecedores.hidden);
});

listaFornecedores.addEventListener("click", function (evento) {
  const opcao = evento.target.closest(".opcao-fornecedor");

  if (!opcao) {
    return;
  }

  campoCodigoFornecedor.value = opcao.dataset.codigo;
  campoCodigoFornecedor.dispatchEvent(new Event("change", { bubbles: true }));
  definirListaFornecedoresAberta(false);
  campoCodigoFornecedor.focus();
});

document.addEventListener("click", function (evento) {
  if (!evento.target.closest(".seletor-fornecedor")) {
    definirListaFornecedoresAberta(false);
  }
});

document.addEventListener("keydown", function (evento) {
  if (evento.key === "Escape" && !listaFornecedores.hidden) {
    definirListaFornecedoresAberta(false);
    botaoFornecedores.focus();
  }
});
