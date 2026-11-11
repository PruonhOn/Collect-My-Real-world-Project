import { useLanguage } from '../../context/LanguageContext'

export default function AboutPage() {
  const { t } = useLanguage()

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div className="max-w-3xl mx-auto">
        <h1 className="text-3xl font-bold text-gray-900 mb-6">{t('about')} {t('appName')}</h1>

        <div className="space-y-6">
          <section className="bg-white rounded-2xl shadow-sm p-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-3">Our Mission</h2>
            <p className="text-gray-600 leading-relaxed">
              Pi-Education Cambodia is dedicated to making quality educational content accessible to all Cambodians.
              We provide a digital library of e-books across mathematics, science, literature, technology, and more.
            </p>
          </section>

          <section className="bg-white rounded-2xl shadow-sm p-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-3">What We Offer</h2>
            <ul className="space-y-3 text-gray-600">
              <li className="flex items-start gap-2">
                <span className="text-indigo-600 mt-1">•</span>
                Free educational e-books for students of all levels
              </li>
              <li className="flex items-start gap-2">
                <span className="text-indigo-600 mt-1">•</span>
                Paid premium books with secure Bakong payment
              </li>
              <li className="flex items-start gap-2">
                <span className="text-indigo-600 mt-1">•</span>
                Books in Khmer and English languages
              </li>
              <li className="flex items-start gap-2">
                <span className="text-indigo-600 mt-1">•</span>
                Reading progress tracking and bookmarks
              </li>
              <li className="flex items-start gap-2">
                <span className="text-indigo-600 mt-1">•</span>
                Reviews and ratings from the community
              </li>
            </ul>
          </section>

          <section className="bg-white rounded-2xl shadow-sm p-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-3">Contact Us</h2>
            <p className="text-gray-600 mb-4">Have questions or suggestions? We'd love to hear from you.</p>
            <a
              href="/contact"
              className="inline-block px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700"
            >
              {t('contact')}
            </a>
          </section>
        </div>
      </div>
    </div>
  )
}