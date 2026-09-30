<script setup lang="ts">
// register.tpl.php + ldRegister::registerNow + ajax require=register (validação campo a campo no onblur).
// A conta criada entra no banco do front (fica no navegador) e já pode logar: sem backend não há
// e-mail de ativação, então o bloqueio até ativar (EMAIL_ACTIVE) não é aplicado.
import { reactive, ref } from 'vue'
import InnerLayout from '@/layouts/InnerLayout.vue'
import { useAccountsStore } from '@/stores/accounts'
import { useSessionStore } from '@/stores/session'
import { captchaImage } from '@/data/placeholders'
import { blankAccount } from '@/data/db'
import { REGISTER, VIP_NAMES } from '@/config/settings'
import { phpDate } from '@/lib/date'
import { sprintf } from '@/lang/format'
import * as A from '@/lang/ldajax'
import * as R from '@/lang/ldregister'

const accounts = useAccountsStore()
const session = useSessionStore()

// código do captcha de exemplo (o do kit); no original vinha de $_SESSION["SecurityCode"]
const SECURITY_CODE = '7f3a9c1e'
const SYMBOLS = /[^a-zA-Z0-9_!=?&-]/
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/ // FILTER_VALIDATE_EMAIL, simplificado

const f = reactive({
  login: '', senha: '', resenha: '', email: '', reemail: '', nome: '', telefone: '', sexo: 'Feminino',
  nascimento_dia: '', nascimento_mes: '', nascimento_ano: '', pergunta: '', resposta: '', codigo: '', registerBonus: '-1',
})

const days = Array.from({ length: 31 }, (_, i) => String(i + 1).padStart(2, '0'))
const months = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'))
const years = Array.from({ length: 2008 - 1904 + 1 }, (_, i) => String(2008 - i))
const questions = [
  'Nome da sua primeira escola?', 'Mascote da sua escola?', 'Qual o nome da minha Bizavó?',
  'Qual o nome da minha professora de Matematica?', 'Oque Você Sonha ser?', 'Minha mãe se casou no dia? Ex: ##/##/####',
  'Seus dois melhores amigos de infância?', 'Qual a cidade natal do meu bizavó?', 'Qual o nome do Tio da Avó do meu Cunhado?',
  'O nome do Medico(a) que fez meu parto?',
]

// ---- validação no onblur (ldAjax::registerCheck): borda/cor do campo + mensagem ao lado ----
type Mark = 'ok' | 'error' | null
const marks = reactive<Record<'login' | 'senha' | 'email', Mark>>({ login: null, senha: null, email: null })
const fieldErrors = reactive({ login: '', senha: '', email: '' })
const OK_STYLE = { border: '1px solid #00D800', color: '#009F00' }
const ERROR_STYLE = { border: '1px solid #FF8080', color: '#FF2121' }
const styleOf = (m: Mark) => (m === 'ok' ? OK_STYLE : m === 'error' ? ERROR_STYLE : undefined)
const red = (text: string) => `<strong><font color="#D82020">${text}</font></strong>`

function checkLogin() {
  if (!f.login) return
  if (SYMBOLS.test(f.login)) {
    marks.login = 'error'
    fieldErrors.login = `<strong>${A.REGISTER_DO_NOT_USE_SYMBOLS}</strong>`
  } else if (accounts.find(f.login)) {
    marks.login = 'error'
    fieldErrors.login = `<strong>${A.REGISTER_LOGIN_IN_USE}</strong>`
  } else {
    marks.login = 'ok'
    fieldErrors.login = ''
  }
}

function checkPasswords() {
  if (!f.senha || !f.resenha) return
  if (SYMBOLS.test(f.senha) || SYMBOLS.test(f.resenha)) {
    marks.senha = 'error'
    fieldErrors.senha = red(A.REGISTER_DO_NOT_USE_SYMBOLS)
  } else if (f.senha !== f.resenha) {
    marks.senha = 'error'
    fieldErrors.senha = red(A.REGISTER_PASSWORD_NOT_MATCH)
  } else {
    marks.senha = 'ok'
    fieldErrors.senha = ''
  }
}

function checkEmails() {
  if (!f.email || !f.reemail) return
  if (!EMAIL.test(f.email)) {
    marks.email = 'error'
    fieldErrors.email = red(A.REGISTER_WRITE_VALID_EMAIL)
  } else if (f.email !== f.reemail) {
    marks.email = 'error'
    fieldErrors.email = red(A.REGISTER_EMAIL_NOT_MATCH)
  } else {
    marks.email = 'ok'
    fieldErrors.email = ''
  }
}

// ---- envio: Enviar_Check() no navegador, depois registerNow() ----
const result = ref('')

function submit() {
  const required = [f.login, f.senha, f.resenha, f.email, f.reemail, f.nome, f.telefone, f.sexo,
    f.nascimento_dia, f.nascimento_mes, f.nascimento_ano, f.pergunta, f.resposta]
  if (required.some((v) => v === '')) {
    alert('Erro: Você deve preencher todos os campos antes de prosseguir.')
    return
  }
  result.value = register()
  window.scrollTo(0, 0)
}

function register(): string {
  if (session.isLogged) return `<div class='qdestaques'>${R.REGISTER_LOGOUT_FOR_REGISTER}</div>`

  let errors = ''
  if (f.codigo !== SECURITY_CODE) errors += `${R.REGISTER_INCORRECT_SECURITY_CODE}<br />`
  if (SYMBOLS.test(f.login)) errors += `${R.REGISTER_DO_NOT_USE_SYMBOLS_LOGIN}<br />`
  if (f.login.length > 10) errors += `${R.REGISTER_LOGIN_INVALID_SIZE}<br />`
  const login = REGISTER.FORCELOWER ? f.login.toLowerCase() : f.login
  if (SYMBOLS.test(f.senha)) errors += `${R.REGISTER_DO_NOT_USE_SYMBOLS_PASSWORD}<br />`
  if (SYMBOLS.test(f.resenha)) errors += `${R.REGISTER_DO_NOT_USE_SYMBOLS_REPASSWORD}<br />`
  if (!REGISTER.EMAIL_REPEAT && accounts.all.some((a) => a.email === f.email)) errors += `${R.REGISTER_EMAIL_IN_USE}<br />`
  if (accounts.find(login)) errors += `${A.REGISTER_LOGIN_IN_USE}<br />`
  if (f.senha !== f.resenha) errors += `${A.REGISTER_PASSWORD_NOT_MATCH}<br />`
  if (f.email !== f.reemail) errors += `${A.REGISTER_EMAIL_NOT_MATCH}<br />`
  if (!EMAIL.test(f.email)) errors += `${A.REGISTER_WRITE_VALID_EMAIL}<br />`
  if (errors) return `<div class="quadros">${errors}</div>`

  // MEMB_INFO (+ webCash com o bônus de cadastro, 0 na settings, e webVips com o vip de brinde)
  const now = Date.now()
  const vipEnd = now + REGISTER.BONUS_VIP.DAYS * 24 * 60 * 60 * 1000
  accounts.add(blankAccount({
    login, password: f.senha, privilege: 0, email: f.email, question: f.pergunta, answer: f.resposta,
    name: f.nome, tel: f.telefone, createdAt: phpDate('d/m/Y G:i', new Date(now)),
    birthday: `${f.nascimento_dia}/${f.nascimento_mes}/${f.nascimento_ano}`,
    ...(REGISTER.BONUS_VIP.ACTIVE ? { vip: REGISTER.BONUS_VIP.TYPE, vipBegin: now, vipEnd } : {}),
  }))

  let bonus = ''
  if (REGISTER.BONUS_VIP.ACTIVE) {
    bonus += `<div class='qdestaques2'>${sprintf(R.REGISTER_SUCCESS_REGISTER_BONUS_VIP, REGISTER.BONUS_VIP.DAYS, VIP_NAMES[REGISTER.BONUS_VIP.TYPE]!, phpDate('d-m-Y g:i a', new Date(vipEnd)))}</div>`
  }
  const kit = REGISTER.KITS[Number(f.registerBonus)]
  if (REGISTER.BONUS_ITEM_ACTIVE && kit) {
    bonus += `<div class='qdestaques2'>${sprintf(R.REGISTER_SUCCESS_REGISTER_BONUS_ITEMS, kit.name)}</div>`
  }
  const email = REGISTER.EMAIL_ACTIVE
    ? `<div class='qdestaques2'><strong>${R.CREATE_ACCOUNT_EMAIL_SEND_INFO_ACTIVE_SUCCESS}</strong></div>`
    : `<div class='qdestaques2'><strong>${R.CREATE_ACCOUNT_EMAIL_SEND_INFO_SUCCESS}</strong></div>`
  return `<div class="quadros">${sprintf(R.REGISTER_SUCCESS_REGISTER, login, f.senha, f.pergunta, f.resposta)}</div>${bonus}${email}`
}
</script>

<template>
  <InnerLayout location="CADASTRO" subtitle="Cadastro">
    <div class="quadrosOut" style="margin-bottom: 25px;" v-html="result"></div>
    <div id="resultados" style="display: none;"></div>
    <form name="cadastro" action="" method="post" @submit.prevent>
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Dados da Conta:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td width="23%" align="right"><strong>Login: </strong></td>
                <td width="43%"><input id="login" v-model="f.login" name="login" maxlength="10" type="text" class="inputbox" :style="styleOf(marks.login)" @keyup="f.login = f.login.toLowerCase()" @blur="checkLogin" /></td>
                <td width="34%"><div id="login_error" v-html="fieldErrors.login"></div></td>
              </tr>
              <tr>
                <td align="right"><strong>Senha: </strong></td>
                <td><input id="senha" v-model="f.senha" name="senha" maxlength="10" type="password" class="inputbox" :style="styleOf(marks.senha)" @blur="checkPasswords" /></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="right"><strong>Repetir Senha: </strong></td>
                <td><input id="resenha" v-model="f.resenha" name="resenha" maxlength="10" type="password" class="inputbox" :style="styleOf(marks.senha)" @blur="checkPasswords" /></td>
                <td><div id="senha_error" v-html="fieldErrors.senha"></div></td>
              </tr>
              <tr>
                <td align="right"><strong>E-mail: </strong></td>
                <td><input id="email" v-model="f.email" name="email" maxlength="40" type="text" class="inputbox" :style="styleOf(marks.email)" @blur="checkEmails" /></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="right"><strong>Repetir E-mail: </strong></td>
                <td><input id="reemail" v-model="f.reemail" name="reemail" maxlength="40" type="text" class="inputbox" :style="styleOf(marks.email)" @blur="checkEmails" /></td>
                <td><div id="email_error" v-html="fieldErrors.email"></div></td>
              </tr>
              <tr>
                <td colspan="3"><p class="message"><strong>Obs.: Use de preferência um e-mail cadastrado no MSN Messenger válido, <br />pois só através dele você poderá alterar/recuperar os dados de sua conta.</strong></p></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Dados Pessoais:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td width="23%" align="right"><strong>Nome: </strong></td>
                <td width="43%"><input id="nome" v-model="f.nome" name="nome" maxlength="10" type="text" class="inputbox" /></td>
                <td width="34%">&nbsp;</td>
              </tr>
              <tr>
                <td align="right"><strong>Telefone: </strong></td>
                <td><input id="telefone" v-model="f.telefone" name="telefone" maxlength="80" type="text" class="inputbox" /></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="right"><strong>Sexo: </strong></td>
                <td><select id="sexo" v-model="f.sexo" name="sexo" class="inputbox"><option value="Feminino">Feminino</option><option value="Masculino">Masculino</option></select></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="right"><strong>Nascimento: </strong></td>
                <td><select id="nascimento_dia" v-model="f.nascimento_dia" name="nascimento_dia" class="inputbox">
                    <option value="">Dia</option>
                    <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
                  </select>
                  <select id="nascimento_mes" v-model="f.nascimento_mes" name="nascimento_mes" class="inputbox">
                    <option value="">Mês</option>
                    <option v-for="m in months" :key="m" :value="m">{{ m }}</option>
                  </select>
                  <select id="nascimento_ano" v-model="f.nascimento_ano" name="nascimento_ano" class="inputbox">
                    <option value="">Ano</option>
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                  </select></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="right"><strong>Pergunta Secreta: </strong></td>
                <td colspan="2"><select id="pergunta" v-model="f.pergunta" name="pergunta" class="inputbox">
                    <option value="">Selecione Uma pergunta</option>
                    <option v-for="q in questions" :key="q" :value="q">{{ q }}</option>
                  </select></td>
              </tr>
              <tr>
                <td align="right"><strong>Resposta Secreta: </strong></td>
                <td colspan="2"><input id="resposta" v-model="f.resposta" name="resposta" size="52" maxlength="50" type="text" class="inputbox" @keyup="f.resposta = f.resposta.toLowerCase()" /></td>
              </tr>
              <tr>
                <td align="right"><strong>Código: </strong></td>
                <td colspan="2"><img :src="captchaImage" style="border:none;" width="135" height="25" alt="captcha" /></td>
              </tr>
              <tr>
                <td align="right"><strong>Repita o Código: </strong></td>
                <td colspan="2"><input id="codigo" v-model="f.codigo" name="codigo" size="21" maxlength="8" type="text" class="" @keyup="f.codigo = f.codigo.toLowerCase()" /></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-if="REGISTER.BONUS_ITEM_ACTIVE" class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Bônus de cadastro:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td width="23%" align="right"><strong>Selecione o bônus: </strong></td>
                <td width="43%"><select v-model="f.registerBonus" name="registerBonus"><option value="-1">Nenhum</option><template v-for="(kit, k) in REGISTER.KITS" :key="kit.name"><option :value="String(k)" style="font-weight: bold;">&gt; {{ kit.name }}</option><option value="" disabled>--- {{ R.REGISTER_BONUS_PACKET_DETAILS }}:</option><option v-for="item in kit.items" :key="item" value="" disabled>----- {{ item }}</option></template></select></td>
                <td width="34%">&nbsp;</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="legend" style="margin-bottom: 15px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Termos de Uso:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td colspan="3" align="center"><iframe src="/termos.html" width="500" height="180"></iframe></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div align="center">
        <input type="button" value="Cadastrar (Declaro concordar com os termos de uso)" class="button" @click="submit" />
      </div>
    </form>
  </InnerLayout>
</template>
