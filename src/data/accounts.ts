// Contas de exemplo do front (sem backend). A senha é a mesma da kira01 do FakeDB do kit
// (reference/_build/pumock.php). Privilégio como no MuSite: 0 = usuário, 1 = game master, 2 = admin.
export type Privilege = 0 | 1 | 2

export interface Account {
  login: string
  password: string
  privilege: Privilege
  email: string
  question: string
  answer: string
  // AccountCharacter.GameIDC: último personagem usado (quem assina comentários e screenshots)
  lastCharacter?: string
}

export const accounts: Account[] = [
  { login: 'kira01', password: '123456', privilege: 0, email: 'kira01@example.com', question: 'Mascote da sua escola?', answer: 'rex', lastCharacter: 'Kira' },
  { login: 'gm01', password: '123456', privilege: 1, email: 'gm01@example.com', question: 'Nome da sua primeira escola?', answer: 'central' },
  { login: 'admin', password: '123456', privilege: 2, email: 'admin@example.com', question: 'Nome da sua primeira escola?', answer: 'central' },
]
