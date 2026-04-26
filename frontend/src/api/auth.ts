import { apiFetch } from './client'

export interface AuthUser {
  id: number
  email: string
  displayName: string | null
  avatar: string | null
  roles: string[]
}

export const authApi = {
  me: () => apiFetch<AuthUser>('/auth/me'),
  login: (email: string, password: string) =>
    apiFetch<AuthUser>('/login', 'POST', { email, password }),
  register: (email: string, password: string, displayName: string) =>
    apiFetch<{ id: number; email: string }>('/auth/register', 'POST', { email, password, displayName }),
  logout: () => apiFetch<void>('/auth/logout', 'POST'),
}
