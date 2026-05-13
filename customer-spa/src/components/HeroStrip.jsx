import { PhoneIcon, WhatsAppIcon, ForkKnifeIcon } from './Icons';
import { createT } from '../i18n';

function getTodayHours(openingHours = {}) {
  const days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
  const today = openingHours[days[new Date().getDay()]];
  if (!today) return null;
  if (!today.is_open) return null;
  if (!today.from || !today.to) return null;
  return `${today.from} - ${today.to}`;
}

export default function HeroStrip({ settings, isRtl }) {
  const t = createT(isRtl);
  const s = settings || {};
  const name = s.restaurant_name || t('menu');
  const isOpen = !!s.is_open_now && s.is_accepting_orders !== false;
  const hours = getTodayHours(s.opening_hours || {});

  return (
    <div className="hero-strip">
      <div className="hero-strip-inner">
        {/* Logo */}
        {s.restaurant_logo ? (
          <img className="hero-logo-img" src={s.restaurant_logo} alt={name}
               onError={e => { e.target.style.display = 'none'; e.target.nextSibling.style.display = 'flex'; }} />
        ) : null}
        <div className="hero-logo-fallback" style={s.restaurant_logo ? { display: 'none' } : {}}>
          <ForkKnifeIcon />
        </div>

        {/* Info */}
        <div className="hero-info">
          <h1 className="hero-name">{name}</h1>
          <div className="hero-badges">
            <span className={`open-badge ${isOpen ? 'open' : 'closed'}`}>
              <span className="status-dot" />
              {isOpen ? t('openNow') : t('closedNow')}
            </span>
            {hours && <span className="hero-hours">{hours}</span>}
          </div>
          <div className="hero-links">
            {s.restaurant_phone && (
              <a className="hero-link" href={`tel:${s.restaurant_phone}`}>
                <PhoneIcon />{t('phone')}: {s.restaurant_phone}
              </a>
            )}
            {s.restaurant_whatsapp && (
              <a className="hero-link wa"
                 href={`https://wa.me/${s.restaurant_whatsapp.replace(/\D/g,'')}`}
                 target="_blank" rel="noopener noreferrer">
                <WhatsAppIcon />{t('whatsapp')}
              </a>
            )}
          </div>
        </div>
      </div>
      {s.delivery_note && (
        <p className="hero-delivery-note">{s.delivery_note}</p>
      )}
    </div>
  );
}
