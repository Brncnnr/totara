<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module tui
-->

<template>
  <FormField
    v-slot="{ value, update, touch, attrs }"
    :name="name"
    :validate="validate"
    :validations="validations"
    :char-length="charLength"
  >
    <TagList
      v-bind="attrs"
      :label-name="labelName"
      :disabled="disabled"
      :tags="formatValue(value)"
      :items="items"
      :filter="filter"
      :separator="separator"
      :close-on-click="closeOnClick"
      :virtual-scroll-options="virtualScrollOptions"
      :input-placeholder="inputPlaceholder"
      :debounce-filter="debounceFilter"
      @select="e => handleSelect(e, value, update, touch)"
      @remove="e => handleRemove(e, value, update, touch)"
      @filter="$emit('filter', $event)"
      @scrolltop="$emit('scrolltop')"
      @scrollbottom="$emit('scrollbottom')"
      @open="$emit('open')"
    >
      <template v-if="$scopedSlots.item" v-slot:item="slotProps">
        <slot name="item" v-bind="slotProps" />
      </template>
      <template v-if="$scopedSlots.tag" v-slot:tag="slotProps">
        <slot name="tag" v-bind="slotProps" />
      </template>
    </TagList>
  </FormField>
</template>

<script>
import { FormField } from 'tui/components/uniform';
import TagList from 'tui/components/tag/TagList';

export default {
  components: {
    FormField,
    TagList,
  },

  props: {
    name: {
      type: [String, Number, Array],
      required: true,
    },
    validate: Function,
    validations: [Function, Array],
    charLength: String,
    labelName: String,
    disabled: Boolean,
    items: Array,
    filter: String,
    separator: {
      type: Boolean,
      default: false,
    },
    closeOnClick: {
      type: Boolean,
      default: false,
    },
    virtualScrollOptions: Object,
    inputPlaceholder: String,
    debounceFilter: Boolean,
    singleSelect: {
      type: Boolean,
      default: false,
    },
  },

  methods: {
    /**
     * @param {any} e - Selected item
     * @param {any} value
     * @param {function} update
     * @param {function} touch
     */
    handleSelect(e, value, update, touch) {
      if (this.singleSelect) {
        if (value === e) {
          return;
        }
        update(e);
      } else {
        if (value.some(item => item === e)) {
          return;
        }
        update([...value, e]);
      }
      this.$emit('filter', '');
      touch();
    },

    /**
     * @param {any} e - Selected item
     * @param {any} value
     * @param {function} update
     * @param {function} touch
     */
    handleRemove(e, value, update, touch) {
      if (this.singleSelect) {
        update(null);
      } else {
        update(value.filter(item => e !== item));
      }
      touch();
    },

    /**
     * @param {any} value
     * @return {array}
     */
    formatValue(value) {
      return this.singleSelect ? (value == null ? [] : [value]) : value;
    },
  },
};
</script>
