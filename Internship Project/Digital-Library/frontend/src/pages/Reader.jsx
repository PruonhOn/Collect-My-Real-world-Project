import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useParams, useNavigate } from 'react-router-dom'
import * as pdfjsLib from 'pdfjs-dist'
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker

export default function Reader() {
  const { t } = useLanguage()
  const { id } = useParams()
  const navigate = useNavigate()
  const { isAuthenticated } = useAuth()
  const [book, setBook] = useState(null)
  const [pdfDoc, setPdfDoc] = useState(null)
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(0)
  const [loading, setLoading] = useState(true)
  const [rendering, setRendering] = useState(false)
  const [accessDenied, setAccessDenied] = useState(false)
  const [loginRequired, setLoginRequired] = useState(false)
  const [bookmarks, setBookmarks] = useState([])
  const [progress, setProgress] = useState(0)
  const [pageInput, setPageInput] = useState('1')
  const [addNote, setAddNote] = useState('')
  const [addingBookmark, setAddingBookmark] = useState(false)
  const canvasRef = useRef(null)
  const renderTaskRef = useRef(null)
  const { toast } = useToast()

  const renderPage = useCallback(async (pageNum) => {
    if (!pdfDoc) return
    setRendering(true)
    if (renderTaskRef.current) {
      renderTaskRef.current.cancel()
    }
    try {
      const pdfPage = await pdfDoc.getPage(pageNum)
      const viewport = pdfPage.getViewport({ scale: 1.5 })
      const canvas = canvasRef.current
      if (!canvas) return
      const context = canvas.getContext('2d')
      const devicePixelRatio = window.devicePixelRatio || 1
      canvas.width = viewport.width * devicePixelRatio
      canvas.height = viewport.height * devicePixelRatio
      canvas.style.width = `${viewport.width}px`
      canvas.style.height = `${viewport.height}px`
      context.scale(devicePixelRatio, devicePixelRatio)
      const renderTask = pdfPage.render({ canvasContext: context, viewport })
      renderTaskRef.current = renderTask
      await renderTask.promise
    } catch (err) {
      if (err.name !== 'RenderingCancelledException') {
        console.error(err)
      }
    } finally {
      setRendering(false)
    }
  }, [pdfDoc])

  useEffect(() => {
    renderPage(page)
  }, [page, renderPage])

  const loadDoc = useCallback(async (arrayBuffer) => {
    const doc = await pdfjsLib.getDocument({
      data: arrayBuffer.slice(0),
    }).promise
    setPdfDoc(doc)
    setTotalPages(doc.numPages)
    return doc
  }, [])

  useEffect(() => {
    let cancelled = false
    setLoading(true)

    client
      .get(`/reader/${id}`)
      .then(async ({ data }) => {
        if (cancelled) return
        setBook(data.book)
        setProgress(data.progress?.progress || 0)
        const startPage = data.progress?.last_page || 1
        setPage(startPage)
        setPageInput(String(startPage))
        setBookmarks(data.bookmarks || [])

        const resp = await client.get(`/reader/${id}/stream`, { responseType: 'arraybuffer' })
        if (cancelled) return
        const doc = await loadDoc(resp.data)
        if (cancelled) return
        const docPage = Math.min(startPage, doc.numPages)
        setPage(docPage)
        setPageInput(String(docPage))
      })
      .catch((err) => {
        if (!cancelled && err.response?.status === 403) setAccessDenied(true)
        else if (!cancelled && err.response?.status === 401) setLoginRequired(true)
        else if (!cancelled) toast(t('errLoadReader'), 'error')
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => {
      cancelled = true
      if (renderTaskRef.current) renderTaskRef.current.cancel()
    }
  }, [id, loadDoc, toast, t])

  const goToPage = useCallback(
    async (target) => {
      const clamped = Math.min(Math.max(1, Number(target) || 1), totalPages)
      setPage(clamped)
      setPageInput(String(clamped))
      const pct = totalPages > 0 ? Math.round((clamped / totalPages) * 100) : 0
      setProgress(pct)
      if (!isAuthenticated) return
      try {
        await client.post(`/reader/${id}/progress`, {
          last_page: clamped,
          total_pages: totalPages,
        })
      } catch {
        /* non-blocking */
      }
    },
    [id, totalPages, isAuthenticated],
  )

  const handlePageSubmit = (e) => {
    e.preventDefault()
    goToPage(pageInput)
  }

  const addBookmark = async () => {
    if (!isAuthenticated) {
      toast(t('loginToBookmark'), 'info')
      navigate('/login')
      return
    }
    setAddingBookmark(true)
    try {
      const { data } = await client.post(`/reader/${id}/bookmarks`, {
        page,
        note: addNote || null,
      })
      setBookmarks((prev) => {
        const exists = prev.some((b) => b.page === data.bookmark.page)
        return exists ? prev : [...prev, data.bookmark].sort((a, b) => a.page - b.page)
      })
      setAddNote('')
      toast(t('bookmarkAdded'))
    } catch {
      toast(t('errAddBookmark'), 'error')
    } finally {
      setAddingBookmark(false)
    }
  }

  const removeBookmark = async (bookmark) => {
    if (!isAuthenticated) return
    try {
      await client.delete(`/reader/bookmarks/${bookmark.id}`)
      setBookmarks((prev) => prev.filter((b) => b.id !== bookmark.id))
      toast(t('bookmarkRemoved'), 'info')
    } catch {
      toast(t('errRemoveBookmark'), 'error')
    }
  }

  if (accessDenied) {
    return (
      <ErrorState
        title={t('noAccess')}
        message={t('noAccessMsg')}
        action={<Link to={`/books/${id}`} className="px-4 py-2 bg-indigo-600 text-white rounded-lg">{t('viewBookInfo')}</Link>}
      />
    )
  }

  if (loginRequired) {
    return (
      <ErrorState
        title={t('loginRequired')}
        message={t('loginRequiredMsg')}
        action={
          <div className="flex gap-3">
            <Link to="/login" className="px-4 py-2 bg-indigo-600 text-white rounded-lg">{t('login')}</Link>
            <Link to={`/books/${id}`} className="px-4 py-2 border border-gray-300 rounded-lg">{t('viewBookInfo')}</Link>
          </div>
        }
      />
    )
  }

  if (loading) return <Spinner label={t('loadingBook')} />

  if (!pdfDoc) {
    return <ErrorState title={t('cannotLoadDoc')} message={t('cannotLoadDocMsg')} />
  }

  return (
    <div className="min-h-[90vh] bg-gray-100 flex flex-col">
      {/* Toolbar */}
      <div className="bg-white shadow-sm sticky top-16 z-40">
        <div className="max-w-5xl mx-auto px-4 py-2 flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-3 min-w-0">
            <button
              onClick={() => window.history.back()}
              className="text-gray-600 hover:text-gray-900 p-1"
              aria-label={t('back')}
            >
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div className="min-w-0">
              <h1 className="font-semibold text-gray-900 truncate">{book?.title}</h1>
              <div className="flex items-center gap-2 text-xs text-gray-500">
                <span>{t('readingLabel')} {progress}%</span>
                <div className="w-24 bg-gray-200 rounded-full h-1.5 overflow-hidden">
                  <div className="bg-indigo-600 h-1.5 rounded-full transition-all duration-500 ease-out" style={{ width: `${progress}%` }} />
                </div>
              </div>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button
              onClick={() => page > 1 && goToPage(page - 1)}
              disabled={page <= 1}
              className="px-3 py-1.5 border border-gray-300 rounded-lg text-sm disabled:opacity-40 hover:bg-gray-50"
            >
              ←
            </button>
            <form onSubmit={handlePageSubmit} className="flex items-center gap-1">
              <input
                type="number"
                value={pageInput}
                min="1"
                max={totalPages}
                onChange={(e) => setPageInput(e.target.value)}
                className="w-16 px-2 py-1 border border-gray-300 rounded-lg text-center text-sm"
              />
              <span className="text-sm text-gray-500">/ {totalPages}</span>
            </form>
            <button
              onClick={() => page < totalPages && goToPage(page + 1)}
              disabled={page >= totalPages}
              className="px-3 py-1.5 border border-gray-300 rounded-lg text-sm disabled:opacity-40 hover:bg-gray-50"
            >
              →
            </button>
            <div className="w-px h-6 bg-gray-200 mx-1 hidden sm:block" />
            <button
              onClick={addBookmark}
              disabled={addingBookmark}
              className="px-3 py-1.5 bg-amber-500 text-white rounded-lg text-sm hover:bg-amber-600 disabled:opacity-50"
              title={t('addBookmark')}
            >
              🔖
            </button>
          </div>
        </div>
      </div>

      {/* Guest banner */}
      {!isAuthenticated && (
        <div className="bg-indigo-50 border-b border-indigo-100">
          <div className="max-w-5xl mx-auto px-4 py-2 flex items-center justify-between">
            <p className="text-sm text-indigo-700">
              {t('guestReaderHint')}
            </p>
            <Link to="/login" className="text-sm font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap ml-4">
              {t('login')} →
            </Link>
          </div>
        </div>
      )}

      {/* Body */}
      <div className="flex-1 max-w-5xl mx-auto w-full px-4 py-6 flex flex-col md:flex-row gap-6">
        {/* PDF canvas */}
        <div className="flex-1 flex flex-col items-center bg-white rounded-xl shadow-sm p-4">
          {rendering && (
            <div className="py-2 text-sm text-gray-400">{t('rendering')}</div>
          )}
          <canvas ref={canvasRef} className="max-w-full shadow-lg" />
          <div className="mt-4 flex gap-3">
            <button
              onClick={() => page > 1 && goToPage(page - 1)}
              disabled={page <= 1}
              className="px-5 py-2 border border-gray-300 rounded-lg text-sm font-medium disabled:opacity-40 hover:bg-gray-50"
            >
              ← {t('prev')}
            </button>
            <span className="px-4 py-2 text-sm text-gray-600 self-center">
              {page} / {totalPages}
            </span>
            <button
              onClick={() => page < totalPages && goToPage(page + 1)}
              disabled={page >= totalPages}
              className="px-5 py-2 border border-gray-300 rounded-lg text-sm font-medium disabled:opacity-40 hover:bg-gray-50"
            >
              {t('next')} →
            </button>
          </div>
        </div>

        {/* Bookmarks sidebar */}
        <div className="w-full md:w-72 shrink-0">
          <div className="bg-white rounded-xl shadow-sm p-4 sticky top-32">
            <h2 className="font-semibold text-gray-900 mb-3">{t('bookmarks')}</h2>
            <div className="flex gap-2 mb-3">
              <input
                value={addNote}
                onChange={(e) => setAddNote(e.target.value)}
                placeholder={t('noteOptional')}
                className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm"
              />
              <button
                onClick={addBookmark}
                disabled={addingBookmark}
                className="px-3 py-2 bg-amber-500 text-white rounded-lg text-sm disabled:opacity-50"
              >
                🔖
              </button>
            </div>
            <div className="space-y-2 max-h-96 overflow-y-auto">
              {bookmarks.length === 0 ? (
                <p className="text-sm text-gray-400">{t('noBookmarksYet')}</p>
              ) : (
                bookmarks.map((b) => (
                  <div key={b.id} className="border border-gray-100 rounded-lg p-3 hover:bg-gray-50">
                    <div className="flex justify-between items-center">
                      <button onClick={() => goToPage(b.page)} className="text-indigo-600 text-sm font-medium hover:underline">
                        {t('pageWord')} {b.page}
                      </button>
                      <button onClick={() => removeBookmark(b)} className="text-red-400 hover:text-red-600 text-sm" title={t('remove')}>
                        ×
                      </button>
                    </div>
                    {b.note && <p className="text-xs text-gray-500 mt-1">{b.note}</p>}
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
