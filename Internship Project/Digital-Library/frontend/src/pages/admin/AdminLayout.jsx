import { NavLink, Outlet } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'

const items = [
  { to: '/admin', labelKey: 'dashboards', icon: '📊', end: true },
  { to: '/admin/users', labelKey: 'aUsers', icon: '👥' },
  { to: '/admin/books', labelKey: 'aBooks', icon: '📚' },
  { to: '/admin/categories', labelKey: 'aCategories', icon: '🗂️' },
  { to: '/admin/courses', labelKey: 'aCourses', icon: '🎓' },
  { to: '/admin/payments', labelKey: 'aPayments', icon: '💳' },
  { to: '/admin/sharing', labelKey: 'aSharingKeys', icon: '🔑' },
  { to: '/admin/reviews', labelKey: 'aReviews', icon: '⭐' },
]

export default function AdminLayout() {
  const { t } = useLanguage()
  return (
    <div className="flex min-h-[90vh]">
      <aside className="w-60 bg-gray-900 text-white shrink-0 hidden md:block">
        <div className="p-4 border-b border-gray-800">
          <h2 className="font-bold">{t('adminPanel')}</h2>
        </div>
        <nav className="p-3 space-y-1">
          {items.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors ${
                  isActive ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800'
                }`
              }
            >
              <span>{item.icon}</span>
              <span>{t(item.labelKey)}</span>
            </NavLink>
          ))}
        </nav>
      </aside>

      <main className="flex-1 bg-gray-50 min-w-0">
        <div className="md:hidden bg-gray-900 text-white p-3 flex gap-2 overflow-x-auto">
          {items.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                `px-3 py-1.5 rounded-lg text-xs whitespace-nowrap ${isActive ? 'bg-indigo-600' : 'text-gray-300'}`
              }
            >
              {item.icon} {t(item.labelKey)}
            </NavLink>
          ))}
        </div>
        <div className="p-4 sm:p-6">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
