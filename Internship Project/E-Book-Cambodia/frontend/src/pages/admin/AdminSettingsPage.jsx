import { useState } from 'react'
import { useLanguage } from '../../context/LanguageContext'

const STORAGE_KEY = 'pi_admin_settings'

function readSettings() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export default function AdminSettingsPage() {
  const { t } = useLanguage()
  const saved = readSettings()

  const [siteName, setSiteName] = useState(saved?.siteName || 'Pi Education Cambodia')
  const [tagline, setTagline] = useState(saved?.tagline || 'Digital Books for Every Learner')
  const [defaultLocale, setDefaultLocale] = useState(saved?.defaultLocale || 'en')
  const [notifyEmail, setNotifyEmail] = useState(saved?.notifyEmail || '')
  const [maintenance, setMaintenance] = useState(saved?.maintenance || false)
  const [savedAt, setSavedAt] = useState(null)

  const handleSave = (e) => {
    e.preventDefault()
    const settings = { siteName, tagline, defaultLocale, notifyEmail, maintenance }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(settings))
    setSavedAt(new Date())
  }

  const inputClass =
    'w-full h-11 px-3 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-400 focus:bg-white transition-colors'

  const labelClass = 'block text-sm font-medium text-gray-700 mb-1.5'

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('adminSettings')}</h1>
        {savedAt && (
          <span className="text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
            Saved {savedAt.toLocaleTimeString()}
          </span>
        )}
      </div>

      <form onSubmit={handleSave} className="bg-white rounded-2xl shadow-sm p-6 space-y-6">
        <div>
          <h2 className="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">General</h2>
          <div className="space-y-4">
            <div>
              <label htmlFor="siteName" className={labelClass}>Site Name</label>
              <input
                id="siteName"
                type="text"
                value={siteName}
                onChange={(e) => setSiteName(e.target.value)}
                className={inputClass}
              />
            </div>
            <div>
              <label htmlFor="tagline" className={labelClass}>Site Tagline</label>
              <input
                id="tagline"
                type="text"
                value={tagline}
                onChange={(e) => setTagline(e.target.value)}
                className={inputClass}
              />
            </div>
            <div>
              <label htmlFor="notifyEmail" className={labelClass}>Notification Email</label>
              <input
                id="notifyEmail"
                type="email"
                placeholder="admin@pi-education.edu.kh"
                value={notifyEmail}
                onChange={(e) => setNotifyEmail(e.target.value)}
                className={inputClass}
              />
            </div>
          </div>
        </div>

        <div className="border-t border-gray-100 pt-6">
          <h2 className="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Preferences</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label htmlFor="defaultLocale" className={labelClass}>Default Language</label>
              <select
                id="defaultLocale"
                value={defaultLocale}
                onChange={(e) => setDefaultLocale(e.target.value)}
                className={inputClass}
              >
                <option value="km">ខ្មែរ</option>
                <option value="en">English</option>
                <option value="zh">中文</option>
                <option value="fr">Français</option>
              </select>
            </div>
          </div>

          <div className="flex items-center justify-between mt-5 p-4 rounded-xl bg-gray-50">
            <div>
              <div className="text-sm font-medium text-gray-800">Maintenance Mode</div>
              <div className="text-xs text-gray-500 mt-0.5">
                Temporarily hide the storefront while you work on the platform.
              </div>
            </div>
            <button
              type="button"
              role="switch"
              aria-checked={maintenance}
              onClick={() => setMaintenance((v) => !v)}
              className={`relative w-12 h-7 rounded-full transition-colors shrink-0 ${
                maintenance ? 'bg-indigo-600' : 'bg-gray-300'
              }`}
            >
              <span
                className={`absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow transition-transform ${
                  maintenance ? 'translate-x-5' : ''
                }`}
              />
            </button>
          </div>
        </div>

        <div className="border-t border-gray-100 pt-6 flex justify-end">
          <button
            type="submit"
            className="inline-flex items-center justify-center h-11 px-6 text-sm font-medium bg-indigo-600 text-white rounded-xl shadow-sm shadow-indigo-600/20 transition-all duration-200 hover:bg-indigo-700 active:scale-95"
          >
            Save Settings
          </button>
        </div>
      </form>
    </div>
  )
}
