import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';

export default function SystemInfo() {
    const [info, setInfo] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        axios.get('/api/admin/system-info').then(r => setInfo(r.data)).catch(() => {}).finally(() => setLoading(false));
    }, []);

    const sections = info ? [
        { title: 'Application', data: info.app },
        { title: 'Server', data: info.server },
        { title: 'System', data: info.system },
        { title: 'Storage', data: info.storage },
    ] : [];

    return (
        <AdminLayout>
            <div className="max-w-4xl mx-auto">
                <h1 className="text-2xl font-bold mb-6">System Information</h1>

                {loading ? (
                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">Loading...</div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {sections.map(section => (
                            <div key={section.title} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                <h2 className="text-lg font-semibold mb-4">{section.title}</h2>
                                <dl className="space-y-3">
                                    {Object.entries(section.data).map(([key, value]) => (
                                        <div key={key} className="flex justify-between items-center">
                                            <dt className="text-sm text-gray-500 dark:text-gray-400 capitalize">{key.replace(/_/g, ' ')}</dt>
                                            <dd className="text-sm font-mono text-gray-900 dark:text-gray-100 ml-4 text-right">
                                                {String(value)}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
