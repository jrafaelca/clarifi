<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit } from '@/routes/workspace';
import type { Team } from '@/types';

type Props = {
    workspace: Team;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Workspace',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Workspace" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Workspace"
            description="Your current ClariFi workspace and operating currency"
        />

        <Card>
            <CardHeader>
                <CardTitle>{{ workspace.name }}</CardTitle>
                <CardDescription>
                    The MVP uses a single personal workspace with one operating
                    currency.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="font-medium">Mode</span>
                    <Badge variant="secondary">
                        {{ workspace.isPersonal ? 'Personal workspace' : 'Shared workspace' }}
                    </Badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Slug
                        </p>
                        <p class="mt-1 font-medium">{{ workspace.slug }}</p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Currency
                        </p>
                        <p class="mt-1 font-medium">{{ workspace.currency }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
