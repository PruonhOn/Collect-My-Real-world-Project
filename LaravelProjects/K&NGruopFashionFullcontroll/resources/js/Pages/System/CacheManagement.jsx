import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';

export default function CacheManagement() {
    const [running, setRunning] = useState(null);
    const [results, setResults] = useState(null);

    const cacheActions = [
        { key: 'all', label: 'Clear All Cache', description: 'Config, route, view, and application cache' },
        { key: 'config', label: 'Clear Config Cache', description: 'Clear and reload config files' },
        { key: 'route', label: 'Clear Route Cache', description: 'Clear and regenerate route cache' },
        { key: 'view', label: 'Clear View Cache', description: 'Clear compiled Blade/React views' },
        { key: 'cache', label: 'Clear App Cache', description: 'Clear application cache store' },
        { key: 'optimize', label: 'Optimize Clear', description: 'Run optimize:clear (full optimization reset)' },
    ];

    const handleClear = async (type) => {
        setRunning(type);
        setResults(null);
        try {
            const res = await axios.post('/api/admin/clear-cache', { type });
            setResults(res.data.results || []);
        } catch (err) {
            setResults([{ command: type, status: 'error', output: 'Failed to execute' }]);
        }
        setRunning(null);
    };

    return (
        <AdminLayout>
            <div className="max-w-4xl mx-auto">
                <h1 className="text-2xl font-bold mb-6">Cache Management</h1>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    {cacheActions.map(action => (
                        <button
                            key={action.key}
                            onClick={() => handleClear(action.key)}
                            disabled={running !== null}
                            className={`text-left p-5 rounded-xl border transition-all ${running === action.key ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600'} disabled:opacity-50`}
                        >
                            <h3 className="font-semibold">{running === action.key ? 'Clearing...' : action.label}</h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{action.description}</p>
                        </button>
                    ))}
                </div>

                {results && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 className="text-lg font-semibold mb-4">Results</h2>
                        <div className="space-y-3">
                            {results.map((r, i) => (
                                <div key={i} className={`p-3 rounded-lg text-sm ${r.status === 'ok' ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300'}`}>
                                    <span className="font-medium">{r.command}</span>: {r.status === 'ok' ? 'Completed' : 'Failed'}
                                    {r.output && <pre className="mt-1 text-xs opacity-75 overflow-x-auto">{r.output}</pre>}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
