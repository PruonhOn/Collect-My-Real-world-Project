import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  adminGetMessages,
  adminCreateMessage,
  adminUpdateMessage,
  adminReplyMessage,
  adminDeleteMessage,
} from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const PROGRAMS = [
  'School',
  'Author',
  'NGO',
  'Sponsor',
]

const programLabel = (program, t) => program && t(`collabPage.partner${program}Title`)

const photoUrl = (message) => (message?.photo ? `/storage/${message.photo}` : null)

const parseProgram = (message) => {
  const match = message?.match(/^\[([^\]]+)\]/)
  return match ? { program: match[1], body: message.slice(match[0].length).trimStart() } : { program: null, body: message }
}

const inputClass =
  'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

export default function AdminCollaboratorsPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('')
  const [search, setSearch] = useState('')
  const [replying, setReplying] = useState(null)
  const [replyText, setReplyText] = useState('')
  const [adding, setAdding] = useState(false)
  const [editing, setEditing] = useState(null)
  const [addStatus, setAddStatus] = useState({ type: '', text: '' })

  const { data, isLoading } = useQuery({
    queryKey: ['admin-collaborators', status, search],
    queryFn: () => adminGetMessages({ collaborators: 1, status, search, per_page: 50 }),
    refetchInterval: 20000,
    refetchOnWindowFocus: true,
  })

  const requests = data?.data?.data || []

  const invalidate = () => {
    queryClient.invalidateQueries(['admin-collaborators'])
    queryClient.invalidateQueries(['admin-messages'])
    queryClient.invalidateQueries(['admin-dashboard'])
  }

  const replyMutation = useMutation({
    mutationFn: ({ id, reply }) => adminReplyMessage(id, { reply }),
    onSuccess: () => {
      invalidate()
      setReplying(null)
      setReplyText('')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteMessage(id),
    onSuccess: invalidate,
  })

  const handleDelete = (request) => {
    if (window.confirm(`${t('confirmDelete')}?`)) {
      deleteMutation.mutate(request.id)
    }
  }

  const openReply = (request) => {
    setReplying(request)
    setReplyText(request.reply || '')
  }

  const submitReply = (e) => {
    e.preventDefault()
    if (!replyText.trim()) return
    replyMutation.mutate({ id: replying.id, reply: replyText.trim() })
  }

  const openAdd = () => {
    setAddForm({
      name: '',
      email: '',
      phone: '',
      program: PROGRAMS[0],
      message: '',
      photoPreview: null,
    })
    setAdding(true)
  }

  const openEdit = (request) => {
    const { program, body } = parseProgram(request.message)
    setEditPhoto(null)
    setEditKeepExisting(true)
    setEditForm({
      id: request.id,
      name: request.sender_name || request.user?.name || '',
      email: request.sender_email || request.user?.email || '',
      phone: '',
      program: PROGRAMS.includes(program) ? program : PROGRAMS[0],
      message: body || program || '',
      photoPreview: photoUrl(request),
      existingPhoto: photoUrl(request),
    })
    setEditing(true)
  }

  const [addForm, setAddForm] = useState({
    name: '',
    email: '',
    phone: '',
    program: PROGRAMS[0],
    message: '',
    photoPreview: null,
  })
  const [editForm, setEditForm] = useState({
    id: null,
    name: '',
    email: '',
    phone: '',
    program: PROGRAMS[0],
    message: '',
    photoPreview: null,
    existingPhoto: null,
  })

  const addMutation = useMutation({
    mutationFn: adminCreateMessage,
    onSuccess: () => {
      invalidate()
      setAdding(false)
      setAddStatus({ type: 'success', text: t('collaboratorAdded') })
      setTimeout(() => setAddStatus({ type: '', text: '' }), 3000)
    },
    onError: () => {
      setAddStatus({ type: 'error', text: t('collaboratorAddError') })
      setTimeout(() => setAddStatus({ type: '', text: '' }), 4000)
    },
  })

  const editMutation = useMutation({
    mutationFn: ({ id, data }) => adminUpdateMessage(id, data),
    onSuccess: () => {
      invalidate()
      setEditing(false)
      setAddStatus({ type: 'success', text: t('collaboratorUpdated') })
      setTimeout(() => setAddStatus({ type: '', text: '' }), 3000)
    },
    onError: () => {
      setAddStatus({ type: 'error', text: t('collaboratorUpdateError') })
      setTimeout(() => setAddStatus({ type: '', text: '' }), 4000)
    },
  })

  const buildFormData = (form, photo) => {
    const fd = new FormData()
    fd.append('name', form.name.trim())
    fd.append('email', form.email.trim() || '')
    fd.append('phone', form.phone.trim() || '')
    fd.append('program', form.program)
    fd.append('message', form.message.trim())
    if (photo) fd.append('photo', photo)
    return fd
  }

  const submitAdd = (e) => {
    e.preventDefault()
    if (!addForm.name.trim()) return
    addMutation.mutate(buildFormData(addForm, addPhoto))
  }

  const submitEdit = (e) => {
    e.preventDefault()
    if (!editForm.name.trim()) return
    const fd = buildFormData(editForm, editPhoto)
    if (editForm.existingPhoto && !editPhoto && !editKeepExisting) fd.append('remove_photo', '1')
    editMutation.mutate({ id: editForm.id, data: fd })
  }

  const [addPhoto, setAddPhoto] = useState(null)
  const [editPhoto, setEditPhoto] = useState(null)
  const [editKeepExisting, setEditKeepExisting] = useState(true)

  const handleAddPhoto = (file) => {
    setAddPhoto(file)
    setAddForm((f) => ({ ...f, photoPreview: file ? URL.createObjectURL(file) : null }))
  }

  const handleEditPhoto = (file) => {
    setEditPhoto(file)
    setEditKeepExisting(false)
    setEditForm((f) => ({ ...f, photoPreview: file ? URL.createObjectURL(file) : f.existingPhoto }))
  }

  const removeEditPhoto = () => {
    setEditPhoto(null)
    setEditKeepExisting(false)
    setEditForm((f) => ({ ...f, photoPreview: null }))
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('adminCollaborators')}</h1>
          <p className="text-sm text-gray-500 mt-1">{t('adminCollaboratorsSub')}</p>
        </div>
        <div className="flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            onClick={openAdd}
            className="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors"
          >
            + {t('addCollaborator')}
          </button>
          <input
            type="search"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={t('search')}
            className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">All</option>
            <option value="pending">{t('pending')}</option>
            <option value="replied">{t('replied')}</option>
          </select>
        </div>
      </div>

      {addStatus.text && (
        <div
          className={`mb-4 p-3 rounded-xl text-sm ${
            addStatus.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'
          }`}
        >
          {addStatus.text}
        </div>
      )}

      {isLoading ? (
        <div className="p-8 text-center text-gray-500">{t('loading')}</div>
      ) : requests.length === 0 ? (
        <div className="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-500">{t('noCollaborators')}</div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {requests.map((request) => {
            const { program, body } = parseProgram(request.message)
            const avatar = photoUrl(request)
            return (
              <div key={request.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div className="flex items-start justify-between gap-3 mb-3">
                  <div className="min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 text-xs font-semibold">
                        {programLabel(program, t) || program || t('noSubject')}
                      </span>
                    </div>
                    {program && <div className="text-xs text-gray-400 mt-1">{request.subject}</div>}
                    <div className="flex items-center gap-2 mt-1">
                      {avatar ? (
                        <img src={avatar} alt="" className="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100" />
                      ) : (
                        <span className="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold">
                          {(request.user?.name || request.sender_name || '?').charAt(0).toUpperCase()}
                        </span>
                      )}
                      <div className="min-w-0">
                        <div className="text-sm font-medium text-gray-900 truncate">
                          {request.user?.name || request.sender_name || '-'}
                        </div>
                        <div className="text-xs text-gray-500 truncate">
                          {request.user?.email || request.sender_email || ''} ·{' '}
                          {new Date(request.created_at).toLocaleDateString()}
                        </div>
                      </div>
                    </div>
                  </div>
                  <span
                    className={`shrink-0 text-xs font-medium px-2 py-1 rounded-full ${
                      request.is_replied ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                    }`}
                  >
                    {request.is_replied ? t('replied') : t('pending')}
                  </span>
                </div>

                <p className="text-sm text-gray-700 whitespace-pre-line">{body || request.message}</p>

                {request.is_replied && (
                  <div className="mt-3 bg-emerald-50 rounded-xl p-3">
                    <div className="text-xs font-semibold text-emerald-700 mb-1">{t('replyFromUs')}</div>
                    <p className="text-sm text-gray-700 whitespace-pre-line">{request.reply}</p>
                  </div>
                )}

                <div className="mt-4 flex items-center gap-4 pt-3 border-t border-gray-100">
                  <button
                    onClick={() => openEdit(request)}
                    className="text-sm font-medium text-gray-600 hover:text-indigo-700"
                  >
                    {t('edit')}
                  </button>
                  <button
                    onClick={() => openReply(request)}
                    className={`text-sm font-medium ${
                      request.is_replied ? 'text-emerald-600 hover:text-emerald-800' : 'text-indigo-600 hover:text-indigo-800'
                    }`}
                  >
                    {t('reply')}
                  </button>
                  <button onClick={() => handleDelete(request)} className="text-sm text-red-600 hover:text-red-800">
                    {t('delete')}
                  </button>
                </div>
              </div>
            )
          })}
        </div>
      )}

      {adding && (
        <CollaboratorModal
          title={`+ ${t('addCollaborator')}`}
          form={addForm}
          setForm={setAddForm}
          photo={addPhoto}
          onPhotoChange={handleAddPhoto}
          onClose={() => setAdding(false)}
          onSubmit={submitAdd}
          isPending={addMutation.isPending}
          t={t}
        />
      )}

      {editing && (
        <CollaboratorModal
          title={t('collaboratorEditTitle')}
          form={editForm}
          setForm={setEditForm}
          photo={editPhoto}
          onPhotoChange={handleEditPhoto}
          onRemovePhoto={removeEditPhoto}
          onClose={() => setEditing(false)}
          onSubmit={submitEdit}
          isPending={editMutation.isPending}
          t={t}
        />
      )}

      {replying && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between gap-3 mb-4">
              <div className="min-w-0">
                <h2 className="text-lg font-bold text-gray-900">
                  {t('replyTo')} {replying.user?.name || replying.sender_name || '-'}
                </h2>
                <div className="text-sm text-gray-500 truncate">{replying.subject || t('noSubject')}</div>
              </div>
              <button
                onClick={() => setReplying(null)}
                className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
                aria-label={t('close')}
              >
                ✕
              </button>
            </div>

            <div className="bg-gray-50 rounded-xl p-3 mb-4 max-h-40 overflow-y-auto">
              <p className="text-sm text-gray-700 whitespace-pre-line">{replying.message}</p>
            </div>

            {replying.is_replied && (
              <div className="bg-emerald-50 rounded-xl p-3 mb-4 max-h-40 overflow-y-auto">
                <div className="text-xs font-semibold text-emerald-700 mb-1">{t('replyFromUs')}</div>
                <p className="text-sm text-gray-700 whitespace-pre-line">{replying.reply}</p>
              </div>
            )}

            <form onSubmit={submitReply} className="space-y-3">
              <textarea
                rows="4"
                value={replyText}
                onChange={(e) => setReplyText(e.target.value)}
                placeholder={t('replyPlaceholder')}
                required
                className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setReplying(null)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50"
                >
                  {t('cancel')}
                </button>
                <button
                  type="submit"
                  disabled={replyMutation.isPending || !replyText.trim()}
                  className="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
                >
                  {replyMutation.isPending ? t('loading') : t('sendReply')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}

function CollaboratorModal({
  title,
  form,
  setForm,
  photo,
  onPhotoChange,
  onRemovePhoto,
  onClose,
  onSubmit,
  isPending,
  t,
}) {
  const hasExistingPhoto = !!form.existingPhoto
  const showRemove = onRemovePhoto && hasExistingPhoto && !photo

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between gap-3 mb-4">
          <h2 className="text-lg font-bold text-gray-900">{title}</h2>
          <button
            onClick={onClose}
            className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
            aria-label={t('close')}
          >
            ✕
          </button>
        </div>

        <form onSubmit={onSubmit} className="space-y-3">
          {/* Photo */}
          <div className="flex items-center gap-4">
            {form.photoPreview ? (
              <img src={form.photoPreview} alt="" className="w-16 h-16 rounded-full object-cover ring-2 ring-gray-100" />
            ) : (
              <div className="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-bold shrink-0">
                {(form.name || '?').charAt(0).toUpperCase()}
              </div>
            )}
            <div className="flex flex-col gap-1.5">
              <label className="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 9l2-2h4l2-2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <circle cx="12" cy="13" r="3" />
                </svg>
                {t('changePhoto')}
                <input
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={(e) => onPhotoChange(e.target.files?.[0])}
                />
              </label>
              {showRemove && (
                <button type="button" onClick={onRemovePhoto} className="text-xs text-red-500 hover:text-red-600 text-left">
                  {t('removePhoto')}
                </button>
              )}
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('name')} *</label>
            <input
              type="text"
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              className={inputClass}
              required
            />
          </div>
          <div className="grid sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
              <input
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('phone')}</label>
              <input
                type="tel"
                value={form.phone}
                onChange={(e) => setForm({ ...form, phone: e.target.value })}
                placeholder="+855 ..."
                className={inputClass}
              />
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('collaboratorProgram')}</label>
            <select
              value={form.program}
              onChange={(e) => setForm({ ...form, program: e.target.value })}
              className={inputClass}
            >
              {PROGRAMS.map((program) => (
                <option key={program} value={program}>
                  {programLabel(program, t) || program}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('collaboratorNotes')}</label>
            <textarea
              rows="3"
              value={form.message}
              onChange={(e) => setForm({ ...form, message: e.target.value })}
              className={inputClass}
            />
          </div>
          <div className="flex justify-end gap-2 pt-1">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              disabled={isPending}
              className="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
            >
              {isPending ? t('loading') : t('save')}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
