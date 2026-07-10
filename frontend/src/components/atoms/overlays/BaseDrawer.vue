<script setup lang="ts">
withDefaults(defineProps<{
  title?: string
  width?: number | string
  placement?: 'right' | 'left' | 'top' | 'bottom'
  padding?: string | number
}>(), {
  width: 480,
  placement: 'right',
  padding: 24,
})

const isOpen = defineModel<boolean>({ default: false })
const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <a-drawer
    v-model:open="isOpen"
    :title="title"
    :width="width"
    :placement="placement"
    class="base-drawer"
    @close="emit('close')"
    :bodyStyle="{ padding: padding }"
  >
    <slot />
    <template v-if="$slots.footer" #footer>
      <slot name="footer" />
    </template>
  </a-drawer>
</template>

<style>
/*
 * Not scoped: a-drawer teleports its DOM to <body>, outside this
 * component's template subtree, so Vue never attaches the
 * data-v-xxxx scope attribute to it and :deep() cannot match it.
 */
.base-drawer.ant-drawer-content {
  /*background-color: #fff;*/
}

.ant-drawer-content-wrapper:has(.base-drawer) {
  max-width: 100vw;
}

@media (max-width: 576px) {
  .ant-drawer-content-wrapper:has(.base-drawer) .ant-drawer-body {
    padding: 16px !important;
  }
}
</style>
