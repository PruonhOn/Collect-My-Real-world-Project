import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { addBookmark, deleteBookmark, getBookmarks } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import AiAssistantPanel from '../ai/AiAssistantPanel'
import { useColumnPagination } from '../../hooks/useColumnPagination'
import { useReaderSettings } from '../../hooks/useReaderSettings'
import {
  addAnnotation,
  createAnnotation,
  loadAnnotations,
  removeAnnotation,
  saveAnnotations,
} from '../../utils/readerAnnotations'
import {
  extractReferences,
  parseSections,
  sectionText,
} from '../../utils/readerContent'
import { bodyFontSize } from '../../utils/readerSettings'
import RichText from './RichText'
import ReaderTopBar from './ReaderTopBar'
import ReaderBottomBar from './ReaderBottomBar'
import ReaderDrawer from './ReaderDrawer'
import TocPanel from './TocPanel'
import NotesPanel from './NotesPanel'
import SettingsPanel from './SettingsPanel'
import SearchPanel from './SearchPanel'
import HelpPanel from './HelpPanel'
import ScanSheet from './ScanSheet'
import SelectionToolbar from './SelectionToolbar'
import { CheckIcon, CloseIcon, LockIcon } from './ReaderIcons'
import './reader.css'
import 'katex/dist/katex.min.css'

const COLUMN_GAP = 64
const MIN_ZOOM = 0.5
const MAX_ZOOM = 3
const ZOOM_STEP = 0.25
const HINT_KEY = 'pi-reader-hint'

const clamp = (value, min, max) => Math.min(max, Math.max(min, value))
const roundZoom = (value) => Math.round(clamp(value, MIN_ZOOM, MAX_ZOOM) * 100) / 100

function sharedPageFromUrl() {
  if (typeof window === 'undefined') return null
  const value = Number(new URLSearchParams(window.location.search).get('page'))
  return Number.isFinite(value) && value > 0 ? Math.floor(value) : null
}

/**
 * Reflowable reader for text books.
 *
 * PDF books deliberately stay on the existing renderer: page-exact PDF display
 * is its own problem, and replacing it would risk changing what readers see.
 * This component owns the full reflow experience, including pagination, TOC,
 * search, highlights, settings, AI context and the in-reader unlock CTA.
 */
export default function ReflowBookReader({
  content,
  title,
  author,
  viewCount,
  viewCountLabel,
  readerName,
  initialPage = 1,
  initialTotal = 0,
  onPageChange,
  onClose,
  bookId,
  toc = [],
  locale,
  onLockedNavigate,
  previewMode = false,
  previewGate = 'preview',
  vipRequired = false,
  onUnlock,
  badge,
  sourcePageCount,
  previewPageCount,
}) {
  const { t } = useLanguage()
  const { user, isAuthenticated } = useAuth()
  const queryClient = useQueryClient()

  const [settings, updateSettings] = useReaderSettings({ user, isAuthenticated })
  const sections = useMemo(
    () => parseSections(content, { bookTitle: title }),
    [content, title],
  )
  const references = useMemo(() => extractReferences(sections), [sections])
  const isKhmer = useMemo(
    () => /[\u1780-\u17FF]/.test(String(content || '').slice(0, 4000)),
    [content],
  )

  // Portalled UI (drawers, scan sheet, selection toolbar) lives outside this
  // component, so mirror the theme tokens on <html> where they can inherit.
  useEffect(() => {
    const root = document.documentElement
    const previousTheme = root.dataset.rdrTheme
    const previousSize = root.style.getPropertyValue('--rdr-font-size')
    const previousLine = root.style.getPropertyValue('--rdr-line-height')
    root.dataset.rdrTheme = settings.theme
    root.style.setProperty('--rdr-font-size', `${bodyFontSize(settings, isKhmer)}px`)
    root.style.setProperty('--rdr-line-height', String(settings.lineHeight))
    return () => {
      if (previousTheme) root.dataset.rdrTheme = previousTheme
      else delete root.dataset.rdrTheme
      if (previousSize) root.style.setProperty('--rdr-font-size', previousSize)
      else root.style.removeProperty('--rdr-font-size')
      if (previousLine) root.style.setProperty('--rdr-line-height', previousLine)
      else root.style.removeProperty('--rdr-line-height')
    }
  }, [isKhmer, settings])

  const [viewportWidth, setViewportWidth] = useState(() =>
    typeof window === 'undefined' ? 1280 : window.innerWidth,
  )
  const visibleCount =
    settings.mode === 'spread' && viewportWidth >= 1024 ? 2 : 1
  const isScroll = settings.mode === 'scroll'

  const viewportRef = useRef(null)
  const contentRef = useRef(null)
  const stageRef = useRef(null)
  const scrollInnerRef = useRef(null)
  const initialScrollRef = useRef(false)

  const sharedPage = useMemo(sharedPageFromUrl, [])
  const effectiveInitial = sharedPage || initialPage

  const {
    page: columnPage,
    pageCount: columnPageCount,
    ready: columnReady,
    goToPage: columnGoToPage,
    goNext: columnGoNext,
    goPrev: columnGoPrev,
  } = useColumnPagination({
    viewportRef,
    contentRef,
    enabled: !isScroll && sections.length > 0,
    visibleCount,
    contentKey: `${settings.fontSize}|${settings.font}|${settings.lineHeight}|${sections.length}|${String(content || '').length}`,
    gap: COLUMN_GAP,
    initialPage: effectiveInitial,
    initialTotal: sharedPage ? 0 : initialTotal,
  })

  const scrollPageCount = Math.max(1, sections.length + (previewMode ? 1 : 0))
  const [scrollPage, setScrollPage] = useState(() => {
    const saved = clamp(Number(effectiveInitial) || 1, 1, scrollPageCount)
    const total = Number(initialTotal) || 0
    if (total > 1 && sections.length > 1) {
      const ratio = clamp((saved - 1) / (total - 1), 0, 1)
      return clamp(Math.round(ratio * (sections.length - 1)) + 1, 1, scrollPageCount)
    }
    return saved
  })
  const [activeSection, setActiveSection] = useState(() =>
    isScroll ? scrollPage - 1 : 0,
  )

  const page = isScroll ? scrollPage : columnPage
  const pageCount = isScroll ? scrollPageCount : columnPageCount
  const ready = isScroll ? sections.length > 0 : columnReady

  const [drawer, setDrawer] = useState(null)
  const [scanOpen, setScanOpen] = useState(false)
  const [aiOpen, setAiOpen] = useState(false)
  const [selection, setSelection] = useState(null)
  const [citation, setCitation] = useState(null)
  const [toast, setToast] = useState('')
  const [barsVisible, setBarsVisible] = useState(true)
  const [isFullscreen, setIsFullscreen] = useState(false)
  const [zoom, setZoom] = useState(1)
  const [annotations, setAnnotations] = useState(() =>
    loadAnnotations(user?.id, bookId),
  )
  const [aiSeed, setAiSeed] = useState({ question: '', nonce: 0 })
  const [hintVisible, setHintVisible] = useState(false)

  const hideTimerRef = useRef(0)
  const toastTimerRef = useRef(0)
  const onPageChangeRef = useRef(onPageChange)
  const reportedRef = useRef('')
  const previousModeRef = useRef(settings.mode)
  const hintTimerRef = useRef(0)

  useEffect(() => {
    onPageChangeRef.current = onPageChange
  }, [onPageChange])

  useEffect(() => {
    const onResize = () => setViewportWidth(window.innerWidth)
    window.addEventListener('resize', onResize)
    window.addEventListener('orientationchange', onResize)
    return () => {
      window.removeEventListener('resize', onResize)
      window.removeEventListener('orientationchange', onResize)
    }
  }, [])

  useEffect(() => {
    setAnnotations(loadAnnotations(user?.id, bookId))
  }, [bookId, user?.id])

  const notify = useCallback((message) => {
    window.clearTimeout(toastTimerRef.current)
    setToast(message)
    toastTimerRef.current = window.setTimeout(() => setToast(''), 2600)
  }, [])

  useEffect(
    () => () => {
      window.clearTimeout(toastTimerRef.current)
      window.clearTimeout(hideTimerRef.current)
      window.clearTimeout(hintTimerRef.current)
    },
    [],
  )

  const pokeBars = useCallback(() => {
    setBarsVisible(true)
    window.clearTimeout(hideTimerRef.current)
    hideTimerRef.current = window.setTimeout(() => setBarsVisible(false), 3200)
  }, [])

  useEffect(() => {
    if (drawer || aiOpen || scanOpen || selection) {
      window.clearTimeout(hideTimerRef.current)
      setBarsVisible(true)
      return
    }
    pokeBars()
  }, [aiOpen, drawer, pokeBars, scanOpen, selection])

  // The gesture hint is shown once per reader, then never again.
  useEffect(() => {
    try {
      if (window.localStorage.getItem(HINT_KEY)) return undefined
      setHintVisible(true)
      hintTimerRef.current = window.setTimeout(() => setHintVisible(false), 8000)
    } catch {
      // Storage may be disabled; the hint is optional.
    }
    return () => window.clearTimeout(hintTimerRef.current)
  }, [])

  const dismissHint = () => {
    setHintVisible(false)
    try {
      window.localStorage.setItem(HINT_KEY, '1')
    } catch {
      // Ignore storage failures.
    }
  }

  // ---------------------------------------------------------------------
  // Bookmarks
  // ---------------------------------------------------------------------
  const bookmarksQuery = useQuery({
    queryKey: ['reader-bookmarks', bookId],
    queryFn: () => getBookmarks(bookId),
    enabled: !!bookId && isAuthenticated,
  })
  const bookmarks = bookmarksQuery.data?.data?.bookmarks || []
  const currentBookmark = bookmarks.find(
    (bookmark) => Number(bookmark.page) === Number(page),
  )

  const invalidateBookmarks = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ['reader-bookmarks', bookId] })
  }, [bookId, queryClient])

  const addBookmarkMutation = useMutation({
    mutationFn: (data) => addBookmark(bookId, data),
    onSuccess: () => {
      invalidateBookmarks()
      notify(t('reader.bookmarkSaved'))
    },
    onError: () => notify(t('reader.bookmarkFailed')),
  })

  const removeBookmarkMutation = useMutation({
    mutationFn: (id) => deleteBookmark(id),
    onSuccess: () => {
      invalidateBookmarks()
      notify(t('reader.bookmarkRemoved'))
    },
    onError: () => notify(t('reader.bookmarkFailed')),
  })

  const toggleBookmark = useCallback(() => {
    if (!isAuthenticated) {
      notify(t('reader.signInBookmarks'))
      return
    }
    if (currentBookmark) {
      removeBookmarkMutation.mutate(currentBookmark.id)
    } else {
      addBookmarkMutation.mutate({ page })
    }
  }, [
    addBookmarkMutation,
    currentBookmark,
    isAuthenticated,
    notify,
    page,
    removeBookmarkMutation,
    t,
  ])

  // ---------------------------------------------------------------------
  // Page position and progress
  // ---------------------------------------------------------------------
  const scrollToScrollSection = useCallback(
    (sectionIndex, behavior = 'smooth') => {
      const stage = stageRef.current
      if (!stage) return
      const selector =
        sectionIndex >= sections.length
          ? '[data-paywall]'
          : `[data-section-index="${sectionIndex}"]`
      const target = stage.querySelector(selector)
      if (!target) return
      const top =
        (target.getBoundingClientRect().top - stage.getBoundingClientRect().top) /
          Math.max(0.01, zoom) +
        stage.scrollTop -
        18
      stage.scrollTo({ top: Math.max(0, top), behavior })
    },
    [sections.length, zoom],
  )

  useEffect(() => {
    if (!isScroll || initialScrollRef.current || !sections.length) return undefined
    initialScrollRef.current = true
    const frame = requestAnimationFrame(() =>
      scrollToScrollSection(scrollPage - 1, 'auto'),
    )
    return () => cancelAnimationFrame(frame)
    // Restore the saved scroll position once, on first mount.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isScroll, sections.length])

  const goToPage = useCallback(
    (next, options = {}) => {
      if (isScroll) {
        const target = clamp(Math.round(Number(next) || 1), 1, scrollPageCount)
        setScrollPage(target)
        setSelection(null)
        scrollToScrollSection(target - 1)
        return
      }
      columnGoToPage(next, options)
      setSelection(null)
    },
    [columnGoToPage, isScroll, scrollPageCount, scrollToScrollSection],
  )

  const goNext = useCallback(() => {
    if (isScroll) goToPage(page + 1)
    else columnGoNext()
  }, [columnGoNext, goToPage, isScroll, page])

  const goPrev = useCallback(() => {
    if (isScroll) goToPage(page - 1)
    else columnGoPrev()
  }, [columnGoPrev, goToPage, isScroll, page])

  useEffect(() => {
    // Preview pagination covers only the free pages, so saving it would make a
    // later full-book resume jump to the wrong place. Keep the last full-book
    // position instead and start the preview where the reader left off only
    // when that position is inside the preview.
    if (!ready || previewMode) return
    const key = `${page}/${pageCount}`
    if (reportedRef.current === key) return
    reportedRef.current = key
    onPageChangeRef.current?.(page, pageCount)
  }, [page, pageCount, previewMode, ready])

  useEffect(() => {
    if (!ready) return
    const url = new URL(window.location.href)
    if (page > 1) url.searchParams.set('page', String(page))
    else url.searchParams.delete('page')
    window.history.replaceState({}, '', url)
  }, [page, ready])

  // Which source section is on screen? Drives the chapter label, TOC marker,
  // AI context and scan view.
  useEffect(() => {
    if (!sections.length) return undefined
    if (isScroll) {
      setActiveSection(clamp(scrollPage - 1, 0, sections.length - 1))
      return undefined
    }
    if (!columnReady) return undefined

    const frame = requestAnimationFrame(() => {
      const viewport = viewportRef.current
      const flow = contentRef.current
      if (!viewport || !flow) return
      const left = viewport.getBoundingClientRect().left
      let current = 0
      flow.querySelectorAll('[data-section-index]').forEach((node) => {
        if (node.getBoundingClientRect().left <= left + 8) {
          current = Number(node.dataset.sectionIndex)
        }
      })
      setActiveSection(current)
    })

    return () => cancelAnimationFrame(frame)
  }, [columnPage, columnReady, isScroll, scrollPage, sections.length])

  // Scroll mode: keep the page counter and chapter label in sync with the
  // section at the top of the viewport.
  useEffect(() => {
    if (!isScroll) return undefined
    const stage = stageRef.current
    if (!stage) return undefined

    let frame = 0
    const onScroll = () => {
      cancelAnimationFrame(frame)
      frame = requestAnimationFrame(() => {
        const nodes = stage.querySelectorAll('[data-section-index]')
        const top = stage.getBoundingClientRect().top + 72
        let current = 0
        nodes.forEach((node) => {
          if (node.getBoundingClientRect().top <= top) {
            current = Number(node.dataset.sectionIndex)
          }
        })
        const atBottom =
          stage.scrollTop + stage.clientHeight >= stage.scrollHeight - 24
        setScrollPage(atBottom && previewMode ? scrollPageCount : current + 1)
      })
    }

    stage.addEventListener('scroll', onScroll, { passive: true })
    return () => {
      cancelAnimationFrame(frame)
      stage.removeEventListener('scroll', onScroll)
    }
  }, [isScroll, previewMode, scrollPageCount])

  // Switching layouts should keep the reader near the same section.
  useEffect(() => {
    const previous = previousModeRef.current
    if (previous === settings.mode) return
    previousModeRef.current = settings.mode

    if (settings.mode === 'scroll') {
      const target = clamp(activeSection + 1, 1, scrollPageCount)
      setScrollPage(target)
      requestAnimationFrame(() => scrollToScrollSection(activeSection))
    } else if (previous === 'scroll') {
      requestAnimationFrame(() =>
        columnGoToPage(pageForSection(activeSection)),
      )
    }
    // Layout changes are the only trigger.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [settings.mode])

  // ---------------------------------------------------------------------
  // Navigation helpers
  // ---------------------------------------------------------------------
  const pageForSection = useCallback(
    (sectionIndex) => {
      if (isScroll) return clamp(sectionIndex + 1, 1, scrollPageCount)
      const viewport = viewportRef.current
      const flow = contentRef.current
      if (!viewport || !flow) return 1
      const node = flow.querySelector(`[data-section-index="${sectionIndex}"]`)
      if (!node) return 1
      const step = (viewport.clientWidth + COLUMN_GAP) / Math.max(1, visibleCount)
      const offset =
        (node.getBoundingClientRect().left - flow.getBoundingClientRect().left) /
        Math.max(0.01, zoom)
      const column = Math.max(0, Math.floor(offset / step))
      return Math.max(1, Math.floor(column / visibleCount) + 1)
    },
    [isScroll, scrollPageCount, visibleCount, zoom],
  )

  const goToSection = useCallback(
    (sectionIndex) => {
      const target = clamp(sectionIndex, 0, Math.max(0, sections.length - 1))
      if (isScroll) {
        goToPage(target + 1)
      } else {
        columnGoToPage(pageForSection(target))
      }
      setDrawer(null)
      setSelection(null)
    },
    [columnGoToPage, goToPage, isScroll, pageForSection, sections.length],
  )

  // ---------------------------------------------------------------------
  // Selection, highlights and notes
  // ---------------------------------------------------------------------
  const captureSelection = useCallback(() => {
    const browserSelection = window.getSelection()
    if (
      !browserSelection ||
      browserSelection.isCollapsed ||
      !browserSelection.rangeCount
    ) {
      setSelection(null)
      return
    }

    const quote = browserSelection.toString().replace(/\s+/g, ' ').trim()
    if (quote.length < 2) {
      setSelection(null)
      return
    }

    const range = browserSelection.getRangeAt(0)
    const node =
      range.commonAncestorContainer.nodeType === 1
        ? range.commonAncestorContainer
        : range.commonAncestorContainer.parentElement
    const sectionNode = node?.closest?.('[data-section-index]')
    const textContainer = contentRef.current || scrollInnerRef.current
    if (!sectionNode || !textContainer?.contains(sectionNode)) {
      setSelection(null)
      return
    }

    const rect = range.getBoundingClientRect()
    if (!rect.width && !rect.height) return

    setSelection({
      quote: quote.slice(0, 600),
      section: Number(sectionNode.dataset.sectionIndex) || 0,
      rect: {
        left: rect.left,
        top: rect.top,
        right: rect.right,
        bottom: rect.bottom,
        width: rect.width,
        height: rect.height,
      },
    })
  }, [])

  const persistAnnotations = useCallback(
    (next) => {
      setAnnotations(next)
      saveAnnotations(user?.id, bookId, next)
    },
    [bookId, user?.id],
  )

  const clearBrowserSelection = () => {
    try {
      window.getSelection()?.removeAllRanges()
    } catch {
      // Ignore browsers that refuse while a selection is being changed.
    }
  }

  const handleHighlight = useCallback(
    (color) => {
      if (!selection) return
      const annotation = createAnnotation({
        quote: selection.quote,
        color,
        section: selection.section,
        page,
      })
      if (annotation) {
        persistAnnotations(addAnnotation(user?.id, bookId, annotation))
        notify(t('reader.highlightSaved'))
      }
      setSelection(null)
      clearBrowserSelection()
    },
    [bookId, notify, page, persistAnnotations, selection, t, user?.id],
  )

  const handleNote = useCallback(
    (note) => {
      if (!selection) return
      const annotation = createAnnotation({
        quote: selection.quote,
        note,
        color: 'yellow',
        section: selection.section,
        page,
      })
      if (annotation) {
        persistAnnotations(addAnnotation(user?.id, bookId, annotation))
        notify(t('reader.noteSaved'))
      }
      setSelection(null)
      clearBrowserSelection()
    },
    [bookId, notify, page, persistAnnotations, selection, t, user?.id],
  )

  const copySelection = useCallback(async () => {
    if (!selection) return
    try {
      await navigator.clipboard.writeText(selection.quote)
      notify(t('reader.copied'))
    } catch {
      notify(t('reader.copyFailed'))
    }
    setSelection(null)
  }, [notify, selection, t])

  const askAboutSelection = useCallback(() => {
    if (!selection) return
    setAiSeed({
      question: `${t('reader.explainPassage')} ${selection.quote}`.slice(0, 480),
      nonce: Date.now(),
    })
    setAiOpen(true)
    setSelection(null)
    clearBrowserSelection()
  }, [selection, t])

  const deleteAnnotation = (id) => {
    persistAnnotations(removeAnnotation(user?.id, bookId, id))
    notify(t('reader.highlightRemoved'))
  }

  // ---------------------------------------------------------------------
  // Fullscreen, zoom and keyboard
  // ---------------------------------------------------------------------
  useEffect(() => {
    const onChange = () => setIsFullscreen(!!document.fullscreenElement)
    document.addEventListener('fullscreenchange', onChange)
    return () => document.removeEventListener('fullscreenchange', onChange)
  }, [])

  const toggleFullscreen = useCallback(async () => {
    try {
      if (document.fullscreenElement) await document.exitFullscreen()
      else await document.documentElement.requestFullscreen()
    } catch {
      notify(t('reader.fullscreenFailed'))
    }
  }, [notify, t])

  const zoomIn = useCallback(() => setZoom((value) => roundZoom(value + ZOOM_STEP)), [])
  const zoomOut = useCallback(() => setZoom((value) => roundZoom(value - ZOOM_STEP)), [])
  const resetZoom = useCallback(() => setZoom(1), [])

  const closeOverlays = useCallback(() => {
    if (selection) {
      setSelection(null)
      clearBrowserSelection()
      return true
    }
    if (citation) {
      setCitation(null)
      return true
    }
    if (scanOpen) {
      setScanOpen(false)
      return true
    }
    if (aiOpen) {
      setAiOpen(false)
      return true
    }
    if (drawer) {
      setDrawer(null)
      return true
    }
    return false
  }, [aiOpen, citation, drawer, scanOpen, selection])

  useEffect(() => {
    const onKeyDown = (event) => {
      const target = event.target
      if (
        target instanceof HTMLElement &&
        (target.tagName === 'INPUT' ||
          target.tagName === 'TEXTAREA' ||
          target.tagName === 'SELECT' ||
          target.isContentEditable)
      ) {
        return
      }
      if (event.metaKey || event.ctrlKey || event.altKey) return

      pokeBars()
      let handled = true

      switch (event.key) {
        case 'ArrowRight':
        case 'PageDown':
          if (event.shiftKey) goPrev()
          else goNext()
          break
        case 'ArrowLeft':
        case 'PageUp':
          if (event.shiftKey) goNext()
          else goPrev()
          break
        case ' ':
          if (event.shiftKey) goPrev()
          else goNext()
          break
        case 'Home':
          goToPage(1)
          break
        case 'End':
          goToPage(pageCount)
          break
        case '+':
        case '=':
          zoomIn()
          break
        case '-':
        case '_':
          zoomOut()
          break
        case 'f':
        case 'F':
          toggleFullscreen()
          break
        case 't':
        case 'T':
          setDrawer('toc')
          break
        case '/':
          setDrawer('search')
          break
        case '?':
          setDrawer('help')
          break
        case 'Escape':
          handled = closeOverlays()
          break
        default:
          handled = false
      }

      if (handled) event.preventDefault()
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [
    closeOverlays,
    goNext,
    goPrev,
    goToPage,
    pageCount,
    pokeBars,
    toggleFullscreen,
    zoomIn,
    zoomOut,
  ])

  // ---------------------------------------------------------------------
  // Touch and pointer gestures
  // ---------------------------------------------------------------------
  const touchRef = useRef(null)

  const onTouchStart = (event) => {
    if (isScroll || event.touches.length !== 1) return
    const touch = event.touches[0]
    touchRef.current = { x: touch.clientX, y: touch.clientY, time: Date.now() }
  }

  const onTouchEnd = (event) => {
    const start = touchRef.current
    touchRef.current = null
    if (!start || isScroll) return
    if (window.getSelection()?.toString()) return

    const touch = event.changedTouches[0]
    const dx = touch.clientX - start.x
    const dy = touch.clientY - start.y
    const elapsed = Date.now() - start.time

    if (elapsed < 700 && Math.abs(dx) > 56 && Math.abs(dx) > Math.abs(dy) * 1.4) {
      if (dx < 0) goNext()
      else goPrev()
    }
  }

  const onViewerClick = (event) => {
    if (isScroll) return
    if (event.target.closest('button, a, input, textarea, mark, .rdr-cite')) return
    if (!window.getSelection()?.isCollapsed) return
    const viewport = viewportRef.current
    if (!viewport) return
    const rect = viewport.getBoundingClientRect()
    const x = event.clientX - rect.left
    if (x > rect.width * 0.9) goNext()
    else if (x < rect.width * 0.1) goPrev()
  }

  // ---------------------------------------------------------------------
  // Citation popover
  // ---------------------------------------------------------------------
  const handleCite = useCallback((ids, anchor) => {
    const rect = anchor.getBoundingClientRect()
    setCitation({ ids, x: rect.left + rect.width / 2, y: rect.bottom })
    setSelection(null)
  }, [])

  useEffect(() => {
    if (!citation) return undefined
    const onPointerDown = (event) => {
      if (!event.target.closest?.('.rdr-cite-pop')) setCitation(null)
    }
    document.addEventListener('pointerdown', onPointerDown)
    return () => document.removeEventListener('pointerdown', onPointerDown)
  }, [citation])

  // ---------------------------------------------------------------------
  // Derived display values
  // ---------------------------------------------------------------------
  const activeTitle =
    sections[activeSection]?.title || (activeSection === 0 ? title : '')
  const progress =
    pageCount > 1 ? ((page - 1) / (pageCount - 1)) * 100 : 0

  const aiPages = useMemo(() => sections.map(sectionText), [sections])
  const aiLimit = Math.max(
    1,
    Number(previewMode ? previewPageCount : sourcePageCount) ||
      sections.length ||
      1,
  )
  const aiSourcePage = clamp(activeSection + 1, 1, aiLimit)

  const scanSection = sections[activeSection]

  const annotationsBySection = useMemo(() => {
    const map = new Map()
    for (const annotation of annotations) {
      const key = Number(annotation.section) || 0
      if (!map.has(key)) map.set(key, [])
      map.get(key).push(annotation)
    }
    return map
  }, [annotations])

  const bodySize = bodyFontSize(settings, isKhmer)
  const readerLabel = readerName || user?.name

  const paywall = previewMode ? (
    <div className="rdr-paywall" data-rdr-block data-paywall>
      <span className="rdr-paywall-kicker">
        {previewGate === 'readtest' ? t('reader.readtestEnd') : t('reader.previewEnd')}
      </span>
      <h2 className="rdr-paywall-title">
        {vipRequired ? t('reader.vipUnlockTitle') : t('reader.unlockTitle')}
      </h2>
      <p className="rdr-paywall-text">
        {vipRequired ? t('reader.vipUnlockBody') : t('reader.unlockBody')}
      </p>
      <button type="button" className="rdr-primary-btn rdr-paywall-btn" onClick={onUnlock}>
        <LockIcon size={18} />
        {vipRequired ? t('reader.becomeVip') : t('reader.unlockBook')}
      </button>
    </div>
  ) : null

  return (
    <div
      className={`rdr-reader rdr-theme-${settings.theme} rdr-font-${settings.font}${
        barsVisible ? '' : ' is-immersive'
      }`}
      style={{
        '--rdr-font-size': `${bodySize}px`,
        '--rdr-line-height': settings.lineHeight,
        '--rdr-zoom': zoom,
        '--rdr-columns': visibleCount,
      }}
    >
      <ReaderTopBar
        title={title}
        author={author}
        chapter={activeTitle}
        progress={progress}
        visible={barsVisible}
        isFullscreen={isFullscreen}
        viewerCount={viewCount}
        viewCountLabel={viewCountLabel}
        readerName={readerLabel}
        badge={badge}
        onExit={onClose}
        onOpenToc={() => setDrawer('toc')}
        onOpenSearch={() => setDrawer('search')}
        onOpenNotes={() => setDrawer('notes')}
        onOpenScan={() => setScanOpen(true)}
        onOpenSettings={() => setDrawer('settings')}
        onToggleFullscreen={toggleFullscreen}
      />

      <main
        className={`rdr-main${isScroll ? ' is-scroll' : ''}`}
        onPointerMove={pokeBars}
        onTouchStart={onTouchStart}
        onTouchEnd={onTouchEnd}
        onPointerUp={(event) => {
          if (event.button === 0) window.setTimeout(captureSelection, 10)
        }}
        onKeyUp={(event) => {
          if (event.shiftKey) window.setTimeout(captureSelection, 10)
        }}
      >
        <div className="rdr-stage" ref={isScroll ? stageRef : null}>
          <div
            className={`rdr-book${isScroll ? ' is-scroll' : ''}`}
            style={{
              transform: `scale(${zoom})`,
              width: `${100 / zoom}%`,
              maxWidth: `${(isScroll || visibleCount === 2 ? 1100 : 720) / zoom}px`,
              ...(isScroll ? null : { height: `${100 / zoom}%` }),
            }}
          >
            {isScroll ? (
              <div className="rdr-scroll-inner" ref={scrollInnerRef}>
                {sections.map((section, index) => (
                  <div
                    className="rdr-scroll-section"
                    data-section-index={section.index}
                    key={`scroll-${section.index}`}
                  >
                    <span className="rdr-scroll-marker">
                      {t('reader.section')} {index + 1}
                    </span>
                    <RichText
                      section={section}
                      annotations={annotationsBySection.get(section.index) || []}
                      onCite={handleCite}
                    />
                  </div>
                ))}
                {paywall}
              </div>
            ) : (
              <div
                className="rdr-viewport"
                ref={viewportRef}
                onClick={onViewerClick}
                role="region"
                aria-label={t('reader.content')}
              >
                <div className="rdr-flow" ref={contentRef}>
                  {sections.map((section) => (
                    <RichText
                      key={section.index}
                      section={section}
                      annotations={annotationsBySection.get(section.index) || []}
                      onCite={handleCite}
                    />
                  ))}
                  {paywall}
                </div>
              </div>
            )}
          </div>
        </div>

        {!sections.length ? (
          <div className="rdr-empty-state">
            <p>{t('reader.noReadableSection')}</p>
          </div>
        ) : null}
      </main>

      <ReaderBottomBar
        page={page}
        pageCount={pageCount}
        pageLabel={page}
        progress={progress}
        canPrev={page > 1}
        canNext={page < pageCount}
        onPrev={goPrev}
        onNext={goNext}
        onGoTo={goToPage}
        zoom={zoom}
        onZoomIn={zoomIn}
        onZoomOut={zoomOut}
        onResetZoom={resetZoom}
        isBookmarked={!!currentBookmark}
        onToggleBookmark={toggleBookmark}
        aiOpen={aiOpen}
        onToggleAi={() => setAiOpen((open) => !open)}
        onOpenHelp={() => setDrawer('help')}
        onOpenSettings={() => setDrawer('settings')}
      />

      <ReaderDrawer
        open={drawer === 'toc'}
        title={t('reader.toc')}
        subtitle={t('reader.tocSubtitle')}
        onClose={() => setDrawer(null)}
      >
        <TocPanel
          toc={toc}
          sections={sections}
          currentSection={activeSection}
          locale={locale}
          onSelect={goToSection}
          onLocked={() => {
            setDrawer(null)
            onLockedNavigate?.()
          }}
        />
      </ReaderDrawer>

      <ReaderDrawer
        open={drawer === 'notes'}
        title={t('reader.notes')}
        subtitle={t('reader.notesSubtitle')}
        onClose={() => setDrawer(null)}
      >
        <NotesPanel
          bookmarks={bookmarks}
          annotations={annotations}
          page={page}
          isBookmarked={!!currentBookmark}
          canBookmark={isAuthenticated}
          onToggleBookmark={toggleBookmark}
          onDeleteBookmark={(bookmark) => removeBookmarkMutation.mutate(bookmark.id)}
          onDeleteAnnotation={deleteAnnotation}
          onSelectSection={goToSection}
          onSelectPage={goToPage}
        />
      </ReaderDrawer>

      <ReaderDrawer
        open={drawer === 'search'}
        title={t('reader.search')}
        subtitle={t('reader.searchSubtitle')}
        onClose={() => setDrawer(null)}
      >
        <SearchPanel sections={sections} onSelect={goToSection} />
      </ReaderDrawer>

      <ReaderDrawer
        open={drawer === 'settings'}
        title={t('reader.settings')}
        subtitle={t('reader.settingsSubtitle')}
        onClose={() => setDrawer(null)}
      >
        <SettingsPanel
          settings={settings}
          onChange={updateSettings}
          zoom={zoom}
          onZoomIn={zoomIn}
          onZoomOut={zoomOut}
          onResetZoom={resetZoom}
          synced={isAuthenticated}
        />
      </ReaderDrawer>

      <ReaderDrawer
        open={drawer === 'help'}
        title={t('reader.help')}
        subtitle={t('reader.helpSubtitle')}
        onClose={() => setDrawer(null)}
      >
        <HelpPanel />
      </ReaderDrawer>

      <ScanSheet
        open={scanOpen}
        onClose={() => setScanOpen(false)}
        bookId={bookId}
        page={page}
        section={scanSection}
        title={activeTitle || title}
        onNotify={notify}
      />

      <AiAssistantPanel
        bookId={bookId}
        pageNumber={aiSourcePage}
        pdfDoc={null}
        isPdf={false}
        pages={aiPages}
        isOpen={aiOpen}
        onToggle={() => setAiOpen((open) => !open)}
        hideLauncher
        initialQuestion={aiSeed.question}
        askNonce={aiSeed.nonce}
      />

      <SelectionToolbar
        key={selection ? `${selection.section}-${selection.quote}` : 'none'}
        selection={selection}
        onClose={() => setSelection(null)}
        onHighlight={handleHighlight}
        onCopy={copySelection}
        onAsk={askAboutSelection}
        onNote={handleNote}
      />

      {hintVisible ? (
        <div className="rdr-hint" role="status">
          <p>{t('reader.hint')}</p>
          <button type="button" className="rdr-hint-btn" onClick={dismissHint}>
            <CheckIcon size={16} />
            {t('reader.gotIt')}
          </button>
        </div>
      ) : null}

      {toast ? (
        <div className="rdr-toast" role="status">
          {toast}
        </div>
      ) : null}

      {citation ? (
        <div
          className="rdr-cite-pop"
          style={{
            left: clamp(
              citation.x - 120,
              10,
              (typeof window === 'undefined' ? 1024 : window.innerWidth) - 250,
            ),
            top: citation.y + 8,
          }}
          role="dialog"
          aria-label={t('reader.sources')}
        >
          <div className="rdr-cite-head">
            <strong>{t('reader.sources')}</strong>
            <button
              type="button"
              className="rdr-cite-close"
              onClick={() => setCitation(null)}
              aria-label={t('reader.closePanel')}
            >
              <CloseIcon size={16} />
            </button>
          </div>
          <ul className="rdr-cite-list">
            {citation.ids.map((id) => (
              <li key={id}>
                <span className="rdr-cite-number">{id}</span>
                <span className="rdr-cite-text">
                  {references.get(Number(id)) || t('reader.sourceUnavailable')}
                </span>
              </li>
            ))}
          </ul>
        </div>
      ) : null}

      {!sections.length ? null : (
        <span className="rdr-sr-only" aria-live="polite">
          {t('reader.page')} {page} / {pageCount}
        </span>
      )}
    </div>
  )
}
