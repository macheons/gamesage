<script setup>
import { useForm } from '@inertiajs/vue3'
import { ask } from '@/actions/App/Http/Controllers/AskController'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'
import 'highlight.js/styles/github-dark.css'

const props = defineProps({
    models: Array,
    selectedModel: String,
    message: String,
    response: String,
    error: String,
})

const form = useForm({
    message: props.message ?? '',
    model: props.selectedModel,
})

const md = new MarkdownIt({
    highlight(str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value
            } catch (e) {}
        }
        return ''
    },
})

const submit = () => {
    form.post(ask())
}
</script>

<template>
    <div class="max-w-3xl mx-auto p-6 space-y-6">
        <h1 class="text-2xl font-bold">Mini ChatGPT</h1>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Modèle</label>
                <select
                    v-model="form.model"
                    class="w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700 p-2"
                >
                    <option
                        v-for="model in props.models"
                        :key="model.id"
                        :value="model.id"
                    >
                        {{ model.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Votre question</label>
                <textarea
                    v-model="form.message"
                    rows="4"
                    class="w-full rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700 p-2"
                    placeholder="Posez votre question..."
                    @keydown.ctrl.enter="submit"
                />
                <p v-if="form.errors.message" class="text-red-500 text-sm mt-1">
                    {{ form.errors.message }}
                </p>
            </div>

            <button
                type="button"
                :disabled="form.processing"
                class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50"
                @click="submit"
            >
                {{ form.processing ? 'Envoi...' : 'Envoyer' }}
            </button>
        </div>

        <div v-if="props.error" class="text-red-500 p-4 rounded bg-red-50 dark:bg-red-950">
            Erreur : {{ props.error }}
        </div>

        <div
            v-if="props.response"
            class="prose dark:prose-invert prose-slate max-w-none border-t pt-6"
            v-html="md.render(props.response)"
        />
    </div>
</template>