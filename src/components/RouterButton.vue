<!--
 - @copyright Copyright (c) 2023, 2024, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 -
 - @author Claus-Justus Heine <himself@claus-justus-heine.de>
 -
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -->
<template>
  <router-link v-if="!external"
               class="button router"
               :to="to"
  >
    <slot v-if="hasIconSlot && iconLeft" name="icon" />
    <span v-else-if="icon && iconLeft"
          class="left-icon"
          :class="[ icon ]"
    />
    <slot />
    <slot v-if="hasIconSlot && iconRight" name="icon" />
    <span v-else-if="icon && iconRight"
          class="right-icon"
          :class="[ icon ]"
    />
  </router-link>
  <a v-else
     class="button router"
     :href="to as string"
  >
    <slot v-if="hasIconSlot && iconLeft" name="icon" />
    <span v-else-if="icon && iconLeft"
          class="left-icon"
          :class="[ icon ]"
    />
    <slot />
    <slot v-if="hasIconSlot && iconRight" name="icon" />
    <span v-else-if="icon && iconRight"
          class="right-icon"
          :class="[ icon ]"
    />
  </a>
</template>

<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

import {
  computed,
  useSlots,
} from 'vue'

const props = withDefaults(defineProps<{
  /**
   * router-link to prop [https://router.vuejs.org/api/#to](https://router.vuejs.org/api/#to)
   */
  to: string|RouteLocationRaw
  external?: boolean
  iconPosition?: 'left'|'right'
  icon?: string
}>(), {
  external: false,
  iconPosition: 'left',
  icon: undefined,
})

const slots = useSlots()

const iconLeft = computed(() => props.iconPosition === 'left')
const iconRight = computed(() => props.iconPosition === 'right')
const hasIconSlot = computed(() => slots.icon !== undefined)
</script>

<style lang="scss" scoped>
.button {
  display: flex;
  align-items: center;
  min-height:44px;
  box-shadow: 0 0 0 2px var(--color-border-dark);
  border: 0;
  &:hover:not(.disabled) {
    box-shadow: 0 0 0 2px var(--color-primary-element);
  }
}

span {
  &[class^='icon-'], &[class*=' icon-'] {
    display: inline-block;
  }
  &.right-icon {
    margin-left: 1ex;
  }
  &.left-icon {
    margin-right: 1ex;
  }
}
</style>
