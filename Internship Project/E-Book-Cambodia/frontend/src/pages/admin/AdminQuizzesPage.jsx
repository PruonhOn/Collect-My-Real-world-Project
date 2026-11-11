import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { adminGetQuizzes, adminDeleteQuiz } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminQuizzesPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-quizzes', page],
    refetchInterval: 15000,
    queryFn: () => adminGetQuizzes({ per_page: 15, page }),
  })

  const quizzes = data?.data?.data || []
  const lastPage = data?.data?.last_page || 1

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteQuiz(id),
    onSuccess: () => queryClient.invalidateQueries(['admin-quizzes']),
  })

  const handleDelete = (quiz) => {
    if (window.confirm(`${t('confirmDelete')} "${quiz.title}"?`)) {
      deleteMutation.mutate(quiz.id)
    }
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('manageQuizzes')}</h1>
          <p className="text-gray-500 text-sm mt-1">{quizzes.length} {t('quizzes')}</p>
        </div>
        <Link
          to="/admin/quizzes/new"
          className="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 whitespace-nowrap"
        >
          + {t('add')}
        </Link>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : quizzes.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noQuizzes')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('title')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('course')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('totalQuestions')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {quizzes.map((quiz) => (
                  <tr key={quiz.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{quiz.id}</td>
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{quiz.title}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">{quiz.course?.title || '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">{quiz.questions_count}</td>
                    <td className="px-6 py-4 text-sm">
                      <span
                        className={`text-xs font-medium px-2 py-1 rounded-full ${
                          quiz.is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'
                        }`}
                      >
                        {quiz.is_published ? t('published') : t('draft')}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right text-sm space-x-2 whitespace-nowrap">
                      <Link to={`/admin/quizzes/${quiz.id}/edit`} className="text-indigo-600 hover:text-indigo-800">
                        {t('edit')}
                      </Link>
                      <button onClick={() => handleDelete(quiz)} className="text-red-600 hover:text-red-800">
                        {t('delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {lastPage > 1 && (
        <div className="mt-6 flex items-center justify-center gap-4">
          <button
            disabled={page <= 1}
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            className="px-4 py-2 text-sm border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50"
          >
            {t('previous')}
          </button>
          <span className="text-sm text-gray-600">
            Page {page} of {lastPage}
          </span>
          <button
            disabled={page >= lastPage}
            onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
            className="px-4 py-2 text-sm border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50"
          >
            {t('next')}
          </button>
        </div>
      )}
    </div>
  )
}
