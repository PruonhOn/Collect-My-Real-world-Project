import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';

export default function Maintenance() {
    const [enabled, setEnabled] = useState(false);
    const [secret, setSecret] = useState('');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');

    const handleToggle = async () => {
        setLoading(true);
        setMessage('');
        try {
            const res = await axios.post('/api/admin/maintenance', {
                enabled: !enabled,
                secret: secret || undefined,
            });
            setEnabled(!enabled);
            setMessage(res.data.message);
        } catch (err) {
            setMessage('Failed to toggle maintenance mode');
        }
        setLoading(false);
    };

    return (
        <AdminLayout>
            <div className="max-w-3xl mx-auto">
                <h1 className="text-2xl font-bold mb-6">Maintenance Mode</h1>

                {message && (
                    <div className={`mb-4 p-3 rounded-lg text-sm ${message.includes('enabled') || message.includes('disabled') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700' : 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-700'}`}>
                        {message}
                    </div>
                )}

                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="font-semibold text-lg">Current Status</h2>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {enabled
                                    ? 'Your site is currently in maintenance mode. Only users with the secret key can access it.'
                                    : 'Your site is publicly accessible.'}
                            </p>
                        </div>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${enabled ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'}`}>
                            {enabled ? 'Down' : 'Live'}
                        </span>
                    </div>

                    {enabled && (
                        <div>
                            <label className="block text-sm font-medium mb-1">Bypass Secret (optional)</label>
                            <input type="text" value={secret} onChange={e => setSecret(e.target.value)} placeholder="e.g. bypass-secret-key" className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Set a secret key to access the site while in maintenance mode. Visit /{secret} to bypass.</p>
                        </div>
                    )}

                    <button onClick={handleToggle} disabled={loading} className={`px-6 py-2.5 rounded-lg font-medium text-white transition-colors disabled:opacity-50 ${enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}`}>
                        {loading ? 'Processing...' : enabled ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode'}
                    </button>
                </div>

                <div className="mt-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4">
                    <h3 className="font-semibold text-amber-800 dark:text-amber-300 text-sm">Warning</h3>
                    <p className="text-sm text-amber-700 dark:text-amber-400 mt-1">Enabling maintenance mode will make your entire site unavailable to visitors. Make sure you have a bypass secret to access the site for testing.</p>
                </div>
            </div>
        </AdminLayout>
    );
}
