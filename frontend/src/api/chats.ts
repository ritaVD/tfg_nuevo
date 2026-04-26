import { apiFetch } from './client'

export interface Chat {
  id: number
  title: string
  isOpen: boolean
  createdAt: string
  closedAt?: string | null
  messageCount?: number
  createdBy: { id: number; email: string; displayName?: string | null }
}

export interface ChatMessage {
  id: number
  content: string
  createdAt: string
  user: { id: number; email: string; displayName?: string | null; avatar?: string | null }
}

export const chatsApi = {
  list: (clubId: number) => apiFetch<Chat[]>(`/clubs/${clubId}/chats`),
  create: (clubId: number, title: string) =>
    apiFetch<Chat>(`/clubs/${clubId}/chats`, 'POST', { title }),
  delete: (clubId: number, chatId: number) =>
    apiFetch<void>(`/clubs/${clubId}/chats/${chatId}`, 'DELETE'),
  messages: (clubId: number, chatId: number, page = 1) =>
    apiFetch<{ messages: ChatMessage[] }>(`/clubs/${clubId}/chats/${chatId}/messages?page=${page}&limit=50`)
      .then(r => r.messages),
  sendMessage: (clubId: number, chatId: number, content: string) =>
    apiFetch<ChatMessage>(`/clubs/${clubId}/chats/${chatId}/messages`, 'POST', { content }),
  deleteMessage: (clubId: number, chatId: number, msgId: number) =>
    apiFetch<void>(`/clubs/${clubId}/chats/${chatId}/messages/${msgId}`, 'DELETE'),
}
