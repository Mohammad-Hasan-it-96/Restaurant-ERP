import { CheckIcon } from './Icons';
import { useI18n } from '../i18n';

export default function OrderSuccess({ orderNumber, onClose }) {
  const { t } = useI18n();
  return (
    <div className="success-overlay">
      <div className="success-card">
        <div className="success-icon">
          <CheckIcon size={40} />
        </div>
        <h2 className="success-title">{t('orderSuccess')}</h2>
        <p className="success-message">{t('orderSuccessMsg')}</p>
        <div className="success-order-number">
          {t('orderNumberDisplay')}: {orderNumber}
        </div>
        <button type="button" className="btn-continue" onClick={onClose}>
          {t('continueBrowsing')}
        </button>
      </div>
    </div>
  );
}
