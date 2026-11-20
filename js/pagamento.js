const modalPagamento = document.getElementById('modalPagamento');

if (modalPagamento instanceof HTMLDialogElement) {
  const numeroDocumento = document.getElementById('modalNumeroDocumento');
  const descricao = document.getElementById('modalDescricao');
  const valor = document.getElementById('modalValor');
  const botaoConfirmar = document.getElementById('confirmarPagamento');
  const botaoCancelar = document.getElementById('cancelarPagamento');
  let formularioPagamento = null;

  document.querySelectorAll('[data-confirmar-pagamento]').forEach((botao) => {
    botao.addEventListener('click', (evento) => {
      const formulario = botao.closest('form');

      if (!(formulario instanceof HTMLFormElement)) {
        return;
      }

      evento.preventDefault();
      formularioPagamento = formulario;
      numeroDocumento.textContent = botao.dataset.numDoc || '—';
      descricao.textContent = botao.dataset.descricao || '—';
      valor.textContent = botao.dataset.valor || '—';
      modalPagamento.showModal();
      botaoConfirmar.focus();
    });
  });

  botaoCancelar.addEventListener('click', () => {
    modalPagamento.close();
  });

  botaoConfirmar.addEventListener('click', () => {
    if (formularioPagamento) {
      formularioPagamento.requestSubmit();
    }
  });

  modalPagamento.addEventListener('click', (evento) => {
    if (evento.target === modalPagamento) {
      modalPagamento.close();
    }
  });

  modalPagamento.addEventListener('close', () => {
    formularioPagamento = null;
  });
}
