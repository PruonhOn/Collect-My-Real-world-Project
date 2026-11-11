import { useState } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import axios from 'axios';

export default function Contact() {
    const [form, setForm] = useState({ name: '', email: '', phone: '', message: '' });
    const [submitted, setSubmitted] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        try {
            await axios.post('/api/contact', form);
            setSubmitted(true);
            setForm({ name: '', email: '', phone: '', message: '' });
        } catch (err) {
            setError('Failed to send message. Please try again.');
        }
    };

    return (
        <PublicLayout>
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <h1 className="text-4xl font-bold mb-8">Contact Us</h1>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Get In Touch</h2>
                        <div className="space-y-4 text-gray-600 dark:text-gray-400">
                            <div className="flex items-start space-x-3">
                                <svg className="w-5 h-5 mt-0.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                <div>
                                    <p className="font-medium">Email</p>
                                    <p>onpruonh123@gmail.com</p>
                                </div>
                            </div>
                            <div className="flex items-start space-x-3">
                                <svg className="w-5 h-5 mt-0.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                <div>
                                    <p className="font-medium">Phone</p>
                                    <p>0967594210</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        {submitted ? (
                            <div className="p-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl text-center">
                                <svg className="w-12 h-12 mx-auto text-green-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <h3 className="text-lg font-semibold text-green-800 dark:text-green-200">Message Sent!</h3>
                                <p className="text-green-600 dark:text-green-400 mt-2">Thank you for reaching out. We'll get back to you soon.</p>
                                <button onClick={() => setSubmitted(false)} className="mt-4 text-sm text-indigo-600 hover:underline">Send another message</button>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit} className="space-y-4">
                                {error && <p className="text-sm text-red-600 bg-red-50 dark:bg-red-900/30 p-3 rounded-lg">{error}</p>}
                                <div>
                                    <label className="block text-sm font-medium mb-1">Name</label>
                                    <input type="text" value={form.name} onChange={e => setForm({...form, name: e.target.value})} required className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Email</label>
                                    <input type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} required className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Phone (optional)</label>
                                    <input type="text" value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Message</label>
                                    <textarea rows={4} value={form.message} onChange={e => setForm({...form, message: e.target.value})} required className="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"></textarea>
                                </div>
                                <button type="submit" className="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors">Send Message</button>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
