import axios from 'axios'

// Tidak ada alamat API yang ditulis dalam komponen Vue.
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('uts_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

export default api
