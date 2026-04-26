import { apiFetch, apiFormData } from './client'

export interface ProfileData {
  id: number
  email: string
  displayName: string | null
  bio: string | null
  avatar: string | null
  shelvesPublic: boolean
  clubsPublic: boolean
  isPrivate: boolean
  followers: number
  following: number
}

export const profileApi = {
  get: () => apiFetch<ProfileData>('/profile'),
  update: (data: { displayName?: string; bio?: string }) =>
    apiFetch<ProfileData>('/profile', 'PUT', data),
  uploadAvatar: (file: File) => {
    const fd = new FormData()
    fd.append('avatar', file)
    return apiFormData<ProfileData>('/profile/avatar', fd)
  },
  updatePrivacy: (data: { shelvesPublic?: boolean; clubsPublic?: boolean; isPrivate?: boolean }) =>
    apiFetch<ProfileData>('/profile/privacy', 'PUT', data),
  changePassword: (currentPassword: string, newPassword: string) =>
    apiFetch<void>('/profile/password', 'PUT', { currentPassword, newPassword }),
}
