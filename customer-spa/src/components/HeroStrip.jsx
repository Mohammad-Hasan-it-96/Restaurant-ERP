import { PhoneIcon } from './Icons';
import { useI18n } from '../i18n';

/**
 * Builds a short label for today's opening hours from the `opening_hours` object.
 */
function getTodayHoursLabel(openingHours, t) {
  const days = [
    'sunday',
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
  ];
  const today = openingHours[days[new Date().getDay()]];
  if (!today) return t('hoursNotSet');
  if (!today.is_open) return t('closedToday');
  if (!today.from || !today.to) return t('openToday');
  return `${today.from} – ${today.to}`;
}

/**
 * Slim status bar: open/closed, today's hours, and phone as `tel:`.
 */
export default function HeroStrip({ settings }) {
  const { t } = useI18n();
  const s = settings || {};
  const isOpen = !!s.is_open_now && s.is_accepting_orders !== false;
  const hoursLabel = getTodayHoursLabel(s.opening_hours || {}, t);
  const phone = String(s.restaurant_phone || '').trim();
  const telHref = phone ? `tel:${phone.replace(/[^\d+]/g, '')}` : '';

  return (
    <div className="info-bar-wrap">
      <div className="info-bar-scroll" role="region" aria-label={t('todayHours')}>
        <span
          className={`info-bar-pill open-badge ${isOpen ? 'open' : 'closed'}`}
        >
          <span className="status-dot" aria-hidden />
          {isOpen ? t('openNow') : t('closedNow')}
        </span>
        <span className="info-bar-pill info-bar-hours">{hoursLabel}</span>
        {phone ? (
          <a
            className="info-bar-pill info-bar-phone"
            href={telHref || `tel:${phone}`}
          >
            <PhoneIcon size={14} aria-hidden />
            {phone}
          </a>
        ) : null}
      </div>
    </div>
  );
}
