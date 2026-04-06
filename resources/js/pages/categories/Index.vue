<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/categories';
import type { CategoryRecord, SelectOption, Team, WorkspaceSummary } from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    categories: CategoryRecord[];
    categoryTypes: SelectOption[];
};

defineProps<Props>();

const page = usePage();
const currentTeam = page.props.currentTeam as Team;

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Categorias',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Categorias" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Categorias"
            description="Las categorias del sistema son de solo lectura; tus categorias personalizadas viven en el espacio actual"
        />

        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <CardTitle>Crear categoria</CardTitle>
                    <CardDescription>
                        Usa categorias para que los presupuestos y los movimientos tengan contexto.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="store.form({ current_team: currentTeam.slug })"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="category-name">Nombre</Label>
                            <Input id="category-name" name="name" placeholder="Comer fuera" />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="category-type">Tipo</Label>
                            <select id="category-type" name="type" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="" disabled selected>Selecciona un tipo</option>
                                <option v-for="type in categoryTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="category-parent">Categoria padre</Label>
                            <select id="category-parent" name="parent_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">Sin padre</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="errors.parent_id" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="category-icon">Icono</Label>
                                <Input id="category-icon" name="icon" placeholder="wallet" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="category-color">Color</Label>
                                <Input id="category-color" name="color" placeholder="#0f766e" />
                            </div>
                        </div>

                        <Button :disabled="processing" class="w-full">Guardar categoria</Button>
                    </Form>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card v-for="category in categories" :key="category.id">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle class="text-lg">{{ category.name }}</CardTitle>
                                <CardDescription>
                                    {{ category.typeLabel }}
                                    <span v-if="category.parentName"> · hija de {{ category.parentName }}</span>
                                </CardDescription>
                            </div>

                            <Badge :variant="category.isSystem ? 'secondary' : 'outline'">
                                {{ category.isSystem ? 'Sistema' : 'Personalizada' }}
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent v-if="category.isSystem" class="text-sm text-muted-foreground">
                        Esta categoria la provee ClariFi y no puede editarse desde la interfaz del espacio.
                    </CardContent>

                    <CardContent v-else>
                        <Form
                            v-bind="update.form({ current_team: currentTeam.slug, category: category.id })"
                            class="space-y-3"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`category-name-${category.id}`">Nombre</Label>
                                    <Input :id="`category-name-${category.id}`" name="name" :default-value="category.name" />
                                    <InputError :message="errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`category-type-${category.id}`">Tipo</Label>
                                    <select
                                        :id="`category-type-${category.id}`"
                                        name="type"
                                        :value="category.type"
                                        class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option v-for="type in categoryTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label :for="`category-parent-${category.id}`">Padre</Label>
                                    <select
                                        :id="`category-parent-${category.id}`"
                                        name="parent_id"
                                        class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">Sin padre</option>
                                        <option
                                            v-for="parent in categories.filter((item) => !item.isSystem && item.id !== category.id)"
                                            :key="parent.id"
                                            :value="parent.id"
                                            :selected="parent.id === category.parentId"
                                        >
                                            {{ parent.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`category-icon-${category.id}`">Icono</Label>
                                    <Input :id="`category-icon-${category.id}`" name="icon" :default-value="category.icon ?? ''" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`category-color-${category.id}`">Color</Label>
                                    <Input :id="`category-color-${category.id}`" name="color" :default-value="category.color ?? ''" />
                                </div>
                            </div>

                            <Button :disabled="processing" size="sm">Actualizar</Button>
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
