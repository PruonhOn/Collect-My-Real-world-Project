import { useState, useEffect, useMemo } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'
import { splitContentIntoPages } from '../../utils/pages'
import { gradeDisplayName, isGradeSub, parseGradeName } from '../../utils/grades'
import {
  authorCreateBook,
  authorUpdateBook,
  authorGetBook,
  authorSubmitBook,
  getCategories,
  coverUrl,
} from '../../api'
import { useLanguage } from '../../context/LanguageContext'

GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url
).toString()

const LEVEL_NAMES = ['Primary', 'Lower Secondary', 'Upper Secondary School', 'Competitive Exams']

/** Tags are typed as free text and normalised here, mirroring the backend. */
const parseTags = (value) => {
  const seen = new Set()
  return String(value || '')
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean)
    .filter((tag) => {
      const key = tag.toLowerCase()
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
    .slice(0, 12)
}

const levelForGrade = (num) => {
  if (num === -1 || num <= 6) return 'Primary'
  if (num <= 9) return 'Lower Secondary'
  return 'Upper Secondary School'
}

const isLevelCategory = (cat) => !isGradeSub(cat) && LEVEL_NAMES.includes(String(cat?.name || ''))

export default function AuthorBookFormPage() {
  const { id } = useParams()
  const isEdit = !!id
  const { t, locale } = useLanguage()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    category_id: '',
    title: '',
    description: '',
    content: '',
    language: 'km',
    price: '0',
    is_free: true,
    is_coming_soon: false,
    pages: '',
    publisher: '',
    subject: '',
    education_level: '',
    grade: '',
    publication_date: '',
    is_vip_only: false,
    audience: 'general',
  })
  const [tagsInput, setTagsInput] = useState('')
  const [coverImage, setCoverImage] = useState(null)
  const [coverPreview, setCoverPreview] = useState(null)
  const [bookFile, setBookFile] = useState(null)
  const [detectingPages, setDetectingPages] = useState(false)
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
          (cat) =>
            isGradeSub(cat) &&
            parseGradeName(cat) === gradeNum &&
            (level === '' || levelForGrade(gradeNum) === level)
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
      else if (isGradeSub(cat) && selectedGradeNum !== null)
        keepCategory = levelForGrade(selectedGradeNum) === value
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

  const { data: bookData } = useQuery({
    queryKey: ['author-book', id],
    queryFn: () => authorGetBook(id),
    enabled: isEdit,
  })

  const book = bookData?.data?.book

  // A book that is under review or already live is frozen: the author must not
  // be able to change what an admin is reviewing. The backend enforces this too.
  const canEdit = !isEdit || book?.status === 'draft' || book?.status === 'rejected'

  useEffect(() => {
    if (book) {
      setForm({
        category_id: book.category_id || '',
        title: book.title || '',
        description: book.description || '',
        content: book.content || '',
        language: book.language || 'km',
        price: String(book.price ?? 0),
        is_free: !!book.is_free,
        is_coming_soon: !!book.is_coming_soon,
        pages: String(book.pages ?? ''),
        publisher: book.publisher || '',
        subject: book.subject || '',
        education_level: book.education_level || '',
        grade: book.grade || '',
        publication_date: book.publication_date || '',
        is_vip_only: !!book.is_vip_only,
        audience: book.audience || 'general',
      })
      setTagsInput((book.tags || []).map((tag) => tag.name).join(', '))
    }
  }, [book])

  const mutation = useMutation({
    mutationFn: (data) => (isEdit ? authorUpdateBook(id, data) : authorCreateBook(data)),
    onSuccess: () => navigate('/author/books'),
    onError: (err) => {
      const errors = err.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message || '')
    },
  })

  const submitMutation = useMutation({
    mutationFn: (data) =>
      (isEdit ? authorUpdateBook(id, data) : authorCreateBook(data)).then((res) =>
        authorSubmitBook(res.data.book.id)
      ),
    onSuccess: () => navigate('/author/books'),
    onError: (err) => {
      const errors = err.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message || '')
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

  const handleCoverChange = (e) => {
    const file = e.target.files[0] || null
    setCoverImage(file)
    if (coverPreview) URL.revokeObjectURL(coverPreview)
    setCoverPreview(file ? URL.createObjectURL(file) : null)
  }

  useEffect(
    () => () => {
      if (coverPreview) URL.revokeObjectURL(coverPreview)
    },
    [coverPreview]
  )

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    const data = new FormData()

    const payload = {
      ...form,
      pages:
        Number(form.pages) > 0 ? form.pages : form.content ? splitContentIntoPages(form.content).length : '',
    }

    Object.keys(payload).forEach((key) => {
      if (payload[key] !== '' && payload[key] !== null) {
        const value =
          key === 'is_free' || key === 'is_vip_only' || key === 'is_coming_soon'
            ? payload[key]
              ? '1'
              : '0'
            : payload[key]
        data.append(key, value)
      }
    })

    parseTags(tagsInput).forEach((tag) => data.append('tags[]', tag))

    if (coverImage) data.append('cover_image', coverImage)
    if (bookFile) data.append('file', bookFile)
    if (isEdit) data.append('_method', 'PUT')

    mutation.mutate(data)
  }

  /**
   * Save the form and, when successful, immediately send the book for review.
   * The backend owns the transition, so this only sequences two calls.
   */
  const handleSaveAndSubmit = (e) => {
    e.preventDefault()
    setError('')
    const data = new FormData()

    const payload = {
      ...form,
      pages:
        Number(form.pages) > 0 ? form.pages : form.content ? splitContentIntoPages(form.content).length : '',
    }

    Object.keys(payload).forEach((key) => {
      if (payload[key] !== '' && payload[key] !== null) {
        const value =
          key === 'is_free' || key === 'is_vip_only' || key === 'is_coming_soon'
            ? payload[key]
              ? '1'
              : '0'
            : payload[key]
        data.append(key, value)
      }
    })

    parseTags(tagsInput).forEach((tag) => data.append('tags[]', tag))

    if (coverImage) data.append('cover_image', coverImage)
    if (bookFile) data.append('file', bookFile)
    if (isEdit) data.append('_method', 'PUT')

    submitMutation.mutate(data)
  }

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500'

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">
        {isEdit ? t('editBook') : t('addBook')}
      </h1>

      {error && <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}

      {isEdit && book?.status === 'rejected' && (
        <div className="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm">
          <p className="font-semibold">{t('changesRequested')}</p>
          {book.review_note && <p className="mt-1">{book.review_note}</p>}
        </div>
      )}

      {isEdit && !canEdit && (
        <div className="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-sm">
          {t('lockedForReview')}
        </div>
      )}

      <form onSubmit={handleSubmit}>
      <fieldset disabled={!canEdit} className="bg-white rounded-2xl shadow-sm p-6 md:p-8 space-y-4">
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
            <select value={form.education_level} onChange={(e) => handleLevelChange(e.target.value)} className={inputClass}>
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
            <select value={gradeSelectValue} onChange={(e) => handleGradeChange(e.target.value)} className={inputClass}>
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
            <select required value={form.category_id} onChange={(e) => handleCategoryChange(e.target.value)} className={inputClass}>
              <option value="">{t('selectCategory')}</option>
              {categoryOptions.map((cat) => (
                <option key={cat.id} value={cat.id}>
                  {cat.display_name || cat.name}
                </option>
              ))}
            </select>
            <p className="mt-1 text-xs text-gray-400">{t('categoryFilteredHint')}</p>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('language')}</label>
            <select value={form.language} onChange={(e) => setForm({ ...form, language: e.target.value })} className={inputClass}>
              <option value="km">Khmer</option>
              <option value="en">English</option>
              <option value="zh">Chinese (中文)</option>
            </select>
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
                    ? 'bg-emerald-600 text-white border-emerald-600'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-300'
                }`}
              >
                {option.label}
              </button>
            ))}
          </div>
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
            <select value={form.is_free ? 'free' : 'paid'} onChange={(e) => setForm({ ...form, is_free: e.target.value === 'free' })} className={inputClass}>
              <option value="free">{t('free')}</option>
              <option value="paid">{t('paid')}</option>
            </select>
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
          <textarea rows={4} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={inputClass} />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('bookContent')}</label>
          <p className="text-xs text-gray-400 mb-2">{t('contentSplitHint')}</p>
          <textarea
            rows={10}
            value={form.content}
            onChange={(e) => setForm({ ...form, content: e.target.value })}
            className={inputClass}
          />
        </div>

        <div className="grid md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('uploadCover')}</label>
            <input type="file" accept="image/*" onChange={handleCoverChange} className={inputClass} />
            {(coverPreview || (book && coverUrl(book))) && (
              <div className="mt-2">
                <img
                  src={coverPreview || coverUrl(book)}
                  alt={t('uploadCover')}
                  className="w-28 h-36 object-contain bg-white p-1.5 rounded-xl ring-1 ring-gray-200"
                />
              </div>
            )}
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('bookFileUpload')}</label>
            <input type="file" accept=".pdf,application/pdf" onChange={handleBookFileChange} className={inputClass} />
            {detectingPages ? (
              <p className="mt-1 text-xs text-emerald-500">{t('detectingPages')}</p>
            ) : (
              <p className="mt-1 text-xs text-gray-500">
                {t('pagesAuto')}
                {Number(form.pages) > 0 && (
                  <span className="ml-1 font-medium text-emerald-600">
                    ({form.pages} {t('pages')})
                  </span>
                )}
              </p>
            )}
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('publisher')}</label>
            <input type="text" value={form.publisher} onChange={(e) => setForm({ ...form, publisher: e.target.value })} className={inputClass} />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('subject')}</label>
            <input type="text" value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} className={inputClass} />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('publicationDate')}</label>
            <input type="date" value={form.publication_date} onChange={(e) => setForm({ ...form, publication_date: e.target.value })} className={inputClass} />
          </div>
        </div>

        <div className="flex items-center gap-6">
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_vip_only}
              onChange={(e) => setForm({ ...form, is_vip_only: e.target.checked })}
              className="w-4 h-4 text-emerald-600 rounded"
            />
            {t('vipOnly')}
          </label>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_coming_soon}
              onChange={(e) => setForm({ ...form, is_coming_soon: e.target.checked })}
              className="w-4 h-4 text-emerald-600 rounded"
            />
            {t('comingSoon')}
          </label>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('tags')}</label>
          <input
            type="text"
            value={tagsInput}
            onChange={(e) => setTagsInput(e.target.value)}
            placeholder={t('tagsPlaceholder')}
            className={inputClass}
          />
          <p className="mt-1 text-xs text-gray-400">{t('tagsHint')}</p>
        </div>

        <div className="flex flex-wrap gap-3 pt-4">
          <button
            type="submit"
            disabled={mutation.isPending || !canEdit}
            className="flex-1 min-w-[10rem] py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-60"
          >
            {mutation.isPending ? t('loading') : t('save')}
          </button>
          <button
            type="button"
            onClick={handleSaveAndSubmit}
            disabled={submitMutation.isPending || !canEdit}
            className="flex-1 min-w-[10rem] py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 disabled:opacity-60"
          >
            {submitMutation.isPending ? t('loading') : t('submitForReview')}
          </button>
          <button
            type="button"
            onClick={() => navigate('/author/books')}
            className="px-6 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50"
          >
            {t('cancel')}
          </button>
        </div>
      </fieldset>
      </form>
    </div>
  )
}