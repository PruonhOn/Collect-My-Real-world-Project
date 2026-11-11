const socialLinks = [
  {
    label: 'Email',
    value: 'onpruonh123@gmail.com',
    href: 'mailto:onpruonh123@gmail.com',
    icon: (
      <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M3 8l7.9 5.26a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
    ),
  },
  {
    label: 'Telegram',
    value: '@PruonhOn',
    href: 'https://t.me/PruonhOn',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M21.93 4.62a1.5 1.5 0 00-2.04-.68L2.86 11.53a1.5 1.5 0 00.13 2.75l4.4 1.58 1.71 5.13a1.5 1.5 0 002.44.6l2.42-2.21 4.27 3.12a1.5 1.5 0 002.37-.92l3.33-15.96zM8.98 14.9l.65-3.24 6.79-6.15-7.71 7.35 2.19 3.82-.39 4.32-1.53-6.1z" />
      </svg>
    ),
  },
  {
    label: 'Facebook',
    value: 'Pi-Education Cambodia',
    href: 'https://www.facebook.com/profile.php?id=100075216953022',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M22 12a10 10 0 10-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0022 12z" />
      </svg>
    ),
  },
  {
    label: 'TikTok',
    value: '@darkness29293',
    href: 'https://www.tiktok.com/@darkness29293?_r=1&_t=ZS-99ZqqOWoNka',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1.04-.1z" />
      </svg>
    ),
  },
]

export default function Footer() {
  return (
    <footer className="bg-gray-900 text-gray-300 mt-auto">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div>
            <div className="flex items-center gap-2 mb-3">
              <img src="/pi-logo.jpg" alt="Pi-Education logo" className="w-12 h-12 rounded-xl object-cover" />
              <span className="font-bold text-white text-lg">Pi Education Cambodia</span>
            </div>
            <p className="text-sm text-gray-400">Digital Books for Every Learner. Learn anywhere, anytime.</p>
          </div>
          <div>
            <h4 className="text-white font-semibold mb-3">Quick Links</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/books" className="hover:text-white">Books</a></li>
              <li><a href="/categories" className="hover:text-white">Categories</a></li>
              <li><a href="/authors" className="hover:text-white">Authors</a></li>
              <li><a href="/about" className="hover:text-white">About Us</a></li>
              <li><a href="/contact" className="hover:text-white">Contact</a></li>
            </ul>
          </div>
          <div>
            <h4 className="text-white font-semibold mb-3">Contact</h4>
            <ul className="space-y-2 text-sm text-gray-400">
              {socialLinks.map((link) => (
                <li key={link.label}>
                  <a
                    href={link.href}
                    target={link.href.startsWith('mailto') ? undefined : '_blank'}
                    rel={link.href.startsWith('mailto') ? undefined : 'noreferrer'}
                    className="inline-flex items-center gap-2 hover:text-white"
                  >
                    <span className="text-indigo-400">{link.icon}</span>
                    {link.label}: {link.value}
                  </a>
                </li>
              ))}
            </ul>
          </div>
        </div>
        <div className="mt-8 pt-6 border-t border-gray-800 text-center text-sm text-gray-500">
          © {new Date().getFullYear()} Pi-Education Cambodia. All rights reserved.
        </div>
      </div>
    </footer>
  )
}