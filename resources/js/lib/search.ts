export const normalizeForSearch = (value: unknown): string => {
    if (value === null || value === undefined) return '';

    return String(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
};

export const includesNormalized = (
    source: unknown,
    query: unknown,
): boolean => {
    const normalizedQuery = normalizeForSearch(query);
    if (!normalizedQuery) return true;

    return normalizeForSearch(source).includes(normalizedQuery);
};

