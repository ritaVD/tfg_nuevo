const BASE = '/api'

type Method = 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE'

export async function apiFetch<T>(
  path: string,
  method: Method = 'GET',
  body?: unknown
): Promise<T> {
  const opts: RequestInit = {
    method,
    credentials: 'include',
    headers: body ? { 'Content-Type': 'application/json' } : {},
  }
  if (body) opts.body = JSON.stringify(body)
  const res = await fetch(BASE + path, opts)
  if (res.status === 204) return undefined as T
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`)
  return data as T
}

export async function apiFormData<T>(path: string, formData: FormData): Promise<T> {
  const res = await fetch('/api' + path, {
    method: 'POST',
    credentials: 'include',
    body: formData,
  })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`)
  return data as T
}
