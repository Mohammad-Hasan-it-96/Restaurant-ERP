import {
  createContext,
  createElement,
  useCallback,
  useContext,
  useLayoutEffect,
  useMemo,
  useState,
} from 'react';

/** All UI strings: Arabic and English */
export const STRINGS = {
  ar: {
    documentTitle: 'قائمة المطعم',
    menu: 'القائمة',
    cart: 'السلة',
    checkout: 'إتمام الطلب',
    search: 'ابحث عن وجبة...',
    all: 'الكل',
    featured: 'مميز ⭐',
    openNow: 'يستقبل الطلبات الآن',
    closedNow: 'مغلق حالياً',
    todayHours: 'ساعات اليوم',
    hoursNotSet: 'غير محدد',
    closedToday: 'مغلق اليوم',
    openToday: 'مفتوح اليوم',
    phone: 'اتصال',
    whatsapp: 'واتساب',
    noDescription: 'لا يوجد وصف',
    unavailable: 'غير متاح حالياً',
    addToCart: 'أضف للسلة',
    details: 'التفاصيل',
    yourOrder: 'طلبك',
    emptyCart: 'السلة فارغة',
    emptyCartHint: 'أضف وجبات من القائمة',
    subtotal: 'الإجمالي',
    proceedCheckout: 'متابعة الطلب ←',
    orderDetails: 'تفاصيل الطلب',
    fullName: 'الاسم الكامل',
    phoneNumber: 'رقم الهاتف',
    orderType: 'نوع الطلب',
    table: 'على الطاولة',
    delivery: 'توصيل',
    takeaway: 'استلام',
    tableNumber: 'رقم الطاولة',
    address: 'العنوان',
    previousAddressPrefix: 'العنوان السابق:',
    useThisAddress: '← استخدمه',
    deliveryType: 'نوع التوصيل',
    immediate: 'فوري',
    scheduled: 'مجدول',
    scheduledAt: 'وقت التوصيل',
    deliveryZone: 'منطقة التوصيل',
    selectZone: 'اختر المنطقة',
    noZones: 'لا توجد مناطق توصيل',
    notes: 'ملاحظات للمطبخ',
    notesPlaceholder: 'أي طلبات خاصة...',
    placeOrder: 'إرسال الطلب',
    sending: 'جاري الإرسال...',
    orderSuccess: '🎉 تم استلام طلبك!',
    orderSuccessMsg: 'سيتم التواصل معك قريباً',
    orderNumber: 'رقم الطلب',
    continueBrowsing: 'متابعة التصفح',
    required: 'مطلوب',
    reqName: 'الاسم مطلوب',
    reqPhone: 'رقم الهاتف مطلوب',
    reqTable: 'رقم الطاولة مطلوب',
    reqAddress: 'العنوان مطلوب',
    reqScheduled: 'وقت التسليم مطلوب',
    scheduleWindowError: 'وقت التسليم يجب أن يكون بعد {n} دقيقة على الأقل من الآن',
    noProducts: 'لا توجد منتجات',
    productUnavailableAlert: 'هذا المنتج غير متاح حالياً.',
    profile: 'حسابي',
    qty: 'الكمية',
    retry: 'إعادة المحاولة',
    failedLoad: 'حدث خطأ في تحميل البيانات.',
    lang: 'EN',
    notAvailableNow: 'غير متاح',
    clearCart: 'مسح السلة',
    remove: 'حذف',
    close: 'إغلاق',
    loadPartialError: 'تعذّر تحميل بعض البيانات.',
    toggleLanguageAria: 'تبديل اللغة',
    bellAria: 'الإشعارات',
    mainNavAria: 'التنقل الرئيسي',
    times: '×',
    orderNumberDisplay: 'رقم الطلب',
    orderUnknown: '---',
    myOrders: 'طلباتي',
    noOrdersYet: 'لا توجد طلبات محفوظة على هذا الجهاز.',
    loading: 'جاري التحميل…',
    orderLoadError: 'تعذر التحميل',
    orderTotal: 'الإجمالي',
    orderStatus_pending: 'قيد الانتظار',
    orderStatus_accepted: 'تم القبول',
    orderStatus_preparing: 'جاري التحضير',
    orderStatus_ready: 'جاهز',
    orderStatus_delivered: 'تم التوصيل',
    orderStatus_completed: 'مكتمل',
    orderStatus_cancelled: 'ملغى',
    orderStatus_cancelled_by_admin: 'ألغاه المطعم',
    orderStatus_cancelled_by_customer: 'ألغيته أنت',
    orderStatus_rejected: 'مرفوض',
    myAccount: 'حسابي',
    saveProfile: 'حفظ التغييرات',
    saving: 'جاري الحفظ...',
    profileSaved: '✓ تم حفظ البيانات بنجاح',
    ordersPage: 'طلباتي',
    orderDate: 'التاريخ',
    backToMenu: 'القائمة',
    orderItems: 'المنتجات',
    itemName: 'المنتج',
    itemQty: 'الكمية',
    itemPrice: 'السعر',
    itemTotal: 'الإجمالي',
    orderDetailTitle: 'تفاصيل الطلب',
    noItemsInOrder: 'لا توجد منتجات في هذا الطلب',
    // Order modification
    modifyOrder: 'تعديل الطلب',
    modifyOrderTitle: 'تعديل الطلب',
    modifyOrderConfirm: 'سيتم إلغاء الطلب الحالي وإنشاء طلب جديد بالتعديلات',
    orderStatus_modified: 'تم التعديل',
    modifySuccess: 'تم تعديل طلبك بنجاح',
    newOrderNumber: 'رقم الطلب الجديد',
    itemQtyMin: 'الكمية لا يمكن أن تكون صفر',
    removeItem: 'حذف',
    modifying: 'جاري التعديل...',
    confirmModify: 'تأكيد التعديل',
    addProduct: '+ إضافة منتج',
    searchProducts: 'ابحث عن منتج...',
    noProductsFound: 'لا توجد نتائج',
    loadingProducts: 'جاري تحميل المنتجات...',
    currentItems: 'المنتجات الحالية',
    addNewItems: 'إضافة منتجات',
    // Order cancellation
    cancelOrder: 'إلغاء الطلب',
    cancelOrderPrompt: 'هل أنت متأكد أنك تريد إلغاء هذا الطلب؟',
    cancelOrderConfirm: 'نعم، إلغاء',
    cancelOrderBack: 'لا، تراجع',
    cancelling: 'جاري الإلغاء...',
    cancelOrderSuccess: 'تم إلغاء طلبك بنجاح',
    cancelNotAllowed: 'لا يمكن إلغاء هذا الطلب',
    scheduleBeforeClose: 'يجب أن يكون وقت التوصيل قبل نهاية الدوام',
    scheduleToday: 'يجب أن يكون وقت التوصيل في نفس اليوم',
    closedTodayNoSchedule: 'المطعم مغلق اليوم، التوصيل المجدول غير متاح',
    noSlotsLeft: 'لا تتوفر مواعيد توصيل متاحة لهذا اليوم',
    todayHoursLabel: 'أوقات العمل اليوم:',
    selectWeight: 'اختر الوزن',
    weightRequired: 'يرجى اختيار الوزن',
    perKg: '/ كغ',
  },
  en: {
    documentTitle: 'Restaurant Menu',
    menu: 'Menu',
    cart: 'Cart',
    checkout: 'Checkout',
    search: 'Search food...',
    all: 'All',
    featured: 'Featured ⭐',
    openNow: 'Accepting Orders',
    closedNow: 'Closed',
    todayHours: "Today's Hours",
    hoursNotSet: 'Hours not set',
    closedToday: 'Closed Today',
    openToday: 'Open Today',
    phone: 'Call',
    whatsapp: 'WhatsApp',
    noDescription: 'No description',
    unavailable: 'Unavailable',
    addToCart: 'Add to Cart',
    details: 'Details',
    yourOrder: 'Your Order',
    emptyCart: 'Cart is empty',
    emptyCartHint: 'Add items from the menu',
    subtotal: 'Subtotal',
    proceedCheckout: 'Proceed to Checkout →',
    orderDetails: 'Order Details',
    fullName: 'Full Name',
    phoneNumber: 'Phone Number',
    orderType: 'Order Type',
    table: 'Dine In',
    delivery: 'Delivery',
    takeaway: 'Takeaway',
    tableNumber: 'Table Number',
    address: 'Address',
    previousAddressPrefix: 'Previous address:',
    useThisAddress: '← Use this',
    deliveryType: 'Delivery Type',
    immediate: 'Immediate',
    scheduled: 'Scheduled',
    scheduledAt: 'Scheduled Time',
    deliveryZone: 'Delivery Zone',
    selectZone: 'Select zone',
    noZones: 'No delivery zones',
    notes: 'Kitchen Notes',
    notesPlaceholder: 'Any special requests...',
    placeOrder: 'Place Order',
    sending: 'Placing order...',
    orderSuccess: '🎉 Order Received!',
    orderSuccessMsg: "We'll contact you shortly",
    orderNumber: 'Order #',
    continueBrowsing: 'Continue Browsing',
    required: 'Required',
    reqName: 'Name is required',
    reqPhone: 'Phone is required',
    reqTable: 'Table number is required',
    reqAddress: 'Address is required',
    reqScheduled: 'Scheduled time is required',
    scheduleWindowError: 'Scheduled time must be at least {n} minutes from now',
    noProducts: 'No products found',
    productUnavailableAlert: 'This product is not available right now.',
    profile: 'Profile',
    qty: 'Qty',
    retry: 'Retry',
    failedLoad: 'Failed to load data.',
    lang: 'ع',
    notAvailableNow: 'Unavailable',
    clearCart: 'Clear Cart',
    remove: 'Remove',
    close: 'Close',
    loadPartialError: 'Failed to load some data.',
    toggleLanguageAria: 'Switch language',
    bellAria: 'Notifications',
    mainNavAria: 'Main navigation',
    times: '×',
    orderNumberDisplay: 'Order number',
    orderUnknown: '---',
    myOrders: 'My orders',
    noOrdersYet: 'No saved orders on this device yet.',
    loading: 'Loading…',
    orderLoadError: 'Could not load',
    orderTotal: 'Total',
    orderStatus_pending: 'Pending',
    orderStatus_accepted: 'Accepted',
    orderStatus_preparing: 'Preparing',
    orderStatus_ready: 'Ready',
    orderStatus_delivered: 'Delivered',
    orderStatus_completed: 'Completed',
    orderStatus_cancelled: 'Cancelled',
    orderStatus_cancelled_by_admin: 'Cancelled by restaurant',
    orderStatus_cancelled_by_customer: 'Cancelled by you',
    orderStatus_rejected: 'Rejected',
    myAccount: 'My Account',
    saveProfile: 'Save Changes',
    saving: 'Saving...',
    profileSaved: '✓ Profile saved successfully',
    ordersPage: 'My Orders',
    orderDate: 'Date',
    backToMenu: 'Menu',
    orderItems: 'Items',
    itemName: 'Item',
    itemQty: 'Qty',
    itemPrice: 'Price',
    itemTotal: 'Total',
    orderDetailTitle: 'Order Details',
    noItemsInOrder: 'No items in this order',
    // Order modification
    modifyOrder: 'Modify Order',
    modifyOrderTitle: 'Modify Order',
    modifyOrderConfirm: 'The current order will be cancelled and a new one created with your changes',
    orderStatus_modified: 'Modified',
    modifySuccess: 'Your order was modified successfully',
    newOrderNumber: 'New Order #',
    itemQtyMin: 'Quantity cannot be zero',
    removeItem: 'Remove',
    modifying: 'Modifying...',
    confirmModify: 'Confirm Modification',
    addProduct: '+ Add Product',
    searchProducts: 'Search products...',
    noProductsFound: 'No results',
    loadingProducts: 'Loading products...',
    currentItems: 'Current Items',
    addNewItems: 'Add Items',
    // Order cancellation
    cancelOrder: 'Cancel Order',
    cancelOrderPrompt: 'Are you sure you want to cancel this order?',
    cancelOrderConfirm: 'Yes, Cancel',
    cancelOrderBack: 'No, Go Back',
    cancelling: 'Cancelling...',
    cancelOrderSuccess: 'Your order has been cancelled',
    cancelNotAllowed: 'This order cannot be cancelled',
    scheduleBeforeClose: 'Scheduled time must be before closing time',
    scheduleToday: 'Scheduled time must be for today',
    closedTodayNoSchedule: 'Restaurant is closed today, scheduled delivery unavailable',
    noSlotsLeft: 'No delivery slots available for today',
    todayHoursLabel: "Today's hours:",
    selectWeight: 'Select Weight',
    weightRequired: 'Please select a weight',
    perKg: '/ kg',
  },
};

const I18nContext = createContext(null);

/**
 * Resolves text direction from language code.
 */
function dirFromLang(lang) {
  return lang === 'ar' ? 'rtl' : 'ltr';
}

/**
 * Reads initial UI language from storage or the document.
 */
export function getInitialLang() {
  const stored = localStorage.getItem('spa_lang');
  if (stored === 'ar' || stored === 'en') return stored;
  const legacyDir = localStorage.getItem('spa_dir');
  if (legacyDir === 'rtl') return 'ar';
  if (legacyDir === 'ltr') return 'en';
  const html = (document.documentElement.lang || 'ar').toLowerCase();
  return html.startsWith('ar') ? 'ar' : 'en';
}

/**
 * Sets Laravel session locale via GET /lang/{locale} then reloads the app.
 */
export function switchLanguage(locale) {
  const next = locale === 'ar' || locale === 'en' ? locale : 'en';
  localStorage.setItem('spa_lang', next);
  window.location.assign(`/lang/${next}`);
}

/**
 * Provides `lang`, `dir`, and `t(key)` to the React tree.
 */
export function I18nProvider({ children }) {
  const [lang] = useState(getInitialLang);

  useLayoutEffect(() => {
    const dir = dirFromLang(lang);
    document.documentElement.lang = lang;
    document.documentElement.dir = dir;
    localStorage.setItem('spa_lang', lang);
    localStorage.setItem('spa_dir', dir);
  }, [lang]);

  const t = useCallback(
    (key) => STRINGS[lang]?.[key] ?? STRINGS.en[key] ?? key,
    [lang]
  );

  const value = useMemo(
    () => ({
      lang,
      dir: dirFromLang(lang),
      t,
    }),
    [lang, t]
  );

  return createElement(I18nContext.Provider, { value }, children);
}

/**
 * Access i18n context (must be inside I18nProvider).
 */
export function useI18n() {
  const ctx = useContext(I18nContext);
  if (!ctx) {
    throw new Error('useI18n must be used within I18nProvider');
  }
  return ctx;
}
