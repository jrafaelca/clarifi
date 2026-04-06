<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    title: string;
    value: string;
    description?: string;
};

defineProps<Props>();
</script>

<template>
    <Card class="border-border/70">
        <CardHeader class="space-y-1">
            <CardDescription>{{ title }}</CardDescription>
            <CardTitle class="text-2xl">{{ value }}</CardTitle>
        </CardHeader>
        <CardContent v-if="description" class="pt-0 text-sm text-muted-foreground">
            {{ description }}
        </CardContent>
    </Card>
</template>
