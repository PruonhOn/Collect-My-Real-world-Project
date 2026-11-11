import { useMutation, useQuery } from '@tanstack/react-query'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { getQuiz, submitQuizAttempt } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useState } from 'react'

const MAX_ATTEMPTS = 3

export default function QuizDetailPage() {
  const { id } = useParams()
  const { t } = useLanguage()
  const { isAuthenticated } = useAuth()
  const navigate = useNavigate()
  const [answers, setAnswers] = useState({})
  const [result, setResult] = useState(null)

  const { data, isLoading } = useQuery({
    queryKey: ['quiz', id],
    refetchInterval: 30000,
    queryFn: () => getQuiz(id),
    enabled: !!id,
  })

  const quiz = data?.data?.quiz
  const attempts = data?.data?.attempts || []
  const attemptsUsed = attempts.length
  const attemptsLeft = Math.max(0, MAX_ATTEMPTS - attemptsUsed)

  const attemptMutation = useMutation({
    mutationFn: () => submitQuizAttempt(quiz.id, { answers }),
    onSuccess: (res) => setResult(res.data),
    onError: (error) => alert(error.response?.data?.message || t('error')),
  })

  if (isLoading) {
    return <div className="max-w-4xl mx-auto px-4 py-10 text-center">{t('loading')}</div>
  }

  if (!quiz) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">{t('pageNotFound')}</h1>
        <Link to="/quizzes" className="text-indigo-600 font-medium">
          {t('backToHome')}
        </Link>
      </div>
    )
  }

  if (!isAuthenticated) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-gray-900 mb-4">{quiz.title}</h1>
        <p className="text-gray-600 mb-6">{t('restrictQuiz')}</p>
        <button
          onClick={() => navigate('/login')}
          className="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700"
        >
          {t('login')}
        </button>
      </div>
    )
  }

  const allAnswered = quiz.questions.length > 0 && quiz.questions.every((q) => answers[q.id] !== undefined)

  const handleSubmit = () => {
    if (!allAnswered) {
      alert(t('answerAllQuestions'))
      return
    }
    attemptMutation.mutate()
  }

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <nav className="text-sm text-gray-500 mb-4">
        <Link to="/quizzes" className="hover:text-indigo-600">
          {t('quizzes')}
        </Link>
        <span className="mx-2">/</span>
        <span>{quiz.title}</span>
      </nav>

      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900">{quiz.title}</h1>
          {quiz.course && <p className="text-indigo-600 font-medium mt-1">{quiz.course.title}</p>}
        </div>
        <div className="text-sm text-gray-500">
          {quiz.questions.length} {t('totalQuestions')} · {t('passPercent')}: {quiz.pass_percent}%
        </div>
      </div>

      {attemptsUsed > 0 && (
        <div className="mb-6 text-sm text-gray-600">
          {t('attemptsUsed')}: {attemptsUsed} / {MAX_ATTEMPTS}
        </div>
      )}

      {result ? (
        <ResultView t={t} quiz={quiz} result={result} onRetry={() => setResult(null)} />
      ) : attemptsLeft <= 0 ? (
        <div className="text-center py-16 bg-white rounded-2xl shadow-sm">
          <p className="text-gray-500 mb-4">{t('attemptsUsed')}: {MAX_ATTEMPTS}</p>
          <Link to="/quizzes" className="text-indigo-600 font-medium">
            {t('backToHome')}
          </Link>
        </div>
      ) : (
        <div className="space-y-6">
          {quiz.questions.map((question, qIndex) => (
            <div key={question.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <div className="text-xs font-semibold text-indigo-600 uppercase mb-2">
                {t('question')} {qIndex + 1}
              </div>
              <h2 className="text-lg font-semibold text-gray-900 mb-4">{question.question}</h2>
              <div className="space-y-2">
                {question.options.map((option, oIndex) => (
                  <label
                    key={oIndex}
                    className={`flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors ${
                      answers[question.id] === oIndex
                        ? 'border-indigo-500 bg-indigo-50'
                        : 'border-gray-200 hover:bg-gray-50'
                    }`}
                  >
                    <input
                      type="radio"
                      name={`q-${question.id}`}
                      checked={answers[question.id] === oIndex}
                      onChange={() => setAnswers({ ...answers, [question.id]: oIndex })}
                      className="w-4 h-4 accent-indigo-600"
                    />
                    <span className="text-gray-800">{option}</span>
                  </label>
                ))}
              </div>
            </div>
          ))}

          <div className="flex flex-wrap gap-3">
            <button
              onClick={handleSubmit}
              disabled={attemptMutation.isPending}
              className="px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
            >
              {attemptMutation.isPending ? t('loading') : t('submitAnswers')}
            </button>
            {!allAnswered && <span className="text-sm text-gray-400 self-center">{t('answerAllQuestions')}</span>}
          </div>
        </div>
      )}
    </div>
  )
}

function ResultView({ t, quiz, result, onRetry }) {
  const review = result.review || []
  const correctCount = result.attempt?.score ?? review.filter((r) => r.is_correct).length

  return (
    <div>
      <div
        className={`rounded-2xl p-6 mb-6 ${
          result.passed ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'
        }`}
      >
        <div className="text-3xl font-bold">
          {t('yourScore')}: {correctCount}/{result.attempt?.total ?? review.length}
        </div>
        <div className="mt-1 font-semibold uppercase text-sm tracking-wide">
          {result.passed ? t('passed') : t('failed')}
        </div>
        <div className="mt-1 text-sm opacity-80">
          {t('passPercent')}: {quiz.pass_percent}%
        </div>
      </div>

      <div className="space-y-6">
        {review.map((item, qIndex) => (
          <div key={item.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="text-xs font-semibold uppercase mb-2">
              <span className={item.is_correct ? 'text-emerald-600' : 'text-red-600'}>
                {item.is_correct ? t('passed') : t('failed')}
              </span>
              <span className="text-gray-400 ml-2">
                {t('question')} {qIndex + 1}
              </span>
            </div>
            <h2 className="text-lg font-semibold text-gray-900 mb-3">{item.question}</h2>
            <div className="space-y-2">
              {item.options.map((option, oIndex) => {
                const isCorrect = oIndex === item.correct_option
                const isSelected = oIndex === item.selected
                return (
                  <div
                    key={oIndex}
                    className={`p-3 rounded-xl border text-sm ${
                      isCorrect
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                        : isSelected
                          ? 'border-red-300 bg-red-50 text-red-700'
                          : 'border-gray-200 text-gray-700'
                    }`}
                  >
                    {option}
                    {isCorrect && <span className="ml-2 text-xs font-semibold">{t('correctAnswer')}</span>}
                    {isSelected && !isCorrect && (
                      <span className="ml-2 text-xs font-semibold">{t('yourAnswer')}</span>
                    )}
                  </div>
                )
              })}
            </div>
          </div>
        ))}
      </div>

      <div className="mt-6 flex flex-wrap gap-3">
        <Link
          to="/quizzes"
          className="px-6 py-3 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"
        >
          {t('backToHome')}
        </Link>
        {!result.passed && (
          <button
            onClick={onRetry}
            className="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700"
          >
            {t('takeQuiz')}
          </button>
        )}
      </div>
    </div>
  )
}