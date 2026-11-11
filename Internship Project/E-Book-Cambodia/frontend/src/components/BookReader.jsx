import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'
import AiAssistantPanel from './ai/AiAssistantPanel'
import './ai/aiAssistant.css'
import {
  createPagePreloader,
  PRELOAD_AHEAD,
  PRELOAD_BEHIND,
  QUALITY_DRAFT,
  QUALITY_FULL,
} from '../utils/pagePreloader'
import { getReaderMetrics, recordMetric, resetReaderMetrics } from '../utils/readerMetrics'

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
// Padding of the shell frame. The book (the spread) lives inside it, so the
// JS sizing and the CSS padding have to agree.
const SHELL_PAD_X = 16
const SHELL_PAD_Y = 28
// Fallback page ratio (A4) used until the real page size of the document is
// known - text books have no page geometry of their own.
const DEFAULT_PAGE_RATIO = 0.707
const DESKTOP_MIN_W = 768
// Resolution of the low-quality "draft" renders used to fill the preload
// window before the full-resolution page is ready.
const DRAFT_RENDER_SCALE = 0.55
const DRAFT_MIN_PX = 360
// How long a page turn may wait for the next page bitmap before it plays
// anyway (keeps the reader responsive on slow devices).
const FLIP_WAIT_MS = 260
const FLIP_WAIT_DRAFT_MS = 140

/**
 * Size of the shell that hosts the book.
 *
 * The spread is fitted to the available area using the *real* ratio of the
 * document pages, so the paper on screen has exactly the proportions of the
 * book it shows.
 */
function shellSize(availW, availH, leafRatio, desktop) {
  const ratio = leafRatio || DEFAULT_PAGE_RATIO
  const wide = desktop === undefined ? availW >= DESKTOP_MIN_W : desktop
  const innerW = Math.max(availW - SHELL_PAD_X, 120)
  const innerH = Math.max(availH - SHELL_PAD_Y, 120)
  const spreadRatio = wide ? ratio * 2 : ratio
  let w = innerW
  let h = Math.round(innerW / spreadRatio)
  if (h > innerH) { h = innerH; w = Math.round(innerH * spreadRatio) }
  return { w: Math.round(w + SHELL_PAD_X), h: Math.round(h + SHELL_PAD_Y) }
}

/**
 * Size of a single page (leaf) inside the book.
 *
 * The leaf always keeps the ratio of the document page, which is what makes
 * the on-screen template identical to the printed page: no stretched and no
 * cropped content.
 */
function leafSize(bookW, bookH, pageRatio, desktop) {
  const ratio = pageRatio || DEFAULT_PAGE_RATIO
  const spreadRatio = desktop ? ratio * 2 : ratio
  let spreadW = bookW
  let spreadH = spreadW / spreadRatio
  if (spreadH > bookH) { spreadH = bookH; spreadW = spreadH * spreadRatio }
  return {
    w: Math.round(desktop ? spreadW / 2 : spreadW),
    h: Math.round(spreadH),
  }
}

function viewportBase() {
  if (typeof window === 'undefined') return shellSize(1400, 980)
  const availW = Math.max(window.innerWidth, 320)
  const availH = Math.max(window.innerHeight - 72, 160)
  return shellSize(availW, availH)
}

function renderBucket(leafCss, contentZoom, dpr) {
  const displayPx = Math.max(leafCss * (contentZoom / 100), 320)
  return Math.round(Math.min(displayPx * dpr, MAX_RENDER_W) / 16)
}

function spreadFor(active, total) {
  if (active <= 1) return { L: null, R: 1 }
  const L = active % 2 === 0 ? active : active - 1
  const R = total && L + 1 > total ? null : L + 1
  return { L, R }
}

function docGone(candidate) {
  if (!candidate) return true
  try {
    return candidate.loadingTask?.destroyed === true
  } catch {
    return true
  }
}

function canvasToBlob(canvas, mime, quality) {
  return new Promise((resolve) => {
    if (typeof canvas.toBlob !== 'function') {
      resolve(null)
      return
    }
    canvas.toBlob((blob) => resolve(blob || null), mime, quality)
  })
}

function canvasToUrl(canvas, mime, quality) {
  return canvasToBlob(canvas, mime, quality).then((blob) => {
    if (blob && typeof URL !== 'undefined') return URL.createObjectURL(blob)
    return canvas.toDataURL(mime, quality)
  })
}

/**
 * Pick a preload/rendering profile from the device and network. These are
 * conservative limits, not promises: low-end phones render fewer pages at a
 * lower pixel budget, while desktop machines can keep more pages warm.
 */
function detectDeviceProfile() {
  const fallback = {
    tier: 'mid',
    ahead: PRELOAD_AHEAD,
    behind: PRELOAD_BEHIND,
    fullWindow: 2,
    maxConcurrent: 2,
    maxFull: 14,
    maxDraft: 16,
    maxDpr: 2,
    pixelBudget: 24000000,
    rangeChunkSize: 262144,
  }
  if (typeof navigator === 'undefined') return fallback

  const memory = Number(navigator.deviceMemory) || 4
  const cores = Number(navigator.hardwareConcurrency) || 4
  const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection
  const slowNetwork = !!connection?.saveData || /(^|-)2g$/.test(connection?.effectiveType || '')
  const rangeChunkSize = slowNetwork ? 65536 : 262144

  if (slowNetwork || memory <= 2 || cores <= 2) {
    return {
      tier: 'low',
      ahead: 3,
      behind: 1,
      fullWindow: 1,
      maxConcurrent: 1,
      maxFull: 8,
      maxDraft: 10,
      maxDpr: 2,
      pixelBudget: 16000000,
      rangeChunkSize,
    }
  }

  if (memory <= 4 || cores <= 4) {
    return {
      ...fallback,
      tier: 'mid',
      ahead: 4,
      behind: 2,
      fullWindow: 2,
      maxConcurrent: 2,
      maxFull: 14,
      maxDraft: 16,
      maxDpr: 2,
      rangeChunkSize,
    }
  }

  return {
    tier: 'high',
    ahead: 5,
    behind: 2,
    fullWindow: 3,
    maxConcurrent: 2,
    maxFull: 20,
    maxDraft: 24,
    maxDpr: 3,
    pixelBudget: 36000000,
    rangeChunkSize,
  }
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

  .bkr-meta-title { display: inline-flex; align-items: center; gap: .35rem; flex: 0 0 170px; min-width: 170px;
    text-align: left; color: #37336b; font-size: .74rem; font-weight: 800; letter-spacing: .01em; }
  .bkr-meta-title-text { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .bkr-meta-views { display: inline-flex; align-items: center; gap: .18rem; flex: 0 0 auto;
    color: #6b6580; font-size: .62rem; font-weight: 700; white-space: nowrap; }
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
  .bkr-wrap.is-fullscreen .bkr-meta-views { color: #c7c2de; }
  .bkr-wrap.is-fullscreen .bkr-meta-reader { color: #a78bfa; }
  .bkr-wrap.is-fullscreen .bkr-pageind { color: #c7c2de; }
  .bkr-wrap.is-fullscreen .bkr-pageind input { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); color: #e5e7eb; }

  /* ---------- viewport / pan ---------- */
  .bkr-zoomport { flex: 1 1 0%; height: 100%; min-height: 0; overflow: auto; overscroll-behavior: contain; position: relative;
    scroll-behavior: smooth; scrollbar-width: thin; scrollbar-color: #c7b8f2 transparent; }
  .bkr-stage { width: 100%; margin: 0 auto; position: relative; }
  .bkr-zoomport.is-panning { cursor: grabbing !important; }

  /* ---------- shell / book frame ---------- */
  .bkr-shell { position: relative; border-radius: .9rem;
    padding: ${SHELL_PAD_Y / 2}px ${SHELL_PAD_X / 2}px;
    display: flex; flex-direction: column;
    background:
      linear-gradient(165deg, rgba(255,224,130,.12) 0%, rgba(255,255,255,0) 42%),
      radial-gradient(130% 120% at 50% -12%, #4c1d95 0%, #312e81 48%, #1e1b4b 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.22), inset 0 -16px 32px -22px rgba(0,0,0,.65),
      0 30px 60px -26px rgba(24,18,66,.6); will-change: transform;
    transition: transform .18s ease-out; }
  .bkr-book { position: relative; flex: 1 1 auto; min-height: 0;
    display: flex; align-items: center; justify-content: center;
    filter: drop-shadow(0 26px 30px rgba(10,6,40,.5)); }

  /* ---------- paper ---------- */
  .bkr-leaf { position: relative; flex: 0 0 auto; overflow: hidden;
    background: linear-gradient(135deg, #fdf9ef 0%, #f8efdd 55%, #f0e3c8 100%);
    box-shadow: inset 0 0 0 1px #d7c69c; }
  .bkr-leaf.left { border-radius: 14px 5px 5px 14px;
    box-shadow: inset 0 0 0 1px #d7c69c, inset -18px 0 28px -14px rgba(122,88,31,.62),
      inset 3px 0 5px -3px rgba(255,255,255,.85), -4px 4px 0 -1px rgba(0,0,0,.045),
      -8px 8px 0 -2px rgba(0,0,0,.03); }
  .bkr-leaf.right { border-radius: 5px 14px 14px 5px;
    box-shadow: inset 0 0 0 1px #d7c69c, inset 18px 0 28px -14px rgba(122,88,31,.62),
      inset -3px 0 5px -3px rgba(255,255,255,.85), 4px 4px 0 -1px rgba(0,0,0,.045),
      8px 8px 0 -2px rgba(0,0,0,.03); }
  .bkr-leaf.single { border-radius: 14px;
    box-shadow: inset 0 0 0 1px #d7c69c, inset 14px 0 26px -16px rgba(122,88,31,.6),
      inset -14px 0 26px -16px rgba(122,88,31,.6), -5px 5px 0 -1px rgba(0,0,0,.045),
      -9px 9px 0 -2px rgba(255,255,255,.28), 5px 5px 0 -1px rgba(0,0,0,.045),
      9px 9px 0 -2px rgba(255,255,255,.28); }
  .bkr-spine { position: absolute; top: 50%; left: 50%; width: 42px;
    height: var(--bkr-leaf-h, 100%); transform: translate(-50%, -50%);
    background: linear-gradient(90deg, rgba(122,88,31,.4) 0%, rgba(255,255,255,.2) 40%,
      rgba(122,88,31,.44) 56%, rgba(122,88,31,.14) 100%);
    box-shadow: 0 0 20px rgba(0,0,0,.14), inset 0 0 10px rgba(122,88,31,.14); }
  .bkr-leaf-surface { position: absolute; inset: 0; display: flex; align-items: center;
    justify-content: center; padding: 0; }
  .bkr-page-img { flex: 0 0 auto; max-width: 100%; max-height: 100%; width: auto; height: auto;
    border-radius: 3px; object-fit: contain; background: #fff;
    box-shadow: 0 3px 14px rgba(90,60,10,.16); }
  .bkr-page-img.is-draft { filter: blur(.6px) saturate(.9); }
  .bkr-page-text { width: 100%; height: 100%; padding: 5% 7%; box-sizing: border-box; overflow: auto;
    font-family: Georgia, 'Times New Roman', serif; font-size: 1.05rem; line-height: 1.9;
    color: #40341f; white-space: pre-wrap; text-align: justify;
    text-shadow: 0 1px 1px rgba(255,255,255,.4); scrollbar-width: thin; }
  .bkr-leaf-placeholder { color: #b9a575; font-size: .85rem; }

  /* ---------- page flip ---------- */
  .bkr-flip-outer { position: absolute; top: 50%; height: var(--bkr-leaf-h, 100%);
    transform: translateY(-50%); perspective: 2000px; z-index: 20; }
  .bkr-flip-outer.right { left: 50%; width: var(--bkr-leaf-w, 50%); }
  .bkr-flip-outer.left { right: 50%; width: var(--bkr-leaf-w, 50%); }
  .bkr-flip-outer.single { left: 50%; transform: translate(-50%, -50%); width: var(--bkr-leaf-w, 82%); }
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

  /* ---------- table of contents drawer ---------- */
  .bkr-toc-scrim { position: fixed; inset: 0; z-index: 40; background: rgba(13,12,34,.5); }
  .bkr-toc-drawer { position: fixed; top: 0; left: 0; z-index: 41; height: 100vh; height: 100dvh;
    width: min(22rem, 86vw); display: flex; flex-direction: column;
    background: #fff; box-shadow: 18px 0 48px -20px rgba(0,0,0,.5); }
  .bkr-toc-head { display: flex; align-items: center; justify-content: space-between;
    padding: .9rem 1rem; border-bottom: 1px solid #e7e5f4; }
  .bkr-toc-title { font-weight: 800; color: #2b2740; font-size: .95rem; }
  .bkr-toc-close { width: 1.9rem; height: 1.9rem; border-radius: 9999px; border: 1px solid #e7e5f4;
    background: #fff; color: #57536b; font-weight: 700; cursor: pointer; }
  .bkr-toc-close:hover { background: #eef2ff; color: #4f46e5; }
  .bkr-toc-body { flex: 1; overflow-y: auto; padding: .6rem .6rem 1.2rem; }
  .bkr-toc-list { list-style: none; margin: 0; }
  .bkr-toc-item { margin: 1px 0; }
  .bkr-toc-link { width: 100%; display: flex; align-items: center; gap: .5rem;
    padding: .5rem .6rem; border: 0; border-radius: .6rem; background: transparent;
    color: #3b3752; font-size: .82rem; font-weight: 600; text-align: left; cursor: pointer; }
  .bkr-toc-link:hover { background: #eef2ff; color: #4f46e5; }
  .bkr-toc-link.is-active { background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff; }
  .bkr-toc-link.is-locked { color: #a6a2b8; cursor: not-allowed; }
  .bkr-toc-link.is-locked:hover { background: transparent; color: #a6a2b8; }
  .bkr-toc-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .bkr-toc-page { font-variant-numeric: tabular-nums; font-size: .74rem; opacity: .75; }
  .bkr-toc-lock { display: inline-flex; opacity: .8; }

  /* ---------- free preview ---------- */
  .bkr-preview-badge { display: inline-flex; align-items: center; padding: .15rem .5rem;
    border-radius: 9999px; background: rgba(251,191,36,.18); color: #b45309;
    font-size: .62rem; font-weight: 800; letter-spacing: .02em; white-space: nowrap; }
  .bkr-paywall { position: fixed; inset: 0; z-index: 45; display: flex; align-items: center;
    justify-content: center; padding: 1.25rem; background: rgba(13,12,34,.72); backdrop-filter: blur(3px); }
  .bkr-paywall-card { width: min(26rem, 94vw); background: #fff; border-radius: 1.1rem;
    padding: 1.6rem 1.5rem; text-align: center; box-shadow: 0 30px 70px -30px rgba(0,0,0,.65); }
  .bkr-paywall-chip { display: inline-block; padding: .18rem .6rem; border-radius: 9999px;
    background: #fef3c7; color: #92400e; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
  .bkr-paywall-title { margin: .8rem 0 .4rem; font-size: 1.05rem; font-weight: 800; color: #2b2740; line-height: 1.35; }
  .bkr-paywall-text { margin: 0 0 1.1rem; font-size: .85rem; color: #6b6580; line-height: 1.5; }
  .bkr-paywall-actions { display: flex; flex-direction: column; gap: .55rem; }
  .bkr-paywall-buy { padding: .7rem 1rem; border: 0; border-radius: .8rem; cursor: pointer;
    background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff; font-weight: 700; font-size: .9rem; }
  .bkr-paywall-buy:hover { filter: brightness(1.06); }
  .bkr-paywall-back { padding: .6rem 1rem; border: 1px solid #e7e5f4; border-radius: .8rem;
    background: #fff; color: #57536b; font-weight: 600; font-size: .84rem; cursor: pointer; }
  .bkr-paywall-back:hover { background: #f5f5fb; }

  @media (max-width: 767px) {
    .bkr-page-text { font-size: .94rem; line-height: 1.78; }
    .bkr-bar { border-radius: 1.1rem; gap: 1rem; padding: .3rem .35rem; }
    .bkr-nav { gap: 1rem; padding: .3rem .4rem; }
    .bkr-meta-title { flex: 0 0 120px; min-width: 120px; }
    .bkr-bar-slot--reader { flex: 0 0 0; }
  }
`

function PageSurface({ number, isPdf, image, draft, text, back = false, contentStyle }) {
  const content = isPdf
    ? (image || draft)
      ? (<>
          {draft && !image && (
            <img src={draft} alt="" className="bkr-page-img is-draft" draggable={false}
              style={contentStyle} aria-hidden="true" />
          )}
          {image && (
            <img src={image} alt={`Page ${number}`} className="bkr-page-img" draggable={false}
              style={contentStyle} />
          )}
        </>)
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

function TocList({ entries, locale, active, onOpen, depth = 0 }) {
  if (!Array.isArray(entries) || entries.length === 0) return null

  return (
    <ul className="bkr-toc-list" style={{ paddingLeft: depth === 0 ? 0 : '0.9rem' }}>
      {entries.map((entry) => {
        const label = locale !== 'en' && entry.title ? entry.title : entry.title_en || entry.title
        const isActive = !entry.locked && entry.page === active

        return (
          <li key={entry.id} className="bkr-toc-item">
            <button
              type="button"
              className={`bkr-toc-link${entry.locked ? ' is-locked' : ''}${isActive ? ' is-active' : ''}`}
              onClick={() => onOpen(entry)}
              title={entry.locked ? 'Locked - available after purchase' : label}
            >
              <span className="bkr-toc-label">{label}</span>
              {entry.locked ? (
                <span className="bkr-toc-lock" aria-hidden="true">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <rect x="4" y="11" width="16" height="9" rx="2" />
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                  </svg>
                </span>
              ) : (
                <span className="bkr-toc-page">{entry.page}</span>
              )}
            </button>
            {entry.children?.length > 0 && (
              <TocList
                entries={entry.children}
                locale={locale}
                active={active}
                onOpen={onOpen}
                depth={depth + 1}
              />
            )}
          </li>
        )
      })}
    </ul>
  )
}

const dist = (a, b) => Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY)
const clampv = (v, lo, hi) => (v < lo ? lo : v > hi ? hi : v)

export default function BookReader({
  pdfData,
  pdfUrl = null,
  pdfHttpHeaders = null,
  pages,
  title = '',
  viewCount = 0,
  viewCountLabel = '',
  readerName,
  initialPage = 1,
  onLoad,
  onPageChange,
  onClose,
  pageCache = null,
  bookId = null,
  toolbarBadge = null,
  toc = null,
  locale = 'km',
  onLockedNavigate = null,
  previewMode = false,
  previewGate = 'preview',
  vipRequired = false,
  onUnlock = null,
}) {
  const isPdf = !!(pdfData || pdfUrl)
  const contentTotal = Array.isArray(pages) ? pages.length : 0
  const formattedViewCount = Number(viewCount || 0).toLocaleString()
  const viewLabel = viewCountLabel ? `${formattedViewCount} ${viewCountLabel}` : ''

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
  const [draftImgs, setDraftImgs] = useState({})
  const [zoom, setZoom] = useState(100)
  const [contentZoom, setContentZoom] = useState(100)
  const [contentPan, setContentPan] = useState({ x: 0, y: 0 })
  const [renderSeq, setRenderSeq] = useState(0)
  const [isFullscreen, setIsFullscreen] = useState(false)
  const [base, setBase] = useState(() => viewportBase())
  const [pageRatio, setPageRatio] = useState(null)
  const [pageDraft, setPageDraft] = useState(String(initialPage))
  const [isAiOpen, setIsAiOpen] = useState(false)
  const [isTocOpen, setIsTocOpen] = useState(false)

  const rootRef = useRef(null)
  const shellRef = useRef(null)
  const zoomportRef = useRef(null)
  const docRef = useRef(null)
  const desktopRef = useRef(desktop)
  desktopRef.current = desktop

  const flippingRef = useRef(false)
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
  const pageRatioRef = useRef(pageRatio)
  pageRatioRef.current = pageRatio
  const renderTimerRef = useRef(null)
  const lastKeyRef = useRef(0)
  const targetKeyRef = useRef(0)
  const cacheRef = useRef(null)
  cacheRef.current = pageCache
  const pageUrlRef = useRef({})
  const preloaderRef = useRef(null)
  const profileRef = useRef(null)
  if (!profileRef.current) profileRef.current = detectDeviceProfile()
  const pageProxyRef = useRef({ doc: null, pages: new Map() })
  const visibleWaitRef = useRef({ page: 0, startedAt: 0 })
  const visibleDoneRef = useRef(new Set())

  // The paper on screen always keeps the ratio of the real document page, so
  // the template matches the content of the book.
  const leaf = useMemo(() => leafSize(
    Math.max(base.w - SHELL_PAD_X, 120),
    Math.max(base.h - SHELL_PAD_Y, 120),
    pageRatio,
    desktop
  ), [base, pageRatio, desktop])
  const leafRef = useRef(leaf)
  leafRef.current = leaf

  const trackUrl = useCallback((n, kind, url) => {
    const prev = pageUrlRef.current[n]
    if (prev && prev[kind] && prev[kind] !== url && typeof URL !== 'undefined'
      && typeof prev[kind] === 'string' && prev[kind].startsWith('blob:')) {
      URL.revokeObjectURL(prev[kind])
    }
    pageUrlRef.current[n] = { ...(prev || {}), [kind]: url }
  }, [])

  const releaseUrl = useCallback((n, kind) => {
    const prev = pageUrlRef.current[n]
    if (!prev) return
    const url = prev[kind]
    if (url && typeof URL !== 'undefined' && typeof url === 'string' && url.startsWith('blob:')) {
      URL.revokeObjectURL(url)
    }
    const next = { ...prev }
    delete next[kind]
    if (Object.keys(next).length > 0) pageUrlRef.current[n] = next
    else delete pageUrlRef.current[n]
  }, [])

  const preloaderLiveRef = useRef(false)

  const dropImg = useCallback((kind, n) => {
    releaseUrl(n, kind)
    if (!preloaderLiveRef.current) return
    if (kind === QUALITY_FULL) {
      setFullImgs((prev) => {
        if (!prev[n]) return prev
        const next = { ...prev }
        delete next[n]
        return next
      })
    } else {
      setDraftImgs((prev) => {
        if (!prev[n]) return prev
        const next = { ...prev }
        delete next[n]
        return next
      })
    }
  }, [releaseUrl])

  // PDF.js keeps page proxies internally, but asking for the same page from
  // several render tasks still creates extra promise/stream work. Keep one
  // promise per page for the lifetime of the document.
  const getPageProxy = useCallback((doc, n) => {
    const store = pageProxyRef.current
    if (store.doc !== doc) {
      store.doc = doc
      store.pages.clear()
    }
    const existing = store.pages.get(n)
    if (existing) return existing
    const pending = doc.getPage(n).catch((err) => {
      store.pages.delete(n)
      throw err
    })
    store.pages.set(n, pending)
    return pending
  }, [])

  const renderPdfPage = useCallback(async (n, quality, signal) => {
    const doc = docRef.current
    if (!doc || docGone(doc)) throw new Error('Document destroyed')
    const cache = cacheRef.current
    const cacheEnabled = !!(
      cache && cache.enabled && cache.bookId && cache.version && typeof cache.getPage === 'function'
    )
    const bucket = targetKeyRef.current
    // Check the IndexedDB page cache before touching PDF.js: a cache hit can
    // paint without parsing the page or waiting for a range request.
    if (cacheEnabled && quality === QUALITY_FULL) {
      const cached = await cache
        .getPage(cache.bookId, cache.version, n, bucket)
        .then((record) => (record && record.blob ? record : null))
        .catch(() => null)
      if (signal.cancelled) throw new Error('Cancelled')
      if (cached && cached.blob && typeof URL !== 'undefined') {
        recordMetric({ type: 'cache_hit' })
        return { url: URL.createObjectURL(cached.blob), cached: true }
      }
      if (cacheEnabled) recordMetric({ type: 'cache_miss' })
    }
    const page = await getPageProxy(doc, n)
    if (signal.cancelled) throw new Error('Cancelled')
    if (docRef.current !== doc || docGone(doc)) throw new Error('Document destroyed')
    const isDraft = quality === QUALITY_DRAFT
    const vb = page.getViewport({ scale: 1 })
    setPageRatio((prev) => prev || Math.round((vb.width / vb.height) * 1000) / 1000)
    const profile = profileRef.current
    const dpr = Math.min(window.devicePixelRatio || 1, profile.maxDpr)
    const leafCss = leafRef.current.w * (zoomRef.current / 100)
    const displayPx = Math.max(leafCss * (contentZoomRef.current / 100), 320)
    let needPx = Math.min(displayPx * dpr, MAX_RENDER_W)
    const pixelCap = Math.sqrt((profile.pixelBudget * vb.width) / vb.height)
    needPx = Math.min(needPx, pixelCap)
    if (isDraft) needPx = Math.min(needPx, Math.max(displayPx * DRAFT_RENDER_SCALE, DRAFT_MIN_PX))
    const scale = isDraft
      ? Math.max(needPx / vb.width, 0.5)
      : Math.max(needPx / vb.width, 1)
    const viewport = page.getViewport({ scale })
    const canvas = document.createElement('canvas')
    canvas.width = Math.ceil(viewport.width)
    canvas.height = Math.ceil(viewport.height)
    const context = canvas.getContext('2d')
    const task = page.render({ canvas, canvasContext: context, viewport })
    signal.onCancel = () => {
      try {
        task.cancel()
      } catch {
        /* the render already finished */
      }
    }
    try {
      await task.promise
    } finally {
      signal.onCancel = null
    }
    if (signal.cancelled) throw new Error('Cancelled')
    if (docRef.current !== doc || docGone(doc)) throw new Error('Document destroyed')
    const lossless = contentZoomRef.current >= QR_SCAN_ZOOM
      && Math.abs(n - stateRef.current.active) <= 1
    const mime = lossless ? 'image/png' : PAGE_ENCODE_MIME
    // Encode once and reuse the same Blob for the page cache and the on-screen
    // image. The old code ran toBlob twice per page, doubling encode cost.
    const blob = await canvasToBlob(canvas, mime, PAGE_ENCODE_QUALITY)
    const url = blob && typeof URL !== 'undefined'
      ? URL.createObjectURL(blob)
      : canvas.toDataURL(mime, PAGE_ENCODE_QUALITY)
    // Release the backing store as soon as the bitmap has been encoded.
    canvas.width = 0
    canvas.height = 0
    if (signal.cancelled) {
      if (typeof URL !== 'undefined' && typeof url === 'string' && url.startsWith('blob:')) {
        URL.revokeObjectURL(url)
      }
      throw new Error('Cancelled')
    }
    if (
      cacheEnabled
      && blob
      && !lossless
      && !isDraft
      && contentZoomRef.current <= 100
    ) {
      cache
        .savePage(cache.bookId, cache.version, n, bucket, blob)
        .catch(() => {})
    }
    return { url, cached: false }
  }, [getPageProxy])

  useEffect(() => {
    resetReaderMetrics()
  }, [])

  useEffect(() => {
    const profile = profileRef.current
    const preloader = createPagePreloader({
      ahead: profile.ahead,
      behind: profile.behind,
      fullWindow: profile.fullWindow,
      maxConcurrent: profile.maxConcurrent,
      maxFull: profile.maxFull,
      maxDraft: profile.maxDraft,
      onMetric: recordMetric,
      render: (n, { quality, signal }) => renderPdfPage(n, quality, signal),
      onFull: (n, url) => {
        trackUrl(n, QUALITY_FULL, url)
        if (!preloaderLiveRef.current) return
        setFullImgs((prev) => (prev[n] === url ? prev : { ...prev, [n]: url }))
      },
      onDraft: (n, url) => {
        trackUrl(n, QUALITY_DRAFT, url)
        if (!preloaderLiveRef.current) return
        setDraftImgs((prev) => (prev[n] === url ? prev : { ...prev, [n]: url }))
      },
      onEvict: (n, kind) => dropImg(kind, n),
      onDemote: (n, url) => {
        const prev = pageUrlRef.current[n]
        if (prev) {
          const next = { ...prev }
          delete next[QUALITY_FULL]
          next[QUALITY_DRAFT] = url
          pageUrlRef.current[n] = next
        }
        if (!preloaderLiveRef.current) return
        setFullImgs((prev) => {
          if (!prev[n]) return prev
          const next = { ...prev }
          delete next[n]
          return next
        })
        setDraftImgs((prev) => (prev[n] ? prev : { ...prev, [n]: url }))
      },
    })
    preloaderRef.current = preloader
    preloaderLiveRef.current = true
    return () => {
      preloaderLiveRef.current = false
      preloader.dispose()
      if (preloaderRef.current === preloader) preloaderRef.current = null
    }
  }, [renderPdfPage, trackUrl, dropImg])

  useEffect(() => () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current)
      timerRef.current = null
    }
    const pages = pageUrlRef.current
    pageUrlRef.current = {}
    Object.values(pages).forEach((entry) => {
      Object.values(entry || {}).forEach((u) => {
        if (typeof URL !== 'undefined' && typeof u === 'string' && u.startsWith('blob:')) {
          URL.revokeObjectURL(u)
        }
      })
    })
  }, [])

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
        const next = shellSize(availW, availH, pageRatioRef.current, desktopRef.current)
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
      const next = shellSize(availW, availH, pageRatio, desktop)
      return prev.w === next.w && prev.h === next.h ? prev : next
    })
  }, [pageRatio, desktop])

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
    const hasPdfData = pdfData instanceof Uint8Array && pdfData.length > 0
    if (!hasPdfData && !pdfUrl) {
      docRef.current?.loadingTask?.destroy?.()
      docRef.current = null
      setDoc(null)
      setTotal(0)
      setError('Invalid or empty PDF data.')
      return undefined
    }
    let cancelled = false
    setError('')
    pageProxyRef.current.pages.clear()
    const source = hasPdfData
      ? { data: new Uint8Array(pdfData) }
      : {
          url: pdfUrl,
          ...(pdfHttpHeaders ? { httpHeaders: pdfHttpHeaders } : {}),
          rangeChunkSize: profileRef.current.rangeChunkSize,
        }
    const task = getDocument(source)
    task.promise
      .then((pdf) => {
        if (cancelled) { void pdf.loadingTask?.destroy?.(); return undefined }
        if (docRef.current && docRef.current !== pdf) void docRef.current.loadingTask?.destroy?.()
        docRef.current = pdf
        setDoc(pdf)
        setTotal(pdf.numPages)
        onLoad?.(pdf.numPages)
        // Read the real page geometry right away so the template on screen
        // already matches the book before the first page is rendered.
        pdf.getPage(1)
          .then((p) => {
            if (cancelled) return undefined
            const vp = p.getViewport({ scale: 1 })
            const ratio = vp.height > 0 ? vp.width / vp.height : DEFAULT_PAGE_RATIO
            setPageRatio(Math.round(ratio * 1000) / 1000)
            return undefined
          })
          .catch(() => {})
        return undefined
      })
      .catch((err) => { if (!cancelled) setError(err?.message || 'Failed to load the book.') })
    return () => {
      cancelled = true
      docRef.current?.loadingTask?.destroy?.()
      docRef.current = null
      pageProxyRef.current.pages.clear()
      task.destroy()
      setDoc(null)
      setTotal(0)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isPdf, pdfData, pdfUrl, pdfHttpHeaders])

  // Plain-text books are re-rendered in place after a purchase: the API sends
  // only the preview pages first, then the full content once access is
  // granted. Keep the page count in step so the reader can turn past the
  // preview instead of staying clamped to it.
  useEffect(() => {
    if (isPdf) return
    setTotal(contentTotal)
  }, [isPdf, contentTotal])

  // Apply the resume position once, as soon as the total is known (the PDF
  // page count arrives after mount). Later refetches of the same book must not
  // move the reader - after a purchase the payload grows from the preview to
  // the full book and the reader should stay on the page they unlocked from.
  const resumeAppliedRef = useRef(false)
  useEffect(() => {
    if (!total || resumeAppliedRef.current) return
    resumeAppliedRef.current = true
    const target = Math.min(Math.max(initialPage, 1), total)
    if (target === active) return
    setActive(target)
    setSpread(spreadFor(target, total))
    flippingRef.current = false
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [total, initialPage])

  const targetKey = useMemo(() => {
    if (typeof window === 'undefined') return 0
    const dpr = Math.min(window.devicePixelRatio || 1, profileRef.current.maxDpr)
    return renderBucket(leaf.w * (zoom / 100), contentZoom, dpr)
  }, [leaf.w, zoom, contentZoom])
  targetKeyRef.current = targetKey

  useEffect(() => {
    if (lastKeyRef.current === targetKey) return undefined
    const prev = lastKeyRef.current
    lastKeyRef.current = targetKey
    if (prev === 0) return undefined
    renderTimerRef.current = setTimeout(() => setRenderSeq((s) => s + 1), RENDER_DEBOUNCE_MS)
    return () => { if (renderTimerRef.current) clearTimeout(renderTimerRef.current) }
  }, [targetKey])

  useEffect(() => {
    preloaderRef.current?.reset({ keepVisible: false })
  }, [doc])

  useEffect(() => {
    preloaderRef.current?.reset({ keepVisible: true })
  }, [renderSeq])

  useEffect(() => {
    const preloader = preloaderRef.current
    if (!preloader || !doc || !total) return
    const profile = profileRef.current
    // Zoomed-in pages are expensive to rasterise, so the window shrinks.
    // When zoomed out, we can preload a bit more because each page is cheaper.
    preloader.setWindow({
      active,
      total,
      ahead: contentZoom > 100 ? 2 : profile.ahead,
      behind: contentZoom > 100 ? 1 : profile.behind,
      fullWindow: contentZoom > 100 ? 1 : profile.fullWindow,
    })
  }, [doc, active, total, contentZoom, renderSeq])

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
    if (target === active) {
      preloaderRef.current?.setWindow({ active: target, total })
      return
    }
    setActive(target)
    setSpread(spreadFor(target, total))
    setFlip(null)
    flippingRef.current = false
    preloaderRef.current?.setWindow({ active: target, total })
    onPageChange?.(target)
  }, [active, total, onPageChange])

  const hasToc = Array.isArray(toc) && toc.length > 0

  const atPreviewEnd =
    previewMode &&
    total > 0 &&
    (active >= total || (spread.R !== null && spread.R >= total))

  const isReadtest = previewGate === 'readtest'
  const gateLabel = isReadtest
    ? (locale === 'en' ? 'Readtest' : 'សាកល្បងអាន')
    : (locale === 'en' ? 'Free preview' : 'អានមើល')
  const gateEndTitle = vipRequired
    ? (locale === 'en' ? 'This book is for VIP members only' : 'សៀវភៅនេះសម្រាប់សមាជិក VIP តែប៉ុណ្ណោះ')
    : isReadtest
      ? (locale === 'en' ? 'Your trial reading has ended' : 'ការអានសាកល្បងរបស់អ្នកបានបញ្ចប់')
      : (locale === 'en' ? "You've reached the end of your free preview" : 'អ្នកបានអានអស់ចំនួនទំព័រឥតគិតថ្លៃហើយ')
  const gateEndText = vipRequired
    ? (locale === 'en'
        ? `You read the first ${total} page${total > 1 ? 's' : ''} as a trial. Upgrade to VIP to keep reading.`
        : `អ្នកបានអានសាកល្បង ${total} ទំពថរដំបូង។ សូមដំឡើងទៅការជាសមាជិក VIP ដើម្បីអានបន្ត។`)
    : isReadtest
      ? (locale === 'en'
          ? `You read the first ${total} page${total > 1 ? 's' : ''} as a trial. Purchase this book to keep reading.`
          : `អ្នកបានអានសាកល្បង ${total} ទំព័រដំបូង។ សូមទិញសៀវភៅនេះដើម្បីអានបន្ត។`)
      : (locale === 'en'
          ? `You read the first ${total} page${total > 1 ? 's' : ''} for free. Purchase this book to keep reading.`
          : `អ្នកបានអាន ${total} ទំព័រដំបូងដោយឥតគិតថ្លៃ។ សូមទិញសៀវភៅនេះដើម្បីអានបន្ត។`)

  const gateActionLabel = vipRequired
    ? (locale === 'en' ? 'Upgrade to VIP' : 'ដំឡើងទៅ VIP')
    : (locale === 'en' ? 'Buy to continue' : 'ទិញដើម្បីអានបន្ត')

  const handleUnlock = useCallback(() => {
    if (onUnlock) { onUnlock(); return }
    onClose?.()
  }, [onUnlock, onClose])

  const openTocEntry = useCallback((entry) => {
    if (!entry) return
    if (entry.locked) { onLockedNavigate?.(entry); return }
    goToPage(entry.page)
    setIsTocOpen(false)
  }, [goToPage, onLockedNavigate])

  useEffect(() => {
    setPageDraft(String(active))
    setContentPan({ x: 0, y: 0 })
  }, [active])

  const performFlip = useCallback((dir, done) => {
    if (flippingRef.current) return
    const cur = stateRef.current
    const side = dir === 'next' ? 'right' : 'left'
    let front = null
    let back = null
    if (dir === 'next') {
      if (cur.spread.R === null || cur.spread.R >= cur.total) { done?.(); return }
      front = cur.spread.R
      back = front + 1
    } else {
      if (cur.spread.L === null) { done?.(); return }
      front = cur.spread.L
      back = front - 1
    }
    flippingRef.current = true
    const begin = () => {
      if (!flippingRef.current) return
      setFlip({ side, dir, front, back })
      timerRef.current = setTimeout(() => {
        commit(spreadFor(back, stateRef.current.total), back)
        done?.()
      }, FLIP_MS)
    }
    const preloader = preloaderRef.current
    if (!preloader || preloader.hasFull(back)) {
      begin()
      return
    }
    // The next bitmap is still rendering - hold the turn for a moment so the
    // reader never sees an empty leaf mid-animation. A draft is already on
    // screen, so the wait for the sharp version is kept short.
    const wait = preloader.hasDraft(back) ? FLIP_WAIT_DRAFT_MS : FLIP_WAIT_MS
    preloader.whenFull(back, wait).then(begin)
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
    const draft = draftImgs[n]
    return (
      <div className="bkr-leaf-surface">
        {isPdf ? (
          img || draft
            ? (<>
                {draft && !img && (
                  <img src={draft} alt="" className="bkr-page-img is-draft" draggable={false}
                    style={contentStyle} aria-hidden="true" />
                )}
                {img && (
                  <img src={img} alt={`Page ${n}`} className="bkr-page-img" draggable={false}
                    style={contentStyle} />
                )}
              </>)
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
      const l = leafRef.current
      const pad = 30
      const maxX = (l.w / 2) * ((cZ / 100) - 1) * 0.65 + pad
      const maxY = (l.h / 2) * ((cZ / 100) - 1) * 0.65 + pad
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
            {title || viewLabel ? (
              <span className="bkr-meta-title" title={`${title ? `${title} · ` : ''}${viewLabel}`}>
                <span className="bkr-meta-title-text">{title}</span>
                {viewCountLabel && (
                  <span className="bkr-meta-views" title={viewLabel}>
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {formattedViewCount} {viewCountLabel}
                  </span>
                )}
              </span>
            ) : (
              <span className="bkr-meta-title" aria-hidden="true" />
            )}

            {toolbarBadge}

            {previewMode && (
              <span className="bkr-preview-badge">{gateLabel}</span>
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

            {hasToc && (
              <button
                type="button"
                className={`bkr-btn${isTocOpen ? ' is-active' : ''}`}
                onClick={() => setIsTocOpen((open) => !open)}
                aria-label="Table of contents"
                title="Table of contents"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                </svg>
              </button>
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
                style={{
                  '--bkr-leaf-w': `${leaf.w}px`,
                  '--bkr-leaf-h': `${leaf.h}px`,
                }}
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
                    <div className="bkr-leaf left" style={{ width: leaf.w, height: leaf.h }}>{leafSurface(spread.L)}</div>
                    <div className="bkr-leaf right" style={{ width: leaf.w, height: leaf.h }}>{leafSurface(spread.R)}</div>
                    <div className="bkr-spine" />
                  </>
                ) : (
                  <div className="bkr-leaf single" style={{ width: leaf.w, height: leaf.h }}>{leafSurface(active)}</div>
                )}

                {flip && desktop && (
                  <div className={`bkr-flip-outer ${flip.side}`}>
                    <div className={`bkr-flip-inner turn-${flip.dir}`}>
                      {isPdf ? (
                        <>{(fullImgs[flip.front] || draftImgs[flip.front]) && (
                          <PageSurface number={flip.front} isPdf image={fullImgs[flip.front]}
                            draft={draftImgs[flip.front]} contentStyle={contentStyle} />
                        )}</>
                      ) : (
                        <PageSurface number={flip.front} isPdf={false} text={textFor(flip.front)} contentStyle={contentStyle} />
                      )}
                      {isPdf ? (
                        <>{(fullImgs[flip.back] || draftImgs[flip.back]) && (
                          <PageSurface number={flip.back} isPdf image={fullImgs[flip.back]}
                            draft={draftImgs[flip.back]} back contentStyle={contentStyle} />
                        )}</>
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
                        <>{(fullImgs[flip.front] || draftImgs[flip.front]) && (
                          <PageSurface number={flip.front} isPdf image={fullImgs[flip.front]}
                            draft={draftImgs[flip.front]} contentStyle={contentStyle} />
                        )}</>
                      ) : (
                        <PageSurface number={flip.front} isPdf={false} text={textFor(flip.front)} contentStyle={contentStyle} />
                      )}
                      {isPdf ? (
                        <>{(fullImgs[flip.back] || draftImgs[flip.back]) && (
                          <PageSurface number={flip.back} isPdf image={fullImgs[flip.back]}
                            draft={draftImgs[flip.back]} back contentStyle={contentStyle} />
                        )}</>
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

          {hasToc && isTocOpen && (
            <>
              <div className="bkr-toc-scrim" onClick={() => setIsTocOpen(false)} />
              <aside className="bkr-toc-drawer" role="dialog" aria-label="Table of contents">
                <div className="bkr-toc-head">
                  <span className="bkr-toc-title">Contents</span>
                  <button
                    type="button"
                    className="bkr-toc-close"
                    onClick={() => setIsTocOpen(false)}
                    aria-label="Close table of contents"
                  >
                    ✕
                  </button>
                </div>
                <div className="bkr-toc-body">
                  <TocList entries={toc} locale={locale} active={active} onOpen={openTocEntry} />
                </div>
              </aside>
            </>
          )}

          {atPreviewEnd && (
            <div className="bkr-paywall" role="dialog" aria-label={`${gateLabel} ended`}>
              <div className="bkr-paywall-card">
                <span className="bkr-paywall-chip">{gateLabel}</span>
                <h2 className="bkr-paywall-title">
                  {gateEndTitle}
                </h2>
                <p className="bkr-paywall-text">
                  {gateEndText}
                </p>
                <div className="bkr-paywall-actions">
                  <button type="button" className="bkr-paywall-buy" onClick={handleUnlock}>
                    {gateActionLabel}
                  </button>
                  <button type="button" className="bkr-paywall-back" onClick={() => goToPage(1)}>
                    {locale === 'en' ? 'Back to start' : 'ត្រឡប់ទៅដើម'}
                  </button>
                </div>
              </div>
            </div>
          )}

          {bookId && (
            <AiAssistantPanel
              bookId={bookId}
              pageNumber={Math.min(active, total) || 1}
              pdfDoc={doc}
              isPdf={isPdf}
              pages={pages}
              isOpen={isAiOpen}
              onToggle={() => setIsAiOpen((open) => !open)}
            />
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