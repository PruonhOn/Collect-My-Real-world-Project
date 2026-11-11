import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getCategories, adminCreateCategory, adminUpdateCategory, adminDeleteCategory } from '../../api'
import AdminNav from '../../components/admin/AdminNav'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminCategoriesPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ name: '', description: '' })
  const [error, setError] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-categories'],
    queryFn: () => getCategories(),
  })

  const categories = data?.data?.categories || []

  const advanceQuery = () => {
    queryClient.invalidateQueries(['admin-categories'])
    setShowForm(false)
    setEditing(null)
    setForm({ name: '', description: '' })
    setError('')
  }

  const createMutation = useMutation({
    mutationFn: (data) => adminCreateCategory(data),
    onSuccess: advanceQuery,
    onError: (err) => {
      const errors = err.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message)
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }) => adminUpdateCategory(id, data),
    onSuccess: advanceQuery,
    onError: (err) => {
      const errors = err.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteCategory(id),
    onSuccess: () => queryClient.invalidateQueries(['admin-categories']),
  })

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    if (editing) {
      updateMutation.mutate({ id: editing.id, data: form })
    } else {
      createMutation.mutate(form)
    }
  }

  const startEdit = (cat) => {
    setEditing(cat)
    setShowForm(true)
    setForm({ name: cat.name || '', description: cat.description || '' })
  }

  const handleDelete = (cat) => {
    if (window.confirm(`${t('confirmDelete')} "${cat.name}"?`)) {
      deleteMutation.mutate(cat.id)
    }
  }

  const inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <AdminNav />

      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('categoriesManagement')}</h1>
          <p className="text-gray-500 text-sm mt-1">{categories.length} {t('categories')}</p>
        </div>
        <button
          onClick={() => {
            setShowForm(!showForm)
            setEditing(null)
            setForm({ name: '', description: '' })
            setError('')
          }}
          className="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700"
        >
          + {t('addCategory')}
        </button>
      </div>

      {showForm && (
        <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm p-6 mb-6 space-y-4">
          <h2 className="text-lg font-semibold text-gray-900">
            {editing ? t('editCategory') : t('addCategory')}
          </h2>
          {error && <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('title')} *</label>
            <input type="text" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
            <input type="text" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={inputClass} />
          </div>
          <div className="flex gap-3">
            <button type="submit" disabled={createMutation.isPending || updateMutation.isPending} className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-60">
              {t('save')}
            </button>
            <button type="button" onClick={() => { setShowForm(false); setEditing(null); setError('') }} className="px-6 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
              {t('cancel')}
            </button>
          </div>
        </form>
      )}

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('title')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('description')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('books')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {categories.map((cat) => (
                  <tr key={cat.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm text-gray-500">{cat.id}</td>
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{cat.name || '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{cat.description || '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">{cat.books_count}</td>
                    <td className="px-6 py-4 text-right text-sm space-x-2 whitespace-nowrap">
                      <button onClick={() => startEdit(cat)} className="text-indigo-600 hover:text-indigo-800">
                        {t('edit')}
                      </button>
                      <button onClick={() => handleDelete(cat)} className="text-red-600 hover:text-red-800">
                        {t('delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}