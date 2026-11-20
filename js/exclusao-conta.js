const modalExclusaoConta = document.getElementById('modalExclusaoConta');

if (modalExclusaoConta instanceof HTMLDialogElement) {
  const numeroConta = document.getElementById('numeroContaExclusao');
  const textoNumeroConta = document.getElementById('modalNumeroConta');
  const senhaGerente = document.getElementById('senhaGerente');
  const botaoCancelar = document.getElementById('cancelarExclusaoConta');
  let linhaSelecionada = null;

  function limparSelecao() {
    if (!linhaSelecionada) {
      return;
    }

    linhaSelecionada.classList.remove('conta-selecionada');
    const celulaAcao = linhaSelecionada.querySelector('.celula-acao-conta');
    if (celulaAcao) {
      celulaAcao.hidden = true;
    }
    linhaSelecionada = null;
  }

  function selecionarLinha(linha) {
    if (linhaSelecionada === linha) {
      limparSelecao();
      return;
    }

    limparSelecao();
    linhaSelecionada = linha;
    linha.classList.add('conta-selecionada');

    const celulaAcao = linha.querySelector('.celula-acao-conta');
    if (celulaAcao) {
      celulaAcao.hidden = false;
    }
  }

  document.querySelectorAll('[data-conta-linha]').forEach((linha) => {
    linha.addEventListener('click', (evento) => {
      if (evento.target.closest('button')) {
        return;
      }

      selecionarLinha(linha);
    });

    linha.addEventListener('keydown', (evento) => {
      if (evento.target.closest('button')) {
        return;
      }

      if (evento.key === 'Enter' || evento.key === ' ') {
        evento.preventDefault();
        selecionarLinha(linha);
      }
    });
  });

  document.querySelectorAll('[data-excluir-conta]').forEach((botao) => {
    botao.addEventListener('click', () => {
      const linha = botao.closest('[data-conta-linha]');
      if (!linha) {
        return;
      }

      numeroConta.value = linha.dataset.numDoc || '';
      textoNumeroConta.textContent = linha.dataset.numDoc || '';
      senhaGerente.value = '';
      modalExclusaoConta.showModal();
      senhaGerente.focus();
    });
  });

  botaoCancelar.addEventListener('click', () => {
    modalExclusaoConta.close();
  });

  modalExclusaoConta.addEventListener('click', (evento) => {
    if (evento.target === modalExclusaoConta) {
      modalExclusaoConta.close();
    }
  });

  if (
    modalExclusaoConta.dataset.reabrirSenha === 'true' &&
    modalExclusaoConta.dataset.numDoc
  ) {
    const linhaComErro = Array.from(
      document.querySelectorAll('[data-conta-linha]')
    ).find((linha) => linha.dataset.numDoc === modalExclusaoConta.dataset.numDoc);

    if (linhaComErro) {
      selecionarLinha(linhaComErro);
      numeroConta.value = linhaComErro.dataset.numDoc;
      textoNumeroConta.textContent = linhaComErro.dataset.numDoc;
      modalExclusaoConta.showModal();
      senhaGerente.focus();
    }
  }
}
