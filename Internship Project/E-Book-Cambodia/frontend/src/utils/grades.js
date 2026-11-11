const GRADE_RE = /[-–—]\s*(?:grade\s*(\d+)|kindergarten)\s*$/i

const KHMER_DIGITS = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩']

const toKhmerDigits = (num) =>
  String(num)
    .split('')
    .map((digit) => KHMER_DIGITS[Number(digit)])
    .join('')

export const isGradeSub = (cat) => GRADE_RE.test(String(cat.name || ''))

export const parseGradeName = (cat) => {
  const match = String(cat.name || '').match(GRADE_RE)
  if (!match) return null
  return match[1] ? Number(match[1]) : -1
}

export const baseNameOf = (cat) => String(cat.name || '').replace(GRADE_RE, '').trim()

export const slugOfName = (name) =>
  String(name || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')

export const gradeKey = (num) => String(num)

export const gradeKeyFromName = (cat) => {
  const num = parseGradeName(cat)
  return num === null ? '' : gradeKey(num)
}

export function gradeDisplayName(locale, t, num) {
  if (num === -1) return t('gradeKindergarten')
  if (locale === 'km') return `${t('gradeLabel')}${toKhmerDigits(num)}`
  if (locale === 'zh') return `${num}${t('gradeLabel')}`
  return `${t('gradeLabel')} ${num}`
}

/**
 * Khmer subject prefixes used to build grade sub-navigation labels
 * (e.g. គណិតថ្នាក់ទី១ = "Math Grade 1"). Keep the base category name
 * (e.g. "Math") as the key so future subjects like Khmer, Physics,
 * Chemistry, Biology, English, Chinese and French only need a row here.
 */
const KM_SUBJECT_PREFIX = {
  Math: 'គណិត',
  Khmer: 'ខ្មែរ',
  Physics: 'រូបវិទ្យា',
  Chemistry: 'គីមីវិទ្យា',
  Biology: 'ជីវវិទ្យា',
  English: 'អង់គ្លេស',
  Chinese: 'ចិន',
  French: 'បារាំង',
}

/**
 * Label for a grade item inside a subject's secondary navigation menu.
 * Khmer gets the subject prefix (គណិតថ្នាក់ទី១); other locales keep the
 * existing plain grade label (Grade 1) so behavior is unchanged.
 */
export function gradeNavLabel(locale, t, baseName, num) {
  const label = gradeDisplayName(locale, t, num)
  if (locale !== 'km') return label
  const prefix = KM_SUBJECT_PREFIX[String(baseName || '')]
  return prefix ? `${prefix}${label}` : label
}

/**
 * Grade sub-categories belonging to a main subject category
 * (e.g. "Math" book count to "Math - Grade 1", "Math - Grade 2", ...).
 */
export const gradeSubsFor = (categories = [], cat) => {
  const name = String(cat?.name || '')
  if (!name || isGradeSub(cat)) return []
  return categories.filter((c) => isGradeSub(c) && baseNameOf(c) === name)
}

export const hasGradeSubs = (categories = [], cat) => gradeSubsFor(categories, cat).length > 0

/**
 * Competitive Exams is not part of the Study Book list; it lives on its own
 * page (/competitive-exams) so the category never shows up twice - once as a
 * top-nav item and again as a pill inside the Study Book page.
 */
export const COMPETITIVE_EXAMS_NAME = 'Competitive Exams'

/**
 * Main categories surfaced by the Study Book (books list) and Teacher Book
 * pages. The new education levels are listed alongside the Math and Khmer
 * subjects; every other main category is hidden on those pages.
 */
export const VISIBLE_MAIN_NAMES = new Set([
  'Math',
  'Khmer',
  'Primary',
  'Lower Secondary',
  'Upper Secondary School',
  COMPETITIVE_EXAMS_NAME,
])

/**
 * Display order for the Study Book / Teacher Book category rows. The
 * education levels come first, followed by the Khmer and Math subjects as
 * the last two pills (each expands to its Kindergarten + Grade 1-12 rows).
 */
export const STUDY_MAIN_ORDER = [
  'Primary',
  'Lower Secondary',
  'Upper Secondary School',
  COMPETITIVE_EXAMS_NAME,
  'Khmer',
  'Math',
]

/**
 * Which list route owns a main category. Competitive Exams answers with its own
 * page so Study Book links never drop the user into the competition list.
 */
export const booksBasePath = (name) =>
  String(name || '') === COMPETITIVE_EXAMS_NAME ? '/competitive-exams' : '/books'

const studyMainRank = (cat) => {
  const index = STUDY_MAIN_ORDER.indexOf(String(cat?.name || ''))
  return index === -1 ? STUDY_MAIN_ORDER.length : index
}

/**
 * Category filter for the Study Book / Teacher Book lists. Keeps the visible
 * main categories plus their grade sub-categories (e.g. Math, "Math -
 * Grade 1") and drops every other main category. Competitive Exams (and its
 * grade subs) is included only for its own page, via
 * `includeCompetitive: true`.
 */
export const visibleCategoriesForStudy = (categories = [], { includeCompetitive = false } = {}) => {
  const mains = []
  const subs = []

  for (const cat of categories) {
    const name = isGradeSub(cat) ? baseNameOf(cat) : String(cat?.name || '')
    if (!VISIBLE_MAIN_NAMES.has(name)) continue
    if (!includeCompetitive && name === COMPETITIVE_EXAMS_NAME) continue
    if (isGradeSub(cat)) subs.push(cat)
    else mains.push(cat)
  }

  mains.sort((a, b) => studyMainRank(a) - studyMainRank(b))
  subs.sort((a, b) => parseGradeName(a) - parseGradeName(b))

  return [...mains, ...subs]
}

export const isMainCategory = (cat) => !isGradeSub(cat)

/**
 * Where a category card should lead. Subjects that have graded
 * sub-categories (e.g. Math) go to the books view so all the grade
 * books and the grade filter row are shown; the rest keep their page.
 */
export const categoryLink = (categories = [], cat) => {
  if (!cat) return '/categories'
  return hasGradeSubs(categories, cat)
    ? `${booksBasePath(cat.name)}?category=${slugOfName(cat.name)}`
    : `/categories/${cat.id}`
}

export function resolveCategoryParam(allCategories, category, grade) {
  if (!category) return ''
  const key = String(category).toLowerCase()
  const base = allCategories.find(
    (cat) =>
      !isGradeSub(cat) &&
      (String(cat.id) === String(category) ||
        String(cat.slug).toLowerCase() === key ||
        slugOfName(cat.name) === key)
  )
  if (!base) return String(category)
  const levels = allCategories.filter((cat) => isGradeSub(cat) && baseNameOf(cat) === base.name)
  if (levels.length === 0) return String(base.id)
  if (grade === undefined || grade === null || grade === '') {
    return [String(base.id), ...levels.map((cat) => String(cat.id))].join(',')
  }
  const num = Number(grade)
  const selected = levels.find((cat) => parseGradeName(cat) === num)
  if (!selected) return [String(base.id), ...levels.map((cat) => String(cat.id))].join(',')
  return String(selected.id)
}