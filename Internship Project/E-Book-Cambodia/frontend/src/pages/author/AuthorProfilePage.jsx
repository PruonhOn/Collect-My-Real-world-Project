import { useState, useEffect } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { authorMe, authorUpdateProfile, authorChangePassword, authorPhotoUrl } from '../../api'
import { useAuthor } from '../../context/AuthorContext'
import { useLanguage } from '../../context/LanguageContext'

export default function AuthorProfilePage() {
  const { t } = useLanguage()
  const { author, setAuthor } = useAuthor()

  const { data: meData } = useQuery({
    queryKey: ['author-me'],
    refetchInterval: 60000,
    queryFn: () => authorMe(),
  })

  const profile = meData?.data?.author || author

  const [form, setForm] = useState(() => ({
    name: profile?.name || '',
    phone: profile?.phone || '',
    bio: profile?.bio || '',
    specialization: profile?.specialization || '',
    organization: profile?.organization || '',
    school: profile?.school || '',
    social_links: profile?.social_links ? JSON.stringify(profile.social_links) : '',
  }))
  const [photoFile, setPhotoFile] = useState(null)
  const [photoPreview, setPhotoPreview] = useState(null)
  const [profileError, setProfileError] = useState('')
  const [profileSuccess, setProfileSuccess] = useState('')

  const [pwForm, setPwForm] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [pwError, setPwError] = useState('')
  const [pwSuccess, setPwSuccess] = useState('')

  const profileMutation = useMutation({
    mutationFn: (data) => authorUpdateProfile(data),
    onSuccess: (res) => {
      setAuthor(res.data.author)
      setProfileSuccess(t('profileUpdated'))
      setProfileError('')
    },
    onError: (err) => {
      const errors = err.response?.data?.errors
      setProfileError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message || '')
    },
  })

  const passwordMutation = useMutation({
    mutationFn: (data) => authorChangePassword(data),
    onSuccess: () => {
      setPwSuccess(t('passwordChanged'))
      setPwError('')
      setPwForm({ current_password: '', password: '', password_confirmation: '' })
    },
    onError: (err) => {
      const errors = err.response?.data?.errors
      setPwError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message || '')
    },
  })

  const handleProfileSubmit = (e) => {
    e.preventDefault()
    setProfileSuccess('')
    setProfileError('')

    let social_links
    if (form.social_links.trim()) {
      try {
        social_links = JSON.parse(form.social_links)
      } catch {
        setProfileError('Invalid JSON for social links')
        return
      }
    } else {
      social_links = null
    }

    const data = new FormData()
    Object.entries({ ...form, social_links: social_links ? JSON.stringify(social_links) : '' }).forEach(
      ([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) data.append(key, value)
      }
    )
    if (photoFile) data.append('photo', photoFile)
    data.append('_method', 'PUT')

    profileMutation.mutate(data)
  }

  const handlePhotoChange = (e) => {
    const file = e.target.files[0] || null
    setPhotoFile(file)
    if (photoPreview) URL.revokeObjectURL(photoPreview)
    setPhotoPreview(file ? URL.createObjectURL(file) : null)
  }

  useEffect(
    () => () => {
      if (photoPreview) URL.revokeObjectURL(photoPreview)
    },
    [photoPreview]
  )

  const handlePwSubmit = (e) => {
    e.preventDefault()
    setPwSuccess('')
    setPwError('')
    passwordMutation.mutate(pwForm)
  }

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500'

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('authorProfile')}</h1>

      <div className="space-y-6">
        <form onSubmit={handleProfileSubmit} className="bg-white rounded-2xl shadow-sm p-6 md:p-8 space-y-4">
          <h2 className="text-lg font-semibold text-gray-900">{t('authorProfile')}</h2>

          {profileError && (
            <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm">{profileError}</div>
          )}
          {profileSuccess && (
            <div className="p-3 bg-emerald-50 text-emerald-600 rounded-xl text-sm">{profileSuccess}</div>
          )}

          <div className="flex items-center gap-4">
            {photoPreview || authorPhotoUrl(profile) ? (
              <img src={photoPreview || authorPhotoUrl(profile)} alt="" className="w-20 h-20 rounded-full object-cover bg-gray-100" />
            ) : (
              <div className="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center text-3xl">
                ✍️
              </div>
            )}
            <div className="flex-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorPhoto')}</label>
              <input type="file" accept="image/*" onChange={handlePhotoChange} className={inputClass} />
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorName')} *</label>
              <input type="text" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
              <input type="email" disabled value={profile?.email || ''} className={`${inputClass} bg-gray-50 text-gray-500`} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorPhone')}</label>
              <input type="text" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorSpecialization')}</label>
              <input type="text" value={form.specialization} onChange={(e) => setForm({ ...form, specialization: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorOrganization')}</label>
              <input type="text" value={form.organization} onChange={(e) => setForm({ ...form, organization: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorSchool')}</label>
              <input type="text" value={form.school} onChange={(e) => setForm({ ...form, school: e.target.value })} className={inputClass} />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorBio')}</label>
            <textarea
              rows={4}
              value={form.bio}
              onChange={(e) => setForm({ ...form, bio: e.target.value })}
              className={inputClass}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorSocialLinks')}</label>
            <textarea
              rows={2}
              value={form.social_links}
              onChange={(e) => setForm({ ...form, social_links: e.target.value })}
              className={inputClass}
              placeholder='{"facebook": "https://...", "youtube": "https://..."}'
            />
            <p className="mt-1 text-xs text-gray-400">{t('socialLinksJsonHint')}</p>
          </div>

          <button
            type="submit"
            disabled={profileMutation.isPending}
            className="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-60"
          >
            {profileMutation.isPending ? t('loading') : t('save')}
          </button>
        </form>

        <form onSubmit={handlePwSubmit} className="bg-white rounded-2xl shadow-sm p-6 md:p-8 space-y-4">
          <h2 className="text-lg font-semibold text-gray-900">{t('changePassword')}</h2>

          {pwError && <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm">{pwError}</div>}
          {pwSuccess && (
            <div className="p-3 bg-emerald-50 text-emerald-600 rounded-xl text-sm">{pwSuccess}</div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('currentPassword')}</label>
            <input
              type="password"
              required
              value={pwForm.current_password}
              onChange={(e) => setPwForm({ ...pwForm, current_password: e.target.value })}
              className={inputClass}
            />
          </div>
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('newPassword')}</label>
              <input
                type="password"
                required
                value={pwForm.password}
                onChange={(e) => setPwForm({ ...pwForm, password: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirmPassword')}</label>
              <input
                type="password"
                required
                value={pwForm.password_confirmation}
                onChange={(e) => setPwForm({ ...pwForm, password_confirmation: e.target.value })}
                className={inputClass}
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={passwordMutation.isPending}
            className="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-60"
          >
            {passwordMutation.isPending ? t('loading') : t('changePassword')}
          </button>
        </form>
      </div>
    </div>
  )
}