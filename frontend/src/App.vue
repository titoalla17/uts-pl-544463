<script setup>
import { ref, computed, onMounted } from 'vue'
import api from './services/api'

const token = ref(sessionStorage.getItem('uts_token') || '')
const userName = ref(sessionStorage.getItem('uts_user') || '')
const credentials = ref({ email: '', password: '' })
const films = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const notice = ref('')
const editingId = ref(null)
const search = ref('')
const sortBy = ref('ditonton')
const onlyFavorites = ref(false)
const thisYear = new Date().getFullYear()
const today = new Date().toISOString().slice(0, 10)
const emptyForm = () => ({
  judul: '', sutradara: '', tahun_rilis: thisYear, genre: '', rating: null,
  tanggal_ditonton: today, ulasan: '', favorit: false,
})
const form = ref(emptyForm())

const visibleFilms = computed(() => {
  const q = search.value.toLowerCase()
  const list = films.value.filter((f) =>
    (!onlyFavorites.value || f.favorit) &&
    [f.judul, f.sutradara, f.genre].some((v) => v.toLowerCase().includes(q)))
  const sorters = {
    ditonton: (a, b) => (b.tanggal_ditonton || '').localeCompare(a.tanggal_ditonton || ''),
    rating: (a, b) => (b.rating ?? -1) - (a.rating ?? -1),
    rilis: (a, b) => b.tahun_rilis - a.tahun_rilis,
    judul: (a, b) => a.judul.localeCompare(b.judul),
  }
  return [...list].sort(sorters[sortBy.value])
})
const stats = computed(() => {
  const rated = films.value.filter((f) => f.rating != null)
  const avg = rated.length ? rated.reduce((sum, f) => sum + f.rating, 0) / rated.length : 0
  return {
    total: films.value.length,
    thisYear: films.value.filter((f) => f.tanggal_ditonton?.startsWith(String(thisYear))).length,
    average: rated.length ? avg.toFixed(1) : '–',
    favorites: films.value.filter((f) => f.favorit).length,
  }
})

function starsOf(rating) {
  if (rating == null) return ''
  return '★'.repeat(Math.floor(rating)) + (rating % 1 ? '½' : '')
}
// Warna poster placeholder diturunkan dari judul agar tiap film punya identitas visual.
function posterStyle(film) {
  let hash = 0
  for (const ch of film.judul) hash = (hash * 31 + ch.charCodeAt(0)) % 360
  return { background: `linear-gradient(160deg, hsl(${hash} 45% 32%), hsl(${(hash + 40) % 360} 55% 14%))` }
}
function setRating(value) {
  form.value.rating = form.value.rating === value ? null : value
}
function onStarClick(event, star) {
  const { left, width } = event.currentTarget.getBoundingClientRect()
  setRating(event.clientX - left < width / 2 ? star - 0.5 : star)
}
function starFill(star) {
  const r = form.value.rating || 0
  if (r >= star) return 'full'
  if (r >= star - 0.5) return 'half'
  return ''
}

function getError(err) {
  if (err.response?.status === 422) {
    const validation = err.response.data?.errors || {}
    return Object.values(validation).flat().join(' ')
  }
  return err.response?.data?.message || 'Permintaan gagal. Periksa koneksi ke server.'
}
function handleUnauthorized(err) {
  if (err.response?.status === 401 && token.value) {
    clearSession()
    error.value = 'Sesi tidak berlaku. Silakan login kembali.'
    return true
  }
  return false
}
function clearSession() {
  sessionStorage.removeItem('uts_token')
  sessionStorage.removeItem('uts_user')
  token.value = ''
  userName.value = ''
  films.value = []
}
async function login() {
  loading.value = true
  error.value = ''
  notice.value = ''
  try {
    const { data } = await api.post('/login', credentials.value)
    sessionStorage.setItem('uts_token', data.token)
    sessionStorage.setItem('uts_user', data.user.name)
    token.value = data.token
    userName.value = data.user.name
    credentials.value.password = ''
    await loadFilms()
  } catch (err) {
    error.value = getError(err)
  } finally {
    loading.value = false
  }
}
async function logout() {
  error.value = ''
  try {
    await api.post('/logout')
  } catch (err) {
    if (err.response?.status !== 401) error.value = getError(err)
  } finally {
    clearSession()
  }
}
async function loadFilms() {
  loading.value = true
  try {
    const { data } = await api.get('/films')
    films.value = data.data
  } catch (err) {
    if (!handleUnauthorized(err)) error.value = getError(err)
  } finally {
    loading.value = false
  }
}
function startEdit(film) {
  editingId.value = film.id
  form.value = {
    judul: film.judul, sutradara: film.sutradara, tahun_rilis: film.tahun_rilis,
    genre: film.genre, rating: film.rating, tanggal_ditonton: film.tanggal_ditonton || '',
    ulasan: film.ulasan || '', favorit: film.favorit,
  }
  notice.value = ''
  error.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
function cancelEdit() {
  editingId.value = null
  form.value = emptyForm()
}
async function saveFilm() {
  saving.value = true
  error.value = ''
  notice.value = ''
  const payload = {
    ...form.value,
    tahun_rilis: Number(form.value.tahun_rilis),
    tanggal_ditonton: form.value.tanggal_ditonton || null,
    ulasan: form.value.ulasan || null,
  }
  try {
    if (editingId.value) {
      await api.put(`/films/${editingId.value}`, payload)
      notice.value = 'Log film berhasil diperbarui.'
    } else {
      await api.post('/films', payload)
      notice.value = 'Film berhasil dicatat ke diary.'
    }
    cancelEdit()
    await loadFilms()
  } catch (err) {
    if (!handleUnauthorized(err)) error.value = getError(err)
  } finally {
    saving.value = false
  }
}
async function toggleFavorite(film) {
  error.value = ''
  try {
    await api.put(`/films/${film.id}`, { ...film, favorit: !film.favorit })
    film.favorit = !film.favorit
  } catch (err) {
    if (!handleUnauthorized(err)) error.value = getError(err)
  }
}
async function removeFilm(film) {
  if (!window.confirm(`Hapus "${film.judul}" dari diary?`)) return
  error.value = ''
  notice.value = ''
  try {
    await api.delete(`/films/${film.id}`)
    notice.value = 'Film berhasil dihapus.'
    if (editingId.value === film.id) cancelEdit()
    await loadFilms()
  } catch (err) {
    if (!handleUnauthorized(err)) error.value = getError(err)
  }
}
onMounted(() => { if (token.value) loadFilms() })
</script>

<template>
  <div class="shell">
    <header class="brandbar">
      <div class="brand"><span class="dots" aria-hidden="true"><i></i><i></i><i></i></span><span>Film<span class="brandAccent">Ku</span></span></div>
      <span class="tag">UTS · Laravel + Vue</span>
    </header>

    <main v-if="!token" class="loginWrap">
      <section class="loginCard">
        <div class="pill">Diary film pribadi</div>
        <h1>Catat setiap film yang kamu tonton.</h1>
        <p class="muted">Beri rating, tulis ulasan, dan tandai favoritmu. Masuk menggunakan akun yang disiapkan pada berkas <code>.env</code> di Docker Compose.</p>
        <form @submit.prevent="login" class="formStack">
          <label>Email<input v-model.trim="credentials.email" type="email" required autocomplete="username" placeholder="admin@example.test" /></label>
          <label>Password<input v-model="credentials.password" type="password" required autocomplete="current-password" placeholder="Password akun demo" /></label>
          <button type="submit" class="primary" :disabled="loading">{{ loading ? 'Memproses...' : 'Masuk ke Diary →' }}</button>
        </form>
        <p v-if="error" role="alert" class="alert">{{ error }}</p>
      </section>
      <aside class="heroCard" aria-hidden="true">
        <div class="heroPosters"><span></span><span></span><span></span></div>
        <p>Track films you've watched.<br />Save those you want to see.</p>
        <div class="heroStars">★★★★½</div>
      </aside>
    </main>

    <main v-else class="dashboard">
      <div class="topline">
        <div><span class="eyebrow">DIARY</span><h1>Film yang sudah ditonton</h1><p class="muted">Selamat datang, {{ userName }}. Data tersimpan di database MySQL.</p></div>
        <button class="secondary" @click="logout">Keluar ↗</button>
      </div>
      <div class="stats">
        <div class="stat"><span>Total film</span><strong>{{ stats.total }}</strong></div>
        <div class="stat"><span>Ditonton {{ thisYear }}</span><strong>{{ stats.thisYear }}</strong></div>
        <div class="stat"><span>Rata-rata rating</span><strong>{{ stats.average }}<small v-if="stats.average !== '–'"> ★</small></strong></div>
        <div class="stat"><span>Favorit</span><strong>{{ stats.favorites }}<small> ♥</small></strong></div>
      </div>
      <p v-if="error" role="alert" class="alert">{{ error }}</p>
      <p v-if="notice" role="status" class="success">{{ notice }}</p>

      <div class="contentGrid">
        <section class="panel formPanel">
          <div class="sectionTitle"><span class="eyebrow">{{ editingId ? 'EDIT LOG' : 'LOG FILM' }}</span><h2>{{ editingId ? 'Ubah catatan film' : 'Saya baru menonton…' }}</h2></div>
          <form @submit.prevent="saveFilm" class="formStack">
            <label>Judul film<input v-model.trim="form.judul" required maxlength="150" placeholder="Contoh: Parasite" /></label>
            <label>Sutradara<input v-model.trim="form.sutradara" required maxlength="100" placeholder="Contoh: Bong Joon-ho" /></label>
            <div class="row2">
              <label>Tahun rilis<input v-model.number="form.tahun_rilis" type="number" min="1888" :max="thisYear + 5" required /></label>
              <label>Genre<input v-model.trim="form.genre" required maxlength="60" placeholder="Thriller" list="genres" /></label>
            </div>
            <datalist id="genres"><option v-for="g in ['Aksi','Animasi','Komedi','Drama','Horor','Romantis','Sci-Fi','Thriller','Dokumenter','Fantasi']" :key="g" :value="g" /></datalist>
            <label>Tanggal ditonton<input v-model="form.tanggal_ditonton" type="date" :max="today" /></label>
            <div class="ratingRow">
              <div>
                <span class="fieldLabel">Rating {{ form.rating ? `(${form.rating})` : '' }}</span>
                <div class="starInput" role="radiogroup" aria-label="Rating">
                  <button v-for="star in 5" :key="star" type="button" class="star" :class="starFill(star)"
                    :aria-label="`${star} bintang`" @click="onStarClick($event, star)">★</button>
                </div>
              </div>
              <button type="button" class="heartToggle" :class="{ on: form.favorit }" :aria-pressed="form.favorit" @click="form.favorit = !form.favorit">♥<span>Favorit</span></button>
            </div>
            <label>Ulasan (opsional)<textarea v-model="form.ulasan" maxlength="2000" rows="4" placeholder="Tulis kesanmu tentang film ini..."></textarea></label>
            <button type="submit" class="primary" :disabled="saving">{{ saving ? 'Menyimpan...' : editingId ? 'Simpan Perubahan' : '+ Simpan ke Diary' }}</button>
            <button v-if="editingId" type="button" class="textButton" @click="cancelEdit">Batal mengubah</button>
          </form>
        </section>

        <section class="panel dataPanel">
          <div class="sectionTitle listTitle">
            <div><span class="eyebrow">KOLEKSI</span><h2>{{ visibleFilms.length }} film</h2></div>
            <button class="secondary" @click="loadFilms">↻ Muat ulang</button>
          </div>
          <div class="toolbar">
            <input v-model="search" type="search" placeholder="Cari judul, sutradara, genre..." aria-label="Cari film" />
            <select v-model="sortBy" aria-label="Urutkan">
              <option value="ditonton">Terakhir ditonton</option>
              <option value="rating">Rating tertinggi</option>
              <option value="rilis">Tahun rilis</option>
              <option value="judul">Judul A–Z</option>
            </select>
            <label class="check"><input v-model="onlyFavorites" type="checkbox" /> ♥ saja</label>
          </div>
          <div v-if="loading" class="empty">Memuat data...</div>
          <div v-else-if="films.length === 0" class="empty"><span>🎬</span><h3>Diary masih kosong</h3><p>Catat film pertama yang kamu tonton melalui formulir.</p></div>
          <div v-else-if="visibleFilms.length === 0" class="empty"><h3>Tidak ada film yang cocok</h3><p>Coba kata kunci atau filter lain.</p></div>
          <div v-else class="filmGrid">
            <article v-for="film in visibleFilms" :key="film.id" class="filmCard" :class="{ editing: editingId === film.id }">
              <div class="poster" :style="posterStyle(film)">
                <span class="posterTitle">{{ film.judul }}</span>
                <span class="posterYear">{{ film.tahun_rilis }}</span>
                <button class="favButton" :class="{ on: film.favorit }" :aria-label="film.favorit ? 'Hapus dari favorit' : 'Tandai favorit'" @click="toggleFavorite(film)">♥</button>
              </div>
              <div class="filmInfo">
                <h3 :title="film.judul">{{ film.judul }} <span class="year">{{ film.tahun_rilis }}</span></h3>
                <p>{{ film.sutradara }} · {{ film.genre }}</p>
                <div class="meta">
                  <span class="stars">{{ starsOf(film.rating) || 'Belum dirating' }}</span>
                  <small>{{ film.tanggal_ditonton || 'Tanggal belum dicatat' }}</small>
                </div>
                <p v-if="film.ulasan" class="review">“{{ film.ulasan }}”</p>
              </div>
              <div class="itemActions"><button @click="startEdit(film)" class="editButton">Ubah</button><button @click="removeFilm(film)" class="deleteButton">Hapus</button></div>
            </article>
          </div>
        </section>
      </div>
    </main>
    <footer>FilmKu · UTS Pemrograman Lanjut · Demo edukasi</footer>
  </div>
</template>
