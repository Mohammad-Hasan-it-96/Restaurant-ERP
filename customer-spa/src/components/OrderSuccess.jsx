import { CheckIcon } from './Icons';
import { createT } from '../i18n';

export default function OrderSuccess({ orderNumber, onClose, isRtl }) {
  const t = createT(isRtl);
  return (
    <div className="success-overlay">
      <div className="success-card">
        <div className="success-icon">
          <CheckIcon size={40} />
        </div>
        <h2 className="success-title">{t('orderSuccess')}</h2>
        <p className="success-message">{t('orderSuccessMsg')}</p>
        <div className="success-order-number">#{orderNumber}</div>
        <button className="btn-continue" onClick={onClose}>
          {t('continueBrowsing')}
        </button>
      </div>
    </div>
  );
}
