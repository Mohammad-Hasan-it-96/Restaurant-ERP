import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './index.css';
import App from './App.jsx';

// Restore direction preference
const savedDir = localStorage.getItem('spa_dir');
if (savedDir) {
  document.documentElement.dir = savedDir;
  document.documentElement.lang = savedDir === 'rtl' ? 'ar' : 'en';
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>
);

