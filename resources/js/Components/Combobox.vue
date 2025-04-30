<template>
    <div class="w-full">
      <label v-if="label" :for="name" class="block text-sm font-medium text-gray-700 mb-1">
        {{ label }}
      </label>
  
      <div class="relative">
        <input
          :id="name"
          :name="name"
          type="text"
          v-model="search"
          @focus="isOpen = true"
          @blur="handleBlur"
          @input="filterOptions"
          class="block w-full rounded-md border border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          :placeholder="placeholder"
          autocomplete="off"
        />
  
        <div
          v-show="isOpen && filteredOptions.length"
          class="absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg border border-gray-200 max-h-60 overflow-y-auto"
        >
          <ul class="py-1 text-sm text-gray-700">
            <li
              v-for="option in filteredOptions"
              :key="option.id"
              @mousedown.prevent="selectOption(option)"
              class="cursor-pointer px-4 py-2 hover:bg-indigo-100"
            >
              {{ option.label }}
            </li>
          </ul>
        </div>
      </div>
  
      <input type="hidden" :name="name" :value="selected?.id" />
  
      <p v-if="error" class="text-sm text-red-600 mt-1">{{ error }}</p>
    </div>
  </template>
  
  <script setup>
  import { ref, watch, computed } from 'vue'
  
  const props = defineProps({
    label: String,
    name: {
      type: String,
      required: true,
    },
    options: {
      type: Array,
      default: () => [],
    },
    modelValue: {
      type: [String, Number, Object],
      default: null,
    },
    placeholder: {
      type: String,
      default: 'Select an option',
    },
    error: {
      type: String,
      default: '',
    },
  })
  
  const emit = defineEmits(['update:modelValue'])
  
  const search = ref('')
  const isOpen = ref(false)
  const selected = ref(null)
  
  const filteredOptions = ref([])
  
  watch(
    () => props.modelValue,
    (val) => {
      selected.value = props.options.find((o) =>
        typeof val === 'object' ? o.id === val?.id : o.id === val
      )
      search.value = selected.value?.label || ''
    },
    { immediate: true }
  )
  
  const filterOptions = () => {
    const s = search.value.toLowerCase()
    filteredOptions.value = props.options.filter((opt) =>
      opt.label.toLowerCase().includes(s)
    )
  }
  
  const selectOption = (option) => {
    selected.value = option
    search.value = option.label
    isOpen.value = false
    emit('update:modelValue', option)
  }
  
  const handleBlur = () => {
    setTimeout(() => (isOpen.value = false), 150)
  }
  </script>
  