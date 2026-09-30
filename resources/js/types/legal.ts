export interface LegalInfo {
    version: string;
    updated_at: string;
    product_name: string;
    company_name: string | null;
    company_nit: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    phone: string | null;
    privacy_email: string | null;
    support_email: string | null;
    website: string | null;
}
