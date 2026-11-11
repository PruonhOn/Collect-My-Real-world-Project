export default function ApplicationLogo({ className = 'h-9 w-auto' }) {
    return (
        <svg className={className} viewBox="0 0 200 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="60" rx="8" fill="currentColor" className="text-black dark:text-white" opacity="0.1" />
            <text x="12" y="38" fontFamily="Georgia, serif" fontSize="22" fontWeight="bold" fill="currentColor" className="text-black dark:text-white">
                K&amp;N
            </text>
            <text x="75" y="28" fontFamily="Arial, sans-serif" fontSize="9" fill="currentColor" className="text-black dark:text-white" opacity="0.7">
                COLLECTION
            </text>
            <text x="75" y="42" fontFamily="Arial, sans-serif" fontSize="7" fill="currentColor" className="text-black dark:text-white" opacity="0.5">
                FASHION
            </text>
            <line x1="68" y1="15" x2="68" y2="48" stroke="currentColor" className="text-black dark:text-white" strokeWidth="1.5" opacity="0.3" />
        </svg>
    );
}
