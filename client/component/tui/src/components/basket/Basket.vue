<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-basket" :class="{ 'tui-basket--wideGap': wideGap }">
    <div class="tui-basket__status">
      <div class="tui-basket__selected" aria-live="polite" aria-atomic="true">
        {{ $str('selected', 'totara_core') }}:
        <span class="tui-basket__selectedCount">{{ selectedCount }}</span>
      </div>
      <slot name="status" :empty="empty" />
    </div>
    <div class="tui-basket__actions">
      <slot name="actions" :empty="empty" />
      <Dropdown
        v-if="bulkActions && bulkActions.length > 0 && !singleAction"
        position="bottom-right"
      >
        <template v-slot:trigger="{ toggle, isOpen }">
          <Button
            :aria-expanded="isOpen ? 'true' : 'false'"
            :caret="true"
            :styleclass="{ primary: true, small: true }"
            :text="$str('bulkactions', 'core')"
            :disabled="empty"
            @click="toggle"
          />
        </template>
        <DropdownItem
          v-for="(action, i) in bulkActions"
          :key="i"
          @click="action.action"
        >
          {{ action.label }}
        </DropdownItem>
      </Dropdown>
      <Button
        v-else-if="singleAction"
        :styleclass="{ primary: true, small: true }"
        :text="singleAction.label"
        :disabled="empty"
        @click="singleAction.action"
      />
    </div>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

export default {
  components: {
    Button,
    Dropdown,
    DropdownItem,
  },

  props: {
    items: {
      type: Array,
      required: true,
    },

    bulkActions: Array,

    showClear: Boolean,

    wideGap: Boolean,
  },

  computed: {
    selectedCount() {
      return this.items.length;
    },

    empty() {
      return this.items.length === 0;
    },

    singleAction() {
      return this.bulkActions && this.bulkActions.length === 1
        ? this.bulkActions[0]
        : false;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "bulkactions"
  ],
  "totara_core": [
    "selected"
  ]
}
</lang-strings>

<style lang="scss">
.tui-basket {
  @include tui-font-body();

  display: flex;
  flex-direction: column;
  flex-wrap: wrap;
  background-color: var(--color-neutral-3);
  border: var(--border-width-thin) solid var(--color-neutral-5);

  @media (min-width: $tui-screen-sm) {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  &__status,
  &__actions {
    display: flex;
    flex-shrink: 0;
    flex-wrap: wrap;
    align-items: center;
    padding: var(--gap-2) var(--gap-3);

    & > * {
      margin-top: var(--gap-2);
      margin-left: var(--gap-4);
    }

    @media (min-width: $tui-screen-xs) {
      & > * {
        margin-top: 0;
        margin-left: 0;
      }

      > .tui-formBtn--transparent {
        padding: var(--gap-1);
      }

      > * + * {
        margin-left: var(--gap-2);
      }

      .tui-basket--wideGap & {
        > * + * {
          margin-left: var(--gap-4);
        }
      }
    }
  }

  &__selected {
    font-weight: bold;
  }

  &__selectedCount {
    font-size: var(--font-size-18);
  }
}
</style>
