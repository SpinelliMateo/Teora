<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { useSidebar } from './utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar } = useSidebar()
</script>

<template>
  <button @click="toggleSidebar" class="cursor-pointer">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <g id="ic:baseline-menu">
      <path id="Vector" d="M3 18H21V16H3V18ZM3 13H21V11H3V13ZM3 6V8H21V6H3Z" fill="white"/>
      </g>
    </svg>
  </button>
</template>
