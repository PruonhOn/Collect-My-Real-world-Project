import api from './client'

// ============ Auth ============
export const register = (data) => api.post('/register', data)
export const login = (data) => api.post('/login', data)
export const forgotPassword = (data) => api.post('/forgot-password', data)
export const resetPassword = (data) => api.post('/reset-password', data)
export const googleLogin = (idToken) => api.post('/google-login', { id_token: idToken })
export const logout = () => api.post('/logout')
export const getMe = () => api.get('/me')
export const updateProfile = (data) => api.put('/me', data)
export const saveReaderPreferences = (data) => api.put('/me/reader-preferences', data)
export const sendEmailVerification = () => api.post('/email/verification-notification')

// ============ Public ============
export const getBooks = (params) => api.get('/books', { params })
export const getBook = (id) => api.get(`/books/${id}`)
export const recordBookView = (id) => api.post(`/books/${id}/views`)
export const getStats = () => api.get('/stats')
export const getBookReviews = (id, params) => api.get(`/books/${id}/reviews`, { params })
export const getCategories = (params) => api.get('/categories', { params })
export const getAuthors = () => api.get('/authors')
export const getCategory = (id) => api.get(`/categories/${id}`)
export const getCourses = (params) => api.get('/courses', { params })
export const getCourse = (id) => api.get(`/courses/${id}`)

// ============ Reader ============
export const readBook = (id) => api.get(`/reader/${id}`)
export const getBookFile = (id) => api.get(`/reader/${id}/stream`, { responseType: 'blob' })
export const getBookAccess = (id) => api.get(`/books/${id}/access`)
export const getProgress = (id) => api.get(`/reader/${id}/progress`)
export const saveProgress = (id, data) => api.post(`/reader/${id}/progress`, data)
export const getBookmarks = (id) => api.get(`/reader/${id}/bookmarks`)
export const addBookmark = (id, data) => api.post(`/reader/${id}/bookmarks`, data)
export const deleteBookmark = (bookmarkId) => api.delete(`/reader/bookmarks/${bookmarkId}`)

// ============ AI Assistant ============
// The reader sends the current page's text (or a rendered image of it, for
// scans). It never sends a book file, URL or storage key.
export const getAiOptions = () => api.get('/ai/options')
export const runAiAction = (bookId, data) => api.post(`/reader/${bookId}/ai`, data)

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
export const redeemShareKey = (key) => api.post('/redeem', { code: key })
export const getMySharing = () => api.get('/sharing')
export const revokeShare = (id) => api.delete(`/sharing/${id}`)

// ============ Reviews ============
export const submitReview = (bookId, data) => api.post(`/books/${bookId}/reviews`, data)
export const deleteReview = (id) => api.delete(`/reviews/${id}`)

// ============ Contact ============
export const submitContact = (data) => api.post('/contact', data)

// ============ Messages ============
export const getMyMessages = (params) => api.get('/my/messages', { params })
export const getMyMessagesUnreadCount = () => api.get('/my/messages/unread-count')
export const markMyMessagesRead = () => api.post('/my/messages/read')
export const sendMessage = (data) => api.post('/messages', data)
export const getConversations = () => api.get('/conversations')
export const getConversationMessages = (id) => api.get(`/conversations/${id}/messages`)
export const markMessageRead = (id) => api.patch(`/messages/${id}/read`)
export const adminGetMessages = (params) => api.get('/admin/messages', { params })
export const adminGetMessagesUnreadCount = () => api.get('/admin/messages/unread-count')
export const adminCreateMessage = (data) => api.post('/admin/messages', data)
export const adminSendMessage = (data) => api.post('/admin/messages/send', data)
export const adminUpdateMessage = (id, data) => api.put(`/admin/messages/${id}`, data)
export const adminReplyMessage = (id, data) => api.post(`/admin/messages/${id}/reply`, data)
export const adminDeleteMessage = (id) => api.delete(`/admin/messages/${id}`)

// ============ Notifications ============
// One feed, three portals. The path is what picks the notifiable: member,
// admin, or the author token under /author so api/client.js sends the right
// bearer token.
export const NOTIFICATION_ENDPOINTS = {
  member: {
    list: () => api.get('/notifications'),
    markRead: (id) => api.patch(`/notifications/${id}/read`),
    markAllRead: () => api.post('/notifications/read-all'),
  },
  admin: {
    list: () => api.get('/admin/notifications'),
    markRead: (id) => api.patch(`/admin/notifications/${id}/read`),
    markAllRead: () => api.post('/admin/notifications/read-all'),
  },
  author: {
    list: () => api.get('/author/notifications'),
    markRead: (id) => api.patch(`/author/notifications/${id}/read`),
    markAllRead: () => api.post('/author/notifications/read-all'),
  },
}
export const getAdminNotifications = NOTIFICATION_ENDPOINTS.admin.list
export const markAdminNotificationRead = NOTIFICATION_ENDPOINTS.admin.markRead
export const markAllAdminNotificationsRead = NOTIFICATION_ENDPOINTS.admin.markAllRead

// ============ Admin ============
export const adminGetDashboard = () => api.get('/admin/dashboard')
export const adminGetUsers = (params) => api.get('/admin/users', { params })
export const adminUpdateUserRole = (id, role) => api.patch(`/admin/users/${id}/role`, { role })
export const adminDeleteUser = (id) => api.delete(`/admin/users/${id}`)
export const adminGetVipUsers = (params) => api.get('/admin/vip-users', { params })
export const adminGetVipTestCards = (params) => api.get('/admin/vip-test-cards', { params })
export const adminRevokeVipTestCard = (purchaseId) => api.post(`/admin/vip-test-cards/${purchaseId}/revoke`)

export const adminGetBooks = (params) => api.get('/admin/books', { params })
export const adminGetBook = (id) => api.get(`/admin/books/${id}`)
export const adminCreateBook = (data) => api.post('/admin/books', data)
export const adminUpdateBook = (id, data) => api.put(`/admin/books/${id}`, data)
export const adminDeleteBook = (id) => api.delete(`/admin/books/${id}`)
export const adminGetBookToc = (id) => api.get(`/admin/books/${id}/toc`)
export const adminUpdateBookToc = (id, items) => api.put(`/admin/books/${id}/toc`, { items })
export const adminDeleteBookToc = (id) => api.delete(`/admin/books/${id}/toc`)
export const adminExtractBookToc = (id) => api.post(`/admin/books/${id}/toc/extract`)

export const adminCreateCategory = (data) => api.post('/admin/categories', data)
export const adminUpdateCategory = (id, data) => api.put(`/admin/categories/${id}`, data)
export const adminDeleteCategory = (id) => api.delete(`/admin/categories/${id}`)

export const adminGetAuthors = (params) => api.get('/admin/authors', { params })
export const adminGetAuthor = (id) => api.get(`/admin/authors/${id}`)
export const adminCreateAuthor = (data) => api.post('/admin/authors', data)
export const adminUpdateAuthor = (id, data) => api.put(`/admin/authors/${id}`, data)
export const adminDeleteAuthor = (id) => api.delete(`/admin/authors/${id}`)
export const adminUpdateAuthorStatus = (id, status) => api.patch(`/admin/authors/${id}/status`, { status })
export const adminResetAuthorPassword = (id, data) => api.post(`/admin/authors/${id}/reset-password`, data)
export const adminVerifyAuthorEmail = (id) => api.post(`/admin/authors/${id}/verify-email`)

// ============ Broadcast messages ("To Web Page") ============
// The public read is unauthenticated so guests see announcements too.
export const getBroadcastMessages = (params) => api.get('/broadcast-messages', { params })
export const adminGetBroadcastMessages = (params) => api.get('/admin/broadcast-messages', { params })
export const adminCreateBroadcastMessage = (data) => api.post('/admin/broadcast-messages', data)
export const adminUpdateBroadcastMessage = (id, data) => api.put(`/admin/broadcast-messages/${id}`, data)
export const adminDeleteBroadcastMessage = (id) => api.delete(`/admin/broadcast-messages/${id}`)

export const getEvents = (params) => api.get('/events', { params })
export const getEvent = (id) => api.get(`/events/${id}`)
export const registerForEvent = (id) => api.post(`/events/${id}/register`)

export const adminGetEvents = (params) => api.get('/admin/events', { params })
export const adminGetEvent = (id) => api.get(`/admin/events/${id}`)
export const adminCreateEvent = (data) => api.post('/admin/events', data)
export const adminUpdateEvent = (id, data) => api.put(`/admin/events/${id}`, data)
export const adminDeleteEvent = (id) => api.delete(`/admin/events/${id}`)
export const adminGetEventRegistrations = (id, params) => api.get(`/admin/events/${id}/registrations`, { params })
export const adminDeleteEventRegistration = (eventId, registrationId) =>
  api.delete(`/admin/events/${eventId}/registrations/${registrationId}`)
export const eventCoverUrl = (event) => {
  if (!event?.cover_image) return null
  if (/^https?:\/\//.test(event.cover_image)) return event.cover_image
  return `/storage/${event.cover_image}`
}

// ============ Author Dashboard ============
export const authorLogin = (data) => api.post('/author/login', data)
export const authorLogout = () => api.post('/author/logout')
export const authorMe = () => api.get('/author/me')
export const authorUpdateProfile = (data) => api.put('/author/me', data)
export const authorChangePassword = (data) => api.post('/author/change-password', data)
export const authorGetStats = () => api.get('/author/stats')
export const authorGetBooks = (params) => api.get('/author/books', { params })
export const authorGetBook = (id) => api.get(`/author/books/${id}`)
export const authorCreateBook = (data) => api.post('/author/books', data)
export const authorUpdateBook = (id, data) => api.put(`/author/books/${id}`, data)
export const authorDeleteBook = (id) => api.delete(`/author/books/${id}`)
export const authorSubmitBook = (id) => api.post(`/author/books/${id}/submit`)

// ============ Admin Book Review ============
export const adminGetBookReviews = (params) => api.get('/admin/book-reviews', { params })
export const adminGetBookReview = (id) => api.get(`/admin/book-reviews/${id}`)
export const adminReviewBook = (id, data) => api.post(`/admin/book-reviews/${id}`, data)
export const adminPublishBook = (id) => api.post(`/admin/book-reviews/${id}/publish`)
export const adminUnpublishBook = (id) => api.post(`/admin/book-reviews/${id}/unpublish`)
export const adminApproveBook = (id) => api.post(`/admin/books/${id}/approve`)
export const adminRejectBook = (id, note) => api.post(`/admin/books/${id}/reject`, { review_note: note })
export const adminPublishBookViaResource = (id) => api.post(`/admin/books/${id}/publish`)
export const adminUnpublishBookViaResource = (id) => api.post(`/admin/books/${id}/unpublish`)
export const adminBookFileUrl = (id) => `/api/v1/admin/book-reviews/${id}/file`
export const adminBookPreviewUrl = (book) =>
  book?.preview_url ??
  (book?.id ? `/api/v1/admin/book-reviews/${book.id}/file` : null)

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

export const adminGetVipPlans = () => api.get('/admin/vip-plans')
export const adminCreateVipPlan = (data) => api.post('/admin/vip-plans', data)
export const adminUpdateVipPlan = (id, data) => api.put(`/admin/vip-plans/${id}`, data)
export const adminDeleteVipPlan = (id) => api.delete(`/admin/vip-plans/${id}`)

// ============ Helpers ============
export const coverUrl = (book) => {
  if (!book?.cover_image && !book?.cover_url) return null
  if (book?.cover_url) return book.cover_url
  if (/^https?:\/\//.test(book.cover_image)) return book.cover_image
  return `/storage/${book.cover_image}`
}

export const avatarUrl = (user) => {
  if (!user?.avatar) return null
  if (/^https?:\/\//.test(user.avatar)) return user.avatar
  return `/storage/${user.avatar}`
}

export const authorPhotoUrl = (author) => {
  if (!author?.photo) return null
  if (/^https?:\/\//.test(author.photo)) return author.photo
  return `/storage/${author.photo}`
}
// ============ Organization ============
export const getOrganization = () => api.get('/organization')
export const getOrganizationMember = (id) => api.get(`/organization/members/${id}`)

export const adminGetOrganizationStructure = () => api.get('/admin/organization/structure')
export const adminStoreDepartment = (data) => api.post('/admin/organization/departments', data)
export const adminUpdateDepartment = (id, data) => api.put(`/admin/organization/departments/${id}`, data)
export const adminDeleteDepartment = (id) => api.delete(`/admin/organization/departments/${id}`)
export const adminStorePosition = (data) => api.post('/admin/organization/positions', data)
export const adminUpdatePosition = (id, data) => api.put(`/admin/organization/positions/${id}`, data)
export const adminDeletePosition = (id) => api.delete(`/admin/organization/positions/${id}`)
export const adminStoreMember = (data) => {
  if (data instanceof FormData) return api.post('/admin/organization/members', data)
  const form = new FormData()
  Object.entries(data || {}).forEach(([k, v]) => {
    if (v === undefined || v === null) return
    if (Array.isArray(v)) {
      v.forEach((item, i) => {
        if (item === undefined || item === null) return
        form.append(k + '[' + i + ']', item)
      })
      return
    }
    if (typeof v === 'boolean') { form.append(k, v ? '1' : '0'); return }
    form.append(k, v)
  })
  return api.post('/admin/organization/members', form)
}
export const adminUpdateMember = (id, data) => {
  if (data instanceof FormData) {
    const form = data
    if (!form.has('_method')) form.append('_method', 'PUT')
    return api.post(`/admin/organization/members/${id}?_method=PUT`, form)
  }
  const form = new FormData()
  form.append('_method', 'PUT')
  Object.entries(data || {}).forEach(([k, v]) => {
    if (v === undefined || v === null) return
    if (Array.isArray(v)) {
      v.forEach((item, i) => {
        if (item === undefined || item === null) return
        form.append(k + '[' + i + ']', item)
      })
      return
    }
    if (typeof v === 'boolean') { form.append(k, v ? '1' : '0'); return }
    form.append(k, v)
  })
  return api.post(`/admin/organization/members/${id}?_method=PUT`, form)
}
export const adminDeleteMember = (id) => api.delete(`/admin/organization/members/${id}`)
export const adminStorePartner = (data) => api.post('/admin/organization/partners', data)
export const adminUpdatePartner = (id, data) => api.put(`/admin/organization/partners/${id}`, data)
export const adminDeletePartner = (id) => api.delete(`/admin/organization/partners/${id}`)
export const adminReorderDepartments = (departments) => api.post('/admin/organization/reorder-departments', { departments })
export const adminReorderMembers = (members) => api.post('/admin/organization/reorder-members', { members })
export const adminSeedOrganization = () => api.post('/admin/organization/seed-initial')

