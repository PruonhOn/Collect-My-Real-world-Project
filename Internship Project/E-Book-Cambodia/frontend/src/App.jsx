import { Routes, Route, Navigate } from 'react-router-dom'
import { PublicLayout, ProtectedLayout, AdminLayout, AuthorLayout } from './components/Layout.jsx'

import HomePage from './pages/public/HomePage.jsx'
import WelcomePage from './pages/public/WelcomePage.jsx'
import AboutPage from './pages/public/AboutPage.jsx'
import OrganizationPage from './pages/public/OrganizationPage.jsx'
import ContactPage from './pages/public/ContactPage.jsx'
import TeacherPage from './pages/public/TeacherPage.jsx'
import StudentPage from './pages/public/StudentPage.jsx'
import EventsPage from './pages/public/EventsPage.jsx'
import EventDetailPage from './pages/public/EventDetailPage.jsx'
import CollaborationPage from './pages/public/CollaborationPage.jsx'
import LoginPage from './pages/auth/LoginPage.jsx'
import RegisterPage from './pages/auth/RegisterPage.jsx'
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage.jsx'
import ResetPasswordPage from './pages/auth/ResetPasswordPage.jsx'

import BooksPage from './pages/books/BooksPage.jsx'
import NewArrivalPage from './pages/books/NewArrivalPage.jsx'
import CompetitiveExamsPage from './pages/books/CompetitiveExamsPage.jsx'
import BookDetailPage from './pages/books/BookDetailPage.jsx'
import CategoryDetailPage from './pages/books/CategoryDetailPage.jsx'
import CategoriesPage from './pages/public/CategoriesPage.jsx'
import ReaderPage from './pages/books/ReaderPage.jsx'

import ProfilePage from './pages/user/ProfilePage.jsx'
import MessagesPage from './pages/user/MessagesPage.jsx'
import MyLibraryPage from './pages/user/MyLibraryPage.jsx'
import MyPurchasesPage from './pages/user/MyPurchasesPage.jsx'
import VipPage from './pages/user/VipPage.jsx'

import QuizzesPage from './pages/quizzes/QuizzesPage.jsx'
import QuizDetailPage from './pages/quizzes/QuizDetailPage.jsx'

import AdminDashboardPage from './pages/admin/AdminDashboardPage.jsx'
import AdminBooksPage from './pages/admin/AdminBooksPage.jsx'
import AdminBookReviewPage from './pages/admin/AdminBookReviewPage.jsx'
import AdminBookFormPage from './pages/admin/AdminBookFormPage.jsx'
import AdminCategoriesPage from './pages/admin/AdminCategoriesPage.jsx'
import AdminAuthorsPage from './pages/admin/AdminAuthorsPage.jsx'
import AdminEventsPage from './pages/admin/AdminEventsPage.jsx'
import AdminEventFormPage from './pages/admin/AdminEventFormPage.jsx'
import AdminUsersPage from './pages/admin/AdminUsersPage.jsx'
import AdminVipUsersPage from './pages/admin/AdminVipUsersPage.jsx'
import AdminVipPlansPage from './pages/admin/AdminVipPlansPage.jsx'
import AdminVipTestCardsPage from './pages/admin/AdminVipTestCardsPage.jsx'
import AdminPurchasesPage from './pages/admin/AdminPurchasesPage.jsx'
import AdminReviewsPage from './pages/admin/AdminReviewsPage.jsx'
import AdminMessagesPage from './pages/admin/AdminMessagesPage.jsx'
import AdminCollaboratorsPage from './pages/admin/AdminCollaboratorsPage.jsx'
import AdminOrganizationPage from './pages/admin/AdminOrganizationPage.jsx'
import AdminQuizzesPage from './pages/admin/AdminQuizzesPage.jsx'
import AdminQuizFormPage from './pages/admin/AdminQuizFormPage.jsx'
import AdminNotificationsPage from './pages/admin/AdminNotificationsPage.jsx'
import AdminBroadcastMessagesPage from './pages/admin/AdminBroadcastMessagesPage.jsx'
import AdminReportsPage from './pages/admin/AdminReportsPage.jsx'
import AdminSettingsPage from './pages/admin/AdminSettingsPage.jsx'

import AuthorDashboardPage from './pages/author/AuthorDashboardPage.jsx'
import AuthorBooksPage from './pages/author/AuthorBooksPage.jsx'
import AuthorBookFormPage from './pages/author/AuthorBookFormPage.jsx'
import AuthorProfilePage from './pages/author/AuthorProfilePage.jsx'

function App() {
  return (
    <Routes>
      <Route path="/reader/:id" element={<ReaderPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />
      <Route element={<PublicLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/welcome" element={<WelcomePage />} />
        <Route path="/books" element={<BooksPage />} />
        <Route path="/competitive-exams" element={<CompetitiveExamsPage />} />
        <Route path="/new-arrivals" element={<NewArrivalPage />} />
        <Route path="/books/:id" element={<BookDetailPage />} />
        <Route path="/categories" element={<CategoriesPage />} />
        <Route path="/categories/:id" element={<CategoryDetailPage />} />
        <Route path="/quizzes" element={<QuizzesPage />} />
        <Route path="/about" element={<AboutPage />} />
        <Route path="/organization" element={<OrganizationPage />} />
        <Route path="/contact" element={<ContactPage />} />
        <Route path="/teacher" element={<TeacherPage />} />
        <Route path="/student" element={<StudentPage />} />
        <Route path="/events" element={<EventsPage />} />
        <Route path="/events/:id" element={<EventDetailPage />} />
        <Route path="/collaboration" element={<CollaborationPage />} />
        <Route path="/vip" element={<VipPage />} />
        <Route path="/author/login" element={<Navigate to="/login" replace />} />
      </Route>

      <Route element={<AuthorLayout />}>
        <Route path="/author" element={<AuthorDashboardPage />} />
        <Route path="/author/books" element={<AuthorBooksPage />} />
        <Route path="/author/books/new" element={<AuthorBookFormPage />} />
        <Route path="/author/books/:id" element={<AuthorBookFormPage />} />
        <Route path="/author/profile" element={<AuthorProfilePage />} />
      </Route>

      <Route element={<ProtectedLayout />}>
        <Route path="/profile" element={<ProfilePage />} />
        <Route path="/messages" element={<MessagesPage />} />
        <Route path="/my-library" element={<MyLibraryPage />} />
        <Route path="/my-purchases" element={<MyPurchasesPage />} />
        <Route path="/quizzes/:id" element={<QuizDetailPage />} />
      </Route>

      <Route element={<AdminLayout />}>
        <Route path="/admin" element={<AdminDashboardPage />} />
        <Route path="/admin/books" element={<AdminBooksPage />} />
        <Route path="/admin/book-reviews" element={<AdminBookReviewPage />} />
        <Route path="/admin/books/new" element={<AdminBookFormPage />} />
        <Route path="/admin/books/:id/edit" element={<AdminBookFormPage />} />
        <Route path="/admin/categories" element={<AdminCategoriesPage />} />
        <Route path="/admin/authors" element={<AdminAuthorsPage />} />
        <Route path="/admin/events" element={<AdminEventsPage />} />
        <Route path="/admin/events/new" element={<AdminEventFormPage />} />
        <Route path="/admin/events/:id/edit" element={<AdminEventFormPage />} />
        <Route path="/admin/users" element={<AdminUsersPage />} />
        <Route path="/admin/vip-plans" element={<AdminVipPlansPage />} />
        <Route path="/admin/vip-users" element={<AdminVipUsersPage />} />
        <Route path="/admin/vip-test-cards" element={<AdminVipTestCardsPage />} />
        <Route path="/admin/purchases" element={<AdminPurchasesPage />} />
        <Route path="/admin/reviews" element={<AdminReviewsPage />} />
        <Route path="/admin/messages" element={<AdminMessagesPage />} />
        <Route path="/admin/collaborators" element={<AdminCollaboratorsPage />} />
        <Route path="/admin/organization" element={<AdminOrganizationPage />} />
        <Route path="/admin/quizzes" element={<AdminQuizzesPage />} />
        <Route path="/admin/quizzes/new" element={<AdminQuizFormPage />} />
        <Route path="/admin/quizzes/:id/edit" element={<AdminQuizFormPage />} />
        <Route path="/admin/notifications" element={<AdminNotificationsPage />} />
          <Route path="/admin/broadcast-messages" element={<AdminBroadcastMessagesPage />} />
        <Route path="/admin/reports" element={<AdminReportsPage />} />
        <Route path="/admin/settings" element={<AdminSettingsPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default App