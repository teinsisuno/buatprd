<script setup>
defineProps({ node: Object, path: String, depth: Number, editPath: String, editVal: String });
defineEmits(['rename','commit','add','remove','updateEdit']);
</script>
<template>
    <div :style="'margin-left:'+(depth*12)+'px'" class="py-0.5">
        <div class="flex items-center gap-1 rounded px-1 py-0.5 hover:bg-white/5">
            <span class="text-[0.7rem]">{{ node.type==='folder' ? '📁' : '📄' }}</span>
            <template v-if="editPath===path">
                <input :value="editVal" @input="$emit('updateEdit', $event.target.value)" @keydown.enter="$emit('commit', node)" @blur="$emit('commit', node)" class="input-base py-0.5 text-xs flex-1" autofocus />
            </template>
            <template v-else>
                <span class="flex-1 cursor-pointer truncate" @click="$emit('rename', path, node.name)">{{ node.name }}</span>
            </template>
            <button v-if="node.type==='folder'" type="button" class="rounded px-1 text-[0.65rem] hover:bg-white/10" @click="$emit('add', node, 'folder')" title="Tambah folder">+F</button>
            <button v-if="node.type==='folder'" type="button" class="rounded px-1 text-[0.65rem] hover:bg-white/10" @click="$emit('add', node, 'file')" title="Tambah file">+f</button>
            <button type="button" class="rounded px-1 text-[0.65rem]" style="color: var(--danger);" @click="$emit('remove', node, path)">×</button>
        </div>
        <div v-if="node.children?.length" class="ml-1 border-l pl-1" style="border-color: var(--border-soft);">
            <FolderNode v-for="(child,i) in node.children" :key="i" :node="child" :path="path+'-'+i" :depth="depth+1" :editPath="editPath" :editVal="editVal" @rename="(p,n)=>$emit('rename',p,n)" @commit="(n)=>$emit('commit',n)" @add="(n,t)=>$emit('add',n,t)" @remove="(n,p)=>$emit('remove',n,p)" @updateEdit="(v)=>$emit('updateEdit',v)" />
        </div>
    </div>
</template>
