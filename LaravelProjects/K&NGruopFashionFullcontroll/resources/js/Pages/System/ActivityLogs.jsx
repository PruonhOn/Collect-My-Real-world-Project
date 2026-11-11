import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';

export default function ActivityLogs() {
    const [logs, setLogs] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);

    const loadLogs = (url = '/api/admin/activity-logs') => {
        setLoading(true);
        axios.get(url).then(r => { setLogs(r.data.data); setMeta(r.data); }).catch(() => {}).finally(() => setLoading(false));
    };

    useEffect(() => { loadLogs(); }, []);

    const clearLogs = () => {
        if (confirm('Clear all activity logs?')) {
            axios.delete('/api/admin/activity-logs').then(() => loadLogs()).catch(() => {});
        }
    };

    return (
        <AdminLayout>
            <div className="max-w-5xl mx-auto">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Activity Logs</h1>
                    <button onClick={clearLogs} className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                        Clear All Logs
                    </button>
                </div>

                {loading ? (
                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">Loading...</div>
                ) : logs.length === 0 ? (
                    <div className="text-center py-12 text-gray-500 dark:text-gray-400">No activity logs yet.</div>
                ) : (
                    <>
                        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Time</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">User</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Action</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Description</th>
                                        <th className="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.map(log => (
                                        <tr key={log.id} className="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td className="py-3 px-4 text-gray-500 dark:text-gray-400 whitespace-nowrap">{new Date(log.created_at).toLocaleString()}</td>
                                            <td className="py-3 px-4">{log.user?.name || 'System'}</td>
                                            <td className="py-3 px-4">
                                                <span className="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-mono">{log.action}</span>
                                            </td>
                                            <td className="py-3 px-4 text-gray-600 dark:text-gray-400">{log.description || '-'}</td>
                                            <td className="py-3 px-4 text-gray-500 dark:text-gray-400 font-mono text-xs">{log.ip_address || '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {meta && meta.last_page > 1 && (
                            <div className="flex justify-center space-x-2 mt-6">
                                {Array.from({length: meta.last_page}, (_, i) => i + 1).map(page => (
                                    <button key={page} onClick={() => loadLogs(`/api/admin/activity-logs?page=${page}`)} className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${meta.current_page === page ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'}`}>{page}</button>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
