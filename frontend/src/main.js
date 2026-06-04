import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { initReverb } from '@/realtime/reverb.js'

import App from './App.vue'
import router from './router'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

initReverb({ token: localStorage.getItem('auth_token') })