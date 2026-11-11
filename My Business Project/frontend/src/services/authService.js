import api from '../api/client'

export const authService = {
  async register(payload) {
    const { data } = await api.post('/auth/register', payload)

    return data
  },

  async login(payload) {
    const { data } = await api.post('/auth/login', payload)

    return data
  },

  async logout() {
    const { data } = await api.post('/auth/logout')

    return data
  },

  async me() {
    const { data } = await api.get('/user')

    return data
  },

  async sendResetLink(email) {
    const { data } = await api.post('/auth/forgot-password', { email })

    return data
  },

  async resetPassword(payload) {
    const { data } = await api.post('/auth/reset-password', payload)

    return data
  },

  async sendVerificationNotification() {
    const { data } = await api.post('/email/verification-notification')

    return data
  },

  async invitation(token) {
    const { data } = await api.get(`/invitations/${token}`)

    return data
  },

  async acceptInvitation(token) {
    const { data } = await api.post(`/invitations/${token}/accept`)

    return data
  },

  async plans() {
    const { data } = await api.get('/plans')

    return data.data
  },
}

export default authService
