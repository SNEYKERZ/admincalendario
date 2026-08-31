import { ref, Ref, computed } from 'vue';

type ModalMode = 'view' | 'create' | 'edit' | 'delete';

export const useModalState = () => {
    const isOpen = ref(false);
    const mode = ref<ModalMode>('view');
    const selectedId = ref<number | null>(null);
    const selectedItem = ref<any>(null);

    const isModeView = computed(() => mode.value === 'view');
    const isModeCreate = computed(() => mode.value === 'create');
    const isModeEdit = computed(() => mode.value === 'edit');
    const isModeDelete = computed(() => mode.value === 'delete');

    const open = (modalMode: ModalMode = 'view', item: any = null) => {
        mode.value = modalMode;
        selectedItem.value = item;
        if (item?.id) {
            selectedId.value = item.id;
        }
        isOpen.value = true;
    };

    const close = () => {
        isOpen.value = false;
        setTimeout(() => {
            mode.value = 'view';
            selectedId.value = null;
            selectedItem.value = null;
        }, 300);
    };

    const openView = (item: any) => {
        open('view', item);
    };

    const openCreate = () => {
        open('create', null);
    };

    const openEdit = (item: any) => {
        open('edit', item);
    };

    const openDelete = (item: any) => {
        open('delete', item);
    };

    return {
        isOpen,
        mode,
        selectedId,
        selectedItem,
        isModeView,
        isModeCreate,
        isModeEdit,
        isModeDelete,
        open,
        close,
        openView,
        openCreate,
        openEdit,
        openDelete,
    };
};
