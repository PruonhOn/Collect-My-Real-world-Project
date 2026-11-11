import { BrowserRouter, Route, Routes } from 'react-router-dom'
import Layout from './components/layout/Layout'
import ProtectedRoute from './components/auth/ProtectedRoute'
import AdminRoute from './components/auth/AdminRoute'
import AdminLayout from './components/admin/AdminLayout'
import Home from './pages/Home'
import Books from './pages/Books'
import BookDetail from './pages/BookDetail'
import Categories from './pages/Categories'
import Authors from './pages/Authors'
import Pricing from './pages/Pricing'
import About from './pages/About'
import Contact from './pages/Contact'
import Login from './pages/Login'
import Register from './pages/Register'
import Dashboard from './pages/Dashboard'
import Profile from './pages/Profile'
import MyBooks from './pages/MyBooks'
import Wishlist from './pages/Wishlist'
import Orders from './pages/Orders'
import Notifications from './pages/Notifications'
import Checkout from './pages/Checkout'
import NotFound from './pages/NotFound'
import Reader from './pages/Reader'
import AdminDashboard from './pages/admin/AdminDashboard'
import AdminBooks from './pages/admin/AdminBooks'
import AdminUsers from './pages/admin/AdminUsers'
import AdminCategories from './pages/admin/AdminCategories'
import AdminAuthors from './pages/admin/AdminAuthors'
import AdminPlans from './pages/admin/AdminPlans'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<Home />} />
          <Route path="/books" element={<Books />} />
          <Route path="/books/:slug" element={<BookDetail />} />
          <Route path="/read/:slug" element={<Reader />} />
          <Route path="/categories" element={<Categories />} />
          <Route path="/authors" element={<Authors />} />
          <Route path="/pricing" element={<Pricing />} />
          <Route path="/about" element={<About />} />
          <Route path="/contact" element={<Contact />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/dashboard" element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          } />
          <Route path="/profile" element={
            <ProtectedRoute>
              <Profile />
            </ProtectedRoute>
          } />
          <Route path="/my-books" element={
            <ProtectedRoute>
              <MyBooks />
            </ProtectedRoute>
          } />
          <Route path="/wishlist" element={
            <ProtectedRoute>
              <Wishlist />
            </ProtectedRoute>
          } />
          <Route path="/orders" element={
            <ProtectedRoute>
              <Orders />
            </ProtectedRoute>
          } />
          <Route path="/notifications" element={
            <ProtectedRoute>
              <Notifications />
            </ProtectedRoute>
          } />
          <Route path="/checkout/:orderNumber" element={
            <ProtectedRoute>
              <Checkout />
            </ProtectedRoute>
          } />
          <Route path="*" element={<NotFound />} />
        </Route>

        <Route path="/admin" element={
          <AdminRoute>
            <AdminLayout />
          </AdminRoute>
        }>
          <Route index element={<AdminDashboard />} />
          <Route path="books" element={<AdminBooks />} />
          <Route path="users" element={<AdminUsers />} />
          <Route path="categories" element={<AdminCategories />} />
          <Route path="authors" element={<AdminAuthors />} />
          <Route path="plans" element={<AdminPlans />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}