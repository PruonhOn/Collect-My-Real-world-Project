import { Routes, Route, Navigate } from 'react-router-dom'
import { PublicLayout, ProtectedLayout, AdminLayout } from './components/Layout.jsx'

import HomePage from './pages/public/HomePage.jsx'
import AboutPage from './pages/public/AboutPage.jsx'
import TeacherPage from './pages/public/TeacherPage.jsx'
import StudentPage from './pages/public/StudentPage.jsx'
import LoginPage from './pages/auth/LoginPage.jsx'
import RegisterPage from './pages/auth/RegisterPage.jsx'

import BooksPage from './pages/books/BooksPage.jsx'
import BookDetailPage from './pages/books/BookDetailPage.jsx'
import CategoryDetailPage from './pages/books/CategoryDetailPage.jsx'
import CategoriesPage from './pages/public/CategoriesPage.jsx'
import ReaderPage from './pages/books/ReaderPage.jsx'

import ProfilePage from './pages/user/ProfilePage.jsx'
import MyLibraryPage from './pages/user/MyLibraryPage.jsx'
import MyPurchasesPage from './pages/user/MyPurchasesPage.jsx'
import VipPage from './pages/user/VipPage.jsx'

import QuizzesPage from './pages/quizzes/QuizzesPage.jsx'
import QuizDetailPage from './pages/quizzes/QuizDetailPage.jsx'

import AdminDashboardPage from './pages/admin/AdminDashboardPage.jsx'
import AdminBooksPage from './pages/admin/AdminBooksPage.jsx'
import AdminBookFormPage from './pages/admin/AdminBookFormPage.jsx'
import AdminCategoriesPage from './pages/admin/AdminCategoriesPage.jsx'
import AdminUsersPage from './pages/admin/AdminUsersPage.jsx'
import AdminPurchasesPage from './pages/admin/AdminPurchasesPage.jsx'
import AdminReviewsPage from './pages/admin/AdminReviewsPage.jsx'
import AdminQuizzesPage from './pages/admin/AdminQuizzesPage.jsx'
import AdminQuizFormPage from './pages/admin/AdminQuizFormPage.jsx'

function App() {
  return (
    <Routes>
      <Route path="/reader/:id" element={<ReaderPage />} />
      <Route element={<PublicLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/books" element={<BooksPage />} />
        <Route path="/books/:id" element={<BookDetailPage />} />
        <Route path="/categories" element={<CategoriesPage />} />
        <Route path="/categories/:id" element={<CategoryDetailPage />} />
        <Route path="/quizzes" element={<QuizzesPage />} />
        <Route path="/about" element={<AboutPage />} />
        <Route path="/teacher" element={<TeacherPage />} />
        <Route path="/student" element={<StudentPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
      </Route>

      <Route element={<ProtectedLayout />}>
        <Route path="/profile" element={<ProfilePage />} />
        <Route path="/my-library" element={<MyLibraryPage />} />
        <Route path="/my-purchases" element={<MyPurchasesPage />} />
        <Route path="/vip" element={<VipPage />} />
        <Route path="/quizzes/:id" element={<QuizDetailPage />} />
      </Route>

      <Route element={<AdminLayout />}>
        <Route path="/admin" element={<AdminDashboardPage />} />
        <Route path="/admin/books" element={<AdminBooksPage />} />
        <Route path="/admin/books/new" element={<AdminBookFormPage />} />
        <Route path="/admin/books/:id/edit" element={<AdminBookFormPage />} />
        <Route path="/admin/categories" element={<AdminCategoriesPage />} />
        <Route path="/admin/users" element={<AdminUsersPage />} />
        <Route path="/admin/purchases" element={<AdminPurchasesPage />} />
        <Route path="/admin/reviews" element={<AdminReviewsPage />} />
        <Route path="/admin/quizzes" element={<AdminQuizzesPage />} />
        <Route path="/admin/quizzes/new" element={<AdminQuizFormPage />} />
        <Route path="/admin/quizzes/:id/edit" element={<AdminQuizFormPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default App