import { apiFetch } from './client'

export interface ReadingProgressItem {
  id: number
  mode: 'pages' | 'percent'
  currentPage: number | null
  totalPages: number | null
  percent: number | null
  computed: number          // 0-100 calculated server-side
  startedAt: string
  updatedAt: string
  book: {
    id: number
    externalId: string
    title: string
    authors: string[]
    coverUrl: string | null
    pageCount: number | null
  }
}

export const readingProgressApi = {
  list: () =>
    apiFetch<ReadingProgressItem[]>('/reading-progress'),

  add: (externalId: string, mode: 'pages' | 'percent', totalPages?: number) =>
    apiFetch<ReadingProgressItem>('/reading-progress', 'POST', {
      externalId,
      mode,
      ...(totalPages ? { totalPages } : {}),
    }),

  update: (id: number, patch: {
    mode?: 'pages' | 'percent'
    currentPage?: number | null
    totalPages?: number | null
    percent?: number | null
  }) =>
    apiFetch<ReadingProgressItem>(`/reading-progress/${id}`, 'PATCH', patch),

  delete: (id: number) =>
    apiFetch<void>(`/reading-progress/${id}`, 'DELETE'),
}
