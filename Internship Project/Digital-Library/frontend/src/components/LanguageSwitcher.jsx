import { useLanguage } from '../context/LanguageContext'

export default function LanguageSwitcher() {
  const { lang, setLang } = useLanguage()

  const options = [
    { code: 'kh', label: 'ខ្មែរ' },
    { code: 'en', label: 'EN' },
    { code: 'fr', label: 'FR' },
  ]

  return (
    <div className="flex items-center gap-0.5 border border-gray-200 rounded-lg p-0.5" title="Language">
      {options.map((o) => (
        <button
          key={o.code}
          onClick={() => setLang(o.code)}
          className={`px-2 py-1 rounded-md text-xs font-medium transition-colors ${
            lang === o.code ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'
          }`}
        >
          {o.label}
        </button>
      ))}
    </div>
  )
}
