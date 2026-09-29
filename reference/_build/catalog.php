<?php
/*
 * mensagens.html — catálogo de todas as mensagens de resposta e estados vazios/de erro do template.
 * Página do KIT (não existe no original): usa a moldura interna e os componentes do próprio site
 * (.legend, .quadros, .qdestaques…). Cada mensagem sai com o texto da constante do .lang.php e a
 * marcação exata da classe PHP que a gera, dentro de uma réplica do contêiner onde aparece.
 * As da sidebar (login, enquete) aparecem no lugar real, na sidebar desta página (A19).
 */

// Texto de uma constante; se ela não existe, o nome dela — era o que o PHP 5 imprimia.
$K_MISSING = [];
function k(string $name): string
{
    global $K_MISSING, $PU_UNDEFINED;
    if (defined($name) && !in_array($name, $PU_UNDEFINED ?? [], true)) return constant($name);
    $K_MISSING[$name] = true;
    return $name;
}

function catSection(string $title, string $intro, string $body): string
{
    return "
                    <div class=\"legend\" style=\"margin-bottom: 25px;\">
                      <h3 class=\"legend-title\"><span style=\"font-size: 13px;\">{$title}</span></h3>
                          <div style=\"margin-bottom: 8px; margin-top: 10px;\">
                            " . ($intro !== '' ? "<p class=\"kitNote\">{$intro}</p>" : '') . "
                            {$body}
                          </div>
                    </div>";
}

// legenda de uma mensagem: constante(s) + quando aparece; constantes inexistentes são sinalizadas
function catItem(string $consts, string $when, string $html): string
{
    global $K_MISSING;
    $flags = '';
    foreach (preg_split('/[\s,+]+/', $consts, -1, PREG_SPLIT_NO_EMPTY) as $c) {
        if (isset($K_MISSING[$c])) $flags .= " <span class=\"kitMissing\">{$c} não existe em nenhum .lang.php (o PHP 5 imprimia o nome)</span>";
    }
    $label = $consts !== '' ? '<code>' . str_replace(' + ', '</code> + <code>', $consts) . '</code> — ' : '';
    return "<p class=\"kitCaption\">{$label}{$when}{$flags}</p>\n                            {$html}\n";
}

// ---------- itens ----------

function catLogin(): string
{
    $o = '';
    foreach ([['LOGIN_FILL_ALL_FIELDS', 'login ou senha vazios (é o que o botão mostra aqui com os campos vazios)'],
              ['LOGIN_ERROR_LOCK', '5 erros seguidos: conta travada por 5 minutos'],
              ['LOGIN_ERROR', 'login ou senha incorretos (é o que o botão mostra aqui com os campos preenchidos)'],
              ['LOGIN_SUCCESS', 'sucesso; vem seguido de <code>&lt;script&gt;window.location=\'?\'&lt;/script&gt;</code>']] as [$c, $when]) {
        $o .= catItem($c, $when, "<div class=\"kitSample\"><strong>" . k($c) . "</strong></div>");
    }
    return $o;
}

function catPoll(): string
{
    global $POLL;
    $li = fn(string $t) => "<ul class=\"kitSampleList\"><li><strong>{$t}</strong></li></ul>";
    $o  = catItem('POLL_LOGIN_TO_VOTE', "Votar, com <code>\$POLL['LOGIN'] = true</code> e sem sessão", $li(k('POLL_LOGIN_TO_VOTE')));
    $o .= catItem('POLL_INTERVAL_VOTE', "Votar de novo antes do intervalo (<code>HANG_WITH</code> tipo 1/2/3; %s = <code>\$POLL['HANG_WITH']['time']</code>)", $li(sprintf(k('POLL_INTERVAL_VOTE'), $POLL['HANG_WITH']['time'])));
    $o .= catItem('POLL_INVALID_OPTION', 'Votar sem marcar opção', $li(k('POLL_INVALID_OPTION')));
    $o .= catItem('POLL_DISABLE', 'Votar em enquete desativada', $li(k('POLL_DISABLE')));
    $o .= catItem('POLL_VOTE_SUCESS', 'Voto registrado', $li(k('POLL_VOTE_SUCESS')));
    $o .= catItem('POLL_VOTE_ERROR', 'Falha no UPDATE. O <code>ldajax.lang.php</code> define <code>POLL_VOTE_SUCESS</code> duas vezes; a 2ª ("Erro ao votar.") deveria ser esta', $li(k('POLL_VOTE_ERROR')));
    $o .= catItem('POLL_INVALID', 'Resultado de enquete inexistente (sem <code>&lt;li&gt;</code>)', "<div class=\"kitSample\"><strong>" . k('POLL_INVALID') . "</strong></div>");
    $o .= catItem('POLL_NO_VOTES', 'Resultado de enquete sem votos (sem <code>&lt;li&gt;</code>)', "<div class=\"kitSample\"><strong>" . k('POLL_NO_VOTES') . "</strong></div>");
    $o .= catItem('POLL_NO_ACTIVES', 'Caixa da enquete sem enquete ativa (substitui pergunta + opções)', "<ul class=\"kitSampleList\"><li>" . k('POLL_NO_ACTIVES') . "</li></ul>");
    $o .= catItem('POLL_NO_ANSWER_REGISTER', 'Enquete ativa sem respostas cadastradas (abaixo da pergunta)', "<ul class=\"kitSampleList\"><li>" . k('POLL_NO_ANSWER_REGISTER') . "</li></ul>");
    return $o;
}

// réplica das linhas do form de cadastro (register.tpl.php) no estado em que o registerCheck (ajax) as deixa
function catRegisterRows(bool $error): string
{
    $st = $error ? "border: 1px solid #FF8080; color: #FF2121;" : "border: 1px solid #00D800; color: #009F00;";
    $loginErr = $error ? "<strong>" . k('REGISTER_LOGIN_IN_USE') . "</strong>" : "<strong></strong>";
    $senhaErr = $error ? "<strong><font color=\"#D82020\">" . k('REGISTER_PASSWORD_NOT_MATCH') . "</font></strong>" : "<strong></strong>";
    $emailErr = $error ? "<strong><font color=\"#D82020\">" . k('REGISTER_EMAIL_NOT_MATCH') . "</font></strong>" : "<strong></strong>";
    $email2   = $error ? 'darklord@exemplo.com.br' : 'darklord@exemplo.com';
    return "<table width='100%' border='0'>
                         <tr>
                          <td width=\"23%\" align='right'><strong>Login: </strong></td>
                          <td width=\"43%\"><input name='login' maxlength='10' type='text' class='inputbox' value='darklord' style='{$st}' /></td>
                          <td width=\"34%\"><div>{$loginErr}</div></td>
                         </tr>
                         <tr>
                          <td align='right'><strong>Senha: </strong></td>
                          <td><input name='senha' maxlength='10' type='password' class='inputbox' value='123456' style='{$st}' /></td>
                          <td>&nbsp;</td>
                         </tr>
                         <tr>
                          <td align='right'><strong>Repetir Senha: </strong></td>
                          <td><input name='resenha' maxlength='10' type='password' class='inputbox' value='" . ($error ? '1234567' : '123456') . "' style='{$st}' /></td>
                          <td><div>{$senhaErr}</div></td>
                         </tr>
                         <tr>
                          <td align='right'><strong>E-mail: </strong></td>
                          <td><input name='email' maxlength='40' type='text' class='inputbox' value='darklord@exemplo.com' style='{$st}' /></td>
                          <td>&nbsp;</td>
                         </tr>
                         <tr>
                          <td align='right'><strong>Repetir E-mail: </strong></td>
                          <td><input name='reemail' maxlength='40' type='text' class='inputbox' value='{$email2}' style='{$st}' /></td>
                          <td><div>{$emailErr}</div></td>
                         </tr>
                     </table>";
}

function catRegister(): string
{
    global $REGISTER_SETTINGS;
    $box = fn(string $inner) => "<div style=\"margin-bottom: 25px;\" class=\"quadrosOut\">{$inner}</div>";
    // ldRegister::registerNow: todos os erros num único .quadros, um por linha
    $all = '';
    foreach (['REGISTER_INCORRECT_SECURITY_CODE', 'REGISTER_DO_NOT_USE_SYMBOLS_LOGIN', 'REGISTER_LOGIN_INVALID_SIZE',
              'REGISTER_DO_NOT_USE_SYMBOLS_PASSWORD', 'REGISTER_DO_NOT_USE_SYMBOLS_REPASSWORD', 'REGISTER_EMAIL_IN_USE',
              'REGISTER_LOGIN_IN_USE', 'REGISTER_PASSWORD_NOT_MATCH', 'REGISTER_EMAIL_NOT_MATCH', 'REGISTER_WRITE_VALID_EMAIL'] as $c) {
        $all .= k($c) . "<br />";
    }
    $o  = catItem('REGISTER_*', 'Erros do envio: saem todos juntos num <code>div.quadros</code>, um por linha (aqui, todas as linhas possíveis). <code>REGISTER_EMAIL_IN_USE</code> só com <code>EMAIL_REPEAT = false</code> (na settings está <code>' . ($REGISTER_SETTINGS['EMAIL_REPEAT'] ? 'true' : 'false') . '</code>). <code>REGISTER_EMPTY_INPUTS</code> nunca aparece: o PHP o grava em <code>$errorTmp</code>, variável que não é exibida', $box("<div class=\"quadros\">{$all}</div>"));
    $o .= catItem('REGISTER_LOGOUT_FOR_REGISTER', 'Enviar o cadastro estando logado', $box("<div class='qdestaques'>" . k('REGISTER_LOGOUT_FOR_REGISTER') . "</div>"));
    $o .= catItem('', 'Sucesso: ver <a href="register-resultado.html">register-resultado.html</a>. As 4 variantes do aviso de e-mail que fecham o bloco:', '');
    $o .= catItem('CREATE_ACCOUNT_EMAIL_SEND_INFO_ACTIVE_SUCCESS', 'e-mail de ativação enviado (<code>EMAIL_ACTIVE = true</code>)', $box("<div class='qdestaques2'><strong>" . k('CREATE_ACCOUNT_EMAIL_SEND_INFO_ACTIVE_SUCCESS') . "</strong></div>"));
    $o .= catItem('CREATE_ACCOUNT_EMAIL_SEND_INFO_ACTIVE_ERROR', 'falha no envio do e-mail de ativação', $box("<div class='qdestaques'><strong>" . k('CREATE_ACCOUNT_EMAIL_SEND_INFO_ACTIVE_ERROR') . "</strong></div>"));
    $o .= catItem('CREATE_ACCOUNT_EMAIL_SEND_INFO_SUCCESS', 'e-mail com os dados enviado (<code>EMAIL_ACTIVE = false</code>)', $box("<div class='qdestaques2'><strong>" . k('CREATE_ACCOUNT_EMAIL_SEND_INFO_SUCCESS') . "</strong></div>"));
    $o .= catItem('CREATE_ACCOUNT_EMAIL_SEND_INFO_ERROR', 'falha no envio do e-mail com os dados', $box("<div class='qdestaques'><strong>" . k('CREATE_ACCOUNT_EMAIL_SEND_INFO_ERROR') . "</strong></div>"));
    $o .= catItem('', 'Ativação da conta pelo link do e-mail (<code>?page=register&amp;activeAccount=</code>) e reenvio (<code>&amp;resendActivateEmail=</code>), no mesmo lugar:', '');
    $o .= catItem('CREATE_ACCOUNT_ACTIVE_SUCCESS', 'conta ativada', $box("<div class='qdestaques2'><strong>" . k('CREATE_ACCOUNT_ACTIVE_SUCCESS') . "</strong></div>"));
    $o .= catItem('CREATE_ACCOUNT_ACTIVE_ERROR_HASH', 'código de ativação inválido (%s = código)', $box(sprintf("<div class='qdestaques'><strong>" . k('CREATE_ACCOUNT_ACTIVE_ERROR_HASH') . "</strong></div>", 'a1b2c3d4e5')));
    $o .= catItem('CREATE_ACCOUNT_ACTIVE_NOT_EXISTS_ACCOUNT', 'conta inexistente (ativação e reenvio)', $box("<div class='qdestaques'><strong>" . k('CREATE_ACCOUNT_ACTIVE_NOT_EXISTS_ACCOUNT') . "</strong></div>"));
    $o .= catItem('CREATE_ACCOUNT_ACTIVE_HAS_ACTIVE', 'conta já ativada (ativação e reenvio)', $box("<div class='qdestaques'><strong>" . k('CREATE_ACCOUNT_ACTIVE_HAS_ACTIVE') . "</strong></div>"));
    return $o;
}

function catValidation(): string
{
    $o  = catItem('REGISTER_LOGIN_IN_USE + REGISTER_PASSWORD_NOT_MATCH + REGISTER_EMAIL_NOT_MATCH',
        'Estado de erro: borda <code>#FF8080</code>, texto <code>#FF2121</code>, mensagem na 3ª coluna (<code>#login_error</code>, <code>#senha_error</code>, <code>#email_error</code>). Os outros textos possíveis no mesmo lugar: <code>REGISTER_DO_NOT_USE_SYMBOLS</code> ("' . k('REGISTER_DO_NOT_USE_SYMBOLS') . '", login e senha) e <code>REGISTER_WRITE_VALID_EMAIL</code> ("' . k('REGISTER_WRITE_VALID_EMAIL') . '"). A mensagem do login sai sem <code>&lt;font color="#D82020"&gt;</code>; as de senha e e-mail, com',
        catRegisterRows(true));
    $o .= catItem('', 'Estado ok: borda <code>#00D800</code>, texto <code>#009F00</code>, mensagem apagada (<code>&lt;strong&gt;&lt;/strong&gt;</code>)', catRegisterRows(false));
    return $o;
}

function catRecovery(): string
{
    global $Config_SMTP;
    // ldRecovery: tudo cai em {#ResultTpl}, logo abaixo do form, dentro do .legend
    $wrap = fn(string $inner) => "<div class=\"legend\" style=\"margin-bottom: 10px;\"><div style=\"margin-bottom: 8px; margin-top: 10px;\">{$inner}</div></div>";
    $back = "<br/><a href='recovery.html'>" . k('RECOVERY_BACK') . "</a>";
    $o  = catItem('RECOVERY_FILL_LOGIN + RECOVERY_BACK', 'Recuperar sem digitar o login', $wrap("<div class='qdestaques'>" . k('RECOVERY_FILL_LOGIN') . "{$back}</div>"));
    $o .= catItem('RECOVERY_LOGIN_NOT_FOUND + RECOVERY_BACK', 'Login inexistente', $wrap("<div class='qdestaques'>" . k('RECOVERY_LOGIN_NOT_FOUND') . "{$back}</div>"));
    $o .= catItem('RECOVERY_WAIT_TIME_FOR_SEND_AGAIN + RECOVERY_BACK', 'Novo pedido antes de <code>$Config_SMTP[\'LimitTime\']</code> minutos (%d)', $wrap(sprintf("<div class='qdestaques'>" . k('RECOVERY_WAIT_TIME_FOR_SEND_AGAIN') . "{$back}</div>", $Config_SMTP['LimitTime'])));
    $ok = fn(string $c) => $wrap("<div class='quadros'>" . k('RECOVERY_LOGIN') . ": <strong>darklord</strong> <br/><br/><strong>" . k($c) . "</strong></div>");
    $o .= catItem('RECOVERY_LOGIN + RECOVERY_SEND_SUCCESS', 'E-mail enviado (<code>USE_MD5 = 0</code>, como na settings)', $ok('RECOVERY_SEND_SUCCESS'));
    $o .= catItem('RECOVERY_LOGIN + RECOVERY_SEND_SUCCESS_MD5', 'E-mail enviado com link de confirmação (<code>USE_MD5 = 1</code>)', $ok('RECOVERY_SEND_SUCCESS_MD5'));
    $o .= catItem('RECOVERY_LOGIN + RECOVERY_TRY_AGAIN', 'Falha no envio do e-mail', $ok('RECOVERY_TRY_AGAIN'));
    $o .= catItem('RECOVERY_EMAIL_CHECK_SUCCESS_MD5', 'Link de confirmação válido (<code>?page=recovery&amp;type=confirm</code>)', $wrap("<div class='quadros'>" . k('RECOVERY_EMAIL_CHECK_SUCCESS_MD5') . "</div>"));
    $o .= catItem('RECOVERY_EMAIL_ERROR_LINK_MD5', 'Link de confirmação inválido', $wrap("<div class='quadros'>" . k('RECOVERY_EMAIL_ERROR_LINK_MD5') . "</div>"));
    return $o;
}

function catComments(): string
{
    // ldAjax::loadNoticeComment -> #commentReturn, abaixo do botão, dentro do .quadros do formulário (readNotice.tpl.php)
    $form = fn(string $msg) => "<div class=\"quadros\" style=\"color: black;\">
                                Deixe seu comentário sobre essa notícia:<br />
                                <textarea style=\"width: 100%; height: 80px;\"></textarea>
                                <input type=\"button\" value=\"Postar comentário\" class=\"button\" />
                                <br /><div>{$msg}</div>
                            </div>";
    $o = catItem('NOTICES_COMMENTS_SEND_SUCCESS', 'Comentário publicado (em seguida a lista recarrega)', $form("<div class='qdestaques2'>" . k('NOTICES_COMMENTS_SEND_SUCCESS') . "</div>"));
    $o .= catItem('NOTICES_COMMENTS_NEED_LOGIN', 'Postar sem estar logado', $form("<div class='qdestaques'>" . k('NOTICES_COMMENTS_NEED_LOGIN') . "</div>"));
    $rest = '';
    foreach ([['NOTICES_COMMENTS_DISABLED', "<code>\$NOTICES['COMMENTS'] = false</code>"], ['NOTICES_COMMENTS_WRITE_COMMENT', 'comentário vazio'],
              ['NOTICES_COMMENTS_MIN_SIZE_COMMENT', 'menos de 10 caracteres'], ['NOTICES_COMMENTS_MAX_SIZE_COMMENT', 'mais de 255 caracteres'],
              ['NOTICES_COMMENTS_INTERVAL_COMMENT', 'menos de 60 s desde o último (%d = segundos restantes)'], ['NOTICES_COMMENTS_INVALID_NOTICE', 'notícia inexistente'],
              ['NOTICES_COMMENTS_SEND_ERROR', 'falha no INSERT']] as [$c, $when]) {
        $text = $c === 'NOTICES_COMMENTS_INTERVAL_COMMENT' ? sprintf(k($c), 42) : k($c);
        $rest .= catItem($c, $when, "<div class='qdestaques'>{$text}</div>");
    }
    $o .= catItem('', 'Os demais erros do envio, no mesmo lugar e no mesmo <code>div.qdestaques</code>:', '') . $rest;
    // ldAjax::loadNoticeDeleteComment -> substitui o conteúdo de #divComent_N (o .quadros do comentário)
    $o .= catItem('NOTICES_COMMENTS_DELETE_SUCCESS', '[Excluir] (admin): a resposta substitui o conteúdo do <code>div.quadros</code> do comentário', "<div class=\"quadros\"><div class='qdestaques2'>" . k('NOTICES_COMMENTS_DELETE_SUCCESS') . "</div></div>");
    foreach ([['NOTICES_COMMENTS_INVALID_COMMENT', 'comentário inexistente'], ['NOTICES_COMMENTS_DELETE_ERROR', 'falha no DELETE'],
              ['NOTICES_COMMENTS_DELETE_PREVILEGY_ERROR', 'sem privilégio de admin']] as [$c, $when]) {
        $o .= catItem($c, $when, "<div class=\"quadros\"><div class='qdestaques'>" . k($c) . "</div></div>");
    }
    return $o;
}

function catScreenshots(): string
{
    // screenshots::voteScreenshot: linha antes da imagem, dentro da <table> de #etc (o texto é fixo no módulo, sem .lang)
    $o  = catItem('', 'Voto registrado (texto fixo em <code>screenshots.module.php</code>); a imagem votada vem logo abaixo, como em <a href="screenshot.html">screenshot.html</a>', "<table style=\"width: 100%;\"><tr><td><div class=\"qdestaques2\">Voto computado com sucesso.</div></td></tr></table>");
    $o .= catItem('', 'Votar de novo na mesma screenshot antes de 24 h', "<table style=\"width: 100%;\"><tr><td><div class=\"qdestaques\">Você só pode votar novamente nessa screenshot em 24 horas.</div></td></tr></table>");
    $o .= catItem('', 'Screenshot inexistente e galeria vazia: o módulo monta "Screenshot não encontrada." numa variável local que nunca é exibida — a tabela sai vazia', '');
    return $o;
}

function catLists(): string
{
    $o  = catItem('RANKING_CHARACTER_NOT_FOUND', 'Perfil de personagem inexistente (o título usa "Resultado de" fixo, não <code>RANKING_RESULT_OF</code>)', "<h1>Resultado de Fulano</h1>
                                        <div class='quadros'>
                                        <table width='100%' border='0'>
                                        <tr><td>" . k('RANKING_CHARACTER_NOT_FOUND') . "</td></tr>
                                        </table>
                                        </div>");
    $o .= catItem('RANKING_RESULT_OF + RANKING_GUILD_NOT_FOUND', 'Perfil de guild inexistente', "<h1>" . k('RANKING_RESULT_OF') . " Fulanos</h1>
                                        <div class='quadros'>
                                        <table width='100%' border='0'>
                                        <tr><td>" . k('RANKING_GUILD_NOT_FOUND') . "</td></tr>
                                        </table>
                                        </div>");
    $o .= catItem('BANNED_NO_USERS', 'Lista de banidos vazia (linha única da tabela de <code>banned.tpl.php</code>)', "<table border='0' width='100%'>
                      <tr>
                       <td align='center' bgcolor='#E2DEC5'><strong>Nome</strong></td>
                       <td align='center' bgcolor='#E2DEC5'><strong>Expira</strong></td>
                       <td align='center' bgcolor='#E2DEC5'><strong>Banidor por</strong></td>
                       <td align='center' bgcolor='#E2DEC5'><strong>Motivo</strong></td>
                      </tr>
                      <tr><td align='center' bgcolor='#EDEBDC' colspan='4'>" . k('BANNED_NO_USERS') . "</td></tr>
                    </table>");
    $o .= catItem('ONLINES_TOTAL_ONLINE', 'Ninguém online: só o cabeçalho e o total', "<table border='0' width='100%'>
						  <tr>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . k('ONLINES_CHARACTER') . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . k('ONLINES_CONNECT_IN') . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . k('ONLINES_RESETS') . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . k('ONLINES_ACCOUNT_TYPE') . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . k('ONLINES_SERVER') . "</strong></td>
						  </tr><tr><td colspan='5'><em><strong>" . k('ONLINES_TOTAL_ONLINE') . " 0 </strong></em></td></tr></table>");
    $o .= catItem('NOTICE_NOT_EXISTS', 'Notícia inexistente (<code>?page=readNotice&amp;id=</code>): texto puro no quadro "Notícia:"', "<div class=\"legend\" style=\"margin-bottom: 10px;\"><h3 class=\"legend-title\"><span style=\"font-size: 13px;\">Notícia:</span></h3><div style=\"margin-bottom: 8px; margin-top: 10px;\">" . k('NOTICE_NOT_EXISTS') . "</div></div>");
    $o .= catItem('NOTICE_NOT_EXISTS_NOTICES', 'Nenhuma notícia cadastrada (lista)', "<div class=\"legend\" style=\"margin-bottom: 10px;\"><h3 class=\"legend-title\"><span style=\"font-size: 13px;\">Notícia:</span></h3><div style=\"margin-bottom: 8px; margin-top: 10px;\">" . k('NOTICE_NOT_EXISTS_NOTICES') . "</div></div>");
    return $o;
}

function catHome(): string
{
    // estes aparecem dentro de blocos da home (CSS preso a #maincenter): aqui só o texto e onde entra
    $rows = [
        ['CASTLE_SIEGE_OCCUPY_GUILD', 'no banner do Castle Siege, no lugar do nome da guild dona, quando o castelo não tem dono'],
        ['LLF_ERROR_CONNECT_DATABASE', 'item único da lista "Últimas do forum" (seguido de " (MYSQL)" ou " (MSSQL)"), falha ao conectar no banco do fórum'],
        ['LLF_ERROR_SELECT_DATABASE', 'idem, falha ao selecionar o banco do fórum'],
        ['CHAT_REAL_TIME_DISABLE', 'texto puro dentro de <code>ul#chatRealTimeUl</code> com <code>$CHAT_REAL_TIME[\'ENABLE\'] = false</code>'],
    ];
    $o = "<table border='0' width='100%'>";
    foreach ($rows as [$c, $where]) {
        $o .= "<tr><td bgcolor='#F1F0E4' width='40%'><strong>" . k($c) . "</strong></td><td>" . catItem($c, $where, '') . "</td></tr>";
    }
    $o .= "</table>";
    $o .= catItem('', 'Listas da home sem dados (notícias, tops, guilds, screenshots) e a caixa Equipe sem membros simplesmente saem vazias — o PHP não emite mensagem', '');
    return $o;
}

function catEmails(): string
{
    // corpos HTML dos e-mails (PHPMailer->MsgHTML); valores de exemplo nos %s
    $mail = fn(string $subject, string $body) => "<div class=\"quadros\"><strong>Assunto:</strong> {$subject}</div><div class=\"quadros\" style=\"color: black;\">{$body}</div>";
    $link = 'http://example.com/?page=register&amp;activeAccount=a1b2c3d4e5';
    $o  = catItem('CREATE_ACCOUNT_EMAIL_SUBJECT + CREATE_ACCOUNT_EMAIL_BODY', 'Cadastro sem ativação', $mail(k('CREATE_ACCOUNT_EMAIL_SUBJECT'), sprintf(k('CREATE_ACCOUNT_EMAIL_BODY'), 'darklord', '123456', 'Mascote da sua escola?', 'Rex')));
    $o .= catItem('CREATE_ACCOUNT_ACTIVE_EMAIL_SUBJECT + CREATE_ACCOUNT_ACTIVE_EMAIL_BODY', 'Cadastro com ativação (como na settings)', $mail(k('CREATE_ACCOUNT_ACTIVE_EMAIL_SUBJECT'), sprintf(k('CREATE_ACCOUNT_ACTIVE_EMAIL_BODY'), 'darklord', '123456', 'Mascote da sua escola?', 'Rex', $link, $link)));
    $link = 'http://example.com/?page=recovery&amp;type=confirm&amp;username=darklord&amp;hash=a1b2c3d4e5&amp;Write=true';
    $o .= catItem('RECOVERY_EMAIL_SUBJECT + RECOVERY_EMAIL_BODY', 'Recuperação de senha (<code>USE_MD5 = 0</code>)', $mail(k('RECOVERY_EMAIL_SUBJECT'), sprintf(k('RECOVERY_EMAIL_BODY'), 'darklord', '123456', 'Rex', '187.10.22.1')));
    $o .= catItem('RECOVERY_EMAIL_SUBJECT + RECOVERY_EMAIL_BODY_MD5', 'Recuperação de senha com confirmação (<code>USE_MD5 = 1</code>)', $mail(k('RECOVERY_EMAIL_SUBJECT'), sprintf(k('RECOVERY_EMAIL_BODY_MD5'), 'darklord', '8f2a61c0', 'Rex', '187.10.22.1', $link, $link)));
    return $o;
}

function catRaw(): string
{
    $items = [
        ['', '"<strong> Erro: N&atilde;o &eacute; permitido acessar o arquivo diretamente. </strong>" — abrir um <code>.class.php</code> pela URL'],
        ['', '"Erro nas propriedades do ajax." — <code>?page=ajax</code> com <code>require</code> desconhecido'],
        ['', '"Erro :(" — <code>registerCheck</code> com mais de um login igual no banco'],
        ['CONNECT_SERVER_INVALID_VERSION', '"' . k('CONNECT_SERVER_INVALID_VERSION') . '" — <code>ldCS</code> com versão de ConnectServer inválida na settings'],
        ['LDPU_VIRTUAL_VAULT_CANT_LOAD_DB', '"' . k('LDPU_VIRTUAL_VAULT_CANT_LOAD_DB') . '" — cadastro com bônus de item, sem o banco de itens'],
        ['', '"$RANKING_CONFIGS[\'GENS_MANUFACTURER\'] inválido! Configure corretamente o setting.php" — ranking de Gens'],
    ];
    $o = '';
    foreach ($items as [$c, $t]) $o .= catItem($c, $t, '');
    return $o;
}

function catPanelUser(): string
{
    // ldPanelUser::loadOptionsDefault: {#BAN_ACCOUNT_DATAILS} entra no fim do <ul> de "Status da minha conta"
    $status = fn(string $extra) => "<div class=\"legend\" style=\"margin-top: 10px;\">
                          <h3 class=\"legend-title\"><span style=\"font-size: 13px;\">Status da minha conta</span></h3>
                             <ul style=\"margin: 10px 0px 10px 0px;\">
                                <li>Conta no plano: <strong>Free</strong></li>
                                <li>(...)</li>
                                {$extra}
                             </ul>
                       </div>";
    $o  = catItem('LDPU_OPTIONS_DEFAULT_BANNED_ACCOUNT', 'Conta banida (<code>bloc_code = 1</code>); %s = fim do ban', $status(sprintf("<div class='qdestaques'>" . k('LDPU_OPTIONS_DEFAULT_BANNED_ACCOUNT') . "</div>", date("d/m/Y g:i a", mktime(0, 0, 0, 12, 31, 2030)))));
    $o .= catItem('LDPU_OPTIONS_DEFAULT_NOT_ACTIVE_ACCOUNT', 'Conta ainda não ativada por e-mail (<code>hashActivate</code> preenchido); %s = login', $status(sprintf("<div class='qdestaques'>" . k('LDPU_OPTIONS_DEFAULT_NOT_ACTIVE_ACCOUNT') . "</div>", 'kira01')));
    $o .= catItem('LDPU_OPTIONS_DEFAULT_YOU_NOT_HAVE_CHARACTERS', 'Conta sem personagens: linha única da tabela "Status dos meus personagens"', "<table border='0' width='50%'>
                                <tr>
                                    <td align=\"center\"><strong>Personagem</strong></td>
                                    <td align=\"center\"><strong>Classe</strong></td>
                                    <td align=\"center\"><strong>Resets</strong></td>
                                    <td align=\"center\"><strong>PK</strong></td>
                                </tr>
                                <tr><td colspan='4' align='center'>" . k('LDPU_OPTIONS_DEFAULT_YOU_NOT_HAVE_CHARACTERS') . "</td></tr>
                                </table>");
    $o .= catItem('', 'Personagem banido: ver <a href="paneluser.html">paneluser.html</a> (KiraMG). Opção desligada na settings e opção fora do plano da conta: <a href="paneluser-desactive.html">paneluser-desactive.html</a> e <a href="paneluser-accessdanied.html">paneluser-accessdanied.html</a>', '');
    return $o;
}

// <style> das classes kit* (legendas e amostras), usado pelas páginas-catálogo do kit
function catStyle(): string
{
    return "<style>
                    .kitNote { margin: 0 0 10px; color: #5a5040; }
                    .kitCaption { margin: 14px 0 5px; font-size: 11px; color: #7a7060; }
                    .kitCaption code, .kitNote code { font-family: Consolas, Monaco, monospace; font-size: 11px; color: #4a3a1a; }
                    .kitMissing { color: #B4322E; font-weight: bold; }
                    .kitSample { background: #222; padding: 8px 16px; color: #CFB988; }
                    .kitSampleList { background: #222; padding: 8px 16px; color: #c5c0ad; list-style: none; margin: 0; }
                    </style>";
}

function catalogContent(): string
{
    $style = catStyle();
    $intro = "<div class=\"qdestaques2\">Página do kit (não existe no template original): todas as mensagens de resposta e estados vazios/de erro, com o texto da constante do <code>.lang.php</code> e a marcação da classe PHP que a gera, dentro de uma réplica do contêiner onde aparecem. As da sidebar (login e enquete) aparecem no lugar real, na sidebar desta página.</div>";
    // a ordem importa: k() registra as constantes ausentes antes de catItem() montar as legendas
    return $style . $intro
        . catSection('Login — sidebar, <code>#ResultAjaxLogin</code>:', 'Resposta do ajax <code>require=login</code>, sempre <code>&lt;strong&gt;texto&lt;/strong&gt;</code> em <code>#ResultAjaxLogin</code> (margem 16px, cor <code>#CFB988</code>). Exemplo real na sidebar desta página; o fundo escuro abaixo só simula a caixa.', catLogin())
        . catSection('Enquete — sidebar, <code>#pollResult</code>:', 'Respostas do ajax <code>require=poll</code>. O resultado com barras está na sidebar desta página; "Votar" e "Resultado" funcionam em todas as páginas (A17).', catPoll())
        . catSection('Cadastro — resultado do envio, <code>{&#35;RESULT_REGISTER}</code>:', 'Entra no <code>div.quadrosOut</code> acima do formulário de <a href="register.html">register.html</a>.', catRegister())
        . catSection('Cadastro — validação campo a campo:', 'Ajax <code>require=register</code> no <code>onblur</code> de cada campo; a resposta é só <code>&lt;script&gt;</code> que pinta os inputs e escreve na 3ª coluna. Réplica das linhas de "Dados da Conta".', catValidation())
        . catSection('Recuperar senha — <code>{&#35;ResultTpl}</code>:', 'Entra abaixo do formulário de <a href="recovery.html">recovery.html</a>.', catRecovery())
        . catSection('Comentários de notícia:', 'Envio: resposta em <code>#commentReturn</code>, abaixo do botão "Postar comentário". Exclusão: a resposta substitui o conteúdo do comentário.', catComments())
        . catSection('Screenshots:', '', catScreenshots())
        . catSection('Rankings, banidos, online e notícias — vazios e inexistentes:', '', catLists())
        . catSection('Home:', '', catHome())
        . catSection('Painel do usuário — Minha Conta:', '', catPanelUser())
        . catSection('E-mails enviados:', 'Corpo HTML enviado pelo PHPMailer; os %s com valores de exemplo.', catEmails())
        . catSection('Saídas sem template:', '<code>exit()</code> com texto puro — a página inteira vira só essa linha.', catRaw());
}
