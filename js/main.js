/* sunonline-static — JS mínimo.
 * Substitui apenas o que a home realmente usa dos scripts originais:
 *  - input.login (Common.js): esconde o "label" de fundo do campo ao focar
 *  - raiseEnterAction (jsGeneralFunction.js): Enter no login aciona o botão
 *  - toggle da caixa "Equipe" (antes carregado via ajax)
 *  - login: injeta em #ResultAjaxLogin a resposta do ajax, pré-renderizada em <template> (A19)
 *  - enquete: injeta em #pollResult a resposta do ajax, pré-renderizada em <template> (A17)
 *  - painel: elementos com data-kit-tpl injetam a resposta de ajax pré-renderizada em data-kit-target (A23)
 *  - evento de perguntas: "Responder" injeta a resposta pré-renderizada conforme a opção marcada (A24)
 *  - Golden Archer: "Registrar serial" injeta a resposta pré-renderizada do serial digitado (A25)
 *  - rankings: os forms "Carregar"/"Buscar" (GET para ?page=rankings) abrem a página estática da variante
 * Tudo que era Webzen (Common.js, jsMenuLinkFunction.js, jsGameController.js),
 * Cufon, jQuery, fancybox e ajax.js foi removido.
 */
(function () {
    'use strict';

    // Campos de login: a classe "on" mostra a imagem de fundo com o texto "ID"/"PW".
    var loginInputs = document.querySelectorAll('#loginBox .inputtxt input');
    Array.prototype.forEach.call(loginInputs, function (el) {
        el.addEventListener('focus', function () { el.className = ''; });
        el.addEventListener('blur', function () { if (el.value === '') el.className = 'on'; });
        el.addEventListener('keypress', function (ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                var btn = document.getElementById('btnLogin');
                if (btn) btn.click();
            }
        });
    });

    // Botão de login: mostra em #ResultAjaxLogin a resposta que o ajax require=login traria (A19) —
    // campos vazios -> LOGIN_FILL_ALL_FIELDS; preenchidos -> LOGIN_ERROR (não há contas no kit).
    var btnLogin = document.getElementById('btnLogin');
    if (btnLogin) {
        btnLogin.addEventListener('click', function () {
            var out = document.getElementById('ResultAjaxLogin');
            var user = document.getElementById('userName'), pwd = document.getElementById('userPwd');
            var tpl = document.getElementById(user && pwd && user.value !== '' && pwd.value !== '' ? 'loginError' : 'loginFill');
            if (out && tpl) out.innerHTML = tpl.innerHTML;
        });
    }

    // Caixa "Equipe": [Mostrar] revela a lista.
    var btnStaff = document.getElementById('but');
    var staff = document.getElementById('equipe');
    if (btnStaff && staff) {
        btnStaff.addEventListener('click', function (ev) {
            ev.preventDefault();
            btnStaff.style.display = 'none';
            staff.style.display = '';
        });
    }

    // Enquete: Votar / Resultado injetam em #pollResult a resposta que o Verify() traria
    // (pollVote, pollInvalid, pollResults — geradas pelo harness a partir de ldAjax::loadPoll).
    var pollResult = document.getElementById('pollResult');
    if (pollResult) {
        var show = function (id) {
            var tpl = document.getElementById(id);
            if (tpl) pollResult.innerHTML = tpl.innerHTML;
        };
        var pollButtons = pollResult.parentNode.querySelectorAll('input.button');
        if (pollButtons.length === 2) {
            pollButtons[0].addEventListener('click', function () {
                var answer = document.getElementById('answerNumber');
                show(answer && answer.value !== '' && answer.value !== '0' ? 'pollVote' : 'pollInvalid');
            });
            pollButtons[1].addEventListener('click', function () { show('pollResults'); });
        }
    }

    // Rankings: no original os dois forms fazem GET ?page=rankings&type=…[&period=…|&name=…].
    // Aqui cada combinação vira a página gerada correspondente (top e name não mudam o arquivo).
    var rankingPages = {
        '1-0': 'rankings.html', '1-1': 'rankings-semanal.html', '1-2': 'rankings-mensal.html',
        '2': 'rankings-level.html', '3': 'rankings-guilds.html', '4': 'rankings-pk.html',
        '5': 'rankings-master-resets.html', '6': 'rankings-personagem.html', '7': 'rankings-guild.html',
        '8': 'rankings-gens.html', '9': 'rankings-gens-vanert.html'
    };
    Array.prototype.forEach.call(document.forms, function (form) {
        if (!form.elements.page || form.elements.page.value !== 'rankings' || !form.elements.type) return;
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var type = form.elements.type.value;
            var period = form.elements.period && !form.elements.period.disabled ? form.elements.period.value : '0';
            window.location.href = rankingPages[type === '1' ? '1-' + period : type] || 'rankings.html';
        });
    });

    // Respostas de ajax pré-renderizadas (A23, baú virtual): o clique injeta o <template> no alvo, como o Verify()
    document.addEventListener('click', function (ev) {
        var el = ev.target.closest ? ev.target.closest('[data-kit-tpl]') : null;
        if (!el) return;
        var tpl = document.getElementById(el.getAttribute('data-kit-tpl'));
        var target = document.getElementById(el.getAttribute('data-kit-target'));
        if (tpl && target) target.innerHTML = tpl.innerHTML;
    });

    // Evento de perguntas (A24): 0 = nenhuma opção -> qe-select; certa -> qe-correct; outra -> qe-wrong.
    // Como o ajax.js original (extraiScript), executa os <script> da resposta (o que esconde o contador).
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-kit-qe]') : null;
        if (!btn) return;
        var answer = document.getElementById('answerFinal');
        var v = answer ? answer.value : '0';
        var tpl = document.getElementById(v === '0' || v === '' ? 'qe-select' : (v === btn.getAttribute('data-kit-qe') ? 'qe-correct' : 'qe-wrong'));
        var out = document.getElementById('reponseSubmit');
        if (!tpl || !out) return;
        out.innerHTML = tpl.innerHTML;
        Array.prototype.forEach.call(out.querySelectorAll('script'), function (s) { new Function(s.textContent)(); });
    });

    // Golden Archer (A25): serial vazio -> ga-empty; serial de exemplo -> ga-<serial sem hífens>; outro -> ga-invalid
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form.hasAttribute || !form.hasAttribute('data-kit-ga')) return;
        ev.preventDefault();
        var input = form.querySelector('#serial');
        var serial = (input ? input.value : '').replace(/-/g, '').toUpperCase();
        var tpl = document.getElementById(serial === '' ? 'ga-empty' : 'ga-' + serial) || document.getElementById('ga-invalid');
        var out = document.getElementById('serialResponse');
        if (tpl && out) out.innerHTML = tpl.innerHTML;
    });
})();
