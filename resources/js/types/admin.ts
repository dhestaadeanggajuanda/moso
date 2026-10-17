export type Option<T = string> = {
    value: T;
    label: string;
};

export type StatusValue = {
    value: string;
    label: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/**
 * Bentuk JSON LengthAwarePaginator Laravel.
 */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type CategoryOption = {
    id: number;
    name: string;
};

export type MenuNode = {
    id: number;
    label: string;
    href: string | null;
    is_active: boolean;
    open_in_new_tab: boolean;
    sort_order: number;
    children: MenuNode[];
};
