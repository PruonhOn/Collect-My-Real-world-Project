import { useState, useEffect, useMemo } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'
import { splitContentIntoPages } from '../../utils/pages'
import { gradeDisplayName, isGradeSub, parseGradeName } from '../../utils/grades'
import { adminCreateBook, adminUpdateBook, adminGetBook, getCategories, getAuthors } from '../../api'
import BookTocEditor from '../../components/admin/BookTocEditor'
import { useLanguage } from '../../context/LanguageContext'

GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url
).toString()

const LEVEL_NAMES = ['Primary', 'Lower Secondary', 'Upper Secondary School', 'Competitive Exams']

const levelForGrade = (num) => {
  if (num === -1 || num <= 6) return 'Primary'
  if (num <= 9) return 'Lower Secondary'
  return 'Upper Secondary School'
}

const isLevelCategory = (cat) => !isGradeSub(cat) && LEVEL_NAMES.includes(String(cat?.name || ''))

const formatFileSize = (bytes) => {
  const units = ['B', 'KB', 'MB', 'GB']
  let value = Number(bytes)
  let unit = 0
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024
    unit += 1
  }
  return `${value.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`
}

export default function AdminBookFormPage() {
  const { id } = useParams()
  const isEdit = !!id
  const { t, locale } = useLanguage()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [form, setForm] = useState({
    category_id: '',
    title: '',
    description: '',
    content: '',
    author: '',
    language: 'km',
    price: '0',
    is_free: true,
    is_published: true,
    is_coming_soon: false,
    pages: '',
    publisher: '',
    subject: '',
    education_level: '',
    grade: '',
    publication_date: '',
    is_vip_only: false,
    audience: 'general',
    preview_pages: '0',
    preview_mode: 'none',
  })
  const [coverImage, setCoverImage] = useState(null)
  const [bookFile, setBookFile] = useState(null)
  const [detectingPages, setDetectingPages] = useState(false)
  const [authorMode, setAuthorMode] = useState('select')
  const [error, setError] = useState('')

  const { data: categoriesData } = useQuery({
    queryKey: ['categories-all', locale],
    queryFn: () => getCategories({ locale }),
  })

  const categories = categoriesData?.data?.categories || []

  const levelOptions = useMemo(
    () =>
      LEVEL_NAMES.map((name) => {
        const cat = categories.find((c) => c.name === name)
        return { name, label: cat?.display_name || cat?.name || name }
      }),
    [categories]
  )

  const availableGrades = useMemo(() => {
    const grades = new Set()
    categories.filter(isGradeSub).forEach((sub) => {
      const num = parseGradeName(sub)
      if (num !== null) grades.add(num)
    })
    return [...grades].sort((a, b) => (a === -1 ? 0 : a) - (b === -1 ? 0 : b))
  }, [categories])

  const selectedGradeNum = useMemo(() => {
    const grade = String(form.grade || '')
    if (/kindergarten/i.test(grade)) return -1
    const match = grade.match(/grade\s*(\d+)/i)
    return match ? Number(match[1]) : null
  }, [form.grade])

  const gradeSelectValue = selectedGradeNum === null ? '' : String(selectedGradeNum)

  const categoryOptions = useMemo(() => {
    const level = form.education_level
    const gradeNum = selectedGradeNum
    const options = []

    const push = (cats) => {
      cats
        .slice()
        .sort((a, b) =>
          String(a.display_name || a.name).localeCompare(String(b.display_name || b.name))
        )
        .forEach((cat) => {
          if (!options.some((c) => String(c.id) === String(cat.id))) options.push(cat)
        })
    }

    if (gradeNum === null) {
      push(categories.filter((cat) => !isGradeSub(cat)))
      if (level) push(categories.filter((cat) => isLevelCategory(cat) && cat.name === level))
    } else {
      push(
        categories.filter(
          (cat) => isGradeSub(cat) && parseGradeName(cat) === gradeNum && (level === '' || levelForGrade(gradeNum) === level)
        )
      )
      if (level) push(categories.filter((cat) => isLevelCategory(cat) && cat.name === level))
    }

    const current = categories.find((cat) => String(cat.id) === String(form.category_id))
    if (current && !options.some((cat) => String(cat.id) === String(current.id))) {
      options.unshift(current)
    }

    return options
  }, [categories, form.category_id, form.education_level, selectedGradeNum])

  const handleCategoryChange = (value) => {
    if (!value) {
      setForm({ ...form, category_id: '', grade: '' })
      return
    }
    const cat = categories.find((c) => String(c.id) === String(value))
    const num = cat ? parseGradeName(cat) : null
    const next = { ...form, category_id: value }
    next.grade = num != null ? (num === -1 ? t('gradeKindergarten') : `Grade ${num}`) : form.grade
    if (cat && isLevelCategory(cat)) next.education_level = cat.name
    setForm(next)
  }

  const handleLevelChange = (value) => {
    const cat = categories.find((c) => String(c.id) === String(form.category_id))
    let keepCategory = true
    if (value) {
      if (isLevelCategory(cat)) keepCategory = cat.name === value
      else if (isGradeSub(cat) && selectedGradeNum !== null) keepCategory = levelForGrade(selectedGradeNum) === value
    }
    setForm({ ...form, education_level: value, category_id: keepCategory ? form.category_id : '' })
  }

  const handleGradeChange = (value) => {
    const cat = categories.find((c) => String(c.id) === String(form.category_id))
    const catNum = cat ? parseGradeName(cat) : null
    const newNum = value === '' ? null : Number(value)
    const keepCategory =
      !cat || !isGradeSub(cat) || catNum === null || newNum === null || catNum === newNum
    setForm({
      ...form,
      grade: value === '' ? '' : newNum === -1 ? t('gradeKindergarten') : `Grade ${newNum}`,
      category_id: keepCategory ? form.category_id : '',
    })
  }

  const { data: authorsData } = useQuery({
    queryKey: ['authors-all'],
    queryFn: () => getAuthors(),
  })

  const authors = useMemo(() => authorsData?.data?.authors || [], [authorsData])

  const { data: bookData } = useQuery({
    queryKey: ['admin-book', id],
    queryFn: () => adminGetBook(id),
    enabled: isEdit,
  })

  const book = bookData?.data?.book

  useEffect(() => {
    if (book) {
      setForm({
        category_id: book.category_id || '',
        title: book.title || '',
        description: book.description || '',
        content: book.content || '',
        author: book.author || '',
        language: book.language || 'km',
        price: String(book.price ?? 0),
        is_free: !!book.is_free,
        is_published: !!book.is_published,
        is_coming_soon: !!book.is_coming_soon,
        pages: String(book.pages ?? ''),
        publisher: book.publisher || '',
        subject: book.subject || '',
        education_level: book.education_level || '',
        grade: book.grade || '',
        publication_date: book.publication_date || '',
        is_vip_only: !!book.is_vip_only,
        audience: book.audience || 'general',
        preview_pages: String(book.preview_pages ?? 0),
        preview_mode: book.preview_mode || (Number(book.preview_pages ?? 0) > 0 ? 'preview' : 'none'),
      })
    }
  }, [book])

  useEffect(() => {
    if (!authors.length) return
    if (form.author && !authors.includes(form.author)) setAuthorMode('new')
    else setAuthorMode('select')
  }, [authors, form.author])

  const mutation = useMutation({
    mutationFn: (data) => (isEdit ? adminUpdateBook(id, data) : adminCreateBook(data)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['books'] })
      queryClient.invalidateQueries({ queryKey: ['platform-stats'] })
      queryClient.invalidateQueries({ queryKey: ['admin-books'] })
      queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['categories-all'] })
      navigate('/admin/books')
    },
    onError: (err) => {
      const errors = err.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message || 'Failed to save book')
    },
  })

  const handleBookFileChange = async (e) => {
    const file = e.target.files[0] || null
    setBookFile(file)
    if (!file || !file.name.toLowerCase().endsWith('.pdf')) return

    setDetectingPages(true)
    try {
      const pdf = await getDocument({ data: await file.arrayBuffer() }).promise
      setForm((prev) => ({ ...prev, pages: String(pdf.numPages) }))
      void pdf.destroy?.()
    } catch {
      // Couldn't read the PDF; pages will be auto-counted on the backend.
    } finally {
      setDetectingPages(false)
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    const data = new FormData()

    const { ...rest } = form

    const payload = {
      ...rest,
      preview_pages:
        form.preview_mode !== 'none'
          ? String(Math.max(1, Number(form.preview_pages) || 0))
          : '0',
      preview_mode: form.preview_mode === 'none' ? 'none' : form.preview_mode,
      pages:
        Number(form.pages) > 0
          ? form.pages
          : form.content
            ? splitContentIntoPages(form.content).length
            : '',
    }

    Object.keys(payload).forEach((key) => {
      if (payload[key] !== '' && payload[key] !== null) {
        const value =
          key === 'is_free' || key === 'is_published' || key === 'is_vip_only' || key === 'is_coming_soon'
            ? payload[key]
              ? '1'
              : '0'
            : payload[key]
        data.append(key, value)
      }
    })

    if (coverImage) data.append('cover_image', coverImage)
    if (bookFile) data.append('file', bookFile)

    if (isEdit) {
      data.append('_method', 'PUT')
    }

    mutation.mutate(data)
  }

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">
        {isEdit ? t('editBook') : t('addBook')}
      </h1>

      {error && <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}

      {isEdit && bookData && !book && (
        <div className="mb-4 p-4 bg-amber-50 text-amber-700 rounded-xl text-sm">
          Book not found
        </div>
      )}

      <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm p-6 md:p-8 space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('title')} *</label>
          <input
            type="text"
            required
            value={form.title}
            onChange={(e) => setForm({ ...form, title: e.target.value })}
            className={inputClass}
          />
        </div>

        <div className="grid md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('educationLevel')}</label>
            <select
              value={form.education_level}
              onChange={(e) => handleLevelChange(e.target.value)}
              className={inputClass}
            >
              <option value="">{t('filterAll')}</option>
              {levelOptions.map((opt) => (
                <option key={opt.name} value={opt.name}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('grade')}</label>
            <select
              value={gradeSelectValue}
              onChange={(e) => handleGradeChange(e.target.value)}
              className={inputClass}
            >
              <option value="">{t('gradeAll')}</option>
              {availableGrades.map((num) => (
                <option key={num} value={num}>
                  {gradeDisplayName(locale, t, num)}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('bookCategories')} *</label>
            <select
              required
              value={form.category_id}
              onChange={(e) => handleCategoryChange(e.target.value)}
              className={inputClass}
            >
              <option value="">{t('selectCategory')}</option>
              {categoryOptions.map((cat) => (
                <option key={cat.id} value={cat.id}>
                  {cat.display_name || cat.name}
                </option>
              ))}
            </select>
            <p className="mt-1 text-xs text-gray-400">
              {t('categoryFilteredHint')}
            </p>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('bookAuthors')}</label>
            {authorMode === 'select' ? (
              <select
                value={form.author}
                onChange={(e) => {
                  if (e.target.value === '__new__') {
                    setAuthorMode('new')
                    setForm({ ...form, author: '' })
                  } else {
                    setForm({ ...form, author: e.target.value })
                  }
                }}
                className={inputClass}
              >
                <option value="">{t('selectAuthor')}</option>
                {authors.map((a) => (
                  <option key={a} value={a}>
                    {a}
                  </option>
                ))}
                <option value="__new__">+ {t('addAuthor')}</option>
              </select>
            ) : (
              <div className="flex gap-2">
                <input
                  type="text"
                  value={form.author}
                  onChange={(e) => setForm({ ...form, author: e.target.value })}
                  placeholder={`${t('addAuthor')} name`}
                  className={inputClass}
                />
                <button
                  type="button"
                  onClick={() => setAuthorMode('select')}
                  className="shrink-0 px-3 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 text-sm"
                >
                  Choose
                </button>
              </div>
            )}
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">{t('audienceLabel')}</label>
          <div className="grid grid-cols-3 gap-2">
            {[
              { value: 'general', label: t('audienceGeneral') },
              { value: 'student', label: t('audienceStudent') },
              { value: 'teacher', label: t('audienceTeacher') },
            ].map((option) => (
              <button
                key={option.value}
                type="button"
                onClick={() => setForm({ ...form, audience: option.value })}
                className={`px-3 py-2.5 rounded-xl border text-sm font-medium transition-colors ${
                  form.audience === option.value
                    ? 'bg-indigo-600 text-white border-indigo-600'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'
                }`}
              >
                {option.label}
              </button>
            ))}
          </div>
          <p className="mt-1 text-xs text-gray-400">{t('audienceHint')}</p>
        </div>

        <div className="grid md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('price')} ($)</label>
            <input
              type="number"
              min="0"
              step="0.01"
              value={form.price}
              onChange={(e) => setForm({ ...form, price: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('bookType')}</label>
            <select
              value={form.is_free ? 'free' : 'paid'}
              onChange={(e) => setForm({ ...form, is_free: e.target.value === 'free' })}
              className={inputClass}
            >
              <option value="free">{t('free')}</option>
              <option value="paid">{t('paid')}</option>
            </select>
          </div>
        </div>

        {(!form.is_free || form.is_vip_only) && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('readingGate')}
            </label>
            <select
              value={form.preview_mode}
              onChange={(e) => {
                const mode = e.target.value
                setForm({
                  ...form,
                  preview_mode: mode,
                  preview_pages:
                    mode !== 'none' && !Number(form.preview_pages) ? '1' : form.preview_pages,
                })
              }}
              className={inputClass}
            >
              <option value="none">{t('gateNone')}</option>
              <option value="preview">{t('gateFreePreview')}</option>
              <option value="readtest">{t('gateReadtest')}</option>
            </select>

            {form.preview_mode !== 'none' && (
              <div className="mt-3">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {form.preview_mode === 'readtest' ? t('readtestPages') : t('freePreviewPages')}
                </label>
                <input
                  type="number"
                  min="1"
                  max="10000"
                  step="1"
                  value={form.preview_pages}
                  onChange={(e) =>
                    setForm({ ...form, preview_pages: e.target.value.replace(/[^\d]/g, '') })
                  }
                  className={inputClass}
                />
                <p className="mt-1 text-xs text-gray-400">{t('readingGateHint')}</p>
              </div>
            )}
          </div>
        )}

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
          <textarea
            rows={4}
            value={form.description}
            onChange={(e) => setForm({ ...form, description: e.target.value })}
            className={inputClass}
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Book Content (page by page)</label>
          <p className="text-xs text-gray-400 mb-2">Enter the book content. It will be split into pages (250 words per page) for the reader.</p>
          <textarea
            rows={10}
            value={form.content}
            onChange={(e) => setForm({ ...form, content: e.target.value })}
            placeholder="Enter book content here..."
            className={inputClass}
          />
        </div>

        <div className="grid md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('language')}</label>
            <select
              value={form.language}
              onChange={(e) => setForm({ ...form, language: e.target.value })}
              className={inputClass}
            >
              <option value="km">Khmer</option>
              <option value="en">English</option>
              <option value="zh">Chinese (中文)</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('uploadCover')}</label>
            <input
              type="file"
              accept="image/*"
              onChange={(e) => setCoverImage(e.target.files[0])}
              className={inputClass}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('uploadFile')} (PDF)</label>
            <input
              type="file"
              accept=".pdf,application/pdf"
              onChange={handleBookFileChange}
              className={inputClass}
            />
            {detectingPages ? (
              <p className="mt-1 text-xs text-indigo-500">{t('detectingPages')}</p>
            ) : (
              <p className="mt-1 text-xs text-gray-500">
                {t('pagesAuto')}
                {Number(form.pages) > 0 && (
                  <span className="ml-1 font-medium text-indigo-600">
                    ({form.pages} {t('pages')})
                  </span>
                )}
              </p>
            )}
            {book?.file_path && (
              <p className="mt-1 text-xs text-gray-400 truncate">
                {book.file_name || book.file_path}
                {book.file_size ? ` · ${formatFileSize(book.file_size)}` : ''}
              </p>
            )}
          </div>
        </div>

        <div className="grid md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('publisher')}</label>
            <input
              type="text"
              value={form.publisher}
              onChange={(e) => setForm({ ...form, publisher: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('subject')}</label>
            <input
              type="text"
              value={form.subject}
              onChange={(e) => setForm({ ...form, subject: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('publicationDate')}</label>
            <input
              type="date"
              value={form.publication_date}
              onChange={(e) => setForm({ ...form, publication_date: e.target.value })}
              className={inputClass}
            />
          </div>
        </div>

        <div>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_vip_only}
              onChange={(e) => setForm({ ...form, is_vip_only: e.target.checked })}
              className="w-4 h-4 text-indigo-600 rounded"
            />
            {t('vipOnly')}
          </label>
        </div>

        <div>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_coming_soon}
              onChange={(e) => setForm({ ...form, is_coming_soon: e.target.checked })}
              className="w-4 h-4 text-indigo-600 rounded"
            />
            {t('comingSoon')}
          </label>
        </div>

        <div>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_published}
              onChange={(e) => setForm({ ...form, is_published: e.target.checked })}
              className="w-4 h-4 text-indigo-600 rounded"
            />
            {t('published')}
          </label>
        </div>

        <div className="flex gap-3 pt-4">
          <button
            type="submit"
            disabled={mutation.isPending}
            className="flex-1 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
          >
            {mutation.isPending ? t('loading') : t('save')}
          </button>
          <button
            type="button"
            onClick={() => navigate('/admin/books')}
            className="px-6 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50"
          >
            {t('cancel')}
          </button>
        </div>
      </form>

      {isEdit && (
        <div className="mt-6">
          <BookTocEditor bookId={id} pdfFile={bookFile} getDocument={getDocument} />
        </div>
      )}
    </div>
  )
}
