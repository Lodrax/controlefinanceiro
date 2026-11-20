const botaoMenu = document.getElementById('botaoMenu');
const menuLateral = document.getElementById('menuLateral');
const sobreposicao = document.getElementById('sobreposicao');

function abrirMenu() {
menuLateral.classList.add('ativo');
sobreposicao.classList.add('ativo');

botaoMenu.setAttribute('aria-expanded', 'true');
botaoMenu.setAttribute('aria-label', 'Fechar menu');
menuLateral.setAttribute('aria-hidden', 'false');

}

function fecharMenu() {
menuLateral.classList.remove('ativo');
sobreposicao.classList.remove('ativo');

botaoMenu.setAttribute('aria-expanded', 'false');
botaoMenu.setAttribute('aria-label', 'Abrir menu');
menuLateral.setAttribute('aria-hidden', 'true');

}

botaoMenu.addEventListener('click', function () {
if (menuLateral.classList.contains('ativo')) {
fecharMenu();
} else {
abrirMenu();
}
});

sobreposicao.addEventListener('click', fecharMenu);

document.addEventListener('keydown', function (evento) {
if (evento.key === 'Escape') {
fecharMenu();
}
});
