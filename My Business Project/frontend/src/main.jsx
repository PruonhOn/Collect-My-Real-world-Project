import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { Toaster } from 'sonner'
import App from './App.jsx'
import './index.css'

const appName = import.meta.env.VITE_APP_NAME || 'My Business'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <BrowserRouter>
      <Toaster richColors position="top-right" toastOptions={{ duration: 4000 }} />
      <App appName={appName} />
    </BrowserRouter>
  </StrictMode>,
)
