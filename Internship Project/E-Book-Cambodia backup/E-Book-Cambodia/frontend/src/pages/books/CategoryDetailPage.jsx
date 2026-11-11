import { useQuery } from '@tanstack/react-query'
import { useParams, Link } from 'react-router-dom'
import { getCategory } from '../../api'
import BookGrid from '../../components/BookGrid'
import { useLanguage } from '../../context/LanguageContext'

export default function CategoryDetailPage() {
  const { id } = useParams()
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['category', id],
    queryFn: () => getCategory(id),
    enabled: !!id,
  })

  const res = data?.data
  const category = res?.category

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <Link to="/categories" className="text-indigo-600 text-sm hover:text-indigo-700 mb-4 inline-block">
        ← {t('back')}
      </Link>

      <div className="mb-8">
        <div className="flex items-center gap-4">
          <div className="w-16 h-16 rounded-2xl bg-indigo-100 flex items-center justify-center text-3xl">
            📚
          </div>
          <div>
            <h1 className="text-3xl font-bold text-gray-900">{category?.name || id}</h1>
            {category?.description && <p className="mt-1 text-gray-500">{category.description}</p>}
          </div>
        </div>
      </div>

      <BookGrid books={res?.books || []} loading={isLoading} />
    </div>
  )
}