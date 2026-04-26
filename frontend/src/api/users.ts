import { apiFetch } from './client'

export interface UserResult {
  id: number
  displayName: string
  avatar: string | null
  bio: string | null
  followers: number
  followStatus: 'none' | 'pending' | 'accepted'
  isMe: boolean
}

export const usersApi = {
  search: (q: string) =>
    apiFetch<UserResult[]>(`/users/search?q=${encodeURIComponent(q)}`),
}
