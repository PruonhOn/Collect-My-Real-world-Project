import { useMutation, useQuery } from '@tanstack/react-query'
import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import api from '../api/client'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import { useAuth } from '../context/AuthContext'
import { useI18n } from '../i18n'

function parseChapters(content = '') {
  const lines = content.split(/\r?\n/)
  const chapters = []
  let current = null

  for (const line of lines) {
    const trimmed = line.trim()
    if (trimmed.startsWith('## ')) {
      if (current) chapters.push(current)
      current = { title: trimmed.replace(/^##\s+/, ''), paragraphs: [] }
    } else if (current && trimmed) {
      current.paragraphs.push(trimmed)
    }
  }
  if (current) chapters.push(current)

  return chapters
}

function LockedState({ slug }) {
  const { t } = useI18n()
  const { isAuthenticated } = useAuth()

  return (
    <div className="mx-auto max-w-xl px-4 py-24 text-center">
      <div className="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-slate-800">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-white">
          <rect x="3" y="11" width="18" height="11" rx="2" />
          <path d="M7 11V7a5 5 0 0110 0v4" />
        </svg>
      </div>
      <h1 className="mt-8 text-2xl font-extrabold tracking-tight text-slate-900">{t('reader.lockedTitle')}</h1>
      <p className="mt-3 text-slate-600">{t('reader.lockedDesc')}</p>
      <p className="mt-8 inline-block rounded-lg bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">{slug}</p>
      <div className="mt-6 flex items-center justify-center gap-3">
        {isAuthenticated ? (
          <Link to="/pricing" className="btn-primary px-7 py-3">
            {t('reader.vipCta')}
          </Link>
        ) : (
          <Link to="/login" className="btn-primary px-7 py-3">
            {t('reader.loginCta')}
          </Link>
        )}
      </div>
    </div>
  )
}

export default function Reader() {
  const { slug } = useParams()
  const { t } = useI18n()
  const { isAuthenticated } = useAuth()
  const savedTimer = useRef(null)

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['reader', slug],
    queryFn: async () => {
      const res = await api.get(`/read/${slug}`)
      return res.data.data
    },
    retry: false,
  })

  const chapters = useMemo(() => parseChapters(data?.content), [data?.content])

  const [currentIndex, setCurrentIndex] = useState(0)
  const [savedPercent, setSavedPercent] = useState(null)

  useEffect(() => {
    if (chapters.length && data?.progress) {
      const resumed = Math.floor((data.progress / 100) * chapters.length)
      setCurrentIndex(Math.min(resumed, chapters.length - 1))
    }
  }, [chapters.length, data?.progress])

  const progressPercent = chapters.length
    ? Math.round(((currentIndex + 1) / chapters.length) * 100)
    : 0

  const saveProgress = useMutation({
    mutationFn: (percent) => api.put(`/read/${slug}/progress`, { progress: percent }),
    onSuccess: (res) => {
      setSavedPercent(res.data.data.progress)
      clearTimeout(savedTimer.current)
      savedTimer.current = setTimeout(() => setSavedPercent(null), 2000)
    },
  })

  const changeChapter = (index) => {
    const clamped = Math.max(0, Math.min(index, chapters.length - 1))
    setCurrentIndex(clamped)
    const percent = Math.round(((clamped + 1) / chapters.length) * 100)
    if (isAuthenticated) saveProgress.mutate(percent)
  }

  if (isLoading) return <Loading full />
  if (isError && error?.response?.status === 403) {
    return <LockedState slug={slug} />
  }
  if (isError) return <ErrorState retry={() => window.location.reload()} />

  const chapter = chapters[currentIndex]
  const progress = data?.progress ?? 0

  return (
    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
      <div className="flex items-center justify-between">
        <Link
          to={`/books/${data.book.slug}`}
          className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          {data.book.title}
        </Link>
        {isAuthenticated && (
          <div className="flex items-center gap-2.5 rounded-full bg-white px-3 py-1.5 ring-1 ring-slate-200">
            <div className="h-2 w-28 overflow-hidden rounded-full bg-slate-200">
              <div
                className="h-full rounded-full bg-indigo-600 transition-all"
                style={{ width: `${progressPercent}%` }}
              />
            </div>
            <span className="text-xs font-bold text-slate-700">{progressPercent}%</span>
            {savedPercent !== null && (
              <span className="text-xs font-semibold text-emerald-600">
                ✓ {t('reader.saved')} ({savedPercent}%)
              </span>
            )}
          </div>
        )}
      </div>

      <div className="mt-4 md:hidden">
        <label className="mb-1.5 block text-xs font-bold tracking-widest text-slate-400 uppercase">
          {t('reader.chapters')}
        </label>
        <select
          value={currentIndex}
          onChange={(e) => changeChapter(Number(e.target.value))}
          className="select w-full"
        >
          {chapters.map((c, i) => (
            <option key={i} value={i}>
              {c.title}
            </option>
          ))}
        </select>
      </div>

      <div className="mt-6 grid gap-6 md:grid-cols-[240px_1fr]">
        <aside className="hidden md:block">
          <nav className="sticky top-24 rounded-2xl border border-slate-200 bg-white p-4">
            <h2 className="px-2 text-xs font-bold tracking-widest text-slate-400 uppercase">
              {t('reader.chapters')}
            </h2>
            <ul className="mt-3 space-y-1">
              {chapters.map((chapter, i) => (
                <li key={i}>
                  <button
                    onClick={() => changeChapter(i)}
                    className={`w-full truncate rounded-lg px-3 py-2 text-left text-sm transition ${
                      i === currentIndex
                        ? 'bg-indigo-600 font-semibold text-white'
                        : 'text-slate-600 hover:bg-slate-50'
                    }`}
                  >
                    {chapter.title}
                  </button>
                </li>
              ))}
            </ul>
          </nav>
        </aside>

        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{data.book.title}</h1>
          <div className="mt-2 flex items-center gap-2 text-xs text-slate-400">
            <span className="badge bg-indigo-100 text-indigo-700">{data.book.type}</span>
            <span>{progress}%</span>
          </div>

          <article className="mt-8 rounded-3xl border border-slate-200 bg-white p-8 sm:p-12">
            <h2 className="text-2xl font-bold text-indigo-700">{chapter?.title}</h2>
            <div className="mt-6 space-y-5 leading-8 text-slate-700">
              {chapter?.paragraphs.map((paragraph, i) => (
                <p key={i}>{paragraph}</p>
              ))}
            </div>
          </article>

          <div className="mt-8 flex items-center justify-between gap-3">
            <button
              onClick={() => changeChapter(currentIndex - 1)}
              disabled={currentIndex === 0}
              className="btn-secondary px-5 py-2.5"
            >
              ← {t('reader.previous')}
            </button>
            <span className="text-xs font-semibold text-slate-400">
              {currentIndex + 1} / {chapters.length}
            </span>
            <button
              onClick={() => changeChapter(currentIndex + 1)}
              disabled={currentIndex === chapters.length - 1}
              className="btn-primary px-5 py-2.5"
            >
              {t('reader.next')} →
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}