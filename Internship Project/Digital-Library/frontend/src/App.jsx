import { lazy, Suspense, useEffect } from 'react'
import { Routes, Route, useLocation } from 'react-router-dom'
import Navbar from './components/Navbar'
import Footer from './components/Footer'
import ProtectedRoute from './components/ProtectedRoute'
import AdminRoute from './components/AdminRoute'
import Spinner from './components/Spinner'
import VerifyEmailBanner from './components/VerifyEmailBanner'

const Home = lazy(() => import('./pages/Home'))
const Books = lazy(() => import('./pages/Books'))
const BookDetail = lazy(() => import('./pages/BookDetail'))
const Categories = lazy(() => import('./pages/Categories'))
const Login = lazy(() => import('./pages/Login'))
const Register = lazy(() => import('./pages/Register'))
const ForgotPassword = lazy(() => import('./pages/ForgotPassword'))
const ResetPassword = lazy(() => import('./pages/ResetPassword'))
const MyLibrary = lazy(() => import('./pages/MyLibrary'))
const Reader = lazy(() => import('./pages/Reader'))
const Payments = lazy(() => import('./pages/Payments'))
const Sharing = lazy(() => import('./pages/Sharing'))
const PaymentStatus = lazy(() => import('./pages/PaymentStatus'))

const AdminLayout = lazy(() => import('./pages/admin/AdminLayout'))
const Dashboard = lazy(() => import('./pages/admin/Dashboard'))
const Users = lazy(() => import('./pages/admin/Users'))
const BooksAdmin = lazy(() => import('./pages/admin/BooksAdmin'))
const CategoriesAdmin = lazy(() => import('./pages/admin/CategoriesAdmin'))
const CoursesAdmin = lazy(() => import('./pages/admin/CoursesAdmin'))
const PaymentsAdmin = lazy(() => import('./pages/admin/PaymentsAdmin'))
const SharingKeysAdmin = lazy(() => import('./pages/admin/SharingKeysAdmin'))
const ReviewsAdmin = lazy(() => import('./pages/admin/ReviewsAdmin'))

export default function App() {
  const location = useLocation()

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: 'auto' })
  }, [location.pathname])

  return (
    <div className="min-h-screen flex flex-col">
      <Navbar />
      <VerifyEmailBanner />
      <main className="flex-1">
        <div key={location.pathname} className="animate-page min-h-[60vh] flex flex-col">
          <Suspense fallback={<Spinner />}>
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/books" element={<Books />} />
              <Route path="/books/:id" element={<BookDetail />} />
              <Route path="/categories" element={<Categories />} />
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/forgot-password" element={<ForgotPassword />} />
              <Route path="/reset-password" element={<ResetPassword />} />
              <Route path="/payments/status" element={<PaymentStatus />} />

              <Route
                path="/library"
                element={
                  <ProtectedRoute>
                    <MyLibrary />
                  </ProtectedRoute>
                }
              />
              <Route path="/reader/:id" element={<Reader />} />
              <Route
                path="/payments"
                element={
                  <ProtectedRoute>
                    <Payments />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/sharing"
                element={
                  <ProtectedRoute>
                    <Sharing />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/admin"
                element={
                  <AdminRoute>
                    <AdminLayout />
                  </AdminRoute>
                }
              >
                <Route index element={<Dashboard />} />
                <Route path="users" element={<Users />} />
                <Route path="books" element={<BooksAdmin />} />
                <Route path="categories" element={<CategoriesAdmin />} />
                <Route path="courses" element={<CoursesAdmin />} />
                <Route path="payments" element={<PaymentsAdmin />} />
                <Route path="sharing" element={<SharingKeysAdmin />} />
                <Route path="reviews" element={<ReviewsAdmin />} />
              </Route>

              <Route path="*" element={<Home />} />
            </Routes>
          </Suspense>
        </div>
      </main>
      <Footer />
    </div>
  )
}
