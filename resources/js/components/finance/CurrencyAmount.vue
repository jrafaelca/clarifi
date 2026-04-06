<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    amount: number | string;
    currency: string;
};

const props = defineProps<Props>();

const formattedAmount = computed(() =>
    new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: props.currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(props.amount ?? 0)),
);
</script>

<template>
    <span>{{ formattedAmount }}</span>
</template>
