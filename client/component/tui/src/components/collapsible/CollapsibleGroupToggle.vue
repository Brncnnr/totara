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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-collapsibleGroupToggle"
    :class="{ 'tui-collapsibleGroupToggle--alignEnd': alignEnd }"
  >
    <Button
      :aria-expanded="allExpanded.toString()"
      class="tui-collapsibleGroupToggle__button"
      :styleclass="{
        transparent: transparent,
        small: true,
      }"
      :text="$str(allExpanded ? 'collapseall' : 'expandall', 'core')"
      @click="toggleAllFilters()"
    />
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Button,
  },

  props: {
    id: {
      type: [String, Number],
    },
    value: {
      required: true,
      type: Object,
    },
    alignEnd: {
      type: Boolean,
      default: true,
    },
    transparent: {
      type: Boolean,
      default: true,
    },
  },

  computed: {
    /**
     * Update expand state base on value
     *
     * @return {Bool}
     */
    allExpanded() {
      if (Object.values(this.value).findIndex(elem => elem === false) >= 0) {
        return false;
      }
      return true;
    },

    /**
     * Provide ID for accessibility tags
     *
     * @return {Bool}
     */
    generatedId() {
      return this.id || this.$id();
    },
  },

  methods: {
    /**
     * Emit updated object
     *
     */
    toggleAllFilters() {
      const newState = !this.allExpanded;
      let stateObj = this.value;

      Object.keys(stateObj).forEach(nestedKey => {
        stateObj[nestedKey] = newState;
      });
      this.$emit('input', stateObj);
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "expandall",
    "collapseall"
  ]
}
</lang-strings>

<style lang="scss">
.tui-collapsibleGroupToggle {
  display: flex;
  &--alignEnd &__button {
    margin-left: auto;
  }
}
</style>
