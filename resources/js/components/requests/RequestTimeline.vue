<script setup lang="ts">
import StatusBadge from '@/components/app/StatusBadge.vue';
import { formatRequestDate  } from './utils';
import type {RequestApproval} from './utils';

defineProps<{
    approvals: RequestApproval[];
}>();
</script>

<template>
    <ol v-if="approvals.length" class="relative space-y-4 border-l pl-5">
        <li v-for="approval in approvals" :key="approval.id" class="relative">
            <span
                class="absolute top-1.5 -left-[25px] size-2.5 rounded-full border-2 border-card"
                :class="
                    approval.status === 'aprobado'
                        ? 'bg-success'
                        : approval.status === 'rechazado'
                          ? 'bg-destructive'
                          : 'bg-muted-foreground'
                "
                aria-hidden="true"
            />
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="approval.status" />
                <span class="text-sm font-medium">{{
                    approval.approver_name
                }}</span>
                <span class="text-xs text-muted-foreground">
                    {{ formatRequestDate(approval.decided_at, true) }}
                </span>
            </div>
            <p
                v-if="approval.comment"
                class="mt-1.5 text-sm whitespace-pre-wrap text-muted-foreground"
            >
                {{ approval.comment }}
            </p>
        </li>
    </ol>
</template>
