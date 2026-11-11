import { useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';

export default function Contacts() {
    const [messages, setMessages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selected, setSelected] = useState(null);

    const loadMessages = () => {
        setLoading(true);
        axios.get('/api/admin/contacts').then(r => setMessages(r.data.data)).catch(() => {}).finally(() => setLoading(false));
    };

    useEffect(() => { loadMessages(); }, []);

    const viewMessage = async (msg) => {
        setSelected(msg);
        if (!msg.read) {
            try { await axios.get(`/api/admin/contacts/${msg.id}`); loadMessages(); } catch (err) {}
        }
    };

    const deleteMessage = async (msg) => {
        if (confirm('Delete this message?')) {
            try { await axios.delete(`/api/admin/contacts/${msg.id}`); setSelected(null); loadMessages(); } catch (err) { alert('Failed to delete'); }
        }
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-bold mb-6">Contact Messages</h1>

            {loading ? (
                <div className="text-center py-12 text-gray-500">Loading...</div>
            ) : messages.length === 0 ? (
                <div className="text-center py-12 text-gray-500 dark:text-gray-400">No messages yet.</div>
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="space-y-3">
                        {messages.map(msg => (
                            <button key={msg.id} onClick={() => viewMessage(msg)} className={`w-full text-left p-4 rounded-xl border transition-colors ${selected?.id === msg.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600'}`}>
                                <div className="flex justify-between items-start mb-1">
                                    <span className="font-medium text-sm">{msg.name}</span>
                                    {!msg.read && <span className="px-1.5 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">New</span>}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">{msg.email}</p>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">{msg.message}</p>
                                <p className="text-xs text-gray-400 mt-2">{new Date(msg.created_at).toLocaleString()}</p>
                            </button>
                        ))}
                    </div>
                    <div>
                        {selected ? (
                            <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                <div className="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 className="font-semibold text-lg">{selected.name}</h3>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">{selected.email}</p>
                                        {selected.phone && <p className="text-sm text-gray-500 dark:text-gray-400">{selected.phone}</p>}
                                    </div>
                                    <button onClick={() => deleteMessage(selected)} className="p-1.5 text-gray-500 hover:text-red-600 dark:hover:text-red-400">
                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                <p className="text-xs text-gray-400 mb-4">Received {new Date(selected.created_at).toLocaleString()}</p>
                                <div className="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{selected.message}</p>
                                </div>
                                <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <a href={`mailto:${selected.email}`} className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        Reply via Email
                                    </a>
                                </div>
                            </div>
                        ) : (
                            <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 text-center text-gray-500 dark:text-gray-400">
                                Select a message to view
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
