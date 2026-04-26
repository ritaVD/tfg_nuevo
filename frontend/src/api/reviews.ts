import { apiFetch } from './client'

export interface ReviewUser {
  id: number
  displayName: string
  avatar: string | null
}

export interface Review {
  id: number
  rating: number
  content: string | null
  createdAt: string
  user: ReviewUser
}

export interface ReviewStats {
  average: number | null
  count: number
}

export interface ReviewsResponse {
  stats: ReviewStats
  myRating: { id: number; rating: number; content: string | null } | null
  reviews: Review[]
}

export const reviewsApi = {
  list: (externalId: string) =>
    apiFetch<ReviewsResponse>(`/books/${encodeURIComponent(externalId)}/reviews`),
  upsert: (externalId: string, rating: number, content: string) =>
    apiFetch<{ review: Review; stats: ReviewStats }>(
      `/books/${encodeURIComponent(externalId)}/reviews`,
      'POST',
      { rating, content }
    ),
  delete: (externalId: string) =>
    apiFetch<{ stats: ReviewStats }>(`/books/${encodeURIComponent(externalId)}/reviews`, 'DELETE'),
}
