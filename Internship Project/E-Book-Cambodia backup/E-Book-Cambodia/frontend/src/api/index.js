import api from './client'

// ============ Auth ============
export const register = (data) => api.post('/register', data)
export const login = (data) => api.post('/login', data)
export const googleLogin = (idToken) => api.post('/google-login', { id_token: idToken })
export const logout = () => api.post('/logout')
export const getMe = () => api.get('/me')
export const updateProfile = (data) => api.put('/me', data)
export const sendEmailVerification = () => api.post('/email/verification-notification')

// ============ Public ============
export const getBooks = (params) => api.get('/books', { params })
export const getBook = (id) => api.get(`/books/${id}`)
export const getBookReviews = (id, params) => api.get(`/books/${id}/reviews`, { params })
export const getCategories = (params) => api.get('/categories', { params })
export const getAuthors = () => api.get('/authors')
export const getCategory = (id) => api.get(`/categories/${id}`)
export const getCourses = (params) => api.get('/courses', { params })
export const getCourse = (id) => api.get(`/courses/${id}`)

// ============ Reader ============
export const readBook = (id) => api.get(`/reader/${id}`)
export const getBookFile = (id) => api.get(`/reader/${id}/stream`, { responseType: 'blob' })
export const getProgress = (id) => api.get(`/reader/${id}/progress`)
export const saveProgress = (id, data) => api.post(`/reader/${id}/progress`, data)
export const getBookmarks = (id) => api.get(`/reader/${id}/bookmarks`)
export const addBookmark = (id, data) => api.post(`/reader/${id}/bookmarks`, data)
export const deleteBookmark = (bookmarkId) => api.delete(`/reader/bookmarks/${bookmarkId}`)

// ============ Purchases & Payments ============
export const purchaseBook = (id, data) => api.post(`/books/${id}/purchase`, data)
export const purchaseCourse = (id, data) => api.post(`/courses/${id}/purchase`, data)
export const getPayments = (params) => api.get('/payments', { params })
export const getPayment = (id) => api.get(`/payments/${id}`)
export const checkBakong = (id) => api.post(`/payments/${id}/bakong/check`)
export const getOwnedItems = () => api.get('/owned-items')
export const getLibrary = () => api.get('/library')
export const getLibraryItem = (id) => api.get(`/library/${id}`)

// ============ VIP ============
export const getVipPlans = () => api.get('/vip-plans')
export const purchaseVip = (planId, data) => api.post(`/vip/${planId}/purchase`, data)

// ============ Quizzes ============
export const getQuizzes = (params) => api.get('/quizzes', { params })
export const getQuiz = (id) => api.get(`/quizzes/${id}`)
export const submitQuizAttempt = (id, data) => api.post(`/quizzes/${id}/attempt`, data)
export const getMyQuizAttempts = (id) => api.get(`/quizzes/${id}/attempts`)

// ============ Sharing ============
export const generateShareKey = (bookId) => api.post(`/books/${bookId}/share`)
export const redeemShareKey = (key) => api.post('/redeem', { key })
export const getMySharing = () => api.get('/sharing')
export const revokeShare = (id) => api.delete(`/sharing/${id}`)

// ============ Reviews ============
export const submitReview = (bookId, data) => api.post(`/books/${bookId}/reviews`, data)
export const deleteReview = (id) => api.delete(`/reviews/${id}`)

// ============ Admin ============
export const adminGetDashboard = () => api.get('/admin/dashboard')
export const adminGetUsers = (params) => api.get('/admin/users', { params })
export const adminUpdateUserRole = (id, role) => api.patch(`/admin/users/${id}/role`, { role })
export const adminDeleteUser = (id) => api.delete(`/admin/users/${id}`)

export const adminGetBooks = (params) => api.get('/admin/books', { params })
export const adminGetBook = (id) => api.get(`/admin/books/${id}`)
export const adminCreateBook = (data) => api.post('/admin/books', data)
export const adminUpdateBook = (id, data) => api.put(`/admin/books/${id}`, data)
export const adminDeleteBook = (id) => api.delete(`/admin/books/${id}`)

export const adminCreateCategory = (data) => api.post('/admin/categories', data)
export const adminUpdateCategory = (id, data) => api.put(`/admin/categories/${id}`, data)
export const adminDeleteCategory = (id) => api.delete(`/admin/categories/${id}`)

export const adminGetPayments = (params) => api.get('/admin/payments', { params })
export const adminApprovePayment = (id) => api.post(`/admin/payments/${id}/approve`)
export const adminGetSharingKeys = (params) => api.get('/admin/sharing-keys', { params })

export const adminGetReviews = (params) => api.get('/admin/reviews', { params })
export const adminUpdateReviewStatus = (id, status) => api.patch(`/admin/reviews/${id}`, { status })
export const adminDeleteReview = (id) => api.delete(`/admin/reviews/${id}`)

export const adminGetQuizzes = (params) => api.get('/admin/quizzes', { params })
export const adminGetQuiz = (id) => api.get(`/admin/quizzes/${id}`)
export const adminCreateQuiz = (data) => api.post('/admin/quizzes', data)
export const adminUpdateQuiz = (id, data) => api.put(`/admin/quizzes/${id}`, data)
export const adminDeleteQuiz = (id) => api.delete(`/admin/quizzes/${id}`)

export const adminGetVipPlans = () => api.get('/vip-plans')
export const adminCreateVipPlan = (data) => api.post('/admin/vip-plans', data)
export const adminUpdateVipPlan = (id, data) => api.put(`/admin/vip-plans/${id}`, data)
export const adminDeleteVipPlan = (id) => api.delete(`/admin/vip-plans/${id}`)

// ============ Helpers ============
export const coverUrl = (book) => {
  if (!book?.cover_image) return null
  if (/^https?:\/\//.test(book.cover_image)) return book.cover_image
  return `/storage/${book.cover_image}`
}

export const avatarUrl = (user) => {
  if (!user?.avatar) return null
  if (/^https?:\/\//.test(user.avatar)) return user.avatar
  return `/storage/${user.avatar}`
}