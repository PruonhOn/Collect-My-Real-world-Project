import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { del, get, getPaginated, post, put } from '@/lib/api'
import type { ListQuery } from '@/types/api'
import type { AnyRecord } from '@/features/master-data/types'

/**
 * Data access for master-data modules.
 *
 * The hooks take an API path rather than a surface name so this file does not
 * have to import the surface registry, which in turn needs nothing from here.
 * The page resolves the path once and passes it down.
 *
 * `surface` is only used to scope the cache. Customers, shippers and consignees
 * share the `parties` table, and warehouses and factories share `sites`, so
 * each surface keeps its own key even where the underlying rows are related.
 */
export function masterKeys(surface: string) {
  return {
    all: ['master', surface] as const,
    list: (query: ListQuery) => ['master', surface, 'list', query] as const,
  }
}

/**
 * A paginated listing.
 *
 * `placeholderData` keeps the previous page on screen while the next one loads.
 * Without it every page change and every keystroke in the search box collapses
 * the table to a skeleton, which reads as a flicker when the request is fast.
 */
export function useMasterList(
  surface: string,
  path: string,
  query: ListQuery,
  refetchInterval?: number,
) {
  return useQuery({
    queryKey: masterKeys(surface).list(query),
    queryFn: () => getPaginated<AnyRecord>(path, query),
    placeholderData: (previous) => previous,
    refetchInterval,
  })
}

/**
 * Options for a select that references another module, e.g. a truck's carrier.
 *
 * `per_page: 100` is the API's ceiling. A select that silently cannot reach the
 * 101st carrier is a known limit of the list endpoint, not a bug: dropdowns are
 * for picking from the live fleet, and anything larger belongs behind a search
 * screen.
 */
export function useReferenceList(
  surface: string,
  path: string,
  query: ListQuery = { per_page: 100, sort: 'name', direction: 'asc' },
) {
  return useQuery({
    queryKey: masterKeys(surface).list(query),
    queryFn: () => getPaginated<AnyRecord>(path, query),
    staleTime: 5 * 60_000,
  })
}

/**
 * Create and update.
 *
 * Both return the saved record rather than a 204, because the API hands back the
 * canonical form: the codes are normalised and the discriminator is stamped by
 * the route. Showing the server's version is the only way the form can confirm
 * what was actually stored.
 */
export function useCreateRecord(path: string) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (payload: Record<string, unknown> | FormData): Promise<AnyRecord> =>
      post<AnyRecord>(path, payload),
    // Every module invalidates the whole master-data prefix rather than just its
    // own list: creating a carrier changes the options in the truck and driver
    // dropdowns, and a cache left stale there offers a selection the API will
    // now reject.
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['master'] }),
  })
}

export function useUpdateRecord(path: string) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({
      id,
      payload,
    }: {
      id: number
      payload: Record<string, unknown> | FormData
    }): Promise<AnyRecord> => put<AnyRecord>(`${path}/${id}`, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['master'] }),
  })
}

export function useDeleteRecord(path: string) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) => del<null>(`${path}/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['master'] }),
  })
}

/**
 * A lifecycle action on one record: `POST /invoices/12/issue`.
 *
 * Separate from `useUpdateRecord` because the endpoint is not a PUT and the thing
 * it changes is not just the row it is aimed at. Issuing an invoice also rewrites
 * every charge on it, and voiding one releases them, so the cache is invalidated
 * across the whole master-data prefix rather than only this surface - a receipt
 * list left showing an invoice as unpaid because somebody sent the bill in another
 * tab is the kind of wrong that gets acted on.
 */
export function useRecordAction(path: string) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, endpoint }: { id: number; endpoint: string }): Promise<AnyRecord> =>
      post<AnyRecord>(`${path}/${id}/${endpoint}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['master'] }),
  })
}

/** A single record, for a detail view or an edit that loads fresh. */
export function useMasterRecord(surface: string, path: string, id: number | null) {
  return useQuery({
    queryKey: [...masterKeys(surface).all, 'record', id] as const,
    queryFn: () => get<AnyRecord>(`${path}/${id}`),
    enabled: id !== null,
  })
}
