<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verifica tu correo',
        description:
            'Verifica tu correo electronico haciendo clic en el enlace que te acabamos de enviar.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Verificacion de correo" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        Se envio un nuevo enlace de verificacion al correo que entregaste durante el registro.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            Reenviar correo de verificacion
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Cerrar sesion
        </TextLink>
    </Form>
</template>
