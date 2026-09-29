// Contas de exemplo do front (sem backend). A senha é a mesma da kira01 do FakeDB do kit
// (reference/_build/pumock.php). Privilégio como no MuSite: 0 = usuário, 1 = game master, 2 = admin.
export type Privilege = 0 | 1 | 2

export interface Account {
  login: string
  password: string
  privilege: Privilege
}

export const accounts: Account[] = [
  { login: 'kira01', password: '123456', privilege: 0 },
  { login: 'gm01', password: '123456', privilege: 1 },
  { login: 'admin', password: '123456', privilege: 2 },
]
