import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Products() {
    const [products, setProducts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [meta, setMeta] = useState(null);

    const loadProducts = async (url = '/api/products') => {
        setLoading(true);
        try {
            const res = await axios.get(url);
            setProducts(res.data.data);
            setMeta(res.data);
        } catch (err) {
            setProducts([]);
        }
        setLoading(false);
    };

    useEffect(() => {
        loadProducts();
        axios.get('/api/categories').then(r => setCategories(r.data)).catch(() => {});
    }, []);

    const handleSearch = (e) => {
        e.preventDefault();
        if (search.trim()) {
            loadProducts(`/api/products/search?q=${search}`);
        }
    };

    const filterByCategory = (slug) => {
        setSelectedCategory(slug);
        if (slug) {
            loadProducts(`/api/products/category/${slug}`);
        } else {
            loadProducts('/api/products');
        }
    };

    return (
        <PublicLayout>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 className="text-3xl font-bold mb-8">Our Products</h1>

                <div className="flex flex-col sm:flex-row gap-4 mb-8">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <input type="text" value={search} onChange={e => setSearch(e.target.value)} placeholder="Search products..." className="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all" />
                            <svg className="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                    </form>
                    <div className="flex flex-wrap gap-2">
                        <button onClick={() => filterByCategory('')} className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${!selectedCategory ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'}`}>All</button>
                        {categories.map(cat => (
                            <button key={cat.id} onClick={() => filterByCategory(cat.slug)} className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${selectedCategory === cat.slug ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'}`}>{cat.name}</button>
                        ))}
                    </div>
                </div>

                {loading ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        {[1,2,3,4,5,6,7,8].map(i => (
                            <div key={i} className="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden animate-pulse">
                                <div className="aspect-[3/4] bg-gray-200 dark:bg-gray-700"></div>
                                <div className="p-4 space-y-2">
                                    <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                                    <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : products.length === 0 ? (
                    <div className="text-center py-16">
                        <svg className="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        <p className="text-gray-500 dark:text-gray-400">No products found.</p>
                    </div>
                ) : (
                    <>
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            {products.map(product => (
                                <Link key={product.id} href={`/products/${product.slug}`} className="group rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow bg-white dark:bg-gray-800">
                                    <div className="aspect-[3/4] bg-gray-100 dark:bg-gray-800 relative overflow-hidden">
                                        {product.image ? (
                                            <img src={`/storage/${product.image}`} alt={product.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                        ) : (
                                            <div className="flex items-center justify-center h-full text-gray-400">
                                                <svg className="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            </div>
                                        )}
                                    </div>
                                    <div className="p-4">
                                        <p className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{product.category?.name}</p>
                                        <h3 className="font-semibold group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate">{product.name}</h3>
                                        <p className="text-lg font-bold mt-1">${parseFloat(product.price).toFixed(2)}</p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                        {meta && meta.last_page > 1 && (
                            <div className="flex justify-center space-x-2 mt-8">
                                {Array.from({length: meta.last_page}, (_, i) => i + 1).map(page => (
                                    <button key={page} onClick={() => loadProducts(`/api/products?page=${page}`)} className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${meta.current_page === page ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'}`}>{page}</button>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </PublicLayout>
    );
}
