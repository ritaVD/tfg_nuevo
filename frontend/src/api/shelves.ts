import { apiFetch } from './client'
import type { Book } from './books'

export interface Shelf {
  id: number
  name: string
  orderIndex: number
}

export type ReadingStatus = 'want_to_read' | 'reading' | 'read'

export interface ShelfBook {
  id: number
  book: Book
  status: ReadingStatus
  addedAt: string
}

export const shelvesApi = {
  list: () => apiFetch<Shelf[]>('/shelves'),
  create: (name: string) => apiFetch<Shelf>('/shelves', 'POST', { name }),
  delete: (id: number) => apiFetch<void>(`/shelves/${id}`, 'DELETE'),
  books: (id: number) => apiFetch<ShelfBook[]>(`/shelves/${id}/books`),
  addBook: (shelfId: number, externalId: string, status: ReadingStatus) =>
    apiFetch<ShelfBook>(`/shelves/${shelfId}/books`, 'POST', { externalId, status }),
  updateStatus: (shelfId: number, bookId: number, status: ReadingStatus) =>
    apiFetch<ShelfBook>(`/shelves/${shelfId}/books/${bookId}`, 'PATCH', { status }),
  removeBook: (shelfId: number, bookId: number) =>
    apiFetch<void>(`/shelves/${shelfId}/books/${bookId}`, 'DELETE'),
}
