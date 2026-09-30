import { defineStore } from 'pinia'
import { computed } from 'vue'
import type { NoticeComment } from '@/data/notices'
import type { NoticeRow } from '@/data/db'
import { sprintf } from '@/lang/format'
import * as A from '@/lang/ldajax'
import { useDbStore } from './db'
import { useSessionStore } from './session'

// settings.php: $NOTICES['COMMENTS'] = true
const COMMENTS_ENABLED = true

type Result = { ok: boolean; message: string }

// notícia como as telas usam: conteúdo em HTML e data como Date
export interface NoticeView {
  id: number
  subject: string
  content: string
  date: Date
}

const toView = (n: NoticeRow): NoticeView => ({ ...n, date: new Date(n.date) })

// webNotices (ldNotice, ldHome e o painel do admin) e os comentários do ajax noticecomment,
// noticeselectcomments e noticedeletecomment — no banco do front.
export const useNoticesStore = defineStore('notices', () => {
  const store = useDbStore()
  let nextCommentAt = 0 // $_SESSION['timeComment']

  // da mais nova para a mais antiga
  const list = computed(() => store.db.notices.map(toView).sort((a, b) => b.date.getTime() - a.date.getTime()))

  function find(id: number): NoticeView | undefined {
    const n = store.db.notices.find((x) => x.id === id)
    return n && toView(n)
  }

  function commentsOf(noticeId: number): NoticeComment[] {
    return store.db.noticeComments.filter((c) => c.noticeId === noticeId).sort((a, b) => b.id - a.id).map((c) => ({ ...c, date: new Date(c.date) }))
  }

  function post(noticeId: number, text: string): Result {
    const session = useSessionStore()
    if (!COMMENTS_ENABLED) return { ok: false, message: A.NOTICES_COMMENTS_DISABLED }
    if (!session.account) return { ok: false, message: A.NOTICES_COMMENTS_NEED_LOGIN }
    if (text === '') return { ok: false, message: A.NOTICES_COMMENTS_WRITE_COMMENT }
    const size = new TextEncoder().encode(text).length // strlen(): bytes
    if (size < 10) return { ok: false, message: A.NOTICES_COMMENTS_MIN_SIZE_COMMENT }
    if (size > 255) return { ok: false, message: A.NOTICES_COMMENTS_MAX_SIZE_COMMENT }
    const wait = Math.ceil((nextCommentAt - Date.now()) / 1000)
    if (wait > 0) return { ok: false, message: sprintf(A.NOTICES_COMMENTS_INTERVAL_COMMENT, wait) }
    if (!find(noticeId)) return { ok: false, message: A.NOTICES_COMMENTS_INVALID_NOTICE }

    const id = Math.max(0, ...store.db.noticeComments.map((c) => c.id)) + 1
    store.db.noticeComments.push({ id, noticeId, account: session.account.login, comment: text.trim(), date: Date.now(), ip: '127.0.0.1' })
    nextCommentAt = Date.now() + 60_000
    return { ok: true, message: A.NOTICES_COMMENTS_SEND_SUCCESS }
  }

  // só administradores (ldPanelAdmin::checkPermission, privilégio 2)
  function remove(commentId: number): Result {
    const session = useSessionStore()
    if (session.privilege < 2) return { ok: false, message: A.NOTICES_COMMENTS_DELETE_PREVILEGY_ERROR }
    const i = store.db.noticeComments.findIndex((c) => c.id === commentId)
    if (i === -1) return { ok: false, message: A.NOTICES_COMMENTS_INVALID_COMMENT }
    store.db.noticeComments.splice(i, 1)
    return { ok: true, message: A.NOTICES_COMMENTS_DELETE_SUCCESS }
  }

  return { list, find, commentsOf, post, remove }
})
