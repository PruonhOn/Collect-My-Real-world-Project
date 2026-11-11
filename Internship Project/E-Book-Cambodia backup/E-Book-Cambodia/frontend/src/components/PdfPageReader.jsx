import { useCallback, useEffect, useRef, useState } from 'react'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'

GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url
).toString()

export default function PdfPageReader({
  url,
  data,
  downloadUrl,
  title = '',
  initialPage = 1,
  onPageChange,
  onLoad,
}) {
  const [doc, setDoc] = useState(null)
  const [numPages, setNumPages] = useState(0)
  const [pageNumber, setPageNumber] = useState(initialPage)
  const [rendering, setRendering] = useState(false)
  const [error, setError] = useState('')
  const canvasRef = useRef(null)
  const wrapRef = useRef(null)
  const renderTaskRef = useRef(null)
  const touchStartRef = useRef(null)

  useEffect(() => {
    let cancelled = false
    setError('')
    setPageNumber(initialPage)

    const src = url ? url : data
    if (!src) return undefined

    const loadingTask = getDocument(src)
    loadingTask.promise
      .then((pdf) => {
        if (cancelled) {
          pdf.destroy()
          return
        }
        setDoc(pdf)
        setNumPages(pdf.numPages)
        onLoad?.(pdf.numPages)
      })
      .catch((err) => {
        if (!cancelled) {
          setError(err?.message || 'Failed to load the PDF file.')
        }
      })

    return () => {
      cancelled = true
      loadingTask.destroy()
      setDoc((current) => {
        if (current) current.destroy()
        return null
      })
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [url, data, initialPage])

  const renderPage = useCallback(
    async (number) => {
      if (!doc || !canvasRef.current || !wrapRef.current) return
      renderTaskRef.current?.cancel()
      setRendering(true)
      try {
        const page = await doc.getPage(number)
        const containerWidth = wrapRef.current.clientWidth
        const base = page.getViewport({ scale: 1 })
        const dpr = Math.min(window.devicePixelRatio || 1, 2)
        const scale = (containerWidth / base.width) * dpr
        const viewport = page.getViewport({ scale })
        const canvas = canvasRef.current
        canvas.width = viewport.width
        canvas.height = viewport.height
        canvas.style.width = `${containerWidth}px`
        canvas.style.height = `${containerWidth * (base.height / base.width)}px`

        const renderTask = page.render({
          canvasContext: canvas.getContext('2d'),
          viewport,
        })
        renderTaskRef.current = renderTask
        await renderTask.promise
      } catch (err) {
        if (err?.name !== 'RenderingCancelledException') {
          setError(err?.message || 'Failed to render this page.')
        }
      } finally {
        setRendering(false)
      }
    },
    [doc]
  )

  useEffect(() => {
    if (doc) renderPage(pageNumber)
  }, [pageNumber, renderPage, doc])

  const goTo = (target) => {
    const next = Math.min(Math.max(target, 1), numPages)
    if (next === pageNumber) return
    setPageNumber(next)
    onPageChange?.(next)
  }

  const next = () => {
    if (pageNumber < numPages) goTo(pageNumber + 1)
  }

  const prev = () => {
    if (pageNumber > 1) goTo(pageNumber - 1)
  }

  const handleClick = (e) => {
    const rect = e.currentTarget.getBoundingClientRect()
    const x = e.clientX - rect.left
    if (x > rect.width / 2) {
      next()
    } else {
      prev()
    }
  }

  const handleTouchStart = (e) => {
    touchStartRef.current = e.touches[0].clientX
  }

  const handleTouchEnd = (e) => {
    if (touchStartRef.current === null) return
    const delta = e.changedTouches[0].clientX - touchStartRef.current
    touchStartRef.current = null
    if (delta < -40) {
      next()
    } else if (delta > 40) {
      prev()
    }
  }

  if (error) {
    return (
      <div className="text-center py-8">
        <p className="text-gray-600 mb-4">{error}</p>
        <a
          href={downloadUrl || url}
          target="_blank"
          rel="noreferrer"
          className="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
          </svg>
          Open PDF
        </a>
      </div>
    )
  }

  const lastPage = numPages > 0 && pageNumber === numPages

  return (
    <div>
      <div className="relative">
        {numPages > 0 && pageNumber > 1 && (
          <button
            onClick={prev}
            aria-label="Previous page"
            className="absolute -left-4 md:-left-14 top-1/2 -translate-y-1/2 z-20 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white shadow-md border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}

        <div
          role="button"
          tabIndex={0}
          onClick={handleClick}
          onKeyDown={(e) => {
            if (e.key === 'ArrowRight') next()
            if (e.key === 'ArrowLeft') prev()
          }}
          onTouchStart={handleTouchStart}
          onTouchEnd={handleTouchEnd}
          className="relative min-h-[380px] overflow-hidden rounded-2xl shadow-inner cursor-pointer select-none bg-gray-100"
        >
          {numPages === 0 ? (
            <div className="flex items-center justify-center min-h-[380px]">
              <div className="animate-spin w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full" />
            </div>
          ) : (
            <div ref={wrapRef} className="flex justify-center items-start py-6">
              <canvas
                ref={canvasRef}
                className="shadow-md rounded-sm bg-white"
                aria-label={title}
              />
              {rendering && (
                <div className="absolute inset-0 flex items-center justify-center bg-white/50">
                  <div className="animate-spin w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full" />
                </div>
              )}
            </div>
          )}

          {numPages > 0 && (
            <span className="absolute bottom-4 right-5 z-10 text-xs text-gray-500 bg-white/80 rounded-full px-2 py-0.5">
              {pageNumber} / {numPages}
            </span>
          )}
        </div>

        {numPages > 0 && !lastPage && (
          <button
            onClick={next}
            aria-label="Next page"
            className="absolute -right-4 md:-right-14 top-1/2 -translate-y-1/2 z-20 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white shadow-md border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        )}
      </div>

      <p className="text-center text-xs text-gray-400 mt-3">
        Tap right side, swipe, or use the arrow buttons to turn each page
      </p>
    </div>
  )
}