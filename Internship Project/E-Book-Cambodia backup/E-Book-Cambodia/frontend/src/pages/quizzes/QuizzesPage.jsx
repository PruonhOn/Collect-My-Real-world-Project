import { useQuery } from '@tanstack/react-query'
import { getQuizzes } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { Link } from 'react-router-dom'

export default function QuizzesPage() {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['quizzes'],
    queryFn: () => getQuizzes({ per_page: 20 }),
  })

  const quizzes = data?.data?.data || []

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <h1 className="text-3xl font-bold text-gray-900 mb-6">{t('quizzes')}</h1>

      {isLoading ? (
        <div className="text-center py-10">{t('loading')}</div>
      ) : quizzes.length === 0 ? (
        <div className="text-center py-16 bg-white rounded-2xl shadow-sm">
          <p className="text-gray-500 mb-4">{t('noQuizzes')}</p>
          <Link to="/books" className="text-indigo-600 font-medium">
            {t('allBooks')}
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {quizzes.map((quiz) => (
            <Link
              key={quiz.id}
              to={`/quizzes/${quiz.id}`}
              className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow"
            >
              <div className="text-xs font-semibold text-indigo-600 uppercase tracking-wide">
                {quiz.course?.title || t('quiz')}
              </div>
              <h2 className="mt-2 text-lg font-bold text-gray-900">{quiz.title}</h2>
              {quiz.description && (
                <p className="mt-2 text-sm text-gray-600 line-clamp-2">{quiz.description}</p>
              )}
              <div className="mt-4 flex items-center justify-between text-sm text-gray-500">
                <span>
                  {quiz.questions_count} {t('totalQuestions')}
                </span>
                <span className="text-indigo-600 font-medium">{t('startQuiz')}</span>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}