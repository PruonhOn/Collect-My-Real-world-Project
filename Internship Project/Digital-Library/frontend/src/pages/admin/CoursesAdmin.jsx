import { useEffect, useState } from 'react'
import client from '../../api/client'
import { useToast } from '../../context/ToastContext'
import { useLanguage } from '../../context/LanguageContext'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import EmptyState from '../../components/EmptyState'

const emptyForm = { title: '', description: '', price: 0, is_published: true }

export default function CoursesAdmin() {
  const { t } = useLanguage()
  const [courses, setCourses] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [coverFile, setCoverFile] = useState(null)
  const [saving, setSaving] = useState(false)
  const { toast } = useToast()

  useEffect(() => {
    setLoading(true)
    client
      .get('/courses')
      .then(({ data }) => setCourses(data.courses || []))
      .catch(() => setError(t('errLoadCourses')))
      .finally(() => setLoading(false))
  }, [t])

  const openCreate = () => {
    setEditing(null)
    setForm(emptyForm)
    setCoverFile(null)
    setShowForm(true)
  }

  const openEdit = (course) => {
    setEditing(course)
    setForm({
      title: course.title,
      description: course.description || '',
      price: Number(course.price),
      is_published: Boolean(course.is_published),
    })
    setCoverFile(null)
    setShowForm(true)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    const fd = new FormData()
    Object.entries(form).forEach(([k, v]) => fd.append(k, String(v)))
    if (coverFile) fd.append('cover_image', coverFile)

    try {
      if (editing) {
        fd.append('_method', 'PUT')
        await client.post(`/admin/courses/${editing.id}`, fd)
        toast(t('courseUpdated'))
      } else {
        await client.post('/admin/courses', fd)
        toast(t('courseCreated'))
      }
      setShowForm(false)
      const { data } = await client.get('/courses')
      setCourses(data.courses || [])
    } catch (err) {
      toast(err.response?.data?.message || t('errSaveCourse'), 'error')
    } finally {
      setSaving(false)
    }
  }

  const remove = async (course) => {
    if (!window.confirm(t('deleteCourseConfirm', { title: course.title }))) return
    try {
      await client.delete(`/admin/courses/${course.id}`)
      toast(t('courseDeleted'), 'info')
      setCourses((prev) => prev.filter((c) => c.id !== course.id))
    } catch {
      toast(t('errDeleteCourse'), 'error')
    }
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('coursesAdminTitle')}</h1>
        <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
          {t('addCourse')}
        </button>
      </div>

      {error ? (
        <ErrorState message={error} />
      ) : loading ? (
        <Spinner />
      ) : courses.length === 0 ? (
        <EmptyState title={t('noCourses')} />
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {courses.map((course) => (
            <div key={course.id} className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
              {course.cover_image ? (
                <img
                  src={`${import.meta.env.VITE_API_URL?.replace('/api/v1', '') || ''}/storage/${course.cover_image}`}
                  alt={course.title}
                  className="w-full h-32 object-cover"
                />
              ) : (
                <div className="w-full h-32 bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-4xl text-white">🎓</div>
              )}
              <div className="p-4">
                <div className="flex items-start justify-between">
                  <h3 className="font-semibold text-gray-900">{course.title}</h3>
                  <span className="text-sm font-bold text-gray-900">
                    {Number(course.price) === 0 ? <span className="text-emerald-600">{t('free')}</span> : `$${Number(course.price).toFixed(2)}`}
                  </span>
                </div>
                <p className="text-sm text-gray-500 mt-1 line-clamp-2">{course.description || '-'}</p>
                <p className="text-xs text-gray-400 mt-2">{course.lessons_count || 0} {t('lessons')}</p>
                <div className="flex gap-3 mt-3">
                  <button onClick={() => openEdit(course)} className="text-sm text-indigo-600 hover:text-indigo-800">{t('edit')}</button>
                  <button onClick={() => remove(course)} className="text-sm text-red-500 hover:text-red-700">{t('delete')}</button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {showForm && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl w-full max-w-md p-6">
            <h2 className="text-xl font-bold text-gray-900 mb-4">
              {editing ? t('editCourse') : t('addCourseTitle')}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fCourseTitle')}</label>
                <input
                  value={form.title}
                  onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                  required
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fDescription')}</label>
                <textarea
                  value={form.description}
                  onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                  rows="3"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('fPrice')}</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    value={form.price}
                    onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                  />
                </div>
                <div className="flex items-end">
                  <label className="flex items-center gap-2 text-sm text-gray-700 pb-2">
                    <input
                      type="checkbox"
                      checked={form.is_published}
                      onChange={(e) => setForm((f) => ({ ...f, is_published: e.target.checked }))}
                      className="w-4 h-4"
                    />
                    {t('fPublished')}
                  </label>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('fCover')}</label>
                <input type="file" accept="image/*" onChange={(e) => setCoverFile(e.target.files[0])} className="w-full text-sm" />
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
