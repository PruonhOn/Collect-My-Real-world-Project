export default function StudentIllustration() {
  return (
    <svg viewBox="0 0 400 400" className="w-full h-auto" role="img" aria-label="Student reading a book" fill="none">
      <style>
        {`
          @keyframes si-float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
          }
          @keyframes si-float-slow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-4px); }
          }
          @keyframes si-bob {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
          }
          @keyframes si-blink {
            0%, 92%, 100% { transform: scaleY(1); }
            94%, 98% { transform: scaleY(0.1); }
          }
          @keyframes si-page-flip {
            0%, 100% { transform: rotateY(0deg); }
            50% { transform: rotateY(-6deg); }
          }
          @keyframes si-sparkle {
            0%, 100% { opacity: 0.4; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.1); }
          }
          .si-float { animation: si-float 4s ease-in-out infinite; }
          .si-float-slow { animation: si-float-slow 5s ease-in-out infinite; }
          .si-bob { animation: si-bob 3.6s ease-in-out infinite; }
          .si-blink { animation: si-blink 5.2s ease-in-out infinite; transform-origin: center; }
          .si-page-flip { animation: si-page-flip 3.8s ease-in-out infinite; transform-origin: right center; }
          .si-sparkle { animation: si-sparkle 2.8s ease-in-out infinite; }
          .si-sparkle-delay { animation: si-sparkle 2.8s ease-in-out infinite 1s; }
        `}
      </style>
      <defs>
        <linearGradient id="siShirt" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#a78bfa" />
          <stop offset="100%" stopColor="#6366f1" />
        </linearGradient>
        <linearGradient id="siShirtLt" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#c4b5fd" />
          <stop offset="100%" stopColor="#818cf8" />
        </linearGradient>
        <linearGradient id="siHair" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#3b3a6e" />
          <stop offset="100%" stopColor="#25234d" />
        </linearGradient>
        <linearGradient id="siGlow" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#ffffff" />
          <stop offset="100%" stopColor="#a5b4fc" />
        </linearGradient>
      </defs>

      <g className="si-float-slow">
        <circle cx="330" cy="78" r="96" fill="url(#siGlow)" opacity="0.28" />
      </g>
      <g className="si-float">
        <circle cx="58" cy="96" r="54" fill="url(#siGlow)" opacity="0.2" />
      </g>

      <g stroke="#ffffff" strokeWidth="3" strokeLinecap="round" opacity="0.7">
        <path d="M64 262v22M53 273h22" className="si-sparkle" />
        <path d="M338 196v20M328 206h20" className="si-sparkle-delay" />
        <path d="M46 96v14M39 103h14" className="si-sparkle" />
        <path d="M360 266v16M352 274h16" className="si-sparkle-delay" />
        <path d="M120 208v12M114 214h12" className="si-sparkle" />
      </g>

      <g className="si-float">
        <rect x="286" y="330" width="56" height="9" rx="4.5" fill="#f472b6" />
        <rect x="280" y="320" width="62" height="9" rx="4.5" fill="#60a5fa" />
        <rect x="274" y="310" width="68" height="9" rx="4.5" fill="#fbbf24" />
        <rect x="274" y="300" width="68" height="9" rx="4.5" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="2" />
      </g>

      <g transform="rotate(-18 96 128)" className="si-bob">
        <rect x="76" y="118" width="44" height="30" rx="6" fill="#f472b6" />
        <rect x="82" y="124" width="32" height="18" rx="3" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="2" />
      </g>

      <ellipse cx="204" cy="376" rx="118" ry="14" fill="#000000" opacity="0.08" />

      <g>
        <polygon points="200,64 138,86 200,108 262,86" fill="#25234d" />
        <rect x="170" y="84" width="60" height="30" rx="15" fill="url(#siHair)" />
        <path d="M200 66c26-4 52 4 60 20l-14-2h-7l-8-8-10 8h-42l-8-8-10 8h-7l-14 2c8-16 34-24 60-20z" fill="#25234d" />
        <path d="M222 62c22-5 38 6 44 20l-6-4-8 2-8-6-8 6z" fill="#3b3a6e" />
        <circle cx="214" cy="78" r="4" fill="#c4b5fd" />
        <path d="M214 51v-6M211 48h6" stroke="#f8fafc" strokeWidth="3" strokeLinecap="round" opacity="0.85" />
      </g>

      <g className="si-bob">
        <circle cx="164" cy="152" r="8" fill="#ffd6ae" />
        <circle cx="236" cy="152" r="8" fill="#ffd6ae" />
        <circle cx="200" cy="150" r="40" fill="#ffd6ae" />
        <path d="M162 132a40 40 0 0 1 76 0c-10-8-24-6-30 2-4 5-12 5-16 0-6-8-20-10-30-2z" fill="url(#siHair)" />
        <g className="si-blink">
          <circle cx="187" cy="154" r="4.5" fill="#2d2a5e" />
          <circle cx="213" cy="154" r="4.5" fill="#2d2a5e" />
          <circle cx="188.6" cy="152.6" r="1.6" fill="#ffffff" />
          <circle cx="214.6" cy="152.6" r="1.6" fill="#ffffff" />
        </g>
        <path d="M192 168 q8 8 16 0" stroke="#2d2a5e" strokeWidth="3.5" strokeLinecap="round" fill="none" />
      </g>

      <g className="si-float">
        <rect x="126" y="48" width="30" height="30" rx="6" transform="rotate(12 141 63)" fill="#34d399" opacity="0.9" />
        <circle cx="152" cy="46" r="5" fill="#facc15" className="si-sparkle" />
        <circle cx="122" cy="34" r="4.5" fill="#f472b6" className="si-sparkle-delay" />
      </g>

      <g className="si-bob">
        <path
          d="M136 246c-14-16 2-44 34-44s48 28 34 44c-12 14-56 14-68 0z"
          fill="url(#siShirt)"
        />
        <path d="M178 212l-8 26 16-14 12 14-8-26z" fill="url(#siShirtLt)" />
        <path
          d="M118 232c-6 16 4 34 18 34h38c-16-2-24-18-18-34-20 0-34-8-38 0z"
          fill="url(#siShirtLt)"
        />
        <path
          d="M282 232c6 16-4 34-18 34h-38c16-2 24-18 18-34 20 0 34-8 38 0z"
          fill="url(#siShirtLt)"
        />
        <path d="M200 200c24 0 42 14 42 34" stroke="#312e81" strokeWidth="10" strokeLinecap="round" />
        <path d="M200 200c-24 0-42 14-42 34" stroke="#312e81" strokeWidth="10" strokeLinecap="round" />

        <path d="M158 236q-14 40 6 60l28-1c-12-16-8-44-34-59z" fill="url(#siShirt)" />
        <path d="M242 236q14 40-6 60l-28-1c12-16 8-44 34-59z" fill="url(#siShirt)" />
      </g>

      <g className="si-float-slow">
        <path d="M200 310l-2 74 34-6-8-58z" fill="#fff7ed" stroke="#e2e8f0" strokeWidth="3" strokeLinejoin="round" />
        <path d="M202 348l10-34 24-6-8 38z" fill="#fed7aa" opacity="0.85" />
        <path d="M200 310l68-22-8 56-34 34-26-14z" fill="#fef3c7" stroke="#e2e8f0" strokeWidth="3" strokeLinejoin="round" className="si-page-flip" />
        <path d="M252 310l16 40-24 10-18-38z" fill="#fdba74" opacity="0.85" />
        <path d="M200 310l-68-22 8 56 34 34 26-14z" fill="#ffffff" stroke="#e2e8f0" strokeWidth="3" strokeLinejoin="round" />
        <path d="M148 310l-16 40 24 10 18-38-26-12z" fill="#fcd34d" opacity="0.8" />
        <path d="M206 380l24 2" stroke="#e2e8f0" strokeWidth="3" strokeLinecap="round" />
      </g>

      <g>
        <rect x="126" y="356" width="26" height="14" rx="7" fill="#312e81" />
        <rect x="118" y="362" width="12" height="8" rx="4" fill="#25234d" />
        <rect x="248" y="356" width="26" height="14" rx="7" fill="#312e81" />
        <rect x="270" y="362" width="12" height="8" rx="4" fill="#25234d" />
      </g>
    </svg>
  )
}