import { useEffect, useState } from 'react'
import client from '../../api/client'
import { useToast } from '../../context/ToastContext'
import { useLanguage } from '../../context/LanguageContext'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import EmptyState from '../../components/EmptyState'
import Pagination from '../../components/Pagination'

const emptyForm = {
  category_id: '',
  title: '',
  description: '',
  author: '',
  language: 'km',
  pages: 0,
  price: 0,
  is_free: false,
  is_published: true,
}

export default function BooksAdmin() {
  const { t } = useLanguage()
  const [books, setBooks] = useState([])
  const [categories, setCategories] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [page, setPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [coverFile, setCoverFile] = useState(null)
  const [bookFile, setBookFile] = useState(null)
  const [saving, setSaving] = useState(false)
  const { toast } = useToast()

  useEffect(() => {
    setLoading(true)
    setError(null)

    const loadBooks = () =>
      client
        .get(`/admin/books?page=${page}`)
        .then(({ data }) => {
          setBooks(data.data || [])
          setMeta(data.meta || {})
        })
        .catch(() => setError(t('errLoadBooksAdmin')))
        .finally(() => setLoading(false))

    client
      .get('/categories')
      .then(({ data }) => setCategories(data.categories || []))
      .catch(() => {})

    loadBooks()
  }, [page, t])

  const openCreate = () => {
    setEditing(null)
    setForm(emptyForm)
    setCoverFile(null)
    setBookFile(null)
    setShowForm(true)
  }

  const openEdit = (book) => {
    setEditing(book)
    setForm({
      category_id: book.category_id,
      title: book.title,
      description: book.description || '',
      author: book.author || '',
      language: book.language || 'km',
      pages: book.pages || 0,
      price: Number(book.price),
      is_free: Boolean(book.is_free),
      is_published: Boolean(book.is_published),
    })
    setCoverFile(null)
    setBookFile(null)
    setShowForm(true)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    const fd = new FormData()
    Object.entries(form).forEach(([k, v]) => {
      if (typeof v === 'boolean') {
        fd.append(k, v ? '1' : '0')
      } else {
        fd.append(k, String(v))
      }
    })
    if (coverFile) fd.append('cover_image', coverFile)
    if (bookFile) fd.append('file', bookFile)

    try {
      if (editing) {
        fd.append('_method', 'PUT')
        await client.post(`/admin/books/${editing.id}`, fd)
        toast(t('bookUpdated'))
      } else {
        await client.post('/admin/books', fd)
        toast(t('bookCreated'))
      }
      setShowForm(false)
      setPage(1)
      const { data } = await client.get('/admin/books?page=1')
      setBooks(data.data || [])
      setMeta(data.meta || {})
    } catch (err) {
      const errors = err.response?.data?.errors
      if (errors && typeof errors === 'object') {
        const first = Object.values(errors)[0]?.[0]
        toast(first || t('errSaveBook'), 'error')
      } else {
        toast(err.response?.data?.message || t('errSaveBook'), 'error')
      }
    } finally {
      setSaving(false)
    }
  }

  const remove = async (book) => {
    if (!window.confirm(t('deleteBookConfirm', { title: book.title }))) return
    try {
      await client.delete(`/admin/books/${book.id}`)
      toast(t('bookDeleted'), 'info')
      setBooks((prev) => prev.filter((b) => b.id !== book.id))
    } catch {
      toast(t('errDeleteBook'), 'error')
    }
  }

  const input = (name) => ({
    value: form[name],
    onChange: (e) => setForm((f) => ({ ...f, [name]: e.target.type === 'checkbox' ? e.target.checked : e.target.value })),
  })

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('booksAdminTitle')}</h1>
        <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
          {t('addBook')}
        </button>
      </div>

      {error ? (
        <ErrorState message={error} />
      ) : loading ? (
        <Spinner />
      ) : books.length === 0 ? (
        <EmptyState title={t('noBooks')} />
      ) : (
        <>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thTitle')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thCategory')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thPrice')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thStatus')}</th>
                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{t('thActions')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {books.map((book) => (
                    <tr key={book.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3">
                        <p className="font-medium text-gray-900">{book.title}</p>
                        <p className="text-xs text-gray-500">{book.author}</p>
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">{book.category?.name || '-'}</td>
                      <td className="px-4 py-3 text-sm font-semibold">
                        {Number(book.price) === 0 ? <span className="text-emerald-600">{t('free')}</span> : `$${Number(book.price).toFixed(2)}`}
                      </td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                          book.is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'
                        }`}>
                          {book.is_published ? t('published') : t('draft')}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-right space-x-2">
                        <button onClick={() => openEdit(book)} className="text-sm text-indigo-600 hover:text-indigo-800">{t('edit')}</button>
                        <button onClick={() => remove(book)} className="text-sm text-red-500 hover:text-red-700">{t('delete')}</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}

      {showForm && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
            <h2 className="text-xl font-bold text-gray-900 mb-4">
              {editing ? t('editBook') : t('addBookTitle')}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fCategory')}</label>
                <select {...input('category_id')} required className="w-full px-3 py-2 border border-gray-300 rounded-lg">
                  <option value="">{t('selectCategory')}</option>
                  {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fTitle')}</label>
                <input {...input('title')} required className="w-full px-3 py-2 border border-gray-300 rounded-lg" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fDescription')}</label>
                <textarea {...input('description')} rows="3" className="w-full px-3 py-2 border border-gray-300 rounded-lg" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('fAuthor')}</label>
                  <input {...input('author')} className="w-full px-3 py-2 border border-gray-300 rounded-lg" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('fPages')}</label>
                  <input {...input('pages')} type="number" min="0" className="w-full px-3 py-2 border border-gray-300 rounded-lg" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('fPrice')}</label>
                  <input {...input('price')} type="number" step="0.01" min="0" className="w-full px-3 py-2 border border-gray-300 rounded-lg" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('fLanguage')}</label>
                  <select {...input('language')} className="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="km">{t('langKhmer')}</option>
                    <option value="en">{t('langEnglish')}</option>
                  </select>
                </div>
              </div>
              <div className="space-y-3">
                <div className="flex items-center gap-6">
                  <label className="flex items-center gap-2 text-sm text-gray-700">
                    <input {...input('is_free')} type="checkbox" className="w-4 h-4" />
                    {t('fFree')}
                  </label>
                  <label className="flex items-center gap-2 text-sm text-gray-700">
                    <input {...input('is_published')} type="checkbox" className="w-4 h-4" />
                    {t('fPublished')}
                  </label>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('fCover')}</label>
                  <input type="file" accept="image/*" onChange={(e) => setCoverFile(e.target.files[0])} className="w-full text-sm" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('fBookFile')}</label>
                  <input type="file" accept=".pdf,application/pdf" onChange={(e) => setBookFile(e.target.files[0])} className="w-full text-sm" />
                </div>
              </div>
              <div className="flex gap-3 pt-2">
                <button type="submit" disabled={saving} className="flex-1 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                  {saving ? t('saving') : editing ? t('save') : t('create')}
                </button>
                <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700">
                  {t('cancel')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
