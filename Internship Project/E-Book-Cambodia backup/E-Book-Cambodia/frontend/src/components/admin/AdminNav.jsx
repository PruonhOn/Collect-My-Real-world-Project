import { NavLink } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminNav() {
  const { t } = useLanguage()

  const navItems = [
    { to: '/admin', label: t('adminDashboard'), end: true, icon: '📊' },
    { to: '/admin/books', label: t('adminBooks'), icon: '📚' },
    { to: '/admin/categories', label: t('adminCategories'), icon: '🗂️' },
    { to: '/admin/users', label: t('adminUsers'), icon: '👥' },
    { to: '/admin/purchases', label: t('adminPurchases'), icon: '🛒' },
    { to: '/admin/quizzes', label: t('manageQuizzes'), icon: '📝' },
    { to: '/admin/reviews', label: t('adminReviews'), icon: '⭐' },
  ]

  return (
    <div className="bg-white rounded-2xl shadow-sm p-3 mb-6 overflow-x-auto">
      <div className="flex gap-1 min-w-max">
        {navItems.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.end}
            className={({ isActive }) =>
              `flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-colors ${
                isActive ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'
              }`
            }
          >
            <span>{item.icon}</span>
            {item.label}
          </NavLink>
        ))}
      </div>
    </div>
  )
}