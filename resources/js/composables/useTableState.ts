import { ref, computed, Ref } from 'vue';

interface TableState {
    page: number;
    perPage: number;
    search: string;
    sortBy: string | null;
    sortDir: 'asc' | 'desc';
}

export const useTableState = (initialPerPage = 10) => {
    const page = ref(1);
    const perPage = ref(initialPerPage);
    const search = ref('');
    const sortBy = ref<string | null>(null);
    const sortDir = ref<'asc' | 'desc'>('asc');

    const state = computed<TableState>(() => ({
        page: page.value,
        perPage: perPage.value,
        search: search.value,
        sortBy: sortBy.value,
        sortDir: sortDir.value,
    }));

    const resetPagination = () => {
        page.value = 1;
    };

    const goToPage = (newPage: number) => {
        page.value = newPage;
    };

    const setPerPage = (newPerPage: number) => {
        perPage.value = newPerPage;
        resetPagination();
    };

    const setSearch = (newSearch: string) => {
        search.value = newSearch;
        resetPagination();
    };

    const setSorting = (column: string) => {
        if (sortBy.value === column) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = column;
            sortDir.value = 'asc';
        }
    };

    const reset = () => {
        page.value = 1;
        perPage.value = initialPerPage;
        search.value = '';
        sortBy.value = null;
        sortDir.value = 'asc';
    };

    return {
        page,
        perPage,
        search,
        sortBy,
        sortDir,
        state,
        resetPagination,
        goToPage,
        setPerPage,
        setSearch,
        setSorting,
        reset,
    };
};
