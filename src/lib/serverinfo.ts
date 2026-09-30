// ldGeneral::__construct: os contadores da caixa INFORMAÇÕES da lateral (e do AdminCenter) — os count(1) do
// original sobre o banco do front. O construtor rodava no começo de cada página, antes da ação da tela: os números
// são os do banco naquele momento (o que a própria página altera — Sincronizar, um formulário — aparece na próxima).
import { onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { VIP_NAMES } from '@/config/settings'

export function useServerInfo() {
  const store = useDbStore()

  function read() {
    const { accounts, characters, record } = store.db
    // MEMB_STAT.ConnectStat = 1
    const online = accounts.filter((a) => a.online).length
    const infos = {
      accounts: accounts.length,
      characters: characters.length,
      online,
      recordOnline: record.online,
      // webVips.type = 1..5 (vencidos contam até o Sincronizar)
      vips: [1, 2, 3, 4, 5].map((type): [plan: string, count: number] => [VIP_NAMES[type]!, accounts.filter((a) => a.vip === type).length]),
      // bloc_code = 1 AND hashActivate is null (conta esperando ativação por e-mail não conta)
      bannedAccounts: accounts.filter((a) => a.blocked && a.activationHash === null).length,
      bannedCharacters: characters.filter((c) => c.ctlCode === 1).length,
    }
    // webRecord: mostra o recorde gravado; total online acima dele grava o novo
    if (online > record.online) store.db.record = { online, date: Date.now() }
    return infos
  }

  // a cada página (o afterEach roda antes da tela nova ser montada, e antes das ações que ela executa)
  const infos = ref(read())
  onUnmounted(useRouter().afterEach(() => (infos.value = read())))
  return { infos }
}
