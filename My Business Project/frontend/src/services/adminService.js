import api from '../api/client'

export const adminService = {
  async dashboard() {
    const { data } = await api.get('/admin/dashboard')

    return data.data
  },

  async users(params = {}) {
    const { data } = await api.get('/admin/users', { params })

    return data
  },

  async user(id) {
    const { data } = await api.get(`/admin/users/${id}`)

    return data.data
  },

  async updateUser(id, payload) {
    const { data } = await api.put(`/admin/users/${id}`, payload)

    return data.data
  },

  async deleteUser(id) {
    const { data } = await api.delete(`/admin/users/${id}`)

    return data
  },

  async businesses(params = {}) {
    const { data } = await api.get('/admin/businesses', { params })

    return data
  },

  async business(id) {
    const { data } = await api.get(`/admin/businesses/${id}`)

    return data.data
  },

  async updateBusiness(id, payload) {
    const { data } = await api.put(`/admin/businesses/${id}`, payload)

    return data.data
  },

  async suspendBusiness(id, reason) {
    const { data } = await api.post(`/admin/businesses/${id}/suspend`, { reason })

    return data.data
  },

  async unsuspendBusiness(id, reason) {
    const { data } = await api.post(`/admin/businesses/${id}/unsuspend`, { reason })

    return data.data
  },

  async deleteBusiness(id, reason) {
    const { data } = await api.delete(`/admin/businesses/${id}`, { data: { reason } })

    return data
  },

  async plans() {
    const { data } = await api.get('/admin/plans')

    return data.data
  },

  async createPlan(payload) {
    const { data } = await api.post('/admin/plans', payload)

    return data.data
  },

  async updatePlan(id, payload) {
    const { data } = await api.put(`/admin/plans/${id}`, payload)

    return data.data
  },

  async deletePlan(id) {
    const { data } = await api.delete(`/admin/plans/${id}`)

    return data
  },

  async subscriptions(params = {}) {
    const { data } = await api.get('/admin/subscriptions', { params })

    return data
  },

  async updateSubscription(id, payload) {
    const { data } = await api.put(`/admin/subscriptions/${id}`, payload)

    return data.data
  },

  async payments(params = {}) {
    const { data } = await api.get('/admin/payments', { params })

    return data
  },

  async coupons(params = {}) {
    const { data } = await api.get('/admin/coupons', { params })

    return data
  },

  async coupon(id) {
    const { data } = await api.get(`/admin/coupons/${id}`)

    return data.data
  },

  async createCoupon(payload) {
    const { data } = await api.post('/admin/coupons', payload)

    return data.data
  },

  async updateCoupon(id, payload) {
    const { data } = await api.put(`/admin/coupons/${id}`, payload)

    return data.data
  },

  async deleteCoupon(id) {
    const { data } = await api.delete(`/admin/coupons/${id}`)

    return data
  },
}

export default adminService
