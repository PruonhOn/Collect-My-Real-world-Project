import PublicLayout from '@/Layouts/PublicLayout';

export default function About() {
    return (
        <PublicLayout>
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <h1 className="text-4xl font-bold mb-8">About K & N Collection</h1>
                <div className="prose dark:prose-invert max-w-none space-y-6">
                    <p className="text-lg text-gray-600 dark:text-gray-400">
                        Welcome to K & N Collection, your premier destination for fashionable clothing and accessories. 
                        We are dedicated to providing high-quality products that combine style, comfort, and affordability.
                    </p>
                    <h2 className="text-2xl font-semibold mt-8">Our Story</h2>
                    <p className="text-gray-600 dark:text-gray-400">
                        Founded with a passion for fashion, K & N Collection has grown into a trusted name in the industry. 
                        We carefully curate our collection to bring you the latest trends while maintaining timeless elegance.
                    </p>
                    <h2 className="text-2xl font-semibold mt-8">Our Mission</h2>
                    <p className="text-gray-600 dark:text-gray-400">
                        To make fashion accessible to everyone. We believe that everyone deserves to look and feel their best, 
                        and we strive to offer products that cater to diverse styles and preferences.
                    </p>
                    <h2 className="text-2xl font-semibold mt-8">Why Choose Us</h2>
                    <ul className="list-disc pl-6 text-gray-600 dark:text-gray-400 space-y-2">
                        <li>Premium quality products at affordable prices</li>
                        <li>Wide selection of styles and sizes</li>
                        <li>Fast and reliable shipping</li>
                        <li>Excellent customer service</li>
                        <li>Easy returns and exchanges</li>
                    </ul>
                </div>
            </div>
        </PublicLayout>
    );
}
