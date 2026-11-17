export interface AreaItem {
    id: number;
    name: string;
    description: string | null;
    color: string;
    display_order: number;
    is_active: boolean;
    employee_count: number;
    leader_ids?: number[];
    created_at: string;
}
