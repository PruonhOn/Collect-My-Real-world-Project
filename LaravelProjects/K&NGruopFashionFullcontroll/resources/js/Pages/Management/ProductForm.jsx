import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Link, router } from '@inertiajs/react';
import axios from 'axios';

export default function ProductForm({ productId }) {
    const isEdit = !!productId;
    const [form, setForm] = useState({
        name: '', description: '', price: '', category_id: '',
        stock: '0', size: '', color: '', status: true, featured: false, notes: '',
    });
    const [categories, setCategories] = useState([]);
    const [imageFile, setImageFile] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        axios.get('/api/categories').then(r => setCategories(r.data)).catch(() => {});
        if (isEdit) {
            axios.get(`/api/admin/products/${productId}`).then(r => {
                const p = r.data;
                setForm({
                    name: p.name, description: p.description || '', price: p.price,
                    category_id: p.category_id || '', stock: p.stock, size: p.size || '',
                    color: p.color || '', status: p.status, featured: p.featured, notes: p.notes || '',
                });
                if (p.image) setImagePreview(`/storage/${p.image}`);
            }).catch(() => router.visit(route('management.products')));
        }
    }, [productId]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setError('');

        const fd = new FormData();
        fd.append('name', form.name);
        fd.append('description', form.description);
        fd.append('price', form.price);
        fd.append('category_id', form.category_id);
        fd.append('stock', form.stock);
        fd.append('size', form.size);
        fd.append('color', form.color);
        fd.append('notes', form.notes);
        fd.append('status', form.status ? '1' : '0');
        fd.append('featured', form.featured ? '1' : '0');
        if (imageFile) fd.append('image_file', imageFile);

        try {
            if (isEdit) {
                await axios.post(`/api/admin/products/${productId}`, fd, { headers: { 'Content-Type': 'multipart/form-data' }, params: { _method: 'PUT' } });
            } else {
                await axios.post('/api/admin/products', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
            }
            router.visit(route('management.products'));
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to save product');
        }
        setSaving(false);
    };

    return (
        <AdminLayout>
            <div className="max-w-3xl mx-auto">
                <Link href={route('management.products')} className="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-6 inline-block">&larr; Back to Products</Link>
                <h1 className="text-2xl font-bold mb-6">{isEdit ? 'Edit Product' : 'Add New Product'}</h1>

                <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                    {error && <p className="text-sm text-red-600 bg-red-50 dark:bg-red-900/30 p-3 rounded-lg">{error}</p>}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium mb-1">Product Name</label>
                            <input type="text" value={form.name} onChange={e => setForm({...form, name: e.target.value})} required className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" />
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium mb-1">Description</label>
                            <textarea rows={4} value={form.description} onChange={e => setForm({...form, description: e.target.value})} className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"></textarea>
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Price ($)</label>
                            <input type="number" step="0.01" value={form.price} onChange={e => setForm({...form, price: e.target.value})} required className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Category</label>
                            <select value={form.category_id} onChange={e => setForm({...form, category_id: e.target.value})} className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                                <option value="">Select category</option>
                                {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Stock</label>
                            <input type="number" value={form.stock} onChange={e => setForm({...form, stock: e.target.value})} className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Size</label>
                            <input type="text" value={form.size} onChange={e => setForm({...form, size: e.target.value})} placeholder="e.g. S, M, L, XL" className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Color</label>
                            <input type="text" value={form.color} onChange={e => setForm({...form, color: e.target.value})} placeholder="e.g. Red, Blue, Black" className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none" />
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium mb-1">Notes</label>
                            <textarea rows={2} value={form.notes} onChange={e => setForm({...form, notes: e.target.value})} className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"></textarea>
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium mb-1">Product Image</label>
                            <input type="file" accept="image/*" onChange={e => { const f = e.target.files[0]; setImageFile(f); if (f) setImagePreview(URL.createObjectURL(f)); }} className="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100" />
                            {imagePreview && <img src={imagePreview} alt="Preview" className="mt-2 w-32 h-32 object-cover rounded-lg" />}
                        </div>
                        <div className="flex items-center space-x-6">
                            <label className="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" checked={form.status} onChange={e => setForm({...form, status: e.target.checked})} className="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                                <span className="text-sm font-medium">Active</span>
                            </label>
                            <label className="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" checked={form.featured} onChange={e => setForm({...form, featured: e.target.checked})} className="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
                                <span className="text-sm font-medium">Featured</span>
                            </label>
                        </div>
                    </div>

                    <div className="flex items-center space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            {saving ? 'Saving...' : isEdit ? 'Update Product' : 'Create Product'}
                        </button>
                        <Link href={route('management.products')} className="px-6 py-2.5 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Cancel</Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
