const NON_TESTABLE_TRIGGERS = ['trigger.chat'];

const INSTANT_TRIGGERS = ['trigger.manual', 'trigger.schedule'];

export const isTestableTrigger = (nodeType: string): boolean =>
    nodeType.startsWith('trigger.') && !NON_TESTABLE_TRIGGERS.includes(nodeType);

export const isListeningTrigger = (nodeType: string): boolean =>
    nodeType.startsWith('trigger.') &&
    !NON_TESTABLE_TRIGGERS.includes(nodeType) &&
    !INSTANT_TRIGGERS.includes(nodeType);

export const isScheduleTrigger = (nodeType: string): boolean => nodeType === 'trigger.schedule';

export const isWebhookTrigger = (nodeType: string): boolean => nodeType === 'trigger.webhook';

export const isTelegramTrigger = (nodeType: string): boolean => nodeType === 'trigger.telegram';

export const isGmailTrigger = (nodeType: string): boolean => nodeType === 'trigger.gmail';

export const isWooCommerceTrigger = (nodeType: string): boolean => nodeType === 'trigger.woocommerce';

export const isSheetsTrigger = (nodeType: string): boolean =>
    nodeType === 'trigger.google_sheets_row_added' ||
    nodeType === 'trigger.google_sheets_row_updated' ||
    nodeType === 'trigger.google_sheets_row_added_or_updated';

export const listeningTriggerLabel = (nodeType: string): string => {
    if (isWebhookTrigger(nodeType)) return 'در انتظار دریافت وب‌هوک...';
    if (isTelegramTrigger(nodeType)) return 'در انتظار پیام تلگرام...';
    if (isGmailTrigger(nodeType)) return 'در انتظار ایمیل جدید...';
    if (isWooCommerceTrigger(nodeType)) return 'در انتظار رویداد ووکامرس...';
    if (isSheetsTrigger(nodeType)) return 'در انتظار تغییر در شیت...';
    return 'در انتظار دریافت رویداد...';
};
