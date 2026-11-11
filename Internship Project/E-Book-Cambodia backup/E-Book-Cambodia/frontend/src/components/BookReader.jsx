import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'

GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url
).toString()

let PAGE_ENCODE_MIME = 'image/jpeg'
try {
  const probe = document.createElement('canvas')
  probe.width = 1
  probe.height = 1
  if (probe.toDataURL('image/webp').startsWith('data:image/webp')) {
    PAGE_ENCODE_MIME = 'image/webp'
  }
} catch {
  PAGE_ENCODE_MIME = 'image/jpeg'
}

const FLIP_MS = 650
const ZOOM_MIN = 50
const ZOOM_MAX = 200
const ZOOM_STEP = 25
const CONTENT_ZOOM_MAX = 500
const CONTENT_ZOOM_STEP = 25
const MAX_DPR = 3
const MAX_RENDER_W = 8192
const MAX_RENDER_PX = 36000000
const PAGE_ENCODE_QUALITY = 0.98
const RENDER_DEBOUNCE_MS = 120
const QR_SCAN_ZOOM = 200
const QR_SCAN_TARGET = 350
const SPREAD_ASPECT = 3 / 2
const SINGLE_LEAF_W = 0.82
const SHELL_PAD_X = 12
const SHELL_PAD_Y = 24
const SIDE_OVERFLOW_REM = 11
const SIDE_OVERFLOW_PX = SIDE_OVERFLOW_REM * 16
const DESKTOP_MIN_W = 768

function shellSize(availW, availH, leafRatio) {
  const innerW = Math.max(availW - SHELL_PAD_X, 120)
  const innerH = Math.max(availH - SHELL_PAD_Y, 120)
  const spreadRatio = leafRatio
    ? (availW >= DESKTOP_MIN_W ? leafRatio * 2 : leafRatio / SINGLE_LEAF_W)
    : SPREAD_ASPECT
  let w = innerW
  let h = Math.round(innerW / spreadRatio)
  if (h > innerH) { h = innerH; w = Math.round(innerH * spreadRatio) }
  if (availW >= DESKTOP_MIN_W) {
    w += SIDE_OVERFLOW_PX * 2
  }
  return { w: Math.round(w + SHELL_PAD_X), h: Math.round(h + SHELL_PAD_Y) }
}

function viewportBase() {
  if (typeof window === 'undefined') return shellSize(1400, 980)
  const availW = Math.max(window.innerWidth, 320)
  const availH = Math.max(window.innerHeight - 72, 160)
  return shellSize(availW, availH)
}

function leafCssFor(desktop, baseW, zoom) {
  const scaledW = baseW * (zoom / 100)
  return desktop ? scaledW / 2 : scaledW * SINGLE_LEAF_W
}

function renderBucket(desktop, baseW, zoom, contentZoom, dpr) {
  const leafCss = leafCssFor(desktop, baseW, zoom)
  const displayPx = Math.max(leafCss * (contentZoom / 100), 320)
  return Math.round(Math.min(displayPx * dpr, MAX_RENDER_W) / 16)
}

function spreadFor(active, total) {
  if (active <= 1) return { L: null, R: 1 }
  const L = active % 2 === 0 ? active : active - 1
  const R = total && L + 1 > total ? null : L + 1
  return { L, R }
}

const BOOK_STYLES = `
  /* ---------- root ---------- */
  .bkr-wrap { width: 100%; max-width: 100vw; height: 100vh; height: 100dvh; margin: 0 auto;
    display: flex; flex-direction: column; overflow: hidden;
    background: radial-gradient(1400px 700px at 50% -20%, #322f6e 0%, #16142e 55%, #0d0c22 100%); }
  .bkr-wrap.is-fullscreen { max-width: none; height: 100vh; background: #0f0e2e;
    display: flex; flex-direction: column; padding: .35rem .6rem .6rem; border-radius: 0; }

  /* ---------- toolbar ---------- */
  .bkr-bar { display: flex; align-items: center; gap: 3rem; max-width: min(940px, 100%);
    margin: 0 auto .55rem; padding: .3rem .45rem; flex-wrap: nowrap; justify-content: center;
    background: rgba(255,255,255,.92); border: 1px solid #e7e5f4; border-radius: 9999px;
    box-shadow: 0 12px 32px -14px rgba(37,26,89,.28); backdrop-filter: blur(10px);
    position: relative; z-index: 30; }
  .bkr-bar-slot { display: inline-flex; align-items: center; }
  .bkr-bar-slot--exit { flex: 0 0 2.1rem; }
  .bkr-bar-slot--reader { flex: 0 0 10.5rem; }
  .bkr-btn { min-width: 2.05rem; height: 2.05rem; padding: 0 .3rem; display: inline-flex;
    align-items: center; justify-content: center; gap: .2rem; border-radius: 9999px;
    border: 1px solid #e7e5f4; background: #fff; color: #57536b; font-size: .7rem;
    font-weight: 700; cursor: pointer; transition: transform .16s ease, background .16s ease,
    color .16s ease, border-color .16s ease, box-shadow .16s ease; user-select: none; }
  .bkr-btn:hover { background: #eef2ff; color: #4f46e5; border-color: #c7d2fe; transform: translateY(-1px); }
  .bkr-btn:disabled { opacity: .35; pointer-events: none; }
  .bkr-btn.is-active { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff;
    border-color: transparent; box-shadow: 0 8px 18px -8px rgba(109,40,217,.5); }
  .bkr-exit { padding: 0 .75rem; margin: 0; min-width: auto; white-space: nowrap; }
  .bkr-exit svg { flex: 0 0 auto; }
  .bkr-bar__group { display: inline-flex; align-items: center; gap: .2rem; }
  .bkr-pageind { display: inline-flex; align-items: center; gap: .22rem; min-width: 3.6rem;
    font-size: .7rem; font-weight: 700; color: #6b6580; font-variant-numeric: tabular-nums;
    letter-spacing: .02em; white-space: nowrap; transform: translateX(-4rem); }
  .bkr-pageind input { width: 2.3rem; height: 1.6rem; padding: 0 .2rem; text-align: center;
    font-size: .72rem; font-weight: 700; color: #57536b; font-variant-numeric: tabular-nums;
    background: #fff; border: 1px solid #e7e5f4; border-radius: 9999px; outline: none;
    transition: border-color .16s ease, box-shadow .16s ease; }
  .bkr-pageind input:focus { border-color: #a5b4fc; box-shadow: 0 0 0 2px rgba(99,102,241,.18); }
  .bkr-pageind input:disabled { opacity: .4; }
  .bkr-book-progress { position: absolute; top: .35rem; left: .55rem; right: .55rem;
    height: 5px; border-radius: 9999px; background: rgba(255,255,255,.28);
    box-shadow: inset 0 1px 2px rgba(37,26,89,.1); cursor: pointer; overflow: hidden; z-index: 5; }
  .bkr-book-progress-fill { position: absolute; top: 0; left: 0; height: 100%; border-radius: 9999px;
    background: linear-gradient(90deg, #fbbf24, #d97706); transition: width .35s ease;
    box-shadow: 0 0 8px rgba(217,119,6,.45); pointer-events: none; }

  .bkr-meta-title { flex: 0 0 170px; min-width: 170px; overflow: hidden; text-overflow: ellipsis;
    white-space: nowrap; text-align: left; color: #37336b; font-size: .74rem; font-weight: 800;
    letter-spacing: .01em; }
  .bkr-meta-reader { display: inline-flex; align-items: center; gap: .3rem; flex: 0 0 10.5rem;
    max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    color: #7c3aed; font-size: .7rem; font-weight: 700; }

  /* ---------- fullscreen behaviour ---------- */
  .bkr-wrap.is-fullscreen .bkr-zoomport { flex: 1; min-height: 0; width: auto !important; height: auto !important; margin-left: .5rem }
  .bkr-wrap.is-fullscreen .bkr-hint { display: none; }
  .bkr-wrap.is-fullscreen .bkr-bar { position: sticky; top: .6rem; z-index: 30;
    background: rgba(15,14,46,.62); border-color: rgba(255,255,255,.14);
    box-shadow: 0 14px 38px -16px rgba(0,0,0,.6); opacity: 0; pointer-events: none;
    transform: translateY(-14px); transition: opacity .22s ease, transform .22s ease; }
  .bkr-wrap.is-fullscreen:hover .bkr-bar { opacity: 1; pointer-events: auto; transform: none; }
  .bkr-wrap.is-fullscreen .bkr-btn { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.16); color: #e5e7eb; }
  .bkr-wrap.is-fullscreen .bkr-btn:hover { background: #fbbf24; color: #0f0e2e; border-color: #fbbf24; }
  .bkr-wrap.is-fullscreen .bkr-btn.is-active { background: linear-gradient(135deg, #a78bfa, #7c3aed); }
  .bkr-wrap.is-fullscreen .bkr-meta-title { color: #e5e7eb; }
  .bkr-wrap.is-fullscreen .bkr-meta-reader { color: #a78bfa; }
  .bkr-wrap.is-fullscreen .bkr-pageind { color: #c7c2de; }
  .bkr-wrap.is-fullscreen .bkr-pageind input { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); color: #e5e7eb; }

  /* ---------- viewport / pan ---------- */
  .bkr-zoomport { flex: 1 1 0%; height: 100%; min-height: 0; overflow: auto; overscroll-behavior: contain; position: relative;
    scroll-behavior: smooth; scrollbar-width: thin; scrollbar-color: #c7b8f2 transparent; }
  .bkr-stage { width: 100%; margin: 0 auto; position: relative; }
  .bkr-zoomport.is-panning { cursor: grabbing !important; }

  /* ---------- shell / book frame ---------- */
  .bkr-shell { position: relative; border-radius: .9rem; padding: .85rem .45rem .85rem;
    background:
      linear-gradient(165deg, rgba(255,224,130,.12) 0%, rgba(255,255,255,0) 42%),
      radial-gradient(130% 120% at 50% -12%, #4c1d95 0%, #312e81 48%, #1e1b4b 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.22), inset 0 -16px 32px -22px rgba(0,0,0,.65),
      0 30px 60px -26px rgba(24,18,66,.6); will-change: transform;
    transition: transform .18s ease-out; }
  .bkr-book { position: absolute; inset: .85rem .45rem;
    filter: drop-shadow(0 26px 30px rgba(10,6,40,.5)); }

  /* ---------- paper ---------- */
  .bkr-leaf { position: absolute; top: 0; bottom: 0; width: 50%; overflow: hidden;
    background: linear-gradient(135deg, #fdf9ef 0%, #f8efdd 55%, #f0e3c8 100%);
    border: 1px solid #d7c69c; }
  .bkr-leaf.left { left: 0; border-radius: 14px 5px 5px 14px;
    box-shadow: inset -18px 0 28px -14px rgba(122,88,31,.62), inset 3px 0 5px -3px rgba(255,255,255,.85),
      -4px 4px 0 -1px rgba(0,0,0,.045), -8px 8px 0 -2px rgba(0,0,0,.03); }
  .bkr-leaf.right { right: 0; border-radius: 5px 14px 14px 5px;
    box-shadow: inset 18px 0 28px -14px rgba(122,88,31,.62), inset -3px 0 5px -3px rgba(255,255,255,.85),
      4px 4px 0 -1px rgba(0,0,0,.045), 8px 8px 0 -2px rgba(0,0,0,.03); }
  .bkr-leaf.single { left: 50%; transform: translateX(-50%); width: 82%; border-radius: 14px;
    box-shadow: inset 14px 0 26px -16px rgba(122,88,31,.6), inset -14px 0 26px -16px rgba(122,88,31,.6),
      -5px 5px 0 -1px rgba(0,0,0,.045), -9px 9px 0 -2px rgba(255,255,255,.28),
      5px 5px 0 -1px rgba(0,0,0,.045), 9px 9px 0 -2px rgba(255,255,255,.28); }
  .bkr-spine { position: absolute; top: 0; bottom: 0; left: 50%; width: 42px;
    transform: translateX(-50%);
    background: linear-gradient(90deg, rgba(122,88,31,.4) 0%, rgba(255,255,255,.2) 40%,
      rgba(122,88,31,.44) 56%, rgba(122,88,31,.14) 100%);
    box-shadow: 0 0 20px rgba(0,0,0,.14), inset 0 0 10px rgba(122,88,31,.14); }
  .bkr-leaf-surface { position: absolute; inset: 0; display: flex; align-items: center;
    justify-content: center; padding: 0; }
  .bkr-page-img { width: 100%; height: 100%; border-radius: 3px; object-fit: contain;
    background: #fff; box-shadow: 0 3px 14px rgba(90,60,10,.16); }
  .bkr-page-text { width: 100%; height: 100%; padding: 5% 7%; box-sizing: border-box; overflow: auto;
    font-family: Georgia, 'Times New Roman', serif; font-size: 1.05rem; line-height: 1.9;
    color: #40341f; white-space: pre-wrap; text-align: justify;
    text-shadow: 0 1px 1px rgba(255,255,255,.4); scrollbar-width: thin; }
  .bkr-leaf-placeholder { color: #b9a575; font-size: .85rem; }

  /* ---------- page flip ---------- */
  .bkr-flip-outer { position: absolute; top: 0; bottom: 0; perspective: 2000px; z-index: 20; }
  .bkr-flip-outer.right { left: 50%; width: 50%; }
  .bkr-flip-outer.left { left: 0; width: 50%; }
  .bkr-flip-outer.single { left: 9%; width: 82%; }
  .bkr-flip-inner { position: absolute; inset: 0; transform-style: preserve-3d;
    transform-origin: left center; will-change: transform; }
  .bkr-flip-outer.left .bkr-flip-inner { transform-origin: right center; }
  .bkr-flip-inner.turn-next { animation: bkr-next ${FLIP_MS}ms cubic-bezier(.33,1,.68,1) forwards; }
  .bkr-flip-inner.turn-prev { animation: bkr-prev ${FLIP_MS}ms cubic-bezier(.33,1,.68,1) forwards; }
  .bkr-flip-inner.turn-next, .bkr-flip-inner.turn-prev {
    filter: drop-shadow(0 12px 16px rgba(64,42,10,.28)); }
  @keyframes bkr-next {
    0%   { transform: rotateY(0deg); }
    45%  { transform: rotateY(-85deg) translateZ(10px); }
    100% { transform: rotateY(-179deg); }
  }
  @keyframes bkr-prev {
    0%   { transform: rotateY(0deg); }
    45%  { transform: rotateY(85deg) translateZ(10px); }
    100% { transform: rotateY(179deg); }
  }
  .bkr-page-face { position: absolute; inset: 0; display: flex; align-items: center;
    justify-content: center; padding: 0; border-radius: 8px; overflow: hidden;
    background: linear-gradient(135deg, #fdf9ef 0%, #f8efdd 60%, #f0e3c8 100%);
    border: 1px solid #d7c69c; backface-visibility: hidden; }
  .bkr-page-face.is-back { transform: rotateY(180deg); }

  /* ---------- bottom slide nav (on the paper) ---------- */
  .bkr-nav { position: fixed; left: 50%; bottom: 16px; transform: translateX(-50%);
    z-index: 40; display: flex; align-items: center; gap: 3rem; padding: .28rem .34rem;
    max-width: calc(100vw - 1rem);
    background: rgba(20,15,50,.74); border: 1px solid rgba(255,255,255,.18);
    border-radius: 9999px; box-shadow: 0 16px 34px -14px rgba(0,0,0,.6);
    backdrop-filter: blur(9px); }
  .bkr-nbtn { display: inline-flex; align-items: center; gap: .3rem; height: 2.35rem;
    padding: 0 .85rem; border-radius: 9999px; border: none; background: transparent;
    color: #fff; font-size: .84rem; font-weight: 700; cursor: pointer;
    transition: background .18s ease, color .18s ease, transform .18s ease; user-select: none; }
  .bkr-nbtn:hover:not(:disabled) { background: rgba(124,58,237,.92); color: #fff; }
  .bkr-nbtn.is-active { background: rgba(124,58,237,.92); color: #fff; }
  .bkr-nbtn:disabled { opacity: .35; cursor: default; }
  .bkr-nav-indicator { min-width: 4.5rem; text-align: center; color: #fde68a; font-size: .82rem;
    font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: .03em; }
  .bkr-zoomgroup { display: inline-flex; align-items: center; gap: .1rem; padding: 0 .1rem 0 .25rem;
    border-left: 1px solid rgba(255,255,255,.16); }
  .bkr-zoomgroup .bkr-nbtn { height: 2.2rem; min-width: 2.2rem; padding: 0 .55rem; }
  .bkr-zoomind { min-width: 2.5rem; text-align: center; color: #fde68a; font-size: .72rem;
    font-weight: 800; font-variant-numeric: tabular-nums; cursor: pointer; user-select: none;
    padding: .4rem .15rem; border-radius: 9999px; }
  .bkr-zoomind:hover { background: rgba(124,58,237,.45); }

  /* ---------- hint ---------- */
  .bkr-hint { text-align: center; color: #9b96b0; font-size: .68rem; margin-top: .45rem; }

  @media (max-width: 767px) {
    .bkr-page-text { font-size: .94rem; line-height: 1.78; }
    .bkr-bar { border-radius: 1.1rem; gap: 1rem; padding: .3rem .35rem; }
    .bkr-nav { gap: 1rem; padding: .3rem .4rem; }
    .bkr-meta-title { flex: 0 0 120px; min-width: 120px; }
    .bkr-bar-slot--reader { flex: 0 0 0; }
  }
`

function PageSurface({ number, isPdf, image, text, back = false, contentStyle }) {
  const content = isPdf
    ? image
      ? (<img src={image} alt={`Page ${number}`} className="bkr-page-img" draggable={false}
        style={contentStyle} />)
      : (<span className="bkr-leaf-placeholder">…</span>)
    : text
      ? (<div className="bkr-page-text" style={contentStyle}>{text}</div>)
      : (<span className="bkr-leaf-placeholder">…</span>)

  return (
    <div className={`bkr-page-face${back ? ' is-back' : ''}`}>
      {content}
    </div>
  )
}

const dist = (a, b) => Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY)
const clampv = (v, lo, hi) => (v < lo ? lo : v > hi ? hi : v)

export default function BookReader({
  pdfData,
  pages,
  title = '',
  readerName,
  initialPage = 1,
  onLoad,
  onPageChange,
  onClose,
}) {
  const isPdf = !!pdfData
  const contentTotal = Array.isArray(pages) ? pages.length : 0

  const [doc, setDoc] = useState(null)
  const [total, setTotal] = useState(contentTotal)
  const [active, setActive] = useState(initialPage)
  const [spread, setSpread] = useState(() => spreadFor(initialPage, contentTotal))
  const [flip, setFlip] = useState(null)
  const [error, setError] = useState('')
  const [desktop, setDesktop] = useState(
    () => typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches
  )

  const [fullImgs, setFullImgs] = useState({})
  const [zoom, setZoom] = useState(100)
  const [contentZoom, setContentZoom] = useState(100)
  const [contentPan, setContentPan] = useState({ x: 0, y: 0 })
  const [renderSeq, setRenderSeq] = useState(0)
  const [isFullscreen, setIsFullscreen] = useState(false)
  const [base, setBase] = useState(() => viewportBase())
  const [pageRatio, setPageRatio] = useState(null)
  const [pageDraft, setPageDraft] = useState(String(initialPage))

  const rootRef = useRef(null)
  const shellRef = useRef(null)
  const zoomportRef = useRef(null)
  const docRef = useRef(null)
  const desktopRef = useRef(desktop)
  desktopRef.current = desktop

  const flippingRef = useRef(false)
  const fullRef = useRef({})
  const stateRef = useRef({ spread: spreadFor(initialPage, contentTotal), active: initialPage, total: contentTotal })
  stateRef.current = { spread, active, total }

  const timerRef = useRef(null)
  const panRef = useRef({ dragging: false, moved: false, x: 0, y: 0, sl: 0, st: 0 })
  const pinchRef = useRef(null)
  const suppressClickRef = useRef(false)
  const zoomRef = useRef(zoom)
  zoomRef.current = zoom
  const contentZoomRef = useRef(contentZoom)
  contentZoomRef.current = contentZoom
  const baseRef = useRef(base)
  baseRef.current = base
  const pageRatioRef = useRef(pageRatio)
  pageRatioRef.current = pageRatio
  const renderTimerRef = useRef(null)
  const lastKeyRef = useRef(0)

  useEffect(() => () => { timerRef.current && clearTimeout(timerRef.current) }, [])

  useEffect(() => {
    if (typeof window === 'undefined') return undefined
    const mq = window.matchMedia('(min-width: 768px)')
    const handler = (e) => setDesktop(e.matches)
    setDesktop(mq.matches)
    mq.addEventListener('change', handler)
    return () => mq.removeEventListener('change', handler)
  }, [])

  useEffect(() => {
    if (typeof document === 'undefined') return undefined
    const measure = () => {
      const el = zoomportRef.current
      if (!el) return
      const availW = Math.max(el.clientWidth, 320)
      const availH = Math.max(el.clientHeight, 160)
      setBase((prev) => {
        const next = shellSize(availW, availH, pageRatioRef.current)
        return prev.w === next.w && prev.h === next.h ? prev : next
      })
    }
    measure()
    const onFsChange = () => {
      setIsFullscreen(!!document.fullscreenElement || !!document.webkitFullscreenElement)
      setTimeout(measure, 140)
    }
    const onResize = () => measure()
    document.addEventListener('fullscreenchange', onFsChange)
    document.addEventListener('webkitfullscreenchange', onFsChange)
    window.addEventListener('resize', onResize)
    return () => {
      document.removeEventListener('fullscreenchange', onFsChange)
      document.removeEventListener('webkitfullscreenchange', onFsChange)
      window.removeEventListener('resize', onResize)
    }
  }, [])

  useEffect(() => {
    if (!pageRatio) return undefined
    const el = zoomportRef.current
    if (!el) return undefined
    const availW = Math.max(el.clientWidth, 320)
    const availH = Math.max(el.clientHeight, 160)
    setBase((prev) => {
      const next = shellSize(availW, availH, pageRatio)
      return prev.w === next.w && prev.h === next.h ? prev : next
    })
  }, [pageRatio])

  useEffect(() => {
    const el = zoomportRef.current
    if (!el) return undefined
    el.scrollLeft = (el.scrollWidth - el.clientWidth) / 2
    el.scrollTop = (el.scrollHeight - el.clientHeight) / 2
  }, [base, zoom])

  useEffect(() => {
    const el = zoomportRef.current
    if (!el) return undefined
    const onWheel = (e) => {
      if (!e.ctrlKey && !e.metaKey) return
      e.preventDefault()
      setZoom((z) => clampZoom(z + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP)))
    }
    const onTouchStart = (e) => {
      if (e.touches.length === 2) {
        pinchRef.current = { d0: dist(e.touches[0], e.touches[1]), z0: zoomRef.current }
      }
    }
    const onTouchMove = (e) => {
      if (pinchRef.current && e.touches.length >= 2) {
        e.preventDefault()
        const d = dist(e.touches[0], e.touches[1])
        if (d > 0) setZoom(clampZoom(pinchRef.current.z0 * (d / pinchRef.current.d0)))
      }
    }
    const onTouchEnd = () => { pinchRef.current = null }
    el.addEventListener('wheel', onWheel, { passive: false })
    el.addEventListener('touchstart', onTouchStart, { passive: true })
    el.addEventListener('touchmove', onTouchMove, { passive: false })
    el.addEventListener('touchend', onTouchEnd)
    el.addEventListener('touchcancel', onTouchEnd)
    return () => {
      el.removeEventListener('wheel', onWheel)
      el.removeEventListener('touchstart', onTouchStart)
      el.removeEventListener('touchmove', onTouchMove)
      el.removeEventListener('touchend', onTouchEnd)
      el.removeEventListener('touchcancel', onTouchEnd)
    }
  }, [])

  const toggleFullscreen = useCallback(() => {
    const el = rootRef.current
    if (!el) return
    try {
      if (document.fullscreenElement || document.webkitFullscreenElement) {
        const exit = document.exitFullscreen || document.webkitExitFullscreen
        exit?.call(document)
      } else if (el.requestFullscreen) {
        el.requestFullscreen()
      } else if (el.webkitRequestFullscreen) {
        el.webkitRequestFullscreen()
      }
    } catch (err) {
      console.warn('[BookReader] fullscreen failed', err)
    }
  }, [])

  useEffect(() => {
    if (!isPdf) { onLoad?.(contentTotal); return undefined }
    if (!pdfData || !(pdfData instanceof Uint8Array) || pdfData.length === 0) {
      docRef.current?.loadingTask?.destroy?.()
      docRef.current = null
      setDoc(null)
      setTotal(0)
      setError('Invalid or empty PDF data.')
      return undefined
    }
    let cancelled = false
    setError('')
    setFullImgs({})
    fullRef.current = {}
    const copy = new Uint8Array(pdfData)
    const task = getDocument({ data: copy })
    task.promise
      .then((pdf) => {
        if (cancelled) { void pdf.loadingTask?.destroy?.(); return undefined }
        if (docRef.current && docRef.current !== pdf) void docRef.current.loadingTask?.destroy?.()
        docRef.current = pdf
        setDoc(pdf)
        setTotal(pdf.numPages)
        onLoad?.(pdf.numPages)
        return undefined
      })
      .catch((err) => { if (!cancelled) setError(err?.message || 'Failed to load the book.') })
    return () => {
      cancelled = true
      docRef.current?.loadingTask?.destroy?.()
      docRef.current = null
      task.destroy()
      setDoc(null)
      setTotal(0)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isPdf, pdfData])

  useEffect(() => {
    if (!total) return
    const target = Math.min(Math.max(initialPage, 1), total)
    if (target === active) return
    setActive(target)
    setSpread(spreadFor(target, total))
    flippingRef.current = false
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [total, initialPage])

  const docGone = (candidate) => {
    if (!candidate) return true
    try {
      return candidate.loadingTask?.destroyed === true
    } catch {
      return true
    }
  }

  const requestFull = useCallback((n) => {
    if (docGone(doc) || fullRef.current[n]) return
    fullRef.current[n] = true
    let pagePromise
    try {
      pagePromise = doc.getPage(n)
    } catch {
      fullRef.current[n] = false
      return
    }
    pagePromise
      .then(async (page) => {
        if (!page) throw new Error('Page unavailable')
        if (docRef.current !== doc || docGone(doc)) throw new Error('Document destroyed')
        const vb = page.getViewport({ scale: 1 })
        setPageRatio((prev) => prev || Math.round((vb.width / vb.height) * 1000) / 1000)
        const dpr = Math.min(window.devicePixelRatio || 1, MAX_DPR)
        const leafCss = leafCssFor(desktopRef.current, baseRef.current.w, zoomRef.current)
        const displayPx = Math.max(leafCss * (contentZoomRef.current / 100), 320)
        let needPx = Math.min(displayPx * dpr, MAX_RENDER_W)
        const pixelCap = Math.sqrt((MAX_RENDER_PX * vb.width) / vb.height)
        needPx = Math.min(needPx, pixelCap)
        const scale = Math.max(needPx / vb.width, 1)
        const viewport = page.getViewport({ scale })
        const canvas = document.createElement('canvas')
        canvas.width = Math.ceil(viewport.width)
        canvas.height = Math.ceil(viewport.height)
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise
        if (docRef.current !== doc || docGone(doc)) throw new Error('Document destroyed')
        const lossless = contentZoomRef.current >= QR_SCAN_ZOOM
          && Math.abs(n - stateRef.current.active) <= 1
        const url = lossless
          ? canvas.toDataURL('image/png')
          : canvas.toDataURL(PAGE_ENCODE_MIME, PAGE_ENCODE_QUALITY)
        setFullImgs((prev) => (prev[n] ? prev : { ...prev, [n]: url }))
      })
      .catch(() => { fullRef.current[n] = false })
  }, [doc])

  const targetKey = useMemo(() => {
    if (typeof window === 'undefined') return 0
    const dpr = Math.min(window.devicePixelRatio || 1, MAX_DPR)
    return renderBucket(desktop, base.w, zoom, contentZoom, dpr)
  }, [desktop, base.w, zoom, contentZoom])

  useEffect(() => {
    if (lastKeyRef.current === targetKey) return undefined
    const prev = lastKeyRef.current
    lastKeyRef.current = targetKey
    if (prev === 0) return undefined
    renderTimerRef.current = setTimeout(() => setRenderSeq((s) => s + 1), RENDER_DEBOUNCE_MS)
    return () => { if (renderTimerRef.current) clearTimeout(renderTimerRef.current) }
  }, [targetKey])

  useEffect(() => {
    if (!doc) return
    setFullImgs({})
    fullRef.current = {}
  }, [renderSeq, doc])

  useEffect(() => {
    if (!doc) return
    const span = contentZoom > 100 ? 1 : 3
    const lo = Math.max(1, active - (span + 1))
    const hi = Math.min(total, active + span)
    for (let n = lo; n <= hi; n += 1) requestFull(n)
  }, [doc, active, total, requestFull, renderSeq, contentZoom])

  const commit = useCallback((nextSpread, nextActive) => {
    setSpread(nextSpread)
    setActive(nextActive)
    onPageChange?.(nextActive)
    setFlip(null)
    flippingRef.current = false
  }, [onPageChange])

  const goToPage = useCallback((n) => {
    if (!total) { setPageDraft(String(active || 0)); return }
    const target = Math.min(Math.max(Math.floor(n) || 1, 1), total)
    setPageDraft(String(target))
    if (target === active) return
    setActive(target)
    setSpread(spreadFor(target, total))
    setFlip(null)
    flippingRef.current = false
    onPageChange?.(target)
  }, [active, total, onPageChange])

  useEffect(() => {
    setPageDraft(String(active))
    setContentPan({ x: 0, y: 0 })
  }, [active])

  const performFlip = useCallback((dir, done) => {
    if (flippingRef.current) return
    const cur = stateRef.current
    if (dir === 'next') {
      if (cur.spread.R === null || cur.spread.R >= cur.total) { done?.(); return }
      const front = cur.spread.R
      const back = front + 1
      flippingRef.current = true
      setFlip({ side: 'right', dir: 'next', front, back })
      timerRef.current = setTimeout(() => {
        commit(spreadFor(back, cur.total), back)
        done?.()
      }, FLIP_MS)
      return
    }
    if (cur.spread.L === null) { done?.(); return }
    const front = cur.spread.L
    const back = front - 1
    flippingRef.current = true
    setFlip({ side: 'left', dir: 'prev', front, back })
    timerRef.current = setTimeout(() => {
      commit(spreadFor(back, cur.total), back)
      done?.()
    }, FLIP_MS)
  }, [commit])

  const canNext = () => stateRef.current.spread.R !== null && stateRef.current.spread.R < stateRef.current.total
  const canPrev = () => stateRef.current.spread.L !== null

  const handleClick = (e) => {
    if (suppressClickRef.current) { suppressClickRef.current = false; return }
    if (contentZoomRef.current > 100) return
    const rect = e.currentTarget.getBoundingClientRect()
    const x = (e.clientX - rect.left) / rect.width
    if (flippingRef.current) return
    if (x >= 0.5) { if (canNext()) performFlip('next') } else if (canPrev()) performFlip('prev')
  }

  const handleTouchStart = useRef(null)

  const progressPct = total ? Math.round((active /total) * 100) : 0

  const contentStyle = contentZoom === 100 && contentPan.x === 0 && contentPan.y === 0
    ? undefined
    : {
        transform: `translate(${contentPan.x}px, ${contentPan.y}px) scale(${contentZoom / 100})`,
        transformOrigin: 'center',
      }

  const textFor = (n) => (isPdf || !pages ? '' : pages[n - 1] || '')

  const leafSurface = (n) => {
    if (!n) {
      return <div className="bkr-leaf-surface"><span className="bkr-leaf-placeholder" /></div>
    }
    const img = fullImgs[n]
    return (
      <div className="bkr-leaf-surface">
        {isPdf ? (
          img
            ? (<img src={img} alt={`Page ${n}`} className="bkr-page-img" draggable={false} style={contentStyle} />)
            : (<span className="bkr-leaf-placeholder">Loading page {n}…</span>)
        ) : (
          <div className="bkr-page-text" style={contentStyle}>{textFor(n)}</div>
        )}
      </div>
    )
  }

  const onZoomIn = () => setContentZoom((z) => Math.min(CONTENT_ZOOM_MAX, z + CONTENT_ZOOM_STEP))
  const onZoomOut = () => setContentZoom((z) => Math.max(100, z - CONTENT_ZOOM_STEP))
  const onScan = () => setContentZoom((z) => (z >= QR_SCAN_ZOOM ? 100 : QR_SCAN_TARGET))

  const clampZoom = (z) => Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round(z)))

  const beginPan = (e) => {
    const el = e.currentTarget
    const cZ = contentZoomRef.current
    if (cZ > 100) {
      panRef.current = { dragging: true, mode: 'content', moved: false, x: e.clientX, y: e.clientY, cx: contentPan.x, cy: contentPan.y }
      el.setPointerCapture?.(e.pointerId)
      el.classList.add('is-panning')
      return
    }
    const overflow = el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1
    if (!overflow) { panRef.current.dragging = false; return }
    panRef.current = { dragging: true, mode: 'scroll', moved: false, x: e.clientX, y: e.clientY, sl: el.scrollLeft, st: el.scrollTop }
    el.setPointerCapture?.(e.pointerId)
    el.classList.add('is-panning')
  }

  const movePan = (e) => {
    const p = panRef.current
    const el = e.currentTarget
    if (!p.dragging) return
    if (p.mode === 'content') {
      p.moved = true
      const cZ = contentZoomRef.current
      const b = baseRef.current
      const pad = 30
      const maxX = (b.w / 2) * ((cZ / 100) - 1) * 0.65 + pad
      const maxY = (b.h / 2) * ((cZ / 100) - 1) * 0.65 + pad
      setContentPan({
        x: clampv(p.cx + (e.clientX - p.x), -maxX, maxX),
        y: clampv(p.cy + (e.clientY - p.y), -maxY, maxY),
      })
      return
    }
    p.moved = true
    el.scrollLeft = p.sl - (e.clientX - p.x)
    el.scrollTop = p.st - (e.clientY - p.y)
  }

  const endPan = (e) => {
    const p = panRef.current
    const moved = p.moved
    p.dragging = false
    e.currentTarget.classList.remove('is-panning')
    if (moved) setTimeout(() => { suppressClickRef.current = false }, 0)
  }

  return (
    <div ref={rootRef} className={`bkr-wrap${isFullscreen ? ' is-fullscreen' : ''}`}>
      <style>{BOOK_STYLES}</style>
      {error && (
        <div className="text-center py-8">
          <p className="text-gray-600 mb-4">{error}</p>
        </div>
      )}

      {!error && (
        <>
          <div className="bkr-bar">
            {onClose ? (
              <button
                type="button"
                className="bkr-btn bkr-exit"
                onClick={onClose}
                aria-label="Exit book"
                title="Exit book"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M15 19l-7-7 7-7" />
                </svg>
                <span>Exit book</span>
              </button>
            ) : (
              <span className="bkr-bar-slot bkr-bar-slot--exit" aria-hidden="true" />
            )}
            {title ? (
              <span className="bkr-meta-title" title={title}>{title}</span>
            ) : (
              <span className="bkr-meta-title" aria-hidden="true" />
            )}

            <label className="bkr-pageind" title="Jump to page (Enter)">
              <input
                type="text"
                inputMode="numeric"
                autoComplete="off"
                value={pageDraft}
                disabled={!total}
                aria-label="Go to page"
                onChange={(e) => setPageDraft(e.target.value.replace(/[^\d]/g, ''))}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') goToPage(Number(pageDraft))
                  if (e.key === 'Escape') { setPageDraft(String(active)); e.currentTarget.blur() }
                }}
                onBlur={() => goToPage(Number(pageDraft))}
              />
              <span> / {total}</span>
            </label>

            {readerName ? (
              <span className="bkr-meta-reader" title={readerName}>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <circle cx="12" cy="8" r="4" />
                  <path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5" />
                </svg>
                {readerName}
              </span>
            ) : (
              <span className="bkr-bar-slot bkr-bar-slot--reader" aria-hidden="true" />
            )}

            <button
              type="button"
              className={`bkr-btn${isFullscreen ? ' is-active' : ''}`}
              onClick={toggleFullscreen}
              aria-label={isFullscreen ? 'Exit fullscreen' : 'Read full page'}
              title={isFullscreen ? 'Exit fullscreen' : 'Read full page'}
            >
              {isFullscreen ? (
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M8 3v5H3M16 3v5h5M8 21v-5H3M16 21v-5h5" />
                </svg>
              ) : (
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M4 6h4V4h-4v4zM4 18h4v2H4v-4zM20 6h-4V4h4v4zM20 18h-4v2h4v-4z" />
                </svg>
              )}
            </button>
          </div>

          <div
            ref={zoomportRef}
            className="bkr-zoomport"
            onPointerDown={beginPan}
            onPointerMove={movePan}
            onPointerUp={endPan}
            onPointerCancel={endPan}
          >
            <div
              className="bkr-stage"
              style={{ width: base.w * (zoom / 100), height: base.h * (zoom / 100) }}
            >
              <div
                ref={shellRef}
                className="bkr-shell"
                style={{
                  width: base.w,
                  height: base.h,
                  margin: '0 auto',
                  transform: `scale(${zoom / 100})`,
                  transformOrigin: 'center',
                }}
              >
              <div
                className="bkr-book-progress"
                role="slider"
                aria-label="Reading progress"
                aria-valuemin={1}
                aria-valuemax={Math.max(total, 1)}
                aria-valuenow={Math.min(active, total) || 1}
                title="Jump to page"
                onClick={(e) => {
                  const rect = e.currentTarget.getBoundingClientRect()
                  const frac = Math.min(Math.max((e.clientX - rect.left) / rect.width, 0), 1)
                  goToPage(Math.round(frac * total))
                }}
              >
                <div className="bkr-book-progress-fill" style={{ width: `${progressPct}%` }} />
              </div>
              <div
                className="bkr-book"
                role="button"
                tabIndex={0}
                onClick={handleClick}
                onKeyDown={(e) => {
                  if (e.key === 'ArrowRight' && canNext()) performFlip('next')
                  if (e.key === 'ArrowLeft' && canPrev()) performFlip('prev')
                }}
                onTouchStart={(e) => { handleTouchStart.current = e.changedTouches[0].clientX }}
                onTouchEnd={(e) => {
                  if (handleTouchStart.current === null) return
                  if (contentZoomRef.current > 100) { handleTouchStart.current = null; return }
                  const dx = e.changedTouches[0].clientX - handleTouchStart.current
                  handleTouchStart.current = null
                  if (dx < -40 && canNext()) performFlip('next')
                  if (dx > 40 && canPrev()) performFlip('prev')
                }}
              >
                {desktop ? (
                  <>
                    <div className="bkr-leaf left">{leafSurface(spread.L)}</div>
                    <div className="bkr-leaf right">{leafSurface(spread.R)}</div>
                    <div className="bkr-spine" />
                  </>
                ) : (
                  <div className="bkr-leaf single">{leafSurface(active)}</div>
                )}

                {flip && desktop && (
                  <div className={`bkr-flip-outer ${flip.side}`}>
                    <div className={`bkr-flip-inner turn-${flip.dir}`}>
                      {isPdf ? (
                        <>{fullImgs[flip.front] && <PageSurface number={flip.front} isPdf image={fullImgs[flip.front]} contentStyle={contentStyle} />}</>
                      ) : (
                        <PageSurface number={flip.front} isPdf={false} text={textFor(flip.front)} contentStyle={contentStyle} />
                      )}
                      {isPdf ? (
                        <>{fullImgs[flip.back] && <PageSurface number={flip.back} isPdf image={fullImgs[flip.back]} back contentStyle={contentStyle} />}</>
                      ) : (
                        <PageSurface number={flip.back} isPdf={false} text={textFor(flip.back)} back contentStyle={contentStyle} />
                      )}
                    </div>
                  </div>
                )}

                {flip && !desktop && (
                  <div className="bkr-flip-outer single">
                    <div className={`bkr-flip-inner turn-${flip.dir}`}>
                      {isPdf ? (
                        <>{fullImgs[flip.front] && <PageSurface number={flip.front} isPdf image={fullImgs[flip.front]} contentStyle={contentStyle} />}</>
                      ) : (
                        <PageSurface number={flip.front} isPdf={false} text={textFor(flip.front)} contentStyle={contentStyle} />
                      )}
                      {isPdf ? (
                        <>{fullImgs[flip.back] && <PageSurface number={flip.back} isPdf image={fullImgs[flip.back]} back contentStyle={contentStyle} />}</>
                      ) : (
                        <PageSurface number={flip.back} isPdf={false} text={textFor(flip.back)} back contentStyle={contentStyle} />
                      )}
                    </div>
                  </div>
                )}
              </div>
              </div>
            </div>
          </div>

          {total > 0 && (
            <div className="bkr-nav">
              <button
                type="button"
                className="bkr-nbtn"
                disabled={!canPrev() || flippingRef.current}
                onClick={(e) => { e.stopPropagation(); if (canPrev()) performFlip('prev') }}
                aria-label="Previous page"
              >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M15 5l-7 7 7 7" />
                </svg>
                <span className="bkr-nbtn-label">Prev</span>
              </button>
              <span className="bkr-nav-indicator">{Math.min(active, total)} / {total}</span>
              <div className="bkr-zoomgroup">
                <button
                  type="button"
                  className="bkr-nbtn"
                  onClick={onZoomOut}
                  disabled={contentZoom <= 100}
                  aria-label="Zoom out"
                  title="Zoom out the page content"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" aria-hidden="true">
                    <path d="M5 12h14" />
                  </svg>
                </button>
                <span
                  className="bkr-zoomind"
                  role="button"
                  tabIndex={0}
                  onClick={() => setContentZoom(100)}
                  onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setContentZoom(100) } }}
                  title="Reset page content zoom"
                >
                  {Math.round(contentZoom)}%
                </span>
                <button
                  type="button"
                  className="bkr-nbtn"
                  onClick={onZoomIn}
                  disabled={contentZoom >= CONTENT_ZOOM_MAX}
                  aria-label="Zoom in"
                  title="Zoom in the page content"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                  </svg>
                </button>
                <button
                  type="button"
                  className={`bkr-nbtn${contentZoom >= QR_SCAN_ZOOM ? ' is-active' : ''}`}
                  onClick={(e) => { e.stopPropagation(); onScan() }}
                  aria-label={contentZoom >= QR_SCAN_ZOOM ? 'Exit scan mode' : 'Scan QR code'}
                  title={contentZoom >= QR_SCAN_ZOOM ? 'Exit scan mode' : 'Scan QR code - lossless high-resolution zoom'}
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="M21 21l-4.35-4.35" />
                  </svg>
                  <span className="bkr-nbtn-label">Scan</span>
                </button>
              </div>
              <button
                type="button"
                className="bkr-nbtn"
                disabled={!canNext() || flippingRef.current}
                onClick={(e) => { e.stopPropagation(); if (canNext()) performFlip('next') }}
                aria-label="Next page"
              >
                <span className="bkr-nbtn-label">Next</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          )}

          <p className="bkr-hint">
            {contentZoom >= QR_SCAN_ZOOM
              ? 'Scanning mode: lossless sharp page - keep zooming to 500% and scan · tap Scan or % to reset'
              : 'Swipe, tap, or use arrow keys to turn pages · − / + zooms inside a page · Scan opens a lossless QR view'}
          </p>
        </>
      )}
    </div>
  )
}