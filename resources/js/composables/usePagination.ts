import { ref, computed, Ref } from 'vue';

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export const usePagination = (initialData?: PaginationData) => {
    const meta = ref<PaginationData>(
        initialData || {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        }
    );

    const currentPage = computed(() => meta.value.current_page);
    const lastPage = computed(() => meta.value.last_page);
    const perPage = computed(() => meta.value.per_page);
    const total = computed(() => meta.value.total);

    const hasPreviousPage = computed(() => currentPage.value > 1);
    const hasNextPage = computed(() => currentPage.value < lastPage.value);

    const previousPageNumber = computed(() => {
        return hasPreviousPage.value ? currentPage.value - 1 : null;
    });

    const nextPageNumber = computed(() => {
        return hasNextPage.value ? currentPage.value + 1 : null;
    });

    const pageNumbers = computed(() => {
        const pages: number[] = [];
        const startPage = Math.max(1, currentPage.value - 2);
        const endPage = Math.min(lastPage.value, currentPage.value + 2);

        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }

        return pages;
    });

    const setMeta = (newMeta: PaginationData) => {
        meta.value = newMeta;
    };

    const updatePage = (page: number) => {
        meta.value.current_page = page;
    };

    const goToFirstPage = () => {
        updatePage(1);
    };

    const goToLastPage = () => {
        updatePage(lastPage.value);
    };

    const goToNextPage = () => {
        if (hasNextPage.value) {
            updatePage(currentPage.value + 1);
        }
    };

    const goToPreviousPage = () => {
        if (hasPreviousPage.value) {
            updatePage(currentPage.value - 1);
        }
    };

    return {
        meta,
        currentPage,
        lastPage,
        perPage,
        total,
        hasPreviousPage,
        hasNextPage,
        previousPageNumber,
        nextPageNumber,
        pageNumbers,
        setMeta,
        updatePage,
        goToFirstPage,
        goToLastPage,
        goToNextPage,
        goToPreviousPage,
    };
};
