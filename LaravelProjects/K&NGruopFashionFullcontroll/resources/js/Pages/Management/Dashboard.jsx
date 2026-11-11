import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Link } from '@inertiajs/react';
import axios from 'axios';

export default function Dashboard() {
    const [stats, setStats] = useState({ products: 0, categories: 0, users: 0, contacts: 0 });
    const [recentProducts, setRecentProducts] = useState([]);

    useEffect(() => {
        axios.get('/api/admin/products?per_page=5').then(r => { setStats(s => ({...s, products: r.data.total})); setRecentProducts(r.data.data); }).catch(() => {});
        axios.get('/api/categories').then(r => setStats(s => ({...s, categories: r.data.length}))).catch(() => {});
        axios.get('/api/admin/users').then(r => setStats(s => ({...s, users: r.data.total}))).catch(() => {});
        axios.get('/api/admin/contacts').then(r => setStats(s => ({...s, contacts: r.data.total}))).catch(() => {});
    }, []);

    const statCards = [
        { name: 'Products', value: stats.products, href: route('management.products'), color: 'bg-indigo-500', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
        { name: 'Categories', value: stats.categories, href: route('management.categories'), color: 'bg-emerald-500', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
        { name: 'Users', value: stats.users, href: route('management.users'), color: 'bg-amber-500', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
        { name: 'Messages', value: stats.contacts, href: route('management.contacts'), color: 'bg-rose-500', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    ];

    return (
        <AdminLayout>
            <h1 className="text-2xl font-bold mb-6">K & N Management Dashboard</h1>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {statCards.map(card => (
                    <Link key={card.name} href={card.href} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{card.name}</p>
                                <p className="text-3xl font-bold mt-1">{card.value}</p>
                            </div>
                            <div className={`p-3 rounded-lg ${card.color}`}>
                                <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={card.icon} /></svg>
                            </div>
                        </div>
                    </Link>
                ))}
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div className="flex justify-between items-center mb-4">
                    <h2 className="text-lg font-semibold">Recent Products</h2>
                    <Link href={route('management.products')} className="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</Link>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-gray-200 dark:border-gray-700">
                                <th className="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Name</th>
                                <th className="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Price</th>
                                <th className="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th className="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Featured</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentProducts.map(p => (
                                <tr key={p.id} className="border-b border-gray-100 dark:border-gray-700/50">
                                    <td className="py-3 px-2">{p.name}</td>
                                    <td className="py-3 px-2">${parseFloat(p.price).toFixed(2)}</td>
                                    <td className="py-3 px-2">
                                        <span className={`px-2 py-0.5 rounded-full text-xs ${p.status ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'}`}>
                                            {p.status ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="py-3 px-2">
                                        {p.featured ? <span className="text-yellow-500">★</span> : <span className="text-gray-300 dark:text-gray-600">☆</span>}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
