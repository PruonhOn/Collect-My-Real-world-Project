import { useState, useEffect } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useNavigate, useParams } from 'react-router-dom'
import { adminCreateQuiz, adminUpdateQuiz, adminGetQuiz, getCourses } from '../../api'
import AdminNav from '../../components/admin/AdminNav'
import { useLanguage } from '../../context/LanguageContext'

const emptyQuestion = { question: '', options: ['', '', '', ''], correct_option: 0 }

export default function AdminQuizFormPage() {
  const { id } = useParams()
  const isEdit = !!id
  const { t } = useLanguage()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    title: '',
    description: '',
    course_id: '',
    pass_percent: 50,
    is_published: true,
  })
  const [questions, setQuestions] = useState([{ ...emptyQuestion }])
  const [error, setError] = useState('')

  const { data: coursesData } = useQuery({
    queryKey: ['courses-all'],
    queryFn: () => getCourses(),
  })
  const courses = coursesData?.data?.courses || []

  const { data: quizData } = useQuery({
    queryKey: ['admin-quiz', id],
    queryFn: () => adminGetQuiz(id),
    enabled: isEdit,
  })

  const quiz = quizData?.data?.quiz

  useEffect(() => {
    if (quiz) {
      setForm({
        title: quiz.title || '',
        description: quiz.description || '',
        course_id: quiz.course_id || '',
        pass_percent: quiz.pass_percent ?? 50,
        is_published: !!quiz.is_published,
      })
      setQuestions(
        quiz.questions?.length
          ? quiz.questions.map((q) => ({
              question: q.question,
              options: q.options.length >= 2 ? q.options : ['', ''],
              correct_option: q.correct_option,
            }))
          : [{ ...emptyQuestion }]
      )
    }
  }, [quiz])

  const mutation = useMutation({
    mutationFn: (data) => (isEdit ? adminUpdateQuiz(id, data) : adminCreateQuiz(data)),
    onSuccess: () => {
      navigate('/admin/quizzes')
    },
    onError: (err) => {
      const errors = err.response?.data?.errors
      setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message || 'Failed to save quiz')
    },
  })

  const updateQuestion = (index, patch) => {
    setQuestions((prev) => prev.map((q, i) => (i === index ? { ...q, ...patch } : q)))
  }

  const updateOption = (qIndex, oIndex, value) => {
    setQuestions((prev) =>
      prev.map((q, i) =>
        i === qIndex ? { ...q, options: q.options.map((opt, j) => (j === oIndex ? value : opt)) } : q
      )
    )
  }

  const addQuestion = () => setQuestions((prev) => [...prev, { ...emptyQuestion }])
  const removeQuestion = (index) => setQuestions((prev) => prev.filter((_, i) => i !== index))

  const handleSubmit = (e) => {
    e.preventDefault()
    const cleanedQuestions = questions.map((q, index) => ({
      question: q.question,
      options: q.options.filter((opt) => opt.trim() !== ''),
      correct_option: Number(q.correct_option),
      position: index + 1,
    }))

    mutation.mutate({
      title: form.title,
      description: form.description,
      course_id: form.course_id || undefined,
      pass_percent: Number(form.pass_percent),
      is_published: form.is_published,
      questions: cleanedQuestions,
    })
  }

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <AdminNav />

      <h1 className="text-2xl font-bold text-gray-900 mb-6">
        {isEdit ? t('edit') : t('add')} - {t('manageQuizzes')}
      </h1>

      {error && <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="bg-white rounded-2xl shadow-sm p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('question')} Title</label>
            <input
              type="text"
              required
              value={form.title}
              onChange={(e) => setForm({ ...form, title: e.target.value })}
              className={inputClass}
              placeholder={t('quizTitle')}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('description')}</label>
            <textarea
              rows={3}
              value={form.description}
              onChange={(e) => setForm({ ...form, description: e.target.value })}
              className={inputClass}
            />
          </div>
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('course')}</label>
              <select
                value={form.course_id}
                onChange={(e) => setForm({ ...form, course_id: e.target.value })}
                className={inputClass}
              >
                <option value="">-</option>
                {courses.map((course) => (
                  <option key={course.id} value={course.id}>
                    {course.title}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('passPercent')} (%)</label>
              <input
                type="number"
                min="1"
                max="100"
                value={form.pass_percent}
                onChange={(e) => setForm({ ...form, pass_percent: e.target.value })}
                className={inputClass}
              />
            </div>
          </div>
          <div>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={form.is_published}
                onChange={(e) => setForm({ ...form, is_published: e.target.checked })}
                className="w-4 h-4 text-indigo-600 rounded"
              />
              {t('published')}
            </label>
          </div>
        </div>

        <div className="space-y-4">
          {questions.map((q, qIndex) => (
            <div key={qIndex} className="bg-white rounded-2xl shadow-sm p-6">
              <div className="flex items-center justify-between mb-3">
                <span className="text-sm font-bold text-gray-900">
                  {t('question')} {qIndex + 1}
                </span>
                <button
                  type="button"
                  onClick={() => removeQuestion(qIndex)}
                  disabled={questions.length <= 1}
                  className="text-xs text-red-600 hover:text-red-800 disabled:opacity-40"
                >
                  {t('removeQuestion')}
                </button>
              </div>
              <input
                type="text"
                required
                value={q.question}
                onChange={(e) => updateQuestion(qIndex, { question: e.target.value })}
                placeholder={t('questionTitlePlaceholder')}
                className={inputClass}
              />
              <div className="mt-3 space-y-2">
                {q.options.map((option, oIndex) => (
                  <div key={oIndex} className="flex items-center gap-3">
                    <input
                      type="radio"
                      name={`correct-${qIndex}`}
                      checked={q.correct_option === oIndex}
                      onChange={() => updateQuestion(qIndex, { correct_option: oIndex })}
                      className="w-4 h-4 accent-indigo-600"
                      title={t('correctOption')}
                    />
                    <input
                      type="text"
                      required={oIndex < 2}
                      value={option}
                      onChange={(e) => updateOption(qIndex, oIndex, e.target.value)}
                      placeholder={`${t('option')} ${oIndex + 1}`}
                      className={inputClass}
                    />
                  </div>
                ))}
              </div>
              <p className="mt-2 text-xs text-gray-400">{t('correctOption')}: #{Number(q.correct_option) + 1}</p>
            </div>
          ))}
        </div>

        <button
          type="button"
          onClick={addQuestion}
          className="w-full py-3 border-2 border-dashed border-gray-300 rounded-2xl text-gray-600 hover:border-indigo-400 hover:text-indigo-600"
        >
          + {t('addQuestion')}
        </button>

        <div className="flex gap-3 pt-4">
          <button
            type="submit"
            disabled={mutation.isPending}
            className="flex-1 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
          >
            {mutation.isPending ? t('loading') : t('save')}
          </button>
          <button
            type="button"
            onClick={() => navigate('/admin/quizzes')}
            className="px-6 py-3 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50"
          >
            {t('cancel')}
          </button>
        </div>
      </form>
    </div>
  )
}