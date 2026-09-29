import { defineStore } from 'pinia'
import { ref } from 'vue'
import { noticeComments, notices, type NoticeComment } from '@/data/notices'
import { sprintf } from '@/lang/format'
import * as A from '@/lang/ldajax'
import { useSessionStore } from './session'

// settings.php: $NOTICES['COMMENTS'] = true
const COMMENTS_ENABLED = true

type Result = { ok: boolean; message: string }

// ldAjax: noticecomment (postar), noticeselectcomments (listar) e noticedeletecomment (apagar)
export const useNoticesStore = defineStore('notices', () => {
  const comments = ref<NoticeComment[]>(noticeComments.map((c) => ({ ...c })))
  let nextId = Math.max(...noticeComments.map((c) => c.id)) + 1
  let nextCommentAt = 0 // $_SESSION['timeComment']

  function find(id: number) {
    return notices.find((n) => n.id === id)
  }

  function commentsOf(noticeId: number): NoticeComment[] {
    return comments.value.filter((c) => c.noticeId === noticeId).sort((a, b) => b.id - a.id)
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

    comments.value.push({ id: nextId++, noticeId, account: session.account.login, comment: text.trim(), date: new Date(), ip: '127.0.0.1' })
    nextCommentAt = Date.now() + 60_000
    return { ok: true, message: A.NOTICES_COMMENTS_SEND_SUCCESS }
  }

  // só administradores (ldPanelAdmin::checkPermission, privilégio 2)
  function remove(commentId: number): Result {
    const session = useSessionStore()
    if (session.privilege < 2) return { ok: false, message: A.NOTICES_COMMENTS_DELETE_PREVILEGY_ERROR }
    const i = comments.value.findIndex((c) => c.id === commentId)
    if (i === -1) return { ok: false, message: A.NOTICES_COMMENTS_INVALID_COMMENT }
    comments.value.splice(i, 1)
    return { ok: true, message: A.NOTICES_COMMENTS_DELETE_SUCCESS }
  }

  return { find, commentsOf, post, remove }
})
