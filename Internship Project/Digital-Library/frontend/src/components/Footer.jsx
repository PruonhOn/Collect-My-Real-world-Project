import { Link } from 'react-router-dom'
import { useLanguage } from '../context/LanguageContext'

export default function Footer() {
  const { t } = useLanguage()
  return (
    <footer className="bg-gray-900 text-gray-300 mt-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div>
            <h3 className="text-white font-bold text-lg mb-3">{t('appName')}</h3>
            <p className="text-sm text-gray-400">{t('aboutText')}</p>
          </div>
          <div>
            <h4 className="text-white font-semibold mb-3">{t('links')}</h4>
            <ul className="space-y-2 text-sm">
              <li><Link to="/books" className="text-gray-400 hover:text-white transition-colors">{t('navBooks')}</Link></li>
              <li><Link to="/categories" className="text-gray-400 hover:text-white transition-colors">{t('navCategories')}</Link></li>
              <li><Link to="/library" className="text-gray-400 hover:text-white transition-colors">{t('navLibrary')}</Link></li>
            </ul>
          </div>
          <div>
            <h4 className="text-white font-semibold mb-3">{t('contact')}</h4>
            <ul className="space-y-2 text-sm text-gray-400">
              <li>Email: support@digitallibrary.kh</li>
              <li>Phone: +855 12 345 678</li>
              <li>Phnom Penh, Cambodia</li>
            </ul>
          </div>
        </div>
        <div className="border-t border-gray-800 mt-8 pt-6 text-center text-sm text-gray-500">
          © {new Date().getFullYear()} {t('appName')}. {t('allRightsReserved')}
        </div>
      </div>
    </footer>
  )
}
