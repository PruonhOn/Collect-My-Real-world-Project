import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

export default function Users() {
    const { auth } = usePage().props;
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState({ name: '', email: '', password: '', is_admin: false });

    const loadUsers = () => {
        setLoading(true);
        axios.get('/api/admin/users').then(r => setUsers(r.data.data)).catch(() => {}).finally(() => setLoading(false));
    };

    useEffect(() => { loadUsers(); }, []);

    const resetForm = () => { setForm({ name: '', email: '', password: '', is_admin: false }); setShowForm(false); };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await axios.post('/api/admin/users', form);
            resetForm();
            loadUsers();
        } catch (err) { alert('Failed to create user'); }
    };

    const deleteUser = async (user) => {
        if (user.id === auth.user.id) { alert('Cannot delete yourself'); return; }
        if (confirm(`Delete user "${user.name}"?`)) {
            try { await axios.delete(`/api/admin/users/${user.id}`); loadUsers(); } catch (err) { alert('Failed to delete'); }
        }
    };

    return (
        <AdminLayout>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">User Management</h1>
                <button onClick={() => { resetForm(); setShowForm(true); }} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                    <svg className="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    Add User
                </button>
            </div>

            {showForm && (
                <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="text" value={form.name} onChange={e => setForm({...form, name: e.target.value})} placeholder="Name" required className="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        <input type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} placeholder="Email" required className="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        <input type="password" value={form.password} onChange={e => setForm({...form, password: e.target.value})} placeholder="Password" required className="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        <div className="flex items-center space-x-4">
                            <label className="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" checked={form.is_admin} onChange={e => setForm({...form, is_admin: e.target.checked})} className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span className="text-sm">Admin</span>
                            </label>
                            <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Create</button>
                            <button type="button" onClick={resetForm} className="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-500">Cancel</button>
                        </div>
                    </div>
                </form>
            )}

            {loading ? (
                <div className="text-center py-12 text-gray-500">Loading...</div>
            ) : (
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Name</th>
                                <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Email</th>
                                <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Role</th>
                                <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Joined</th>
                                <th className="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.map(u => (
                                <tr key={u.id} className="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td className="py-3 px-4 font-medium">{u.name}</td>
                                    <td className="py-3 px-4 text-gray-500 dark:text-gray-400">{u.email}</td>
                                    <td className="py-3 px-4">
                                        {u.is_admin ? (
                                            <span className="px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">Admin</span>
                                        ) : (
                                            <span className="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">User</span>
                                        )}
                                    </td>
                                    <td className="py-3 px-4 text-gray-500 dark:text-gray-400">{new Date(u.created_at).toLocaleDateString()}</td>
                                    <td className="py-3 px-4 text-right">
                                        {u.id !== auth.user.id && (
                                            <button onClick={() => deleteUser(u)} className="p-1.5 text-gray-500 hover:text-red-600 dark:hover:text-red-400">
                                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminLayout>
    );
}
