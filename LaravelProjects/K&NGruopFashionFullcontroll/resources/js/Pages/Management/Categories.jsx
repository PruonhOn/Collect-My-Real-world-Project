import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';

export default function Categories() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editId, setEditId] = useState(null);
    const [form, setForm] = useState({ name: '', description: '' });

    const loadCategories = () => {
        axios.get('/api/categories').then(r => setCategories(r.data)).catch(() => {}).finally(() => setLoading(false));
    };

    useEffect(() => { loadCategories(); }, []);

    const resetForm = () => { setForm({ name: '', description: '' }); setEditId(null); setShowForm(false); };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editId) {
                await axios.put(`/api/admin/categories/${editId}`, form);
            } else {
                await axios.post('/api/admin/categories', form);
            }
            resetForm();
            loadCategories();
        } catch (err) { alert('Failed to save category'); }
    };

    const editCategory = (cat) => { setForm({ name: cat.name, description: cat.description || '' }); setEditId(cat.id); setShowForm(true); };

    const deleteCategory = async (cat) => {
        if (confirm(`Delete "${cat.name}"?`)) {
            try { await axios.delete(`/api/admin/categories/${cat.id}`); loadCategories(); } catch (err) { alert('Failed to delete'); }
        }
    };

    return (
        <AdminLayout>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Categories</h1>
                <button onClick={() => { resetForm(); setShowForm(true); }} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Add Category</button>
            </div>

            {showForm && (
                <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="text" value={form.name} onChange={e => setForm({...form, name: e.target.value})} placeholder="Category name" required className="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        <input type="text" value={form.description} onChange={e => setForm({...form, description: e.target.value})} placeholder="Description (optional)" className="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        <div className="flex space-x-2">
                            <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">{editId ? 'Update' : 'Create'}</button>
                            <button type="button" onClick={resetForm} className="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-500">Cancel</button>
                        </div>
                    </div>
                </form>
            )}

            {loading ? (
                <div className="text-center py-12 text-gray-500">Loading...</div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {categories.map(cat => (
                        <div key={cat.id} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="font-semibold">{cat.name}</h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">{cat.description || 'No description'}</p>
                                    <p className="text-xs text-gray-400 mt-1">{cat.products_count} products</p>
                                </div>
                                <div className="flex space-x-1">
                                    <button onClick={() => editCategory(cat)} className="p-1.5 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button onClick={() => deleteCategory(cat)} className="p-1.5 text-gray-500 hover:text-red-600 dark:hover:text-red-400">
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
