import { apiFetch } from './client'

export interface AdminStats {
  users: number
  clubs: number
  posts: number
}

export interface AdminUser {
  id: number
  email: string
  displayName: string | null
  avatar: string | null
  roles: string[]
  isVerified: boolean
  isAdmin: boolean
}

export interface AdminClub {
  id: number
  name: string
  description: string | null
  visibility: string
  memberCount: number
  createdAt: string
  owner: { id: number; displayName: string | null; email: string } | null
}

export interface AdminPost {
  id: number
  description: string | null
  imagePath: string
  createdAt: string
  user: { id: number; displayName: string | null; email: string }
}

export const adminApi = {
  stats: () => apiFetch<AdminStats>('/admin/stats'),
  users: () => apiFetch<AdminUser[]>('/admin/users'),
  setRole: (id: number, isAdmin: boolean) =>
    apiFetch<AdminUser>(`/admin/users/${id}/role`, 'PATCH', { isAdmin }),
  deleteUser: (id: number) =>
    apiFetch<void>(`/admin/users/${id}`, 'DELETE'),
  clubs: () => apiFetch<AdminClub[]>('/admin/clubs'),
  deleteClub: (id: number) =>
    apiFetch<void>(`/admin/clubs/${id}`, 'DELETE'),
  posts: () => apiFetch<AdminPost[]>('/admin/posts'),
  deletePost: (id: number) =>
    apiFetch<void>(`/admin/posts/${id}`, 'DELETE'),
}
