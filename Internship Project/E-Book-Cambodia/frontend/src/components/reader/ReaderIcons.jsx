const base = {
  width: 20,
  height: 20,
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 1.9,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
  'aria-hidden': true,
}

const Icon = ({ children, size = 20, ...rest }) => (
  <svg {...base} width={size} height={size} {...rest}>
    {children}
  </svg>
)

export const CloseIcon = (props) => (
  <Icon {...props}>
    <path d="M6 6l12 12M18 6L6 18" />
  </Icon>
)

export const TocIcon = (props) => (
  <Icon {...props}>
    <path d="M4 6h10M4 12h10M4 18h7" />
    <path d="M18 6h2M18 12h2M18 18h2" />
  </Icon>
)

export const SearchIcon = (props) => (
  <Icon {...props}>
    <circle cx="11" cy="11" r="7" />
    <path d="M20.5 20.5L16 16" />
  </Icon>
)

export const NoteIcon = (props) => (
  <Icon {...props}>
    <path d="M5 4h11l3 3v13H5z" />
    <path d="M9 10h7M9 14h5" />
  </Icon>
)

export const SettingsIcon = (props) => (
  <Icon {...props}>
    <circle cx="12" cy="12" r="3" />
    <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21h-4v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1z" />
  </Icon>
)

export const ExpandIcon = (props) => (
  <Icon {...props}>
    <path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5" />
  </Icon>
)

export const CompressIcon = (props) => (
  <Icon {...props}>
    <path d="M3 8h5V3M21 8h-5V3M16 21v-5h5M8 21v-5H3" />
  </Icon>
)

export const ChevronLeftIcon = (props) => (
  <Icon {...props}>
    <path d="M15 5l-7 7 7 7" />
  </Icon>
)

export const ChevronRightIcon = (props) => (
  <Icon {...props}>
    <path d="M9 5l7 7-7 7" />
  </Icon>
)

export const ZoomInIcon = (props) => (
  <Icon {...props}>
    <circle cx="11" cy="11" r="7" />
    <path d="M11 8v6M8 11h6M20.5 20.5L16 16" />
  </Icon>
)

export const ZoomOutIcon = (props) => (
  <Icon {...props}>
    <circle cx="11" cy="11" r="7" />
    <path d="M8 11h6M20.5 20.5L16 16" />
  </Icon>
)

export const SparkIcon = (props) => (
  <Icon {...props}>
    <path d="M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8z" />
    <path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9z" />
  </Icon>
)

export const HelpIcon = (props) => (
  <Icon {...props}>
    <circle cx="12" cy="12" r="9" />
    <path d="M9.5 9a2.5 2.5 0 1 1 3.6 2.3c-.7.4-1.1 1-1.1 1.8v.4" />
    <path d="M12 17.3h.01" />
  </Icon>
)

export const BookmarkIcon = (props) => (
  <Icon {...props}>
    <path d="M6 4h12v17l-6-4-6 4z" />
  </Icon>
)

export const ScanIcon = (props) => (
  <Icon {...props}>
    <path d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4" />
    <path d="M8 12h8" />
  </Icon>
)

export const CopyIcon = (props) => (
  <Icon {...props}>
    <rect x="9" y="9" width="11" height="11" rx="2" />
    <path d="M15 5H6a2 2 0 0 0-2 2v9" />
  </Icon>
)

export const TrashIcon = (props) => (
  <Icon {...props}>
    <path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13" />
    <path d="M10 11v5M14 11v5" />
  </Icon>
)

export const CheckIcon = (props) => (
  <Icon {...props}>
    <path d="M4 12.5l5 5L20 6.5" />
  </Icon>
)

export const SunIcon = (props) => (
  <Icon {...props}>
    <circle cx="12" cy="12" r="4" />
    <path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19" />
  </Icon>
)

export const MoonIcon = (props) => (
  <Icon {...props}>
    <path d="M20 14.5A8 8 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5z" />
  </Icon>
)

export const BookIcon = (props) => (
  <Icon {...props}>
    <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z" />
    <path d="M4 18.5A2.5 2.5 0 0 1 6.5 16H20" />
  </Icon>
)

export const EyeIcon = (props) => (
  <Icon {...props}>
    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z" />
    <circle cx="12" cy="12" r="2.5" />
  </Icon>
)

export const ArrowUpIcon = (props) => (
  <Icon {...props}>
    <path d="M12 20V5M6 11l6-6 6 6" />
  </Icon>
)

export const LockIcon = (props) => (
  <Icon {...props}>
    <rect x="5" y="10" width="14" height="10" rx="2" />
    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
  </Icon>
)

export const LinkIcon = (props) => (
  <Icon {...props}>
    <path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1" />
    <path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1" />
  </Icon>
)
