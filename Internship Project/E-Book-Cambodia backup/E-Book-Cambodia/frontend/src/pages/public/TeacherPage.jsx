import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { getBooks, getCategories } from '../../api'
import BookGrid from '../../components/BookGrid'
import { useLanguage } from '../../context/LanguageContext'

export default function TeacherPage() {
  const { t } = useLanguage()

  const { data: categoriesData } = useQuery({
    queryKey: ['categories'],
    queryFn: () => getCategories(),
  })

  const categories = categoriesData?.data?.categories || []
  const eduCategory = categories.find(
    (c) => c.name.toLowerCase().includes('educ') || c.name.toLowerCase().includes('learn')
  )

  const { data: resourcesData, isLoading } = useQuery({
    queryKey: ['books', 'teacher', eduCategory?.id],
    queryFn: () =>
      getBooks({
        ...(eduCategory ? { category: eduCategory.id } : { free: '1' }),
        per_page: 8,
      }),
  })

  const books = resourcesData?.data?.data || []

  return (
    <div>
      {/* Hero */}
      <section className="bg-gradient-to-r from-emerald-700 via-teal-600 to-cyan-600 text-white">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20 text-center">
          <div className="text-5xl mb-4">👩‍🏫</div>
          <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-4">{t('teacher')}</h1>
          <p className="text-lg text-emerald-100 mb-8">{t('teacherTagline')}</p>
          <Link
            to="/books"
            className="px-6 py-3 bg-white text-emerald-700 font-semibold rounded-xl hover:bg-emerald-50 transition-colors"
          >
            {t('allBooks')}
          </Link>
        </div>
      </section>

      {/* Resources */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="text-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">{t('teacherResources')}</h2>
        </div>
        <BookGrid books={books} loading={isLoading} emptyMessage={t('noBooks')} />
      </section>
    </div>
  )
}