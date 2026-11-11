import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  adminGetBookToc,
  adminUpdateBookToc,
  adminDeleteBookToc,
  adminExtractBookToc,
} from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { extractHeadingsFromPdf } from '../../utils/pdfToc'

const MAX_DEPTH = 4

let uid = 0
const nextKey = () => `toc-${++uid}`

function toRows(items) {
  return items.map((item) => ({ ...item, _key: nextKey() }))
}

/**
 * Admin editor for a book's Table of Contents. Entries are shown as a flat,
 * ordered list; nesting is expressed with `depth` and the parent is derived on
 * save from the nearest preceding entry with a smaller depth.
 */
export default function BookTocEditor({ bookId, pdfFile = null, getDocument = null }) {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [rows, setRows] = useState([])
  const [message, setMessage] = useState('')
  const [dirty, setDirty] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-book-toc', bookId],
    queryFn: () => adminGetBookToc(bookId),
    enabled: !!bookId,
  })

  useEffect(() => {
    if (data?.data?.toc) {
      setRows(toRows(data.data.toc))
      setDirty(false)
    }
  }, [data])

  const saveMutation = useMutation({
    mutationFn: () => adminUpdateBookToc(bookId, toPayload(rows)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-book-toc', bookId] })
      setMessage(t('toc.saved'))
      setDirty(false)
    },
    onError: (err) => setMessage(err.response?.data?.message || t('error')),
  })

  const clearMutation = useMutation({
    mutationFn: () => adminDeleteBookToc(bookId),
    onSuccess: () => {
      setRows([])
      queryClient.invalidateQueries({ queryKey: ['admin-book-toc', bookId] })
      setMessage(t('toc.cleared'))
      setDirty(false)
    },
  })

  const extractMutation = useMutation({
    mutationFn: () => adminExtractBookToc(bookId),
    onSuccess: async (res) => {
      const extracted = res.data?.extracted ?? 0
      if (extracted > 0) {
        queryClient.invalidateQueries({ queryKey: ['admin-book-toc', bookId] })
        setMessage(res.data?.message || t('toc.extracted'))
        setDirty(false)
        return
      }
      // No embedded bookmarks - try the page-text heuristic when we have the
      // file locally (only possible right after an upload in this session).
      const fallback = await runHeadingFallback()
      if (fallback) return
      setMessage(res.data?.message || t('toc.noneFound'))
    },
    onError: (err) => setMessage(err.response?.data?.message || t('error')),
  })

  async function runHeadingFallback() {
    if (!pdfFile || typeof getDocument !== 'function') return false
    const bytes = new Uint8Array(await pdfFile.arrayBuffer())
    const result = await extractHeadingsFromPdf(bytes, { getDocument })

    if (result.scanned) {
      setMessage(t('toc.scannedNotice'))
      return true
    }

    if (result.headings.length === 0) return false

    setRows(
      result.headings.map((heading) => ({
        _key: nextKey(),
        title: heading.title,
        title_en: '',
        page: heading.page,
        depth: heading.depth,
      }))
    )
    setDirty(true)
    setMessage(t('toc.heuristicNotice'))
    return true
  }

  const canMove = (index, direction) => {
    const target = index + direction
    return target >= 0 && target < rows.length
  }

  const move = (index, direction) => {
    setRows((prev) => {
      const target = index + direction
      if (target < 0 || target >= prev.length) return prev
      const copy = [...prev]
      ;[copy[index], copy[target]] = [copy[target], copy[index]]
      return copy
    })
    setDirty(true)
  }

  const updateRow = (index, patch) => {
    setRows((prev) => prev.map((row, i) => (i === index ? { ...row, ...patch } : row)))
    setDirty(true)
  }

  const indent = (index, delta) => {
    setRows((prev) =>
      prev.map((row, i) => {
        if (i !== index) return row
        const depth = Math.min(MAX_DEPTH, Math.max(1, (row.depth || 1) + delta))
        return { ...row, depth }
      })
    )
    setDirty(true)
  }

  const removeRow = (index) => {
    setRows((prev) => prev.filter((_, i) => i !== index))
    setDirty(true)
  }

  const addRow = () => {
    setRows((prev) => [
      ...prev,
      { _key: nextKey(), title: '', title_en: '', page: 1, depth: 1 },
    ])
    setDirty(true)
  }

  if (!bookId) return null

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6 md:p-8 space-y-4">
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div>
          <h2 className="text-lg font-bold text-gray-900">{t('toc.title')}</h2>
          <p className="text-sm text-gray-500">{t('toc.subtitle')}</p>
        </div>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => extractMutation.mutate()}
            disabled={extractMutation.isPending}
            className="px-3 py-2 text-sm font-medium border border-indigo-200 text-indigo-600 rounded-lg hover:bg-indigo-50 disabled:opacity-60"
          >
            {extractMutation.isPending ? t('loading') : t('toc.extractFromPdf')}
          </button>
          <button
            type="button"
            onClick={addRow}
            className="px-3 py-2 text-sm font-medium border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50"
          >
            {t('toc.addEntry')}
          </button>
        </div>
      </div>

      {message && (
        <div className="text-sm rounded-lg bg-indigo-50 text-indigo-700 px-3 py-2">{message}</div>
      )}

      {isLoading ? (
        <p className="text-sm text-gray-400">{t('loading')}</p>
      ) : rows.length === 0 ? (
        <p className="text-sm text-gray-400">{t('toc.empty')}</p>
      ) : (
        <ul className="space-y-2">
          {rows.map((row, index) => (
            <li
              key={row._key}
              className="flex flex-wrap items-center gap-2 border border-gray-100 rounded-xl p-2"
              style={{ marginLeft: `${((row.depth || 1) - 1) * 1.25}rem` }}
            >
              <span className="text-xs font-mono text-gray-400 w-6 text-center">{index + 1}</span>
              <input
                type="text"
                value={row.title}
                onChange={(e) => updateRow(index, { title: e.target.value })}
                placeholder={t('toc.titlePlaceholder')}
                className="flex-1 min-w-[9rem] px-2 py-1.5 text-sm border border-gray-200 rounded-lg"
              />
              <input
                type="text"
                value={row.title_en || ''}
                onChange={(e) => updateRow(index, { title_en: e.target.value })}
                placeholder={t('toc.titleEnPlaceholder')}
                className="w-40 px-2 py-1.5 text-sm border border-gray-200 rounded-lg"
              />
              <input
                type="number"
                min="1"
                value={row.page}
                onChange={(e) => updateRow(index, { page: Number(e.target.value) || 1 })}
                aria-label={t('toc.page')}
                className="w-16 px-2 py-1.5 text-sm border border-gray-200 rounded-lg"
              />
              <div className="flex items-center gap-1">
                <button
                  type="button"
                  onClick={() => indent(index, -1)}
                  disabled={(row.depth || 1) <= 1}
                  className="px-2 py-1 text-xs border border-gray-200 rounded-md disabled:opacity-40"
                  aria-label={t('toc.outdent')}
                >
                  -
                </button>
                <button
                  type="button"
                  onClick={() => indent(index, 1)}
                  disabled={(row.depth || 1) >= MAX_DEPTH}
                  className="px-2 py-1 text-xs border border-gray-200 rounded-md disabled:opacity-40"
                  aria-label={t('toc.indent')}
                >
                  +
                </button>
                <button
                  type="button"
                  onClick={() => move(index, -1)}
                  disabled={!canMove(index, -1)}
                  className="px-2 py-1 text-xs border border-gray-200 rounded-md disabled:opacity-40"
                  aria-label={t('toc.moveUp')}
                >
                  ↑
                </button>
                <button
                  type="button"
                  onClick={() => move(index, 1)}
                  disabled={!canMove(index, 1)}
                  className="px-2 py-1 text-xs border border-gray-200 rounded-md disabled:opacity-40"
                  aria-label={t('toc.moveDown')}
                >
                  ↓
                </button>
                <button
                  type="button"
                  onClick={() => removeRow(index)}
                  className="px-2 py-1 text-xs border border-red-200 text-red-600 rounded-md hover:bg-red-50"
                  aria-label={t('delete')}
                >
                  ✕
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}

      <div className="flex gap-3 pt-2">
        <button
          type="button"
          onClick={() => saveMutation.mutate()}
          disabled={saveMutation.isPending || !dirty}
          className="px-5 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
        >
          {saveMutation.isPending ? t('loading') : t('toc.save')}
        </button>
        <button
          type="button"
          onClick={() => clearMutation.mutate()}
          disabled={clearMutation.isPending || rows.length === 0}
          className="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 disabled:opacity-60"
        >
          {t('toc.clear')}
        </button>
      </div>
    </div>
  )
}

/**
 * Turn editor rows into the payload the API expects. `parent_index` points at
 * the nearest previous entry with a smaller depth, which reproduces the nesting
 * the admin sees.
 */
function toPayload(rows) {
  return rows.map((row, index) => {
    const depth = Math.max(1, Number(row.depth) || 1)
    let parentIndex = null

    for (let i = index - 1; i >= 0; i -= 1) {
      const prevDepth = Math.max(1, Number(rows[i].depth) || 1)
      if (prevDepth < depth) {
        parentIndex = i
        break
      }
    }

    return {
      title: (row.title || '').trim(),
      title_en: (row.title_en || '').trim() || null,
      page: Math.max(1, Number(row.page) || 1),
      depth,
      parent_index: parentIndex,
    }
  })
}
