import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  adminGetOrganizationStructure,
  adminStoreDepartment,
  adminUpdateDepartment,
  adminDeleteDepartment,
  adminStorePosition,
  adminUpdatePosition,
  adminDeletePosition,
  adminStoreMember,
  adminUpdateMember,
  adminDeleteMember,
  adminStorePartner,
  adminUpdatePartner,
  adminDeletePartner,
  adminReorderDepartments,
  adminReorderMembers,
  adminSeedOrganization,
} from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'
const emptyDepartment = { name: '', description: '', sort_order: 0, is_active: true }
const emptyPosition = { department_id: '', title: '', description: '', sort_order: 0, is_active: true }
const emptyMember = {
  department_id: '', position_id: '', reports_to_id: '', name: '', email: '', phone: '',
  type: 'staff', bio: '', responsibilities: '', sort_order: 0, is_active: true, is_department_head: false,
}
const emptyPartner = { member_id: '', organization_name: '', role: '', description: '', sort_order: 0, is_active: true }

const errorMessage = (err, fallback) => {
  const errors = err?.response?.data?.errors
  if (errors) return Object.values(errors).flat().join(', ')
  return err?.response?.data?.message || fallback
}

export default function AdminOrganizationPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [tab, setTab] = useState('members')
  const [error, setError] = useState('')
  const [seeding, setSeeding] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-organization'],
    refetchInterval: 60000,
    queryFn: adminGetOrganizationStructure,
  })

  const departments = useMemo(() => data?.data?.departments || [], [data])
  const positions = useMemo(() => data?.data?.positions || [], [data])
  const members = useMemo(() => data?.data?.members || [], [data])
  const partners = useMemo(() => members.flatMap((m) => (m.partner || []).map((p) => ({ ...p, member: m }))), [members])
  const isEmpty = departments.length === 0 && members.length === 0

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin-organization'] })

  const seedMutation = useMutation({
    mutationFn: adminSeedOrganization,
    onSuccess: invalidate,
  })

  const handleSeed = () => {
    setSeeding(true)
    setError('')
    seedMutation.mutate(undefined, {
      onSettled: () => setSeeding(false),
      onError: (err) => setError(errorMessage(err, t('error'))),
    })
  }

  const tabs = [
    { key: 'members', label: `${t('orgMembers')} (${members.length})` },
    { key: 'departments', label: `${t('orgDepartments')} (${departments.length})` },
    { key: 'positions', label: `${t('orgPositions')} (${positions.length})` },
    { key: 'partners', label: `${t('orgPartners')} (${partners.length})` },
  ]

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('orgStructure')}</h1>
          <p className="text-sm text-gray-500 mt-1">{t('orgStructureSub')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Link to="/organization" target="_blank" rel="noreferrer" className="px-4 py-2 text-sm border border-indigo-200 text-indigo-700 rounded-xl font-medium hover:bg-indigo-50">
            {t('orgViewPublic')} ↗
          </Link>
          {isEmpty && (
            <button onClick={handleSeed} disabled={seeding} className="px-4 py-2 text-sm bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 disabled:opacity-60">
              {seeding ? t('loading') : t('orgSeedInitial')}
            </button>
          )}
        </div>
      </div>

      {error && <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}

      <div className="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        {tabs.map(({ key, label }) => (
          <button
            key={key}
            type="button"
            onClick={() => { setTab(key); setError('') }}
            className={`px-4 py-2 rounded-xl text-sm font-medium transition-colors ${
              tab === key ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="p-8 text-center text-gray-500">{t('loading')}</div>
      ) : (
        <>
          {tab === 'members' && <MembersTab members={members} departments={departments} positions={positions} invalidate={invalidate} setError={setError} />}
          {tab === 'departments' && <DepartmentsTab departments={departments} invalidate={invalidate} setError={setError} />}
          {tab === 'positions' && <PositionsTab positions={positions} departments={departments} invalidate={invalidate} setError={setError} />}
          {tab === 'partners' && <PartnersTab partners={partners} members={members} invalidate={invalidate} setError={setError} />}
        </>
      )}
    </div>
  )
}

function Modal({ title, onClose, children }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose} role="dialog" aria-modal="true">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between gap-3 mb-4">
          <h2 className="text-lg font-bold text-gray-900">{title}</h2>
          <button type="button" onClick={onClose} className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200" aria-label="Close">
            ✕
          </button>
        </div>
        {children}
      </div>
    </div>
  )
}

function ActiveBadge({ active, t }) {
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
      {active ? t('orgActive') : t('orgInactive')}
    </span>
  )
}

function FormActions({ onCancel, submitLabel, isPending, t }) {
  return (
    <div className="flex justify-end gap-2 pt-1">
      <button type="button" onClick={onCancel} className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50">
        {t('cancel')}
      </button>
      <button type="submit" disabled={isPending} className="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60">
        {isPending ? t('loading') : submitLabel}
      </button>
    </div>
  )
}

/* ---------------- Departments ---------------- */

function DepartmentsTab({ departments, invalidate, setError }) {
  const { t } = useLanguage()
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(emptyDepartment)
  const [showForm, setShowForm] = useState(false)

  const saveMutation = useMutation({
    mutationFn: (payload) => (editing ? adminUpdateDepartment(editing.id, payload) : adminStoreDepartment(payload)),
    onSuccess: () => { invalidate(); closeForm() },
    onError: (err) => setError(errorMessage(err, t('error'))),
  })
  const deleteMutation = useMutation({
    mutationFn: adminDeleteDepartment,
    onSuccess: invalidate,
    onError: (err) => setError(errorMessage(err, t('error'))),
  })
  const reorderMutation = useMutation({ mutationFn: adminReorderDepartments, onSuccess: invalidate })

  const closeForm = () => { setShowForm(false); setEditing(null); setForm(emptyDepartment) }
  const startEdit = (dep) => { setEditing(dep); setForm({ name: dep.name || '', description: dep.description || '', sort_order: dep.sort_order ?? 0, is_active: !!dep.is_active }); setShowForm(true) }
  const startAdd = () => { setEditing(null); setForm(emptyDepartment); setShowForm(true) }

  const move = (index, direction) => {
    const target = index + direction
    if (target < 0 || target >= departments.length) return
    const reordered = departments.map((d, i) => ({ id: d.id, sort_order: d.sort_order ?? i }))
    const a = reordered[index]
    const b = reordered[target]
    const sortA = a.sort_order
    a.sort_order = b.sort_order
    b.sort_order = sortA
    // If sort orders were equal, fall back to positional swap.
    if (a.sort_order === b.sort_order) { a.sort_order = target; b.sort_order = index }
    reorderMutation.mutate(reordered)
  }

  const handleDelete = (dep) => {
    if (window.confirm(`${t('confirmDelete')} "${dep.name}"?`)) deleteMutation.mutate(dep.id)
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    saveMutation.mutate({ name: form.name.trim(), description: form.description.trim() || null, sort_order: Number(form.sort_order) || 0, is_active: form.is_active })
  }

  return (
    <div>
      <div className="flex justify-end mb-4">
        <button onClick={startAdd} className="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700">+ {t('orgAddDepartment')}</button>
      </div>
      <div className="bg-white rounded-2xl shadow-sm overflow-hidden overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">↕</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgDepartment')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('description')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgPositions')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('active')}</th>
              <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {departments.map((dep, index) => (
              <tr key={dep.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-sm whitespace-nowrap">
                  <button onClick={() => move(index, -1)} disabled={index === 0} className="px-1.5 py-1 text-gray-400 hover:text-indigo-600 disabled:opacity-30" aria-label="Move up">▲</button>
                  <button onClick={() => move(index, 1)} disabled={index === departments.length - 1} className="px-1.5 py-1 text-gray-400 hover:text-indigo-600 disabled:opacity-30" aria-label="Move down">▼</button>
                </td>
                <td className="px-4 py-3 text-sm font-medium text-gray-900">{dep.name}</td>
                <td className="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{dep.description || '—'}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{dep.positions?.length ?? 0}</td>
                <td className="px-4 py-3"><ActiveBadge active={dep.is_active} t={t} /></td>
                <td className="px-4 py-3 text-right text-sm space-x-3 whitespace-nowrap">
                  <button onClick={() => startEdit(dep)} className="text-indigo-600 hover:text-indigo-800">{t('edit')}</button>
                  <button onClick={() => handleDelete(dep)} className="text-red-600 hover:text-red-800">{t('delete')}</button>
                </td>
              </tr>
            ))}
            {departments.length === 0 && (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-500 text-sm">{t('orgNotSet')}</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {showForm && (
        <Modal title={editing ? `${t('edit')}: ${editing.name}` : t('orgAddDepartment')} onClose={closeForm}>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('name')} *</label>
              <input type="text" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
              <textarea rows={2} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={inputClass} />
            </div>
            <div className="grid sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgSortOrder')}</label>
                <input type="number" value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: e.target.value })} className={inputClass} />
              </div>
              <label className="flex items-center gap-2 self-end pb-2.5 text-sm font-medium text-gray-700">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="w-4 h-4 accent-indigo-600" />
                {t('orgActive')}
              </label>
            </div>
            <FormActions onCancel={closeForm} submitLabel={t('save')} isPending={saveMutation.isPending} t={t} />
          </form>
        </Modal>
      )}
    </div>
  )
}

/* ---------------- Positions ---------------- */

function PositionsTab({ positions, departments, invalidate, setError }) {
  const { t } = useLanguage()
  const [editing, setEditing] = useState(null)
  const [filterDept, setFilterDept] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState(emptyPosition)

  const filtered = filterDept ? positions.filter((p) => String(p.department_id) === filterDept) : positions

  const saveMutation = useMutation({
    mutationFn: (payload) => (editing ? adminUpdatePosition(editing.id, payload) : adminStorePosition(payload)),
    onSuccess: () => { invalidate(); closeForm() },
    onError: (err) => setError(errorMessage(err, t('error'))),
  })
  const deleteMutation = useMutation({
    mutationFn: adminDeletePosition,
    onSuccess: invalidate,
    onError: (err) => setError(errorMessage(err, t('error'))),
  })

  const closeForm = () => { setShowForm(false); setEditing(null); setForm(emptyPosition) }
  const startEdit = (pos) => { setEditing(pos); setForm({ department_id: pos.department_id ?? '', title: pos.title || '', description: pos.description || '', sort_order: pos.sort_order ?? 0, is_active: !!pos.is_active }); setShowForm(true) }
  const startAdd = () => { setEditing(null); setForm(emptyPosition); setShowForm(true) }

  const handleDelete = (pos) => {
    if (window.confirm(`${t('confirmDelete')} "${pos.title}"?`)) deleteMutation.mutate(pos.id)
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    saveMutation.mutate({
      department_id: form.department_id === '' ? null : Number(form.department_id),
      title: form.title.trim(),
      description: form.description.trim() || null,
      sort_order: Number(form.sort_order) || 0,
      is_active: form.is_active,
    })
  }

  const departmentName = (id) => departments.find((d) => d.id === id)?.name || '—'

  return (
    <div>
      <div className="flex flex-wrap justify-between items-center gap-3 mb-4">
        <select value={filterDept} onChange={(e) => setFilterDept(e.target.value)} className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
          <option value="">{t('orgDepartments')} — All</option>
          {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
        </select>
        <button onClick={startAdd} className="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700">+ {t('orgAddPosition')}</button>
      </div>
      <div className="bg-white rounded-2xl shadow-sm overflow-hidden overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgPosition')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgDepartment')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgSortOrder')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('active')}</th>
              <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {filtered.map((pos) => (
              <tr key={pos.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-sm font-medium text-gray-900">{pos.title}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{departmentName(pos.department_id)}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{pos.sort_order}</td>
                <td className="px-4 py-3"><ActiveBadge active={pos.is_active} t={t} /></td>
                <td className="px-4 py-3 text-right text-sm space-x-3 whitespace-nowrap">
                  <button onClick={() => startEdit(pos)} className="text-indigo-600 hover:text-indigo-800">{t('edit')}</button>
                  <button onClick={() => handleDelete(pos)} className="text-red-600 hover:text-red-800">{t('delete')}</button>
                </td>
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-500 text-sm">{t('orgNotSet')}</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {showForm && (
        <Modal title={editing ? t('orgPosition') : t('orgAddPosition')} onClose={closeForm}>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgDepartment')}</label>
              <select value={form.department_id} onChange={(e) => setForm({ ...form, department_id: e.target.value })} className={inputClass}>
                <option value="">—</option>
                {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgPosition')} *</label>
              <input type="text" required value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
              <textarea rows={2} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={inputClass} />
            </div>
            <div className="grid sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgSortOrder')}</label>
                <input type="number" value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: e.target.value })} className={inputClass} />
              </div>
              <label className="flex items-center gap-2 self-end pb-2.5 text-sm font-medium text-gray-700">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="w-4 h-4 accent-indigo-600" />
                {t('orgActive')}
              </label>
            </div>
            <FormActions onCancel={closeForm} submitLabel={t('save')} isPending={saveMutation.isPending} t={t} />
          </form>
        </Modal>
      )}
    </div>
  )
}

/* ---------------- Members ---------------- */

function MembersTab({ members, departments, positions, invalidate, setError }) {
  const { t } = useLanguage()
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState(emptyMember)
  const [photo, setPhoto] = useState(null)
  const [photoPreview, setPhotoPreview] = useState(null)
  const [removePhoto, setRemovePhoto] = useState(false)

  const saveMutation = useMutation({
    mutationFn: (payload) => (editing ? adminUpdateMember(editing.id, payload) : adminStoreMember(payload)),
    onSuccess: () => { invalidate(); closeForm() },
    onError: (err) => setError(errorMessage(err, t('error'))),
  })
  const deleteMutation = useMutation({
    mutationFn: adminDeleteMember,
    onSuccess: invalidate,
    onError: (err) => setError(errorMessage(err, t('error'))),
  })
  const reorderMutation = useMutation({ mutationFn: adminReorderMembers, onSuccess: invalidate })

  const closeForm = () => {
    setShowForm(false); setEditing(null); setForm(emptyMember)
    setPhoto(null); setPhotoPreview(null); setRemovePhoto(false)
  }

  const startAdd = () => { setEditing(null); setForm(emptyMember); setPhoto(null); setPhotoPreview(null); setRemovePhoto(false); setShowForm(true) }
  const startEdit = (member) => {
    setEditing(member)
    setForm({
      department_id: member.department_id ?? '',
      position_id: member.position_id ?? '',
      reports_to_id: member.reports_to_id ?? '',
      name: member.name || '',
      email: member.email || '',
      phone: member.phone || '',
      type: member.type || 'staff',
      bio: member.bio || '',
      responsibilities: (member.responsibilities || []).map((r) => r.responsibility).join('\n'),
      sort_order: member.sort_order ?? 0,
      is_active: !!member.is_active,
      is_department_head: !!member.is_department_head,
    })
    setPhoto(null)
    setPhotoPreview(member.photo_url || null)
    setRemovePhoto(false)
    setShowForm(true)
  }

  const handlePhoto = (file) => {
    setPhoto(file || null)
    setPhotoPreview(file ? URL.createObjectURL(file) : null)
    if (file) setRemovePhoto(false)
  }

  const handleDelete = (member) => {
    if (window.confirm(`${t('confirmDelete')} "${member.name}"?`)) deleteMutation.mutate(member.id)
  }

  const move = (index, direction) => {
    const target = index + direction
    if (target < 0 || target >= members.length) return
    const reordered = members.map((m, i) => ({ id: m.id, sort_order: m.sort_order ?? i }))
    const a = reordered[index]
    const b = reordered[target]
    const sortA = a.sort_order
    a.sort_order = b.sort_order
    b.sort_order = sortA
    if (a.sort_order === b.sort_order) { a.sort_order = target; b.sort_order = index }
    reorderMutation.mutate(reordered)
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    const fd = new FormData()
    fd.append('name', form.name.trim())
    fd.append('email', form.email.trim())
    fd.append('phone', form.phone.trim())
    fd.append('type', form.type)
    fd.append('bio', form.bio.trim())
    fd.append('department_id', form.department_id === '' ? '' : form.department_id)
    fd.append('position_id', form.position_id === '' ? '' : form.position_id)
    fd.append('reports_to_id', form.reports_to_id === '' ? '' : form.reports_to_id)
    fd.append('sort_order', String(Number(form.sort_order) || 0))
    fd.append('is_active', form.is_active ? '1' : '0')
    fd.append('is_department_head', form.is_department_head ? '1' : '0')
    form.responsibilities.split('\n').map((r) => r.trim()).filter(Boolean).forEach((r) => fd.append('responsibilities[]', r))
    if (photo) fd.append('photo', photo)
    if (editing && removePhoto) fd.append('remove_photo', '1')
    saveMutation.mutate(fd)
  }

  const availablePositions = form.department_id === '' ? positions : positions.filter((p) => String(p.department_id) === String(form.department_id) || p.department_id === null)

  return (
    <div>
      <div className="flex justify-end mb-4">
        <button onClick={startAdd} className="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700">+ {t('orgAddMember')}</button>
      </div>
      <div className="bg-white rounded-2xl shadow-sm overflow-hidden overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">↕</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgPosition')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgDepartment')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgType')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('active')}</th>
              <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {members.map((member, index) => (
              <tr key={member.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-sm whitespace-nowrap">
                  <button onClick={() => move(index, -1)} disabled={index === 0} className="px-1.5 py-1 text-gray-400 hover:text-indigo-600 disabled:opacity-30" aria-label="Move up">▲</button>
                  <button onClick={() => move(index, 1)} disabled={index === members.length - 1} className="px-1.5 py-1 text-gray-400 hover:text-indigo-600 disabled:opacity-30" aria-label="Move down">▼</button>
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-2">
                    {member.photo_url ? (
                      <img src={member.photo_url} alt="" className="w-9 h-9 rounded-full object-cover" />
                    ) : (
                      <span className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                        {(member.name || '?').charAt(0).toUpperCase()}
                      </span>
                    )}
                    <div className="min-w-0">
                      <p className="text-sm font-medium text-gray-900 truncate">{member.name}</p>
                      {member.is_department_head && <p className="text-[10px] text-amber-600 font-semibold">★ {t('orgDepartmentHead')}</p>}
                    </div>
                  </div>
                </td>
                <td className="px-4 py-3 text-sm text-gray-600 max-w-[220px] truncate">{member.position?.title || '—'}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{member.department?.name || '—'}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{member.type === 'partner' ? t('orgPartnerType') : t('orgStaff')}</td>
                <td className="px-4 py-3"><ActiveBadge active={member.is_active} t={t} /></td>
                <td className="px-4 py-3 text-right text-sm space-x-3 whitespace-nowrap">
                  <button onClick={() => startEdit(member)} className="text-indigo-600 hover:text-indigo-800">{t('edit')}</button>
                  <button onClick={() => handleDelete(member)} className="text-red-600 hover:text-red-800">{t('delete')}</button>
                </td>
              </tr>
            ))}
            {members.length === 0 && (
              <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-500 text-sm">{t('orgNotSet')}</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {showForm && (
        <Modal title={editing ? `${t('edit')}: ${editing.name}` : t('orgAddMember')} onClose={closeForm}>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="flex items-center gap-4">
              {photoPreview ? (
                <img src={photoPreview} alt="" className="w-16 h-16 rounded-full object-cover ring-2 ring-gray-100" />
              ) : (
                <div className="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-bold shrink-0">
                  {(form.name || '?').charAt(0).toUpperCase()}
                </div>
              )}
              <div className="flex flex-col gap-1.5">
                <label className="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
                  {t('changePhoto')}
                  <input type="file" accept="image/*" className="hidden" onChange={(e) => handlePhoto(e.target.files?.[0])} />
                </label>
                {editing && photoPreview && !photo && (
                  <button type="button" onClick={() => { setRemovePhoto(true); setPhotoPreview(null) }} className="text-xs text-red-500 hover:text-red-600 text-left">
                    {t('removePhoto')}
                  </button>
                )}
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('name')} *</label>
              <input type="text" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} />
            </div>

            <div className="grid sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
                <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className={inputClass} />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('phone')}</label>
                <input type="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className={inputClass} placeholder="+855 ..." />
              </div>
            </div>

            <div className="grid sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgDepartment')}</label>
                <select value={form.department_id} onChange={(e) => setForm({ ...form, department_id: e.target.value, position_id: '' })} className={inputClass}>
                  <option value="">—</option>
                  {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgPosition')}</label>
                <select value={form.position_id} onChange={(e) => setForm({ ...form, position_id: e.target.value })} className={inputClass}>
                  <option value="">—</option>
                  {availablePositions.map((p) => <option key={p.id} value={p.id}>{p.title}</option>)}
                </select>
              </div>
            </div>

            <div className="grid sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgReportsTo')}</label>
                <select value={form.reports_to_id} onChange={(e) => setForm({ ...form, reports_to_id: e.target.value })} className={inputClass}>
                  <option value="">—</option>
                  {members.filter((m) => m.id !== editing?.id).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgType')}</label>
                <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className={inputClass}>
                  <option value="staff">{t('orgStaff')}</option>
                  <option value="partner">{t('orgPartnerType')}</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
              <textarea rows={2} value={form.bio} onChange={(e) => setForm({ ...form, bio: e.target.value })} className={inputClass} />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgResponsibilities')} <span className="text-gray-400 font-normal">(1 per line)</span></label>
              <textarea rows={4} value={form.responsibilities} onChange={(e) => setForm({ ...form, responsibilities: e.target.value })} className={inputClass} placeholder={'Responsibility 1\nResponsibility 2'} />
            </div>

            <div className="grid sm:grid-cols-3 gap-3 items-end">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgSortOrder')}</label>
                <input type="number" value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: e.target.value })} className={inputClass} />
              </div>
              <label className="flex items-center gap-2 pb-2.5 text-sm font-medium text-gray-700">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="w-4 h-4 accent-indigo-600" />
                {t('orgActive')}
              </label>
              <label className="flex items-center gap-2 pb-2.5 text-sm font-medium text-gray-700">
                <input type="checkbox" checked={form.is_department_head} onChange={(e) => setForm({ ...form, is_department_head: e.target.checked })} className="w-4 h-4 accent-indigo-600" />
                ★ {t('orgDepartmentHead')}
              </label>
            </div>

            <FormActions onCancel={closeForm} submitLabel={t('save')} isPending={saveMutation.isPending} t={t} />
          </form>
        </Modal>
      )}
    </div>
  )
}

/* ---------------- Partners ---------------- */

function PartnersTab({ partners, members, invalidate, setError }) {
  const { t } = useLanguage()
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState(emptyPartner)

  const saveMutation = useMutation({
    mutationFn: (payload) => (editing ? adminUpdatePartner(editing.id, payload) : adminStorePartner(payload)),
    onSuccess: () => { invalidate(); closeForm() },
    onError: (err) => setError(errorMessage(err, t('error'))),
  })
  const deleteMutation = useMutation({
    mutationFn: adminDeletePartner,
    onSuccess: invalidate,
    onError: (err) => setError(errorMessage(err, t('error'))),
  })

  const closeForm = () => { setShowForm(false); setEditing(null); setForm(emptyPartner) }
  const startEdit = (partner) => { setEditing(partner); setForm({ member_id: partner.member_id ?? '', organization_name: partner.organization_name || '', role: partner.role || '', description: partner.description || '', sort_order: partner.sort_order ?? 0, is_active: !!partner.is_active }); setShowForm(true) }
  const startAdd = () => { setEditing(null); setForm(emptyPartner); setShowForm(true) }

  const handleDelete = (partner) => {
    if (window.confirm(`${t('confirmDelete')} "${partner.organization_name || partner.member?.name}"?`)) deleteMutation.mutate(partner.id)
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    saveMutation.mutate({
      member_id: Number(form.member_id),
      organization_name: form.organization_name.trim() || null,
      role: form.role.trim() || null,
      description: form.description.trim() || null,
      sort_order: Number(form.sort_order) || 0,
      is_active: form.is_active,
    })
  }

  return (
    <div>
      <div className="flex justify-end mb-4">
        <button onClick={startAdd} className="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700">+ {t('orgAddPartner')}</button>
      </div>
      <div className="bg-white rounded-2xl shadow-sm overflow-hidden overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgPartners')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('orgMembers')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('role')}</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('active')}</th>
              <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {partners.map((partner) => (
              <tr key={partner.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-sm font-medium text-gray-900">{partner.organization_name || '—'}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{partner.member?.name || '—'}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{partner.role || '—'}</td>
                <td className="px-4 py-3"><ActiveBadge active={partner.is_active} t={t} /></td>
                <td className="px-4 py-3 text-right text-sm space-x-3 whitespace-nowrap">
                  <button onClick={() => startEdit(partner)} className="text-indigo-600 hover:text-indigo-800">{t('edit')}</button>
                  <button onClick={() => handleDelete(partner)} className="text-red-600 hover:text-red-800">{t('delete')}</button>
                </td>
              </tr>
            ))}
            {partners.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-500 text-sm">{t('orgNotSet')}</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {showForm && (
        <Modal title={editing ? t('orgPartners') : t('orgAddPartner')} onClose={closeForm}>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgMembers')} *</label>
              <select required value={form.member_id} onChange={(e) => setForm({ ...form, member_id: e.target.value })} className={inputClass}>
                <option value="">—</option>
                {members.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgPartners')}</label>
              <input type="text" value={form.organization_name} onChange={(e) => setForm({ ...form, organization_name: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('role')}</label>
              <input type="text" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
              <textarea rows={2} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={inputClass} />
            </div>
            <div className="grid sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('orgSortOrder')}</label>
                <input type="number" value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: e.target.value })} className={inputClass} />
              </div>
              <label className="flex items-center gap-2 self-end pb-2.5 text-sm font-medium text-gray-700">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="w-4 h-4 accent-indigo-600" />
                {t('orgActive')}
              </label>
            </div>
            <FormActions onCancel={closeForm} submitLabel={t('save')} isPending={saveMutation.isPending} t={t} />
          </form>
        </Modal>
      )}
    </div>
  )
}
