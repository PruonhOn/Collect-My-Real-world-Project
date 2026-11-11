import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';

export default function Settings() {
    const [settings, setSettings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState('');

    useEffect(() => {
        axios.get('/api/admin/settings').then(r => setSettings(r.data)).catch(() => {}).finally(() => setLoading(false));
    }, []);

    const updateSetting = (key, value) => {
        setSettings(prev => prev.map(s => s.key === key ? { ...s, value } : s));
    };

    const handleSave = async () => {
        setSaving(true);
        setMessage('');
        try {
            await axios.put('/api/admin/settings', {
                settings: settings.map(s => ({ key: s.key, value: s.value }))
            });
            setMessage('Settings saved successfully');
        } catch (err) {
            setMessage('Failed to save settings');
        }
        setSaving(false);
    };

    const grouped = settings.reduce((acc, s) => {
        (acc[s.group] = acc[s.group] || []).push(s);
        return acc;
    }, {});

    return (
        <AdminLayout>
            <div className="max-w-4xl mx-auto">
                <h1 className="text-2xl font-bold mb-6">System Settings</h1>

                {message && (
                    <div className={`mb-4 p-3 rounded-lg text-sm ${message.includes('success') ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-700'}`}>
                        {message}
                    </div>
                )}

                {loading ? (
                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">Loading...</div>
                ) : (
                    <div className="space-y-8">
                        {Object.entries(grouped).map(([group, items]) => (
                            <div key={group} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                <h2 className="text-lg font-semibold mb-4 capitalize">{group} Settings</h2>
                                <div className="space-y-4">
                                    {items.map(setting => (
                                        <div key={setting.id}>
                                            <label className="block text-sm font-medium mb-1">{setting.label || setting.key}</label>
                                            {setting.type === 'textarea' ? (
                                                <textarea rows={3} value={setting.value || ''} onChange={e => updateSetting(setting.key, e.target.value)} className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                            ) : (
                                                <input type={setting.type || 'text'} value={setting.value || ''} onChange={e => updateSetting(setting.key, e.target.value)} className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                        <div className="flex justify-end">
                            <button onClick={handleSave} disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                {saving ? 'Saving...' : 'Save All Settings'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
