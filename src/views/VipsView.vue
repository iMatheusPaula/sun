<script setup lang="ts">
// vips.tpl.php e as três opções: [advantages] (ldVips::loadAdvantages), [howtobuy], [howToBuyVips] (loadHowToBuyVips)
import InnerLayout from '@/layouts/InnerLayout.vue'
import {
  ACTIVE_VIPS, CASH_NAME, PANEL_PERMISSIONS, RESET_TIERS, VIP_DAYS, VIP_NAMES, VIP_PRICES,
  type PanelOption, type ResetTier,
} from '@/config/settings'
import { VIPS_NOT, VIPS_YES } from '@/lang/ldvips'

defineProps<{ option: 'index' | 'advantages' | 'howToBuy' | 'howToBuyVips' }>()

// colunas: Free + planos ativos; índice = tipo de vip
const plans = [0, ...ACTIVE_VIPS]

const permissionRows: [PanelOption, string][] = [
  ['MODIFY_DATA', 'Alterar dados'], ['CLEAN_VAULT', 'Limpar Bau'], ['DOUBLE_VAULT', 'Bau Duplo'],
  ['LOG_BUYS', 'Log de compras'], ['MODIFY_PERSONALID', 'Alterar personal ID'], ['RESET', 'Resetar Personagem'],
  ['RESET_TRANSFER', 'Transferir Resets'], ['CLEAN_PK', 'Limpar PK'], ['DISTRIBUTE_POINTS', 'Distribuir Pontos'],
  ['MOVE_CHARACTER', 'Mover Personagem'], ['CHANGE_NICK', 'Mudar Nick'], ['CHANGE_CLASS', 'Mudar Classe'],
  ['REDISTRIBUTE_POINTS', 'Redistribuir Pontos'], ['CLEAN_INVENTORY', 'Limpar Inventário'],
  ['OPEN_COMPLAINTS', 'Abrir Denuncias'], ['OPEN_TICKET', 'Abrir Pedidos (Ticket)'], ['READ_TICKET', 'Ver Pedidos (Ticket)'],
  ['SEND_SMS', 'Enviar SMS para os ADMs'],
]

const tierTitles: [string, string][] = [
  ['0-10', 'Opções de 0 a 10 resets'], ['11-50', 'Opções de 11 a 50 resets'], ['51-100', 'Opções de 51 a 100 resets'],
  ['101-150', 'Opções de 101 a 150 resets'], ['151-200', 'Opções de 151 a 200 resets'], ['201-300', 'Opções de 201 a 300 resets'],
  ['301-XXX', 'Opções de resets após 301 resets'],
]

const numberRows: [keyof ResetTier, string][] = [
  ['LevelReset', 'Level para resetar'], ['LevelAfter', 'Level depois de resetar'],
  ['ZenRequire', 'Zen para resetar'], ['Points', 'Pontos por reset'],
]
const cleanRows: [keyof ResetTier, string][] = [
  ['CleanItens', 'Apaga itens ao resetar'], ['CleanMagics', 'Apaga magias ao resetar'], ['CleanQuests', 'Apaga quests ao resetar'],
]

const periods = ['30 dias (1 Mês)', '90 dias (3 Meses)', '180 dias (6 Meses)', '365 dias (1 Ano)']

// Sim verde / Não vermelho; nas opções que apagam algo ao resetar as cores se invertem
const GREEN = 'color:#40B60E'
const RED = 'color:#FF0000'
</script>

<template>
  <InnerLayout location="VIP" subtitle="Planos Vip">
    <div id="etc">
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Selecione a opção desejada abaixo</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <ul>
            <li><RouterLink to="/vips-vantagens">Vantagens de ser um player vip</RouterLink></li>
            <li><RouterLink to="/vips-como-comprar">O que é {{ CASH_NAME }} e como comprar {{ CASH_NAME }}</RouterLink></li>
            <li><RouterLink to="/vips-como-comprar-vip">Como Comprar vip</RouterLink></li>
            <li><RouterLink to="/paneluser-buy-vips">Clique aqui para comprar o vip</RouterLink></li>
            <li><RouterLink to="/vips-como-comprar">Dados para depósito</RouterLink></li>
          </ul>
        </div>
      </div>
    </div>

    <div v-if="option === 'advantages'" id="etc">
      <h1 style="margin-bottom: 25px;">Vantagens de ser um player vip</h1>

      <div class="legend" style="margin-bottom: 26px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Opções do painel de usuário</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table border="0" width="100%">
            <tbody>
              <tr>
                <td align="center"><strong>Nome</strong></td>
                <td v-for="p in plans" :key="p" align="center"><strong>{{ VIP_NAMES[p] }}</strong></td>
              </tr>
              <tr v-for="[key, label] in permissionRows" :key="key">
                <td align="left"><strong>{{ label }}</strong></td>
                <td v-for="p in plans" :key="p" align="center">
                  <span v-if="PANEL_PERMISSIONS[key][p + 1]" :style="GREEN">{{ VIPS_YES }}</span>
                  <span v-else :style="RED">{{ VIPS_NOT }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-for="[tier, title] in tierTitles" :key="tier" class="legend" style="margin-bottom: 26px;">
        <h3 class="legend-title"><span style="font-size: 13px;">{{ title }}</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <table border="0" width="100%">
            <tbody>
              <tr>
                <td align="center"><strong>Nome</strong></td>
                <td v-for="p in plans" :key="p" align="center"><strong>{{ VIP_NAMES[p] }}</strong></td>
              </tr>
              <tr v-for="[field, label] in numberRows" :key="field">
                <td align="left"><strong>{{ label }}</strong></td>
                <td v-for="p in plans" :key="p" align="center">{{ RESET_TIERS[tier]![field][p] }}</td>
              </tr>
              <tr v-for="[field, label] in cleanRows" :key="field">
                <td align="left"><strong>{{ label }}</strong></td>
                <td v-for="p in plans" :key="p" align="center">
                  <span v-if="RESET_TIERS[tier]![field][p]" :style="RED">{{ VIPS_YES }}</span>
                  <span v-else :style="GREEN">{{ VIPS_NOT }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else-if="option === 'howToBuy'" id="etc">
      <h1>O que é {{ CASH_NAME }} e como comprar {{ CASH_NAME }}</h1>
      <br />
      <ul>
        <li><strong>O que é {{ CASH_NAME }}?</strong></li>
        <li style="list-style-image: none; list-style: none;"> {{ CASH_NAME }} é uma Moeda usada para fazer compras no Site do jogo, com ela você pode comprar vips, e etc.</li>
      </ul>
      <hr style="color: #C5C0AD;" />
      <ul>
        <li><strong>Como posso obter esses {{ CASH_NAME }}?</strong></li>
        <li style="list-style-image: none; list-style: none;"> {{ CASH_NAME }} é uma Moeda que se compra atravez de Depósitos Bancario, mas você tambêm pode ganhar em Eventos Especiais realizados no jogo.</li>
      </ul>
      <hr style="color: #C5C0AD;" />
      <ul>
        <li><strong>Como comprar {{ CASH_NAME }}?</strong></li>
        <li style="list-style-image: none; list-style: none;"> Para Comprar {{ CASH_NAME }} é facil! O Preço é 1 {{ CASH_NAME }} = R$ 0,50 (50 Centavos), você deve depositar o valor desejado na seguinte conta:</li>
      </ul>
      <br />
      <br />

      <h1>Na hora do depósito:</h1>
      <ul>
        <li>Para depositar você deve anotar os dados da Conta Bancaria, que se encontra abaixo.</li>
        <li style="list-style-image: none; list-style: none;">Feito isso, vá até deve ir ao banco <strong>com os dados da conta</strong> e faça seu depósito.</li>
      </ul>
      <hr style="color: #C5C0AD;" />
      <div class="qdestaques2">
        <strong>EXTREMAMENTE IMPORTANTE:</strong><br />
        Caso você perca seu comprovante, você não poderá fazer a confirmação, portando iremos aceitar seu depósito como Doação. Sem direito a devolução do mesmo.
      </div>

      <div style="width: 100%; margin-top: 10px;">
        <!-- dados bancários do autor original trocados por placeholders (A13 do kit) -->
        <div class="code">
          Banco: Banco Exemplo <br />
          Agência: 0000-0 <br />
          Conta:  00000-0 <br />
          Tipo de conta: Conta corrente <br />
          Favorecido: Nome do Favorecido
        </div>

        <div class="qdestaques2"><strong>Obs.:</strong> Ao fazer um depósito nessa conta,<br />
          você declara concordar com os termos de uso do servidor propostos ao criar o cadastro no jogo.</div>
      </div>
      <hr style="color: #C5C0AD;" />

      <ul>
        <li><strong>Como faço para confirmar meu Depósito?</strong></li>
        <li style="list-style-image: none; list-style: none;">Para Confirmar seu Depósito vá até o painel de usuário do site, no Menu clique em Shop depois em Confirmar Pagamento.</li>
      </ul>
      <hr style="color: #C5C0AD;" />

      <ul>
        <li><strong>Qual o Prazo para meus {{ CASH_NAME }} chegarem após a confirmação do pagamento?</strong></li>
        <li style="list-style-image: none; list-style: none;">O prazo para confirmação é de 48 Horas uteis! (Não Conta com Feriados e Finais de Semanas).</li>
      </ul>
    </div>

    <div v-else-if="option === 'howToBuyVips'" id="etc">
      <h1>Como Comprar vip</h1>

      <ul style="margin-bottom: 20px;">
        <li><strong>Como posso Comprar vip?</strong></li>
        <li style="list-style-image: none; list-style: none;">Para comprar seu vip é muito simples! <br />Você deve possuir uma determinada de {{ CASH_NAME }} para efetuar a compra do mesmo.</li>
      </ul>

      <div class="legend" style="margin-bottom: 10px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Tabela de Preços:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <ul v-for="v in ACTIVE_VIPS" :key="v">
            <li v-for="(days, i) in VIP_DAYS" :key="days">{{ VIP_NAMES[v] }} {{ periods[i] }}: <strong>{{ VIP_PRICES[v][i] }}</strong> {{ CASH_NAME }}</li>
          </ul>
        </div>
      </div>

      <ul>
        <li>Caso você não saiba como conseguir {{ CASH_NAME }}, Clique <RouterLink to="/vips-como-comprar"><strong>aqui</strong></RouterLink>.</li>
        <li>Para ver as Vantagens de ser vip, Clique <RouterLink to="/vips-vantagens"><strong>aqui</strong></RouterLink>.</li>
        <li><RouterLink to="/paneluser-buy-vips"><strong>Clique aqui para comprar seu vip</strong></RouterLink>.</li>
      </ul>
    </div>
  </InnerLayout>
</template>
