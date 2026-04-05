<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import Link from '@tiptap/extension-link';
import { watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Link.configure({ openOnClick: false }),
        Placeholder.configure({ placeholder: props.placeholder ?? 'Write a description…' }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[120px] px-3 py-2.5 text-slate-700',
        },
    },
    onUpdate({ editor }) {
        const html = editor.isEmpty ? '' : editor.getHTML();
        emit('update:modelValue', html);
    },
});

// Sync external changes (e.g. form reset)
watch(() => props.modelValue, (val) => {
    if (!editor.value) return;
    const current = editor.value.isEmpty ? '' : editor.value.getHTML();
    if (val !== current) editor.value.commands.setContent(val ?? '', false);
});

type Level = 1 | 2 | 3;

function toggleHeading(level: Level) {
    editor.value?.chain().focus().toggleHeading({ level }).run();
}
function setLink() {
    const prev = editor.value?.getAttributes('link').href ?? '';
    const url = window.prompt('URL', prev);
    if (url === null) return;
    if (url === '') {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
        editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    }
}
</script>

<template>
    <div class="border border-slate-200 rounded-lg overflow-hidden bg-white focus-within:ring-2 focus-within:ring-primary/40 focus-within:border-primary/40 transition">
        <!-- Toolbar -->
        <div v-if="editor" class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 bg-slate-50 border-b border-slate-200">
            <!-- Text style -->
            <button type="button" title="Bold"
                @click="editor.chain().focus().toggleBold().run()"
                :class="editor.isActive('bold') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs font-bold transition-colors">B</button>
            <button type="button" title="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="editor.isActive('italic') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs italic transition-colors">I</button>
            <button type="button" title="Underline"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="editor.isActive('underline') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs underline transition-colors">U</button>
            <button type="button" title="Strikethrough"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="editor.isActive('strike') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs line-through transition-colors">S</button>

            <span class="w-px h-4 bg-slate-200 mx-0.5" />

            <!-- Headings -->
            <button type="button" title="Heading 1"
                @click="toggleHeading(1)"
                :class="editor.isActive('heading', { level: 1 }) ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs font-semibold transition-colors">H1</button>
            <button type="button" title="Heading 2"
                @click="toggleHeading(2)"
                :class="editor.isActive('heading', { level: 2 }) ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs font-semibold transition-colors">H2</button>
            <button type="button" title="Heading 3"
                @click="toggleHeading(3)"
                :class="editor.isActive('heading', { level: 3 }) ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs font-semibold transition-colors">H3</button>

            <span class="w-px h-4 bg-slate-200 mx-0.5" />

            <!-- Lists -->
            <button type="button" title="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="editor.isActive('bulletList') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs transition-colors">• List</button>
            <button type="button" title="Numbered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="editor.isActive('orderedList') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs transition-colors">1. List</button>

            <span class="w-px h-4 bg-slate-200 mx-0.5" />

            <!-- Blocks -->
            <button type="button" title="Blockquote"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="editor.isActive('blockquote') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs transition-colors">" "</button>
            <button type="button" title="Code block"
                @click="editor.chain().focus().toggleCodeBlock().run()"
                :class="editor.isActive('codeBlock') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs font-mono transition-colors">&lt;/&gt;</button>
            <button type="button" title="Link" @click="setLink"
                :class="editor.isActive('link') ? 'bg-slate-200 text-slate-900' : 'text-slate-500 hover:bg-slate-100'"
                class="px-2 py-1 rounded text-xs transition-colors">Link</button>

            <span class="w-px h-4 bg-slate-200 mx-0.5" />

            <!-- History -->
            <button type="button" title="Undo"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                class="px-2 py-1 rounded text-xs text-slate-500 hover:bg-slate-100 disabled:opacity-30 transition-colors">↩</button>
            <button type="button" title="Redo"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                class="px-2 py-1 rounded text-xs text-slate-500 hover:bg-slate-100 disabled:opacity-30 transition-colors">↪</button>
        </div>

        <!-- Editor area -->
        <EditorContent :editor="editor" />
    </div>
</template>

<style>
/* Tiptap placeholder */
.tiptap p.is-editor-empty:first-child::before {
    color: #94a3b8;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

/* Prose styles scoped to editor */
.tiptap.prose h1 { font-size: 1.25rem; font-weight: 700; margin: 0.75rem 0 0.25rem; }
.tiptap.prose h2 { font-size: 1.05rem; font-weight: 600; margin: 0.6rem 0 0.2rem; }
.tiptap.prose h3 { font-size: 0.95rem; font-weight: 600; margin: 0.5rem 0 0.15rem; }
.tiptap.prose p  { margin: 0.25rem 0; }
.tiptap.prose ul { list-style: disc; padding-left: 1.25rem; margin: 0.25rem 0; }
.tiptap.prose ol { list-style: decimal; padding-left: 1.25rem; margin: 0.25rem 0; }
.tiptap.prose blockquote { border-left: 3px solid #e2e8f0; padding-left: 0.75rem; color: #64748b; margin: 0.5rem 0; }
.tiptap.prose pre { background: #f1f5f9; border-radius: 0.375rem; padding: 0.5rem 0.75rem; font-size: 0.8rem; overflow-x: auto; }
.tiptap.prose code { background: #f1f5f9; border-radius: 0.25rem; padding: 0.1rem 0.3rem; font-size: 0.8rem; }
.tiptap.prose pre code { background: none; padding: 0; }
.tiptap.prose a { color: var(--color-primary, #6366f1); text-decoration: underline; }
</style>
