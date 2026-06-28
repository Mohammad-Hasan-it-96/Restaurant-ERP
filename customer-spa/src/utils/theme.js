/**
 * Applies brand theme tokens from the public settings payload
 * (GET /api/v1/settings/public → "theme", sourced from config/theme.php) onto
 * the document :root as CSS custom properties. The defaults baked into
 * index.css act as the fallback until/if settings load.
 */
const TOKEN_MAP = {
  primary: '--primary',
  primary_dark: '--primary-dark',
  primary_light: '--primary-light',
};

export const applyTheme = (theme) => {
  if (!theme || typeof theme !== 'object') return;
  const root = document.documentElement;
  Object.entries(TOKEN_MAP).forEach(([key, cssVar]) => {
    if (theme[key]) root.style.setProperty(cssVar, theme[key]);
  });
};
