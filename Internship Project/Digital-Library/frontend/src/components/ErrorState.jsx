import { useLanguage } from '../context/LanguageContext'

export default function ErrorState({ title: titleProp, message: messageProp, onRetry = null }) {
  const { t } = useLanguage()
  const title = titleProp ?? t('errorsDefaultTitle')
  const message = messageProp ?? t('errorsDefaultMsg')
  return (
    <div className="flex flex-col items-center justify-center py-20 text-center animate-fade-in">
      <div className="w-20 h-20 rounded-full bg-red-50 flex items-center justify-center mb-4">
        <svg className="w-10 h-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h3 className="text-lg font-semibold text-gray-800">{title}</h3>
      <p className="mt-1 text-sm text-gray-500 max-w-sm">{message}</p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"
        >
          {t('tryAgain')}
        </button>
      )}
    </div>
  )
}
