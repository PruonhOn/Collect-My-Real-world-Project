import { useLanguage } from '../../context/LanguageContext'
import {
  FONT_STEPS,
  LINE_HEIGHTS,
  bodyFontSize,
  fontStepLabel,
} from '../../utils/readerSettings'
import { ChevronLeftIcon, ChevronRightIcon, MoonIcon, SunIcon } from './ReaderIcons'

function Choice({ active, onClick, children, label }) {
  return (
    <button
      type="button"
      className={`rdr-choice${active ? ' is-active' : ''}`}
      onClick={onClick}
      aria-pressed={active}
      title={label}
    >
      {children}
    </button>
  )
}

export default function SettingsPanel({
  settings,
  onChange,
  zoom,
  onZoomIn,
  onZoomOut,
  onResetZoom,
  synced,
}) {
  const { t, locale } = useLanguage()
  const sampleSize = bodyFontSize(settings, locale === 'km')

  return (
    <div className="rdr-settings">
      <section className="rdr-setting-group">
        <h3 className="rdr-setting-title">{t('reader.theme')}</h3>
        <div className="rdr-theme-grid" role="group" aria-label={t('reader.theme')}>
          <Choice
            active={settings.theme === 'light'}
            onClick={() => onChange({ theme: 'light' })}
            label={t('reader.themeLight')}
          >
            <span className="rdr-theme-swatch rdr-swatch-light">
              <SunIcon size={18} />
            </span>
            {t('reader.themeLight')}
          </Choice>
          <Choice
            active={settings.theme === 'sepia'}
            onClick={() => onChange({ theme: 'sepia' })}
            label={t('reader.themeSepia')}
          >
            <span className="rdr-theme-swatch rdr-swatch-sepia">Aa</span>
            {t('reader.themeSepia')}
          </Choice>
          <Choice
            active={settings.theme === 'focus'}
            onClick={() => onChange({ theme: 'focus' })}
            label={t('reader.themeFocus')}
          >
            <span className="rdr-theme-swatch rdr-swatch-focus">
              <MoonIcon size={18} />
            </span>
            {t('reader.themeFocus')}
          </Choice>
        </div>
      </section>

      <section className="rdr-setting-group">
        <h3 className="rdr-setting-title">{t('reader.typeface')}</h3>
        <div className="rdr-segmented" role="group" aria-label={t('reader.typeface')}>
          <Choice
            active={settings.font === 'serif'}
            onClick={() => onChange({ font: 'serif' })}
            label={t('reader.serif')}
          >
            <span className="rdr-serif">Aa</span>
            {t('reader.serif')}
          </Choice>
          <Choice
            active={settings.font === 'sans'}
            onClick={() => onChange({ font: 'sans' })}
            label={t('reader.sans')}
          >
            <span className="rdr-sans">Aa</span>
            {t('reader.sans')}
          </Choice>
        </div>
      </section>

      <section className="rdr-setting-group">
        <div className="rdr-setting-line">
          <h3 className="rdr-setting-title">{t('reader.textSize')}</h3>
          <span className="rdr-setting-value">{fontStepLabel(settings)}</span>
        </div>
        <div className="rdr-size-row">
          <button
            type="button"
            className="rdr-size-btn"
            onClick={() => onChange({ fontSize: settings.fontSize - 1 })}
            disabled={settings.fontSize <= 0}
            aria-label={t('reader.smallerText')}
          >
            A−
          </button>
          <div className="rdr-size-track" aria-hidden="true">
            <span style={{ width: `${((settings.fontSize + 1) / FONT_STEPS.length) * 100}%` }} />
          </div>
          <button
            type="button"
            className="rdr-size-btn rdr-size-btn-lg"
            onClick={() => onChange({ fontSize: settings.fontSize + 1 })}
            disabled={settings.fontSize >= FONT_STEPS.length - 1}
            aria-label={t('reader.largerText')}
          >
            A+
          </button>
        </div>
      </section>

      <section className="rdr-setting-group">
        <h3 className="rdr-setting-title">{t('reader.lineSpacing')}</h3>
        <div className="rdr-segmented" role="group" aria-label={t('reader.lineSpacing')}>
          {LINE_HEIGHTS.map((value) => (
            <Choice
              key={value}
              active={settings.lineHeight === value}
              onClick={() => onChange({ lineHeight: value })}
              label={`${value}`}
            >
              <span
                className="rdr-line-sample"
                style={{ lineHeight: value }}
                aria-hidden="true"
              >
                ≡
              </span>
              {value}
            </Choice>
          ))}
        </div>
      </section>

      <section className="rdr-setting-group">
        <h3 className="rdr-setting-title">{t('reader.layout')}</h3>
        <div className="rdr-segmented rdr-segmented-wide" role="group" aria-label={t('reader.layout')}>
          <Choice
            active={settings.mode === 'spread'}
            onClick={() => onChange({ mode: 'spread' })}
            label={t('reader.layoutSpread')}
          >
            <span className="rdr-layout-icon rdr-layout-spread" aria-hidden="true">
              <i />
              <i />
            </span>
            {t('reader.layoutSpread')}
          </Choice>
          <Choice
            active={settings.mode === 'single'}
            onClick={() => onChange({ mode: 'single' })}
            label={t('reader.layoutSingle')}
          >
            <span className="rdr-layout-icon rdr-layout-single" aria-hidden="true">
              <i />
            </span>
            {t('reader.layoutSingle')}
          </Choice>
          <Choice
            active={settings.mode === 'scroll'}
            onClick={() => onChange({ mode: 'scroll' })}
            label={t('reader.layoutScroll')}
          >
            <span className="rdr-layout-icon rdr-layout-scroll" aria-hidden="true">
              <i />
              <i />
              <i />
            </span>
            {t('reader.layoutScroll')}
          </Choice>
        </div>
      </section>

      <section className="rdr-setting-group">
        <div className="rdr-setting-line">
          <h3 className="rdr-setting-title">{t('reader.zoom')}</h3>
          <span className="rdr-setting-value">{Math.round(zoom * 100)}%</span>
        </div>
        <div className="rdr-zoom-row">
          <button
            type="button"
            className="rdr-size-btn"
            onClick={onZoomOut}
            disabled={zoom <= 0.5}
            aria-label={t('reader.zoomOut')}
          >
            <ChevronLeftIcon size={17} />
          </button>
          <button type="button" className="rdr-wide-btn rdr-reset-zoom" onClick={onResetZoom}>
            {t('reader.resetZoom')}
          </button>
          <button
            type="button"
            className="rdr-size-btn"
            onClick={onZoomIn}
            disabled={zoom >= 3}
            aria-label={t('reader.zoomIn')}
          >
            <ChevronRightIcon size={17} />
          </button>
        </div>
      </section>

      <div
        className={`rdr-type-sample rdr-font-${settings.font}`}
        style={{ fontSize: `${Math.min(30, sampleSize + 2)}px`, lineHeight: settings.lineHeight }}
        aria-hidden="true"
      >
        {t('reader.sampleText')}
      </div>

      <p className="rdr-panel-note">
        {synced ? t('reader.settingsSynced') : t('reader.settingsLocal')}
      </p>
    </div>
  )
}
