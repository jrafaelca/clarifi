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
                title: 'Espacio de trabajo',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Espacio de trabajo" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Espacio de trabajo"
            description="Tu espacio actual de ClariFi y su moneda operativa"
        />

        <Card>
            <CardHeader>
                <CardTitle>{{ workspace.name }}</CardTitle>
                <CardDescription>
                    El MVP usa un unico espacio personal con una sola moneda
                    operativa.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="font-medium">Modo</span>
                    <Badge variant="secondary">
                        {{ workspace.isPersonal ? 'Espacio personal' : 'Espacio compartido' }}
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
                            Moneda
                        </p>
                        <p class="mt-1 font-medium">{{ workspace.currency }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
