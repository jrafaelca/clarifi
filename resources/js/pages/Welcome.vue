<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const page = usePage();
const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);
</script>

<template>
    <Head title="Bienvenido" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(255,236,223,0.9),_transparent_35%),linear-gradient(180deg,_#fffdf8_0%,_#fff7ef_100%)] px-6 py-8 text-[#221b15] dark:bg-[linear-gradient(180deg,_#0f0e0d_0%,_#171311_100%)] dark:text-[#f7efe8]"
    >
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl flex-col">
            <header class="flex items-center justify-end gap-3 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboardUrl"
                    class="rounded-full border border-black/10 px-4 py-2 font-medium transition hover:border-black/20 dark:border-white/15 dark:hover:border-white/30"
                >
                    Resumen
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-full border border-transparent px-4 py-2 font-medium transition hover:border-black/10 dark:hover:border-white/15"
                    >
                        Iniciar sesion
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="rounded-full bg-[#1f6b5b] px-4 py-2 font-medium text-white transition hover:bg-[#155244]"
                    >
                        Crear cuenta
                    </Link>
                </template>
            </header>

            <main class="grid flex-1 items-center gap-10 py-12 lg:grid-cols-[1.2fr_0.8fr] lg:py-20">
                <section class="space-y-8">
                    <div class="space-y-4">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[#1f6b5b] dark:text-[#86d1bf]">
                            ClariFi Personal Finance OS
                        </p>
                        <h1 class="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                            Entiende tus finanzas con claridad y conversa con tus datos reales.
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-[#5d554d] dark:text-[#c8bfb8]">
                            Registra movimientos, organiza cuentas, sigue presupuestos, metas y deudas,
                            y usa un asistente financiero que responde desde la informacion real de tu espacio.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <div class="rounded-full border border-[#d8c6b8] bg-white/80 px-4 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-white/5">
                            CLP por defecto
                        </div>
                        <div class="rounded-full border border-[#d8c6b8] bg-white/80 px-4 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-white/5">
                            Espacio personal
                        </div>
                        <div class="rounded-full border border-[#d8c6b8] bg-white/80 px-4 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-white/5">
                            Asistente de solo lectura
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-black/8 bg-white/85 p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-sm font-medium">Registrar</p>
                            <p class="mt-2 text-sm leading-6 text-[#6b625a] dark:text-[#c8bfb8]">
                                Captura ingresos, gastos y transferencias con categorias y adjuntos.
                            </p>
                        </div>
                        <div class="rounded-3xl border border-black/8 bg-white/85 p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-sm font-medium">Planificar</p>
                            <p class="mt-2 text-sm leading-6 text-[#6b625a] dark:text-[#c8bfb8]">
                                Controla presupuestos, metas de ahorro y deudas desde un solo lugar.
                            </p>
                        </div>
                        <div class="rounded-3xl border border-black/8 bg-white/85 p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-sm font-medium">Conversar</p>
                            <p class="mt-2 text-sm leading-6 text-[#6b625a] dark:text-[#c8bfb8]">
                                Consulta tu estado financiero con lenguaje natural y respuestas trazables.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="relative overflow-hidden rounded-[2rem] border border-black/8 bg-[#1f6b5b] p-6 text-white shadow-[0_32px_80px_-40px_rgba(31,107,91,0.75)] dark:border-white/10">
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#78c4b1]/30 blur-2xl"></div>
                    <div class="absolute -bottom-12 -left-8 h-44 w-44 rounded-full bg-[#f6c36b]/25 blur-2xl"></div>

                    <div class="relative space-y-6">
                        <div class="space-y-2">
                            <p class="text-sm uppercase tracking-[0.24em] text-white/70">MVP</p>
                            <h2 class="text-2xl font-semibold">Lo esencial para ordenar tu plata.</h2>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl bg-black/15 p-4 backdrop-blur">
                                <p class="text-sm font-medium">Resumen mensual</p>
                                <p class="mt-2 text-sm text-white/75">
                                    Visualiza saldo total, presupuesto disponible y actividad reciente sin salir del flujo principal.
                                </p>
                            </div>
                            <div class="rounded-2xl bg-black/15 p-4 backdrop-blur">
                                <p class="text-sm font-medium">Asistente conectado al espacio</p>
                                <p class="mt-2 text-sm text-white/75">
                                    Pregunta como vas este mes, que categorias se desviaron o que deuda conviene atacar primero.
                                </p>
                            </div>
                            <div class="rounded-2xl bg-black/15 p-4 backdrop-blur">
                                <p class="text-sm font-medium">Base lista para crecer</p>
                                <p class="mt-2 text-sm text-white/75">
                                    Dominio financiero claro ahora, automatizaciones y multimodalidad despues.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>
