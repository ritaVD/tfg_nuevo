import { apiFetch } from './client'

export interface Book {
  id?: number
  externalId: string
  title: string
  subtitle?: string
  authors: string[]
  thumbnail?: string
  coverUrl?: string
  description?: string
  publisher?: string
  publishedDate?: string
  language?: string
  pageCount?: number
  categories?: string[]
  isbn10?: string
  isbn13?: string
  averageRating?: number
  ratingsCount?: number
  previewLink?: string
  infoLink?: string
}

export interface BookSearchResult {
  results: Book[]
  page: number
  total?: number
}

export interface SearchParams {
  q?: string
  title?: string
  author?: string
  lang?: string
  orderBy?: string
  printType?: string
  page?: number
  limit?: number
}

export const booksApi = {
  search: (params: SearchParams) => {
    const qs = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => v != null && qs.set(k, String(v)))
    return apiFetch<BookSearchResult>(`/books/search?${qs}`)
  },
  get: (externalId: string) => apiFetch<Book>(`/books/${encodeURIComponent(externalId)}`),
}
