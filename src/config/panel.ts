// Configuração do painel do usuário — exportada de modules/settings.php (MuSite).

// $PANELUSER_MODULE['RESET']: RESET_MODE 1 = só a faixa "0-10" vale para todos os resets (ver RESET_TIERS)
export const RESET_MODE: number = 1
export const RESET_LIMIT = [0, 0, 0, 0, 0, 0] // resets máximos por plano (0 = ilimitado)

// $PANELUSER_MODULE['MASTER_RESET']
export const MASTER_RESET = {
  BONUS_ACTIVE: true,
  BONUS_AMOUNT: [10, 20, 30, 30, 30, 30], // SGolds por master reset, por plano
  RESETS_REQUIRE: [500, 450, 400, 400, 400, 400],
  POINTS_REQUIRE: { Strength: 32000, Dexterity: 32000, Vitality: 32000, Energy: 32000, Leadership: 32000 },
  POINTS_AFTER: { Strength: 32, Dexterity: 32, Vitality: 32, Energy: 32, Leadership: 32 },
}

// $PANELUSER_MODULE['CLEAN_PK']: CLEAN_MODE 1 = zen fixo, 2 = zen × mortes
export const CLEAN_PK = { CLEAN_MODE: 1, PRICEZEN: [2000000, 1500000, 1000000, 1000000, 1000000, 1000000] }

export const CHANGE_CLASS = { RESET_QUESTS: true, RESET_SKILLS: true }
export const DISTRIBUTE_POINTS_MAX = 32767
export const RESET_TRANSFER_MIN = 10
export const OPEN_TICKET_MAX_OPEN = 1
export const CONFIRM_PAYMENT_MAX_OPEN = 1

// Coletor de pontos: Jewel of Life (categoria 14, item 16), 2 pontos por unidade
export const COLLECTOR_POINTS = { idCategorie: 14, idItem: 16, premiumInPoints: 2 }

// $Config_SMS: o nome do destinatário da settings (o autor do MuSite) vira "Administrador", como no kit
export const SMS_RECIPIENTS = [{ name: 'Administrador', ddd: '31', number: '00000000', suffix: '@clarotorpedo.com.br' }]
export const SMS_SUBJECT = 'SMS_MU'
// tamanho do remetente do SMTP da settings: entra no limite de caracteres da mensagem
export const SMS_FROM_LENGTH = 21

// $GAME_QUESTION — evento de perguntas ("Show do Milhão")
export interface PremiumItem {
  idCategorie: number
  idItem: number
  options: { Level: number; Option: number; Skill: boolean; Luck: boolean; Excellent: boolean[] }
}
const opt = (Level: number, Option: number, Skill: boolean, Luck: boolean, Excellent: boolean[]) => ({ Level, Option, Skill, Luck, Excellent })
export const GAME_QUESTION = {
  ENABLE: true,
  WAIT: 180, // segundos até poder ser sorteado de novo
  LUCK: [0, 10000, 9500] as const, // sorteia de 0 a 10000; ganha com >= 9500 (5%)
  PREMIUM: {
    A: [
      { idCategorie: 0, idItem: 8, options: opt(5, 3, false, true, [true, false, false, true, false, false]) },
      { idCategorie: 1, idItem: 4, options: opt(3, 4, false, true, [false, true, false, false, true, false]) },
    ],
    B: [
      { idCategorie: 2, idItem: 7, options: opt(9, 5, false, true, [false, true, true, false, true, false]) },
      { idCategorie: 4, idItem: 6, options: opt(8, 6, true, true, [true, false, true, false, false, true]) },
    ],
    C: [
      { idCategorie: 5, idItem: 8, options: opt(11, 7, false, true, [true, true, false, true, true, false]) },
      { idCategorie: 0, idItem: 11, options: opt(11, 7, false, true, [true, false, true, true, true, false]) },
    ],
    D: [
      { idCategorie: 12, idItem: 4, options: opt(13, 7, false, true, [true, true, true, true, false, false]) },
      { idCategorie: 14, idItem: 14, options: opt(0, 0, false, false, [false, false, false, false, false, false]) },
    ],
  } as Record<'A' | 'B' | 'C' | 'D', PremiumItem[]>,
}
