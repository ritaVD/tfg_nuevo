import { apiFetch } from './client'

export interface PostUser {
  id: number
  displayName: string
  avatar: string | null
}

export interface Post {
  id: number
  imagePath: string
  description: string | null
  createdAt: string
  likes: number
  liked: boolean
  commentCount: number
  user: PostUser
}

export interface PostComment {
  id: number
  content: string
  createdAt: string
  user: PostUser
}

export const postsApi = {
  feed: () =>
    apiFetch<Post[]>('/posts'),

  byUser: (userId: number) =>
    apiFetch<Post[]>(`/users/${userId}/posts`),

  create: (image: File, description: string) => {
    const fd = new FormData()
    fd.append('image', image)
    fd.append('description', description)
    return fetch('/api/posts', {
      method: 'POST',
      credentials: 'include',
      body: fd,
    }).then(async res => {
      if (!res.ok) {
        const err = await res.json().catch(() => ({}))
        throw new Error(err.error ?? `Error ${res.status}`)
      }
      return res.json() as Promise<Post>
    })
  },

  delete: (postId: number) =>
    apiFetch<void>(`/posts/${postId}`, 'DELETE'),

  like: (postId: number) =>
    apiFetch<{ liked: boolean; likes: number }>(`/posts/${postId}/like`, 'POST'),

  comments: (postId: number) =>
    apiFetch<PostComment[]>(`/posts/${postId}/comments`),

  addComment: (postId: number, content: string) =>
    apiFetch<PostComment>(`/posts/${postId}/comments`, 'POST', { content }),

  deleteComment: (postId: number, commentId: number) =>
    apiFetch<void>(`/posts/${postId}/comments/${commentId}`, 'DELETE'),
}
