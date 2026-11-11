import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'

const gradeColors = [
  'from-rose-500 to-pink-500',
  'from-orange-500 to-amber-500',
  'from-amber-500 to-yellow-500',
  'from-lime-500 to-green-500',
  'from-emerald-500 to-teal-500',
  'from-cyan-500 to-sky-500',
  'from-blue-500 to-indigo-500',
  'from-indigo-500 to-violet-500',
  'from-purple-500 to-fuchsia-500',
  'from-fuchsia-500 to-pink-500',
  'from-pink-500 to-rose-500',
  'from-teal-500 to-cyan-500',
]

function gradeLabel(grade) {
  if (grade === 1) return '1st Grade'
  if (grade === 2) return '2nd Grade'
  if (grade === 3) return '3rd Grade'
  return `${grade}th Grade`
}

export default function StudentPage() {
  const { t } = useLanguage()
  const grades = Array.from({ length: 12 }, (_, i) => i + 1)

  return (
    <div>
      {/* Hero */}
      <section className="bg-gradient-to-r from-indigo-700 via-indigo-600 to-purple-600 text-white">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20 text-center">
          <div className="text-5xl mb-4">🎓</div>
          <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-4">{t('student')}</h1>
          <p className="text-lg text-indigo-100 mb-8">{t('studentTagline')}</p>
        </div>
      </section>

      {/* Grade selector */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="text-center mb-8">
          <h2 className="text-2xl font-bold text-gray-900">{t('chooseGrade')}</h2>
          <p className="text-gray-500 mt-2 text-sm">{t('studentHint')}</p>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          {grades.map((grade, i) => (
            <Link
              key={grade}
              to={`/books?category=math&grade=${grade}&audience=student`}
              className={`bg-gradient-to-br ${gradeColors[i % gradeColors.length]} rounded-2xl p-6 text-white shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all text-center group relative overflow-hidden`}
            >
              <div className="absolute -right-6 -top-6 w-20 h-20 bg-white/10 rounded-full" />
              <div className="text-4xl font-black mb-1 drop-shadow">{grade}</div>
              <div className="text-sm font-semibold opacity-90">{gradeLabel(grade)}</div>
              <div className="mt-3 inline-flex items-center gap-1 text-xs font-medium bg-white/20 rounded-full px-3 py-1 group-hover:bg-white/30 transition-colors">
                {t('books')} →
              </div>
            </Link>
          ))}
        </div>
      </section>
    </div>
  )
}