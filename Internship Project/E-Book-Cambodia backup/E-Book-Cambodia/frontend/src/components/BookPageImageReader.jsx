import { useCallback, useEffect, useRef, useState } from 'react'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'

GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url
).toString()

const RENDER_AHEAD = 2

export default function BookPageImageReader({
  data,
  initialPage = 1,
  onPageChange,
  onLoad,
}) {
  const [doc, setDoc] = useState(null)
  const [numPages, setNumPages] = useState(0)
  const [pageIndex, setPageIndex] = useState(Math.max((initialPage || 1) - 1, 0))
  const [pageImages, setPageImages] = useState({})
  const [error, setError] = useState('')
  const touchStartRef = useRef(null)
  const renderingRef = useRef(new Set())
  const canvasCacheRef = useRef({})

  useEffect(() => {
    let cancelled = false
    setError('')
    setPageIndex(Math.max((initialPage || 1) - 1, 0))

    if (!data) return undefined

    const loadingTask = getDocument(data)
    loadingTask.promise
      .then((pdf) => {
        if (cancelled) { pdf.destroy(); return }
        setDoc(pdf)
        setNumPages(pdf.numPages)
        onLoad?.(pdf.numPages)
      })
      .catch((err) => {
        if (!cancelled) setError(err?.message || 'Failed to load the book.')
      })

    return () => {
      cancelled = true
      loadingTask.destroy()
      setDoc((c) => { if (c) c.destroy(); return null })
      renderingRef.current.clear()
      canvasCacheRef.current = {}
    }
  }, [data, initialPage])

  const renderPageToImage = useCallback(async (number) => {
    if (!doc || pageImages[number] || renderingRef.current.has(number)) return
    renderingRef.current.add(number)
    try {
      const page = await doc.getPage(number)
      const base = page.getViewport({ scale: 1 })
      const width = Math.min(base.width, 800)
      const dpr = Math.min(window.devicePixelRatio || 1, 2)
      const scale = (width / base.width) * dpr
      const viewport = page.getViewport({ scale })

      let canvas = canvasCacheRef.current[number]
      if (!canvas) {
        canvas = document.createElement('canvas')
        canvasCacheRef.current[number] = canvas
      }
      canvas.width = viewport.width
      canvas.height = viewport.height

      await page.render({
        canvasContext: canvas.getContext('2d'),
        viewport,
      }).promise

      const blob = await new Promise((resolve) =>
        canvas.toBlob(resolve, 'image/jpeg', 0.92)
      )
      if (blob) {
        const url = URL.createObjectURL(blob)
        setPageImages((prev) => {
          if (prev[number]) return prev
          return { ...prev, [number]: url }
        })
      }
    } catch (err) {
      if (err?.name !== 'RenderingCancelledException') {
        console.error('Page render error:', err)
      }
    } finally {
      renderingRef.current.delete(number)
    }
  }, [doc, pageImages])

  useEffect(() => {
    if (!doc || numPages === 0) return
    const start = Math.max(0, pageIndex - RENDER_AHEAD)
    const end = Math.min(numPages - 1, pageIndex + RENDER_AHEAD)
    for (let i = start; i <= end; i++) {
      renderPageToImage(i + 1)
    }
  }, [doc, numPages, pageIndex, renderPageToImage])

  const goTo = (target) => {
    const next = Math.min(Math.max(target, 0), numPages - 1)
    if (next === pageIndex) return
    setPageIndex(next)
    onPageChange?.(next + 1)
  }

  const next = () => { if (pageIndex < numPages - 1) goTo(pageIndex + 1) }
  const prev = () => { if (pageIndex > 0) goTo(pageIndex - 1) }

  const handleClick = (e) => {
    const rect = e.currentTarget.getBoundingClientRect()
    if (e.clientX - rect.left > rect.width / 2) next(); else prev()
  }

  const handleTouchStart = (e) => { touchStartRef.current = e.touches[0].clientX }
  const handleTouchEnd = (e) => {
    if (touchStartRef.current === null) return
    const delta = e.changedTouches[0].clientX - touchStartRef.current
    touchStartRef.current = null
    if (delta < -40) next()
    else if (delta > 40) prev()
  }

  useEffect(() => {
    const handleKey = (e) => {
      if (e.key === 'ArrowRight') next()
      if (e.key === 'ArrowLeft') prev()
    }
    window.addEventListener('keydown', handleKey)
    return () => window.removeEventListener('keydown', handleKey)
  })

  if (error) {
    return (
      <div className="text-center py-8">
        <p className="text-gray-600">{error}</p>
      </div>
    )
  }

  if (numPages === 0) {
    return (
      <div className="flex items-center justify-center min-h-[380px]">
        <div className="animate-spin w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full" />
      </div>
    )
  }

  const lastPage = pageIndex === numPages - 1

  return (
    <div>
      <div className="relative">
        {pageIndex > 0 && (
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
          onKeyDown={(e) => { if (e.key === 'ArrowRight') next(); if (e.key === 'ArrowLeft') prev() }}
          onTouchStart={handleTouchStart}
          onTouchEnd={handleTouchEnd}
          className="relative min-h-[380px] overflow-hidden rounded-2xl shadow-inner cursor-pointer select-none bg-gray-100"
        >
          <div
            className="flex flex-nowrap items-start transition-transform duration-500 ease-in-out will-change-transform"
            style={{ transform: `translateX(-${pageIndex * 100}%)` }}
          >
            {Array.from({ length: numPages }, (_, i) => (
              <div key={i} className="w-full shrink-0 flex justify-center items-start py-[3rem] px-[3rem] min-h-[380px]">
                {pageImages[i + 1] ? (
                  <img
                    src={pageImages[i + 1]}
                    alt={`Page ${i + 1}`}
                    className="max-w-full max-h-[70vh] object-contain shadow-md rounded-sm bg-white"
                    draggable={false}
                  />
                ) : (
                  <div className="flex items-center justify-center w-full min-h-[380px]">
                    <div className="animate-spin w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full" />
                  </div>
                )}
              </div>
            ))}
          </div>

          <span className="absolute bottom-4 right-5 z-10 text-xs text-gray-500 bg-white/80 rounded-full px-2 py-0.5">
            {pageIndex + 1} / {numPages}
          </span>
        </div>

        {!lastPage && (
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

      <div className="mt-4 flex items-center justify-center gap-1.5 flex-wrap">
        {Array.from({ length: numPages }, (_, i) => (
          <button
            key={i}
            aria-label={`Go to page ${i + 1}`}
            onClick={() => goTo(i)}
            className={`w-2 h-2 rounded-full transition-all duration-300 ${
              i === pageIndex ? 'bg-indigo-600 w-5' : 'bg-gray-300 hover:bg-gray-400'
            }`}
          />
        ))}
      </div>

      <p className="text-center text-xs text-gray-400 mt-2">
        Tap right side, swipe, or use arrow keys to turn each page
      </p>
    </div>
  )
}
