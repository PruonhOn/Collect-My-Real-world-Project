import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function Welcome() {
    const [featured, setFeatured] = useState([]);
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        axios.get('/api/products/featured').then(r => setFeatured(r.data)).catch(() => {});
        axios.get('/api/categories').then(r => setCategories(r.data)).catch(() => {});
    }, []);

    return (
        <PublicLayout>
            <section className="relative bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
                    <div className="text-center">
                        <h1 className="text-4xl md:text-6xl font-bold mb-6">K & N Collection</h1>
                        <p className="text-lg md:text-xl text-gray-300 mb-8 max-w-2xl mx-auto">Discover the latest trends in fashion. Quality apparel and accessories that define your style.</p>
                        <Link href={route('products')} className="inline-flex items-center px-6 py-3 text-base font-medium rounded-lg bg-white text-gray-900 hover:bg-gray-100 transition-colors">
                            Shop Now
                            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </Link>
                    </div>
                </div>
            </section>

            {categories.length > 0 && (
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <h2 className="text-2xl font-bold mb-8">Categories</h2>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        {categories.map(cat => (
                            <Link key={cat.id} href={`/products?category=${cat.slug}`} className="group p-6 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-400 transition-all text-center">
                                <h3 className="font-semibold group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{cat.name}</h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{cat.products_count} items</p>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {featured.length > 0 && (
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div className="flex justify-between items-center mb-8">
                        <h2 className="text-2xl font-bold">Featured Products</h2>
                        <Link href={route('products')} className="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</Link>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        {featured.map(product => (
                            <Link key={product.id} href={`/products/${product.slug}`} className="group rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
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
                                    <h3 className="font-semibold text-sm group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate">{product.name}</h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">${parseFloat(product.price).toFixed(2)}</p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            <section className="bg-indigo-600 dark:bg-indigo-800 text-white py-16">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-3xl font-bold mb-4">Stay Connected</h2>
                    <p className="text-lg text-indigo-200 mb-8">Follow us for the latest updates and exclusive offers.</p>
                    <div className="flex justify-center space-x-4">
                        <a href="mailto:onpruonh123@gmail.com" className="inline-flex items-center px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition-colors">Email Us</a>
                        <a href="tel:0967594210" className="inline-flex items-center px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition-colors">Call Us</a>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
