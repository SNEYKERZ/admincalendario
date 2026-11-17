export interface SubscriptionPlan {
    id: number;
    name: string;
    duration_days: number;
    price_cop: number;
    price_usd: number;
    discount_percentage: number;
    original_price_cop: number | null;
    original_price_usd: number | null;
    is_active: boolean;
    display_order: number;
    description: string;
}

export interface SubscriptionSettings {
    conversion_rate: number;
    cop_reference_amount: number;
    usd_reference_amount: number;
    show_ads_days_before: number;
    enable_payments: boolean;
    payment_gateway: string | null;
}

export interface SystemAdmin {
    id: number;
    name: string;
    email: string;
    role: string;
    has_active_subscription: boolean;
    subscription_days_remaining: number;
    subscription_expires_at: string | null;
}

export interface AnnouncementAdmin {
    id: number;
    name: string;
    email: string;
    plan_name: string;
    expires_at: string;
    days_remaining: number;
}

export type AnnouncementType = 'expiring' | 'expired' | 'general';

export interface Announcement {
    id: number;
    title: string;
    message: string;
    type: AnnouncementType;
    days_before: number | null;
    is_active: boolean;
    created_at: string;
}

export type PlanForm = {
    name: string;
    duration_days: number;
    price_cop: number;
    price_usd: number;
    discount_percentage: number;
    original_price_cop: number | null;
    original_price_usd: number | null;
    is_active: boolean;
    display_order: number;
    description: string;
};

export type AnnouncementForm = {
    title: string;
    message: string;
    type: AnnouncementType;
    days_before: number | null;
    is_active: boolean;
};

export const formatCurrency = (amount: number, currency: 'COP' | 'USD') => {
    const safe = Number.isFinite(amount) ? amount : 0;
    if (currency === 'COP') {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0,
        }).format(safe);
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(safe);
};

export const announcementTypeLabel: Record<AnnouncementType, string> = {
    general: 'General',
    expiring: 'Por vencer',
    expired: 'Vencido',
};

/** Extrae el primer mensaje de error de validación de una respuesta 422. */
export const validationErrors = (e: any): Record<string, string> => {
    const errors = e?.response?.data?.errors ?? {};
    const out: Record<string, string> = {};
    Object.keys(errors).forEach((k) => {
        const v = errors[k];
        out[k] = Array.isArray(v) ? String(v[0]) : String(v);
    });
    return out;
};
