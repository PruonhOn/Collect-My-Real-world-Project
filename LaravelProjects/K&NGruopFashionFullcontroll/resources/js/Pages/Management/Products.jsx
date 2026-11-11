import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Link, router } from '@inertiajs/react';
import axios from 'axios';

export default function Products() {
    const [products, setProducts] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);

    const loadProducts = (url = '/api/admin/products') => {
        setLoading(true);
        axios.get(url).then(r => { setProducts(r.data.data); setMeta(r.data); }).catch(() => {}).finally(() => setLoading(false));
    };

    useEffect(() => { loadProducts(); }, []);

    const toggleStatus = (product) => {
        axios.patch(`/api/admin/products/${product.id}/toggle-status`).then(() => loadProducts()).catch(() => {});
    };

    const deleteProduct = (product) => {
        if (confirm(`Delete "${product.name}"?`)) {
            axios.delete(`/api/admin/products/${product.id}`).then(() => loadProducts()).catch(() => {});
        }
    };

    return (
        <AdminLayout>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Products Management</h1>
                <Link href={route('management.products.create')} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors inline-flex items-center">
                    <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
                    Add Product
                </Link>
            </div>

            {loading ? (
                <div className="text-center py-12 text-gray-500 dark:text-gray-400">Loading...</div>
            ) : (
                <>
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Image</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Name</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Category</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Price</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Stock</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Status</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Featured</th>
                                        <th className="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {products.map(p => (
                                        <tr key={p.id} className="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td className="py-3 px-4">
                                                {p.image ? (
                                                    <img src={`/storage/${p.image}`} alt="" className="w-10 h-10 rounded object-cover" />
                                                ) : (
                                                    <div className="w-10 h-10 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-400">
                                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    </div>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 font-medium">{p.name}</td>
                                            <td className="py-3 px-4 text-gray-500 dark:text-gray-400">{p.category?.name || '-'}</td>
                                            <td className="py-3 px-4">${parseFloat(p.price).toFixed(2)}</td>
                                            <td className="py-3 px-4">{p.stock}</td>
                                            <td className="py-3 px-4">
                                                <button onClick={() => toggleStatus(p)} className={`px-2 py-0.5 rounded-full text-xs font-medium ${p.status ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'}`}>
                                                    {p.status ? 'Active' : 'Inactive'}
                                                </button>
                                            </td>
                                            <td className="py-3 px-4">{p.featured ? <span className="text-yellow-500">★</span> : <span className="text-gray-300 dark:text-gray-600">☆</span>}</td>
                                            <td className="py-3 px-4 text-right">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <Link href={`/management/products/${p.id}/edit`} className="p-1.5 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                    </Link>
                                                    <button onClick={() => deleteProduct(p)} className="p-1.5 text-gray-500 hover:text-red-600 dark:hover:text-red-400">
                                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {meta && meta.last_page > 1 && (
                        <div className="flex justify-center space-x-2 mt-6">
                            {Array.from({length: meta.last_page}, (_, i) => i + 1).map(page => (
                                <button key={page} onClick={() => loadProducts(`/api/admin/products?page=${page}`)} className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${meta.current_page === page ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'}`}>{page}</button>
                            ))}
                        </div>
                    )}
                </>
            )}
        </AdminLayout>
    );
}
