// Owner: Lin Sreydeth - 404 page
// Owner: 404 Not Found Page
import { useNavigate } from 'react-router-dom';
import { Home, ArrowLeft } from 'lucide-react';
import Button from '@/components/ui/Button';

const NotFound = () => {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center p-4">
      <div className="text-center">
        <div className="mb-8">
          <h1 className="text-9xl font-bold text-primary-600">404</h1>
          <h2 className="text-3xl font-bold text-gray-900 mt-4">Page Not Found</h2>
          <p className="text-gray-600 mt-2 max-w-md mx-auto">
            The page you are looking for doesn't exist or has been moved.
          </p>
        </div>

        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Button
            variant="primary"
            onClick={() => navigate(-1)}
          >
            <ArrowLeft className="w-5 h-5 mr-2" />
            Go Back
          </Button>
          <Button
            variant="outline"
            onClick={() => navigate('/')}
          >
            <Home className="w-5 h-5 mr-2" />
            Go Home
          </Button>
        </div>

        <div className="mt-12">
          <img
            src="https://illustrations.popsy.co/amber/question-mark.svg"
            alt="404"
            className="w-64 h-64 mx-auto opacity-50"
          />
        </div>
      </div>
    </div>
  );
};

export default NotFound;