import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';

export default function PublicLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors">
            <nav className="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center h-16">
                        <Link href="/" className="flex items-center space-x-2">
                            <ApplicationLogo />
                        </Link>
                        <div className="flex items-center space-x-6">
                            <Link href="/" className="text-sm font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Home</Link>
                            <Link href={route('products')} className="text-sm font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Products</Link>
                            <Link href={route('about')} className="text-sm font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">About</Link>
                            <Link href={route('contact')} className="text-sm font-medium hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Contact</Link>
                            <ThemeToggle />
                            {auth.user ? (
                                <Link href={route('management.dashboard')} className="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                    Management
                                </Link>
                            ) : (
                                <Link href={route('login')} className="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                    Login
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </nav>
            <main>{children}</main>
            <footer className="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-16">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <ApplicationLogo className="h-10 w-auto mb-4" />
                            <p className="text-sm text-gray-600 dark:text-gray-400">Your premium fashion destination. Quality clothing and accessories for every style.</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-semibold uppercase tracking-wider mb-4">Quick Links</h3>
                            <ul className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <li><Link href="/" className="hover:text-indigo-600 dark:hover:text-indigo-400">Home</Link></li>
                                <li><Link href={route('products')} className="hover:text-indigo-600 dark:hover:text-indigo-400">Products</Link></li>
                                <li><Link href={route('about')} className="hover:text-indigo-600 dark:hover:text-indigo-400">About Us</Link></li>
                                <li><Link href={route('contact')} className="hover:text-indigo-600 dark:hover:text-indigo-400">Contact</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="text-sm font-semibold uppercase tracking-wider mb-4">Contact Info</h3>
                            <ul className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <li>Email: onpruonh123@gmail.com</li>
                                <li>Phone: 0967594210</li>
                                <li>&copy; {new Date().getFullYear()} K&N Collection. All rights reserved.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
