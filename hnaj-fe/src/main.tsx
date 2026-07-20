import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'
import { PlaceDetailPage } from './PlaceDetailPage.tsx'

const placeSlug = window.location.pathname.match(/^\/places\/([^/]+)\/?$/)?.[1]

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    {placeSlug ? <PlaceDetailPage slug={placeSlug} /> : <App />}
  </StrictMode>,
)
