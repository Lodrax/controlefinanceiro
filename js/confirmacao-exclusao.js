const modalConfirmacaoExclusao = document.getElementById('modalConfirmacaoExclusao');

if (modalConfirmacaoExclusao instanceof HTMLDialogElement) {
  const titulo = document.getElementById('tituloConfirmacaoExclusao');
  const descricao = document.getElementById('descricaoConfirmacaoExclusao');
  const botaoConfirmar = modalConfirmacaoExclusao.querySelector('[data-confirmar-exclusao]');
  const botaoCancelar = modalConfirmacaoExclusao.querySelector('[data-cancelar-confirmacao]');
  let formularioPendente = null;

  document.querySelectorAll('form[data-confirmacao-exclusao]').forEach((formulario) => {
    const botaoAbrir = formulario.querySelector('[data-abrir-confirmacao]');

    botaoAbrir.addEventListener('click', () => {
      formularioPendente = formulario;
      titulo.textContent = `Excluir ${formulario.dataset.entidade || 'registro'}?`;
      descricao.textContent =
        `Tem certeza de que deseja excluir "${formulario.dataset.nome || 'este registro'}"? ` +
        'Esta ação não pode ser desfeita.';
      modalConfirmacaoExclusao.showModal();
      botaoConfirmar.focus();
    });
  });

  botaoCancelar.addEventListener('click', () => {
    modalConfirmacaoExclusao.close();
    formularioPendente = null;
  });

  botaoConfirmar.addEventListener('click', () => {
    const formulario = formularioPendente;
    formularioPendente = null;
    modalConfirmacaoExclusao.close();

    if (formulario) {
      formulario.requestSubmit();
    }
  });

  modalConfirmacaoExclusao.addEventListener('click', (evento) => {
    if (evento.target === modalConfirmacaoExclusao) {
      modalConfirmacaoExclusao.close();
      formularioPendente = null;
    }
  });

  modalConfirmacaoExclusao.addEventListener('cancel', () => {
    formularioPendente = null;
  });
}
