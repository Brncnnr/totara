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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module tui
-->

<template>
  <div
    :id="uid"
    class="tui-repeater"
    :class="[noSpacing && 'tui-repeater--noSpacing']"
    :aria-labelledby="ariaLabelledby"
    role="group"
    aria-live="polite"
  >
    <template v-for="(row, index) in rows">
      <div
        v-if="$scopedSlots.header && (index === 0 || repeatHeader)"
        :key="index + '-header'"
        class="tui-repeater__headerRow"
      >
        <slot name="header" />
        <ButtonIcon
          v-if="deleteIcon"
          class="tui-repeater__delete"
          :style="{ visibility: 'hidden' }"
          aria-label=""
          aria-hidden="true"
          :styleclass="{ small: true, stealth: true }"
          :disabled="true"
        >
          <DeleteIcon />
        </ButtonIcon>
      </div>
      <div :key="index" class="tui-repeater__row">
        <slot :row="row" :index="index" :last-row="index == rows.length - 1" />
        <ButtonIcon
          v-if="deleteIcon"
          class="tui-repeater__delete"
          :style="{ visibility: showDeleteIcon(index) ? null : 'hidden' }"
          :aria-label="$str('delete', 'core')"
          :styleclass="{ small: true, stealth: true }"
          :disabled="disabled || !showDeleteIcon(index)"
          @click="$emit('remove', row, index)"
        >
          <DeleteIcon />
        </ButtonIcon>
      </div>
      <div
        v-if="$scopedSlots['after-row']"
        :key="index + '-after'"
        class="tui-repeater__afterRow"
      >
        <slot name="after-row" :row="row" :index="index" />
      </div>
    </template>
    <slot name="add">
      <ButtonIcon
        v-if="rows.length < maxRows"
        :aria-label="$str('add', 'core')"
        :aria-controls="uid"
        :styleclass="{ small: true }"
        :disabled="disabled"
        @click="$emit('add')"
      >
        <AddIcon />
      </ButtonIcon>
    </slot>
  </div>
</template>

<script>
import AddIcon from 'tui/components/icons/Add';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import DeleteIcon from 'tui/components/icons/Delete';

export default {
  components: {
    AddIcon,
    ButtonIcon,
    DeleteIcon,
  },

  props: {
    ariaLabelledby: String,
    rows: {
      type: Array,
      required: true,
    },
    minRows: {
      type: Number,
      default: 0,
    },
    maxRows: {
      type: Number,
      default: Infinity,
    },
    disabled: Boolean,
    deleteIcon: Boolean,
    allowDeletingFirstItems: {
      type: Boolean,
      default: true,
    },
    noSpacing: Boolean,
    repeatHeader: {
      type: Boolean,
      default: false,
    },
  },

  methods: {
    showDeleteIcon(index) {
      if (!this.deleteIcon) {
        return false;
      }
      return this.allowDeletingFirstItems
        ? this.rows.length > this.minRows
        : index >= this.minRows;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "add",
    "delete"
  ]
}
</lang-strings>

<style lang="scss">
.tui-repeater {
  display: block;
  flex-grow: 1;
  width: 100%;
  min-width: 0;

  & > * + * {
    margin-top: var(--gap-3);
  }

  &__row,
  &__headerRow {
    display: flex;
    align-items: flex-start;
  }

  &__headerRow + &__row {
    margin-top: var(--gap-2);
  }

  &__row + &__afterRow {
    margin-top: var(--gap-4);
  }

  &__afterRow + &__row {
    margin-top: var(--gap-8);
  }

  &__afterRow + &__headerRow {
    margin-top: var(--gap-4);
  }

  &--noSpacing > * {
    margin: 0;
  }

  &__headerRow &__delete {
    height: 1px;
    min-height: 1px;
    overflow: hidden;
  }
}
</style>
