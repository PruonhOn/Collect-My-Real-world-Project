import { useLanguage } from '../../context/LanguageContext'

const KEYS = [
  ['← / →', 'reader.helpPrevNext'],
  ['Space', 'reader.helpSpace'],
  ['Home / End', 'reader.helpHomeEnd'],
  ['+ / −', 'reader.helpZoom'],
  ['T', 'reader.helpToc'],
  ['/', 'reader.helpSearch'],
  ['F', 'reader.helpFullscreen'],
  ['?', 'reader.helpHelp'],
  ['Esc', 'reader.helpEscape'],
]

export default function HelpPanel() {
  const { t } = useLanguage()

  return (
    <div className="rdr-help">
      <section className="rdr-help-section">
        <h3 className="rdr-setting-title">{t('reader.helpReading')}</h3>
        <ul className="rdr-help-list">
          <li>{t('reader.helpSwipe')}</li>
          <li>{t('reader.helpEdge')}</li>
          <li>{t('reader.helpSelect')}</li>
          <li>{t('reader.helpMath')}</li>
        </ul>
      </section>

      <section className="rdr-help-section">
        <h3 className="rdr-setting-title">{t('reader.helpKeys')}</h3>
        <dl className="rdr-key-list">
          {KEYS.map(([key, label]) => (
            <div className="rdr-key-row" key={key}>
              <dt>
                <kbd>{key}</kbd>
              </dt>
              <dd>{t(label)}</dd>
            </div>
          ))}
        </dl>
      </section>

      <section className="rdr-help-section">
        <h3 className="rdr-setting-title">{t('reader.helpAboutTitle')}</h3>
        <p className="rdr-help-text">{t('reader.helpAbout')}</p>
      </section>
    </div>
  )
}
