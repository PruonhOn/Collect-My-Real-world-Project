/**
 * API contract types.
 *
 * These mirror `App\Support\ApiResponse` on the backend. Any change to the
 * envelope must be made in both places at the same time.
 */

export interface ApiSuccess<T> {
  success: true
  message: string
  data: T
  meta?: Record<string, unknown>
}

export interface ApiError {
  success: false
  message: string
  errors: Record<string, string[] | string>
}

export type ApiResponse<T> = ApiSuccess<T> | ApiError

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
  has_more_pages: boolean
}

export interface Paginated<T> {
  success: true
  message: string
  data: T[]
  meta: PaginationMeta
}

/** Shape returned by GET /api/v1/auth/me. */
export interface CurrentUser {
  id: number
  name: string
  email: string
  phone: string | null
  avatar_path: string | null
  is_active: boolean
  last_login_at: string | null
  customer: { id: number; code: string; company_name: string } | null
  roles: string[]
  permissions: string[]
}

/**
 * The query shape every listing endpoint accepts.
 *
 * The names are the backend's, not the SPA's: `MasterDataController::index`
 * reads `q`, `sort` and `direction`. Sending `search`/`sort_by` here is
 * silently ignored, because Axios drops nothing and Laravel's `query()` simply
 * finds no such key - the list then comes back unfiltered and unsorted with no
 * error to notice.
 *
 * `sort` is not constrained to a union because each module whitelists its own
 * columns server-side; a value outside the whitelist falls back to `id`. The
 * per-module column lists live in the surface registry that drives the tables.
 */
export interface ListQuery {
  page?: number
  per_page?: number
  /** Free-text search across the module's searchable columns. */
  q?: string
  sort?: string
  direction?: 'asc' | 'desc'
  [key: string]: string | number | boolean | undefined
}
