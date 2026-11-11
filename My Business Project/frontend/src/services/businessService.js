import api from '../api/client'

export const businessService = {
  async businesses() {
    const { data } = await api.get('/businesses')

    return data.data
  },

  async createBusiness(payload) {
    const { data } = await api.post('/businesses', payload)

    return data.data
  },

  async switchBusiness(businessId) {
    const { data } = await api.post('/businesses/switch', { business_id: businessId })

    return data.data
  },

  async business(businessId) {
    const { data } = await api.get(`/businesses/${businessId}`)

    return data.data
  },

  async updateBusiness(businessId, payload) {
    const { data } = await api.put(`/businesses/${businessId}`, payload)

    return data.data
  },

  async settings() {
    const { data } = await api.get('/business/settings')

    return data.data
  },

  async updateSettings(payload) {
    const { data } = await api.put('/business/settings', payload)

    return data.data
  },

  async dashboard() {
    const { data } = await api.get('/business/dashboard')

    return data.data
  },

  // Products -----------------------------------------------------------
  async products(params = {}) {
    const { data } = await api.get('/business/products', { params })

    return data
  },

  async createProduct(payload) {
    const { data } = await api.post('/business/products', payload)

    return data.data
  },

  async updateProduct(id, payload) {
    const { data } = await api.put(`/business/products/${id}`, payload)

    return data.data
  },

  async deleteProduct(id) {
    const { data } = await api.delete(`/business/products/${id}`)

    return data
  },

  // Customers ----------------------------------------------------------
  async customers(params = {}) {
    const { data } = await api.get('/business/customers', { params })

    return data
  },

  async createCustomer(payload) {
    const { data } = await api.post('/business/customers', payload)

    return data.data
  },

  async updateCustomer(id, payload) {
    const { data } = await api.put(`/business/customers/${id}`, payload)

    return data.data
  },

  async deleteCustomer(id) {
    const { data } = await api.delete(`/business/customers/${id}`)

    return data
  },

  // Orders -------------------------------------------------------------
  async orders(params = {}) {
    const { data } = await api.get('/business/orders', { params })

    return data
  },

  async order(id) {
    const { data } = await api.get(`/business/orders/${id}`)

    return data.data
  },

  async createOrder(payload) {
    const { data } = await api.post('/business/orders', payload)

    return data.data
  },

  async updateOrder(id, payload) {
    const { data } = await api.put(`/business/orders/${id}`, payload)

    return data.data
  },

  async deleteOrder(id) {
    const { data } = await api.delete(`/business/orders/${id}`)

    return data
  },

  // Invoices -----------------------------------------------------------
  async invoices(params = {}) {
    const { data } = await api.get('/business/invoices', { params })

    return data
  },

  async createInvoice(payload) {
    const { data } = await api.post('/business/invoices', payload)

    return data.data
  },

  async updateInvoice(id, payload) {
    const { data } = await api.put(`/business/invoices/${id}`, payload)

    return data.data
  },

  async deleteInvoice(id) {
    const { data } = await api.delete(`/business/invoices/${id}`)

    return data
  },

  invoicePdfUrl(id) {
    return `${api.defaults.baseURL}/business/invoices/${id}/pdf`
  },

  // Team ---------------------------------------------------------------
  async team() {
    const { data } = await api.get('/business/team')

    return data
  },

  async inviteMember(payload) {
    const { data } = await api.post('/business/team', payload)

    return data.data
  },

  async updateMember(id, payload) {
    const { data } = await api.put(`/business/team/${id}`, payload)

    return data.data
  },

  async removeMember(id) {
    const { data } = await api.delete(`/business/team/${id}`)

    return data
  },

  // Branches -----------------------------------------------------------
  async branches(params = {}) {
    const { data } = await api.get('/business/branches', { params })

    return data
  },

  async createBranch(payload) {
    const { data } = await api.post('/business/branches', payload)

    return data.data
  },

  async updateBranch(id, payload) {
    const { data } = await api.put(`/business/branches/${id}`, payload)

    return data.data
  },

  async deleteBranch(id) {
    const { data } = await api.delete(`/business/branches/${id}`)

    return data
  },

  // Reports ------------------------------------------------------------
  async salesReport(params = {}) {
    const { data } = await api.get('/business/reports/sales', { params })

    return data.data
  },

  async profitReport(params = {}) {
    const { data } = await api.get('/business/reports/profit', { params })

    return data.data
  },

  // Billing ------------------------------------------------------------
  async billing() {
    const { data } = await api.get('/business/billing')

    return data.data
  },

  async checkout(payload) {
    const { data } = await api.post('/business/billing/checkout', payload)

    return data.data
  },

  async confirmCheckout(reference) {
    const { data } = await api.post('/business/billing/confirm', { reference })

    return data.data
  },

  async billingPortal(payload) {
    const { data } = await api.post('/business/billing/portal', payload)

    return data.data
  },

  async billingInvoices(params = {}) {
    const { data } = await api.get('/business/billing/invoices', { params })

    return data
  },

  // Exports ------------------------------------------------------------
  async exportInfo() {
    const { data } = await api.get('/business/exports')

    return data.data
  },

  // API tokens ---------------------------------------------------------
  async apiTokens() {
    const { data } = await api.get('/business/api-tokens')

    return data
  },

  async createApiToken(payload) {
    const { data } = await api.post('/business/api-tokens', payload)

    return data
  },

  async revokeApiToken(id) {
    const { data } = await api.delete(`/business/api-tokens/${id}`)

    return data
  },
}

export default businessService
