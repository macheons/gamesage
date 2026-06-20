<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    instruction: { type: Object, default: null },
})

const form = useForm({
    about_you: props.instruction?.about_you ?? '',
    behavior: props.instruction?.behavior ?? '',
    is_enabled: props.instruction?.is_enabled ?? true,
})

const submit = () => form.put('/custom-instructions', { preserveScroll: true })
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-6 p-6 text-gray-900 dark:text-gray-100">
        <div>
            <Link href="/chat" class="text-sm text-red-600 hover:underline">← Retour au chat</Link>
            <h1 class="mt-2 text-2xl font-bold">Instructions personnalisées</h1>
            <p class="text-sm text-gray-500">Dis à GameSage qui tu es et comment tu veux qu'il te réponde.</p>
        </div>

        <div class="space-y-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.is_enabled" class="rounded" />
                <span class="text-sm">Activer les instructions personnalisées</span>
            </label>

            <div>
                <label class="mb-1 block text-sm font-medium">Qui es-tu ?</label>
                <textarea v-model="form.about_you" rows="4"
                    placeholder="Ex : Je joue surtout en solo, j'adore l'exploration et les ambiances calmes, je débute sur les jeux de société."
                    class="w-full rounded border-gray-300 p-2 dark:border-gray-700 dark:bg-gray-800" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Comment veux-tu que GameSage te réponde ?</label>
                <textarea v-model="form.behavior" rows="4"
                    placeholder="Ex : Va droit au but, 3 recos max avec une phrase de justif, pas de spoilers."
                    class="w-full rounded border-gray-300 p-2 dark:border-gray-700 dark:bg-gray-800" />
            </div>

            <div class="flex items-center gap-3">
                <button :disabled="form.processing" @click="submit"
                    class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700 disabled:opacity-50">
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <span v-if="form.recentlySuccessful" class="text-sm text-green-600">Enregistré ✓</span>
            </div>
        </div>
    </div>
</template>