<!--
 - From: https://swina.github.io/2019/02/how-to-create-a-simple-reusable-vue-input-text-component/
 - and modified quite a bit ..
 -->
<template>
  <div class="input__container" :class="['input-type-' + type, { readonly, collapse }, has_hint, has_icon, ...cloudVersionClasses ]">
    <div class="input-effect" :class="[filled, { readonly, collapse }, has_hint, has_icon ]">
      <NcDateTimePicker v-if="isDatePickerType"
                        v-model="model"
                        class="effect"
                        :type="isDatePickerType"
                        :format="format ? format : formatTypeMap"
                        :data-foo="value"
                        :placeholder="placeholder"
                        :inputClass="['effect', 'mx-input', { focusable: isFocusable }]"
                        :disabled="disabled || readonly"
                        :readonly="readonly"
                        :required="required"
                        v-bind="$attrs"
                        @focus="show = !show;"
                        @blur="show = !show;"
                        @input="$emit('input', $event.target ? $event.target.value : $event);"
      />
      <NcSelect v-else-if="isMultiselectType"
                v-model="model"
                class="effect"
                :placeholder="placeholder"
                :disabled="disabled || readonly"
                :readonly="readonly"
                :label="optionLabel"
                :required="required"
                :labelOutside="true"
                v-bind="$attrs"
                @focus="show = !show;"
                @blur="show = !show;"
      />
      <input v-else
             v-model="model"
             :type="type"
             :placeholder="placeholder"
             :disabled="disabled"
             class="effect"
             :class="[has_icon, { focusable: isFocusable }]"
             :readonly="readonly"
             :required="required"
             v-bind="$attrs"
             @focus="show = !show"
             @blur="show = !show"
             @input="(event) => handleInput(event)"
      >
      <label :style="{ color: color }"><span>{{ label }}</span><span class="readonly-indicator"><LockIcon /></span></label>
      <span class="focus-border" :style="focusBorder" />
    </div>
    <span v-if="show" class="input__hint">{{ hint }}</span>
    <i class="material-icons input__icon">{{ icon }}</i>
  </div>
</template>

<script setup lang="ts">
import { getLanguage } from '@nextcloud/l10n'
import {
  NcDateTimePicker,
  NcSelect,
} from '@nextcloud/vue'
import {
  computed,
  ref,
} from 'vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import cloudVersionClassesImport from '../toolkit/util/cloud-version-classes.ts'

// The following would interfere with the rest of NC:
// import 'vue-material-design-icons/styles.css'
import 'material-icons/iconfont/material-icons.css'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const model = defineModel<string|Date|any[]|object>()

const props = withDefaults(defineProps<{
  type?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  label?: string
  hint?: string
  icon?: string
  placeholder?: string
  color?: string
  optionLabel?: string
  collapse?: boolean
  format?: string
}>(), {
  type: 'text',
  disabled: false,
  readonly: false,
  required: false,
  value: '',
  label: '',
  hint: '',
  icon: '',
  placeholder: '',
  color: 'indigo',
  optionLabel: '',
  // eslint-disable-next-line vue/no-boolean-default
  collapse: true,
  format: undefined,
})

const emit = defineEmits(['input'])

const formatMapDE = {
  date: 'dd.MM.yyyy',
  datetime: 'dd.MM.yyyy H:mm:ss',
  year: 'YYYY',
  month: 'MM.yyyy',
  time: 'H:mm:ss',
  week: 'w',
}

const cloudVersionClasses = computed(() => cloudVersionClassesImport)
const show = ref(false)

// @todo change in vue 3.5+ to props destructuring in order to remove this boiler plate code
const type = computed(() => props.type)
const readonly = computed(() => props.readonly)
const disabled = computed(() => props.disabled)
const value = computed(() => props.value)
const placeholder = computed(() => props.placeholder)
const collapse = computed(() => props.collapse)
const format = computed(() => props.format)
const required = computed(() => props.required)
const optionLabel = computed(() => props.optionLabel)
const color = computed(() => props.color)
const label = computed(() => props.label)
const hint = computed(() => props.hint)
const icon = computed(() => props.icon)

const filled = computed(() => {
  if (!show.value && props.value) {
    return 'has-content'
  }
  return ''
})

// eslint-disable-next-line camelcase
const has_icon = computed(() => {
  if (props.icon) {
    return 'input__has_icon'
  }
  return ''
})

// eslint-disable-next-line camelcase
const has_hint = computed(() => {
  if (props.hint) {
    return 'input__has_hint'
  }
  return 'input__no_hint'
})

const focusBorder = computed(
  () => ({
    'background-color': props.color,
  }),
)

const isMultiselectType = computed(() => props.type === 'multiselect')

const isDatePickerType = computed(() => {
  switch (props.type) {
    case 'time':
    case 'month':
    case 'year':
    case 'week':
    case 'date':
    case 'datetime':
      return props.type
  }
  return false
})

const formatTypeMap = computed(() => {
  if (isDatePickerType.value === false) {
    return null
  }
  if (getLanguage().startsWith('de')) {
    return formatMapDE[props.type] ?? formatMapDE.date
  } else {
    return null
  }
})

/**
 * determines if the action is focusable
 *
 * @return {boolean} is the action focusable ?
 */
const isFocusable = computed(() => !props.disabled)

interface TargetedMouseEvent extends MouseEvent {
  target: HTMLInputElement
}

const handleInput = (vueEvent: Event) => {
  console.info('EVENT', { vueEvent })
  const event = vueEvent as TargetedMouseEvent
  const value = event.target.value
  model.value = value
  emit('input', event.target.value)
}
</script>

<style lang="scss" scoped>
.cloud-version {
  &:not(.cloud-version-major-23, .cloud-version-major-24) {
    --icon-checkmark-000: var(--icon-checkmark-dark);
  }
}

.input__container {
  width: 100%;
  padding: 0.5rem 0.5rem 0 0;
  text-align: left;
  /* &.input__no_hint.collapse {
     .input__icon {
     top: -2rem;
     }
     } */
  &.input__has_icon {
    position: relative;
    left:0;
    top:0;
    .input__icon {
      position: absolute;
      left: 0rem;
      top: 2.5rem;
      opacity: 0.3;
    }
  }
}

.input__icon {
  position: relative;
  left: 0rem;
  top: -3.5rem;
  opacity: 0.3;
}

.input__hint {
  float: left;
  width: 100%;
  margin: -1.2rem 0 0 0;
  position: relative;
  font-size: 0.8rem;
  opacity: 0.6;
}

.input-effect {
  float: left;
  width: 100%;
  margin: 1.5rem 0rem 1.5rem 0;
  position: relative;  /* necessary to give position: relative to parent. */
  &.input__no_hint.collapse {
    margin-bottom:0;
  }
  &.readonly {
    .effect {
      ~ label {
        .readonly-indicator {
          display:inline;
        }
      }
    }
  }
}

input.input__has_icon {
  padding-left: 2rem !important;
}

input.effect {
  &:read-only {
    ~ label {
      .readonly-indicator {
        display:inline;
      }
    }
  }
}

.effect {
  &:not(input) {
    padding:0;
    margin:0;
    border:0;
    width:100%;
  }
  &,
  :deep(.mx-input-wrapper) input.effect.mx-input {
    border: 0;
    padding: 4px 0;
    border-bottom: 1px solid #ccc;
    background-color: transparent;
    box-shadow:none;
    &:hover {
      border-color: var(--color-primary-element);
      outline: none;
    }
  }
  :deep(&.multiselect) {
    max-height:37px;
    &.multiselect--disabled {
      &, & .multiselect__single {
        background-color:transparent!important;
      }
    }
    .multiselect__tags {
      border: 0;
      /* padding: 4px 0; */
      border-bottom: 1px solid #ccc;
      background-color: transparent;
      box-shadow:none;
      &:hover {
        border-color: var(--color-primary-element);
        outline: none;
      }
    }
  }
  ~ .focus-border {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background-color: indigo;
    transition: 0.4s;
  }

  ~ label {
    position: absolute;
    left: 0;
    width: 100%;
    top: -1.3rem;
    color: #aaa;
    transition: 0.3s;
    z-index: -1;
    letter-spacing: 0.5px;
    .readonly-indicator {
      position:absolute;
      top:-0.3em;
      display:none;
      height:1em;
      width:1em;
    }
  }

  &.readonly {
    ~ label {
      .readonly-indicator {
        display:inline;
      }
    }
  }

  &:focus, &:focus-within, &.has-content {
    ~ .focus-border {
      width: 100%;
      transition: 0.4s;
    }
    ~ label {
      top: -1rem;
      font-size: 0.8rem;
      color: indigo;
      transition: 0.3s;
    }
  }
}

::placeholder {
  opacity: 0.4;
}

input {
  &[type='text'], &[type='number'] {
    color: #555;
    width: 100%;
    box-sizing: border-box;
    letter-spacing: 1px;
    outline: none;
    margin-bottom:0;
  }
}

label {
  font-size: 0.9rem;
}
</style>
