<script setup lang="ts">
// div[login].tpl.php (visitante) e div[logout].tpl.php + ldGeneral::loadDivLoginLogout (logado).
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/stores/session'

const session = useSessionStore()
const router = useRouter()

const userName = ref('')
const userPwd = ref('')
// input.login (Common.js): a classe "on" mostra o fundo com "ID"/"PW" enquanto o campo está vazio e sem foco
const focused = ref<'user' | 'pwd' | null>(null)
const result = ref('')

function submit() {
  const r = session.authenticate(userName.value, userPwd.value)
  result.value = r.message
  if (!r.ok || !r.login) return
  // o original respondia "Logado com Sucesso. Aguarde..." e recarregava a home (window.location='?')
  const login = r.login
  setTimeout(() => {
    session.startSession(login)
    userName.value = userPwd.value = result.value = ''
    router.push('/')
  }, 700)
}

function logout() {
  session.signOut()
  router.push('/')
}
</script>

<template>
  <div v-if="!session.isLogged" id="LoginPanel1_pnlLogInBefore" name="pnlLogInBefore">
    <div id="loginBox">
      <fieldset>
        <legend>Login</legend>
        <div class="inputtxt">
          <label for="userID" class="login_id"><span>ID</span><input
            v-model="userName" type="text" id="userName" name="userName" maxlength="10" title="Login" tabindex="1"
            :class="{ on: focused !== 'user' && userName === '' }"
            @focus="focused = 'user'" @blur="focused = null" @keypress.enter.prevent="submit"
          /></label>
          <label for="userPW" class="login_pw"><span>PW</span><input
            v-model="userPwd" type="password" id="userPwd" name="userPwd" maxlength="10" title="Senha" tabindex="2"
            :class="{ on: focused !== 'pwd' && userPwd === '' }"
            @focus="focused = 'pwd'" @blur="focused = null" @keypress.enter.prevent="submit"
          /></label>
        </div>
        <div class="inputbtn">
          <img src="/images/common/btn/bt_login.gif" style="cursor:pointer" alt="Log in" id="btnLogin" @click="submit" />
        </div>
      </fieldset>
      <div id="ResultAjaxLogin" style="margin-left: 16px; color: #CFB988;"><strong v-if="result" v-html="result"></strong></div>
      <ul class="membermnu">
        <li><RouterLink to="/recovery">Recuperar sua senha</RouterLink></li>
        <li><RouterLink to="/register">Não tem conta? <u class="signup">Cadastre-se</u></RouterLink></li>
      </ul>
    </div>
  </div>

  <div v-else id="LoginPanel1_pnlLogInAfter" name="pnlLogInAfter">
    <div class="logoutform">
      <p class="bt_logout"><a href="/" @click.prevent="logout"><img src="/images/common/btn/bt_logout.gif" width="15" height="15" alt="LOGOUT" class="" /></a></p>
      <span class="username">
        Seja bem vindo ao painel
      </span>
      <div class="menuLogin">
        <ul>
          <li><RouterLink to="/paneluser">Painel do usuário</RouterLink></li>
          <li v-if="session.privilege >= 1"><RouterLink to="/panelgamemaster">Painel do game master</RouterLink></li>
          <li v-if="session.privilege >= 2"><RouterLink to="/paneladmin">Painel do admin</RouterLink></li>
          <li><a href="/" @click.prevent="logout">Sair</a></li>
        </ul>
      </div>
    </div>
  </div>
</template>
