import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import axios from 'axios';

export default function ProductDetail({ slug }) {
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        axios.get(`/api/products/${slug}`)
            .then(r => setProduct(r.data))
            .catch(() => setProduct(null))
            .finally(() => setLoading(false));
    }, [slug]);

    if (loading) {
        return (
            <PublicLayout>
                <div className="max-w-4xl mx-auto px-4 py-16">
                    <div className="animate-pulse grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div className="aspect-[3/4] bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
                        <div className="space-y-4">
                            <div className="h-8 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                            <div className="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                            <div className="h-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        </div>
                    </div>
                </div>
            </PublicLayout>
        );
    }

    if (!product) {
        return (
            <PublicLayout>
                <div className="max-w-4xl mx-auto px-4 py-16 text-center">
                    <p className="text-gray-500 dark:text-gray-400">Product not found.</p>
                    <Link href={route('products')} className="text-indigo-600 hover:underline mt-4 inline-block">Back to products</Link>
                </div>
            </PublicLayout>
        );
    }

    return (
        <PublicLayout>
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Link href={route('products')} className="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-6 inline-block">&larr; Back to Products</Link>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div className="aspect-[3/4] bg-gray-100 dark:bg-gray-800 rounded-xl overflow-hidden">
                        {product.image ? (
                            <img src={`/storage/${product.image}`} alt={product.name} className="w-full h-full object-cover" />
                        ) : (
                            <div className="flex items-center justify-center h-full text-gray-400">
                                <svg className="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                        )}
                    </div>
                    <div>
                        {product.category && <p className="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{product.category.name}</p>}
                        <h1 className="text-3xl font-bold mb-2">{product.name}</h1>
                        <p className="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-4">${parseFloat(product.price).toFixed(2)}</p>
                        {product.size && (
                            <div className="mb-4">
                                <span className="text-sm font-medium">Size: </span>
                                <span className="text-sm text-gray-600 dark:text-gray-400">{product.size}</span>
                            </div>
                        )}
                        {product.color && (
                            <div className="mb-4">
                                <span className="text-sm font-medium">Color: </span>
                                <span className="text-sm text-gray-600 dark:text-gray-400">{product.color}</span>
                            </div>
                        )}
                        {product.stock > 0 ? (
                            <p className="text-sm text-green-600 dark:text-green-400 mb-4">In Stock ({product.stock} available)</p>
                        ) : (
                            <p className="text-sm text-red-600 dark:text-red-400 mb-4">Out of Stock</p>
                        )}
                        <div className="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <h3 className="font-semibold mb-2">Description</h3>
                            <p className="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{product.description || 'No description available.'}</p>
                        </div>
                        {product.notes && (
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                <h3 className="font-semibold mb-2">Additional Notes</h3>
                                <p className="text-gray-600 dark:text-gray-400">{product.notes}</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
