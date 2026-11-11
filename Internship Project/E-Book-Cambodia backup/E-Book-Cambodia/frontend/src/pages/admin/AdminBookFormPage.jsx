import { useState, useEffect, useMemo } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'
import { adminCreateBook, adminUpdateBook, adminGetBook, getCategories, getAuthors } from '../../api'
import AdminNav from '../../components/admin/AdminNav'
import { useLanguage } from '../../context/LanguageContext'

GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url
).toString()

export default function AdminBookFormPage() {
  const { id } = useParams()
  const isEdit = !!id
  const { t } = useLanguage()
  const navigate = useNavigate()

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
    pages: '',
    publisher: '',
    subject: '',
    education_level: '',
    grade: '',
    publication_date: '',
    is_vip_only: false,
  })
  const [coverImage, setCoverImage] = useState(null)
  const [bookFile, setBookFile] = useState(null)
  const [detectingPages, setDetectingPages] = useState(false)
  const [authorMode, setAuthorMode] = useState('select')
  const [error, setError] = useState('')

  const { data: categoriesData } = useQuery({
    queryKey: ['categories-all'],
    queryFn: () => getCategories(),
  })

  const categories = categoriesData?.data?.categories || []

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
        pages: String(book.pages ?? ''),
        publisher: book.publisher || '',
        subject: book.subject || '',
        education_level: book.education_level || '',
        grade: book.grade || '',
        publication_date: book.publication_date || '',
        is_vip_only: !!book.is_vip_only,
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
      // Couldn't read the PDF; leave the pages field for manual entry.
    } finally {
      setDetectingPages(false)
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    const data = new FormData()

    Object.keys(form).forEach((key) => {
      if (form[key] !== '' && form[key] !== null) {
        const value = key === 'is_free' || key === 'is_published' || key === 'is_vip_only' ? (form[key] ? '1' : '0') : form[key]
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
      <AdminNav />

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
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('bookCategories')} *</label>
            <select
              required
              value={form.category_id}
              onChange={(e) => setForm({ ...form, category_id: e.target.value })}
              className={inputClass}
            >
              <option value="">{t('selectCategory')}</option>
              {categories.map((cat) => (
                <option key={cat.id} value={cat.id}>
                  {cat.name}
                </option>
              ))}
            </select>
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

        <div className="grid md:grid-cols-3 gap-4">
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
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('pages')}
              {detectingPages && <span className="ml-2 text-xs font-normal text-indigo-500">Detecting from PDF…</span>}
            </label>
            <input
              type="number"
              min="0"
              value={form.pages}
              onChange={(e) => setForm({ ...form, pages: e.target.value })}
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
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('uploadFile')} (PDF/EPUB)</label>
            <input
              type="file"
              accept=".pdf,.epub"
              onChange={handleBookFileChange}
              className={inputClass}
            />
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
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('educationLevel')}</label>
            <input
              type="text"
              value={form.education_level}
              onChange={(e) => setForm({ ...form, education_level: e.target.value })}
              placeholder="Primary / Secondary / High School"
              className={inputClass}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('grade')}</label>
            <input
              type="text"
              value={form.grade}
              onChange={(e) => setForm({ ...form, grade: e.target.value })}
              placeholder="Grade 7"
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
    </div>
  )
}