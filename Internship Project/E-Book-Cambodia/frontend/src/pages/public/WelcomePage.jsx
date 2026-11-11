import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'

export default function WelcomePage() {
  const { t } = useLanguage()

  return (
    <div>
      <section className="bg-gradient-to-r from-indigo-700 via-indigo-600 to-purple-600 text-white">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20 text-center">
          <div className="text-5xl mb-4">📚</div>
          <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-4">
            {t('welcomeTab')} {t('appName')}
          </h1>
          <p className="text-lg text-indigo-100 mb-8">{t('homeHeroSubtitle')}</p>
          <div className="flex flex-wrap items-center justify-center gap-3">
            <Link
              to="/books"
              className="inline-flex items-center justify-center h-11 px-6 text-sm font-semibold bg-white text-indigo-700 rounded-xl shadow-sm hover:bg-indigo-50 transition-colors"
            >
              {t('exploreBooks')}
            </Link>
            <Link
              to="/student"
              className="inline-flex items-center justify-center h-11 px-6 text-sm font-semibold bg-indigo-500 text-white rounded-xl ring-1 ring-white/40 hover:bg-indigo-400 transition-colors"
            >
              {t('studentBook')}
            </Link>
          </div>
        </div>
      </section>

      <section className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid sm:grid-cols-3 gap-6">
          <div className="bg-white rounded-2xl shadow-sm p-6 text-center">
            <div className="text-3xl mb-3">📖</div>
            <h3 className="font-semibold text-gray-900 mb-2">{t('teacherBooks')}</h3>
            <p className="text-sm text-gray-500">{t('teacherTagline')}</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm p-6 text-center">
            <div className="text-3xl mb-3">🎓</div>
            <h3 className="font-semibold text-gray-900 mb-2">{t('studentBook')}</h3>
            <p className="text-sm text-gray-500">{t('studentTagline')}</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm p-6 text-center">
            <div className="text-3xl mb-3">🧩</div>
            <h3 className="font-semibold text-gray-900 mb-2">{t('quizzes')}</h3>
            <p className="text-sm text-gray-500">{t('featureQuizzesDesc')}</p>
          </div>
        </div>
      </section>
    </div>
  )
}