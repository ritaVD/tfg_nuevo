import { apiFetch } from './client'
import type { Book } from './books'

export interface Club {
  id: number
  name: string
  description?: string
  visibility: 'public' | 'private'
  memberCount?: number
  currentBook?: (Book & { since?: string; until?: string }) | null
  userRole?: 'admin' | 'member' | null
  hasPendingRequest?: boolean
  owner?: { id: number; email: string; displayName?: string }
}

export interface ClubMember {
  id: number
  user: { id: number; email: string; displayName?: string | null; avatar?: string | null }
  role: 'admin' | 'member'
  joinedAt: string
}

export interface JoinRequest {
  id: number
  user: { id: number; email: string; displayName?: string | null }
  status: 'pending' | 'approved' | 'rejected'
  requestedAt: string
}

export const clubsApi = {
  list: () => apiFetch<Club[]>('/clubs'),
  get: (id: number) => apiFetch<Club>(`/clubs/${id}`),
  create: (data: { name: string; description?: string; visibility: 'public' | 'private' }) =>
    apiFetch<Club>('/clubs', 'POST', data),
  update: (id: number, data: Partial<{ name: string; description: string; visibility: string }>) =>
    apiFetch<Club>(`/clubs/${id}`, 'PATCH', data),
  delete: (id: number) => apiFetch<void>(`/clubs/${id}`, 'DELETE'),
  join: (id: number) => apiFetch<void>(`/clubs/${id}/join`, 'POST'),
  leave: (id: number) => apiFetch<void>(`/clubs/${id}/leave`, 'DELETE'),
  members: (id: number) => apiFetch<ClubMember[]>(`/clubs/${id}/members`),
  kickMember: (clubId: number, userId: number) =>
    apiFetch<void>(`/clubs/${clubId}/members/${userId}`, 'DELETE'),
  requests: (id: number) => apiFetch<JoinRequest[]>(`/clubs/${id}/requests`),
  approveRequest: (clubId: number, requestId: number) =>
    apiFetch<void>(`/clubs/${clubId}/requests/${requestId}/approve`, 'POST'),
  rejectRequest: (clubId: number, requestId: number) =>
    apiFetch<void>(`/clubs/${clubId}/requests/${requestId}/reject`, 'POST'),
  setCurrentBook: (clubId: number, externalId: string, dateFrom: string, dateUntil: string) =>
    apiFetch<Club['currentBook']>(`/clubs/${clubId}/current-book`, 'PUT', { externalId, dateFrom, dateUntil }),
  removeCurrentBook: (clubId: number) =>
    apiFetch<void>(`/clubs/${clubId}/current-book`, 'DELETE'),
}
