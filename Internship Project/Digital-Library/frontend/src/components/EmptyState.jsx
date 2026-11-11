import { useLanguage } from '../context/LanguageContext'

export default function EmptyState({ title: titleProp, message: messageProp, action = null }) {
  const { t } = useLanguage()
  const title = titleProp ?? t('emptyDefaultTitle')
  const message = messageProp ?? t('emptyDefaultMsg')
  return (
    <div className="flex flex-col items-center justify-center py-20 text-center animate-fade-in">
      <div className="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
        <svg className="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
      </div>
      <h3 className="text-lg font-semibold text-gray-800">{title}</h3>
      <p className="mt-1 text-sm text-gray-500 max-w-sm">{message}</p>
      {action && <div className="mt-4">{action}</div>}
    </div>
  )
}
