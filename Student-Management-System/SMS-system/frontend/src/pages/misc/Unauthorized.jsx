export default function Unauthorized() {
return (
<div className="min-h-screen flex items-center justify-center bg-gray-100 p-6">
<div className="bg-white shadow-xl rounded-2xl p-10 max-w-md text-center">
<div className="text-red-500 text-6xl font-bold mb-4">403</div>
<h1 className="text-2xl font-semibold mb-2">Unauthorized Access</h1>
<p className="text-gray-600 mb-6">
You do not have permission to view this page.
</p>
<a
href="/"
className="px-6 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition"
>
Go Back Home
</a>
</div>
</div>
);
}