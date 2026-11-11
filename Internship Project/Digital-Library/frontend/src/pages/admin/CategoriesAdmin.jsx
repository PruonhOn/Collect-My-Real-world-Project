import { useEffect, useState } from 'react'
import client from '../../api/client'
import { useToast } from '../../context/ToastContext'
import { useLanguage } from '../../context/LanguageContext'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import EmptyState from '../../components/EmptyState'

export default function CategoriesAdmin() {
  const { t } = useLanguage()
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState({ name: '', description: '' })
  const [saving, setSaving] = useState(false)
  const { toast } = useToast()

  const load = () => {
    setLoading(true)
    client
      .get('/categories')
      .then(({ data }) => setCategories(data.categories || []))
      .catch(() => setError(t('errLoadCategoriesAdmin')))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    load()
  }, [])

  const openCreate = () => {
    setEditing(null)
    setForm({ name: '', description: '' })
    setShowForm(true)
  }

  const openEdit = (cat) => {
    setEditing(cat)
    setForm({ name: cat.name, description: cat.description || '' })
    setShowForm(true)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    try {
      if (editing) {
        await client.put(`/admin/categories/${editing.id}`, form)
        toast(t('categoryUpdated'))
      } else {
        await client.post('/admin/categories', form)
        toast(t('categoryCreated'))
      }
      setShowForm(false)
      load()
    } catch (err) {
      toast(err.response?.data?.message || t('errSaveCategory'), 'error')
    } finally {
      setSaving(false)
    }
  }

  const remove = async (cat) => {
    if (!window.confirm(t('deleteCategoryConfirm', { name: cat.name }))) return
    try {
      await client.delete(`/admin/categories/${cat.id}`)
      toast(t('categoryDeleted'), 'info')
      setCategories((prev) => prev.filter((c) => c.id !== cat.id))
    } catch (err) {
      toast(err.response?.data?.message || t('errDeleteCategory'), 'error')
    }
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('categoriesAdminTitle')}</h1>
        <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
          {t('addCategory')}
        </button>
      </div>

      {error ? (
        <ErrorState message={error} />
      ) : loading ? (
        <Spinner />
      ) : categories.length === 0 ? (
        <EmptyState title={t('noCategories')} />
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {categories.map((cat) => (
            <div key={cat.id} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
              <div className="flex items-start justify-between">
                <h3 className="font-semibold text-gray-900">{cat.name}</h3>
                <span className="text-xs text-gray-400">{cat.books_count || 0} {t('catBooks')}</span>
              </div>
              <p className="text-sm text-gray-500 mt-1 line-clamp-2">{cat.description || '-'}</p>
              <div className="flex gap-3 mt-3">
                <button onClick={() => openEdit(cat)} className="text-sm text-indigo-600 hover:text-indigo-800">{t('edit')}</button>
                <button onClick={() => remove(cat)} className="text-sm text-red-500 hover:text-red-700">{t('delete')}</button>
              </div>
            </div>
          ))}
        </div>
      )}

      {showForm && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl w-full max-w-md p-6">
            <h2 className="text-xl font-bold text-gray-900 mb-4">
              {editing ? t('editCategory') : t('addCategoryTitle')}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fName')}</label>
                <input
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  required
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fDesc')}</label>
                <textarea
                  value={form.description}
                  onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                  rows="3"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                />
              </div>
              <div className="flex gap-3 pt-2">
                <button type="submit" disabled={saving} className="flex-1 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                  {saving ? t('saving') : t('save')}
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
