<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import { useStream } from '@laravel/stream-vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'
import 'highlight.js/styles/github-dark.css'

const props = defineProps({
    conversations: { type: Array, default: () => [] },
    current: { type: Object, default: null },
    models: { type: Array, default: () => [] },
    preferredModel: { type: String, default: '' },
})

const md = new MarkdownIt({
    highlight(str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try { return hljs.highlight(str, { language: lang }).value } catch (e) {}
        }
        return ''
    },
})

const form = useForm({
    message: '',
    model: props.current?.model ?? props.preferredModel,
    conversation_id: props.current?.id ?? null,
})

watch(() => props.current, (cur) => {
    form.model = cur?.model ?? props.preferredModel
    form.conversation_id = cur?.id ?? null
})

// --- Streaming ---
const streaming = ref(false)

const { data: streamData, send } = useStream('/chat/stream', {
    onFinish: () => {
        // La réponse est sauvegardée côté serveur → on recharge l'historique
        router.reload({
            only: ['current', 'conversations'],
            onSuccess: () => { streaming.value = false },
        })
    },
    onError: () => { streaming.value = false },
})

// On retire les marqueurs [REASONING] pour l'affichage
const streamedContent = computed(() =>
    (streamData.value ?? '').replace(/\[REASONING\][\s\S]*?\[\/REASONING\]/g, '').trim()
)

const messagesEnd = ref(null)
const scrollToBottom = () => nextTick(() => messagesEnd.value?.scrollIntoView({ behavior: 'smooth' }))
onMounted(scrollToBottom)
watch(() => [props.current?.messages?.length, streamedContent.value], scrollToBottom)

const submit = () => {
    if (!form.message.trim() || streaming.value) return
    form.conversation_id = props.current?.id ?? null
    form.post('/chat', {
        preserveScroll: true,
        onSuccess: (page) => {
            form.reset('message')
            streaming.value = true
            // La conversation est créée/retrouvée → on streame la réponse dedans
            send({ conversation_id: page.props.current.id })
        },
    })
}
</script>

<template>
    <div class="flex h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <aside class="flex w-64 shrink-0 flex-col border-r border-gray-200 dark:border-gray-800">
            <div class="p-3">
                <Link href="/chat"
                    class="block w-full rounded-lg bg-red-600 py-2 text-center text-sm text-white hover:bg-red-700">
                    + Nouveau chat
                </Link>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto px-2">
                <Link v-for="c in props.conversations" :key="c.id" :href="'/chat/' + c.id"
                    class="block truncate rounded-lg px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                    :class="c.id === props.current?.id ? 'bg-gray-100 font-medium dark:bg-gray-800' : ''">
                    {{ c.title ?? 'Nouvelle conversation' }}
                </Link>
            </nav>
        </aside>

        <main class="flex flex-1 flex-col">
            <header class="border-b border-gray-200 p-3 dark:border-gray-800">
                <select v-model="form.model"
                    class="rounded border-gray-300 p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option v-for="m in props.models" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
            </header>

            <div class="flex-1 overflow-y-auto px-4 py-6">
                <div v-if="!props.current" class="flex h-full items-center justify-center text-center">
                    <h2 class="text-2xl font-semibold text-gray-500">Que puis-je faire pour vous aujourd'hui ?</h2>
                </div>

                <div v-else class="mx-auto max-w-3xl space-y-6">
                    <div v-for="m in props.current.messages" :key="m.id"
                        :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div v-if="m.role === 'user'"
                            class="max-w-[80%] whitespace-pre-wrap rounded-2xl bg-red-600 px-4 py-2 text-white">
                            {{ m.content }}
                        </div>
                        <div v-else class="prose max-w-none dark:prose-invert" v-html="md.render(m.content)" />
                    </div>

                    <!-- Réponse en cours de streaming -->
                    <div v-if="streaming" class="flex justify-start">
                        <div v-if="streamedContent" class="prose max-w-none dark:prose-invert"
                            v-html="md.render(streamedContent)" />
                        <div v-else class="animate-pulse text-sm text-gray-400">L'assistant réfléchit…</div>
                    </div>

                    <div ref="messagesEnd"></div>
                </div>
            </div>

            <footer class="border-t border-gray-200 p-4 dark:border-gray-800">
                <div class="mx-auto flex max-w-3xl gap-2">
                    <textarea v-model="form.message" rows="1" placeholder="Envoyez un message…"
                        class="flex-1 resize-none rounded-lg border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-800"
                        @keydown.enter.exact.prevent="submit" />
                    <button :disabled="streaming || !form.message.trim()"
                        class="rounded-lg bg-red-600 px-4 text-white hover:bg-red-700 disabled:opacity-50"
                        @click="submit">
                        ↑
                    </button>
                </div>
            </footer>
        </main>
    </div>
</template>