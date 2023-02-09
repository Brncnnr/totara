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
  <Fieldset class="tui-multiSelectFilter" :hidden="hiddenTitle" :legend="title">
    <template v-for="{ label, id } in visibleOptions">
      <div :key="id" class="tui-multiSelectFilter__item">
        <CheckboxButton
          :checked="isItemSelected(id)"
          @change="itemStateChange(id, $event)"
        >
          {{ label }}
        </CheckboxButton>
      </div>
    </template>

    <!-- Collapsible options (by default hide options when more than x) -->
    <HideShow
      v-if="collapsibleOptions.length"
      :aria-region-label="$str('a11y_more_filters', 'totara_core')"
      class="tui-multiSelectFilter__collapsible"
      :content-before-toggle="true"
      :hide-content-text="$str('show_less', 'totara_core')"
      :show-content-text="$str('show_more', 'totara_core')"
    >
      <template v-slot:trigger="{ controls, text, toggleContent }">
        <Button
          :aria-controls="controls"
          class="tui-multiSelectFilter__collapsible-toggle"
          :styleclass="{ primary: true, small: true, transparent: true }"
          :text="text"
          @click="toggleContent"
        />
      </template>

      <template v-slot:content>
        <div class="tui-multiSelectFilter__collapsible-collapsed">
          <template v-for="{ label, id } in collapsibleOptions">
            <div :key="id" class="tui-multiSelectFilter__item">
              <CheckboxButton
                :checked="isItemSelected(id)"
                @change="itemStateChange(id, $event)"
              >
                {{ label }}
              </CheckboxButton>
            </div>
          </template>
        </div>
      </template>
    </HideShow>
  </Fieldset>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import CheckboxButton from 'tui/components/form/CheckboxButton';
import Fieldset from 'tui/components/form/Fieldset';
import HideShow from 'tui/components/collapsible/HideShow';

export default {
  components: {
    Button,
    CheckboxButton,
    Fieldset,
    HideShow,
  },

  props: {
    hiddenTitle: Boolean,
    options: Array,
    title: String,
    value: Array,
    visibleItemLimit: Number,
  },

  computed: {
    /**
     * Provide array of collapsible options,
     * these are the options which can't be collapsed
     *
     * @return {Array}
     */
    collapsibleOptions() {
      if (!this.options || !this.visibleItemLimit) {
        return [];
      }
      return this.options.slice(this.visibleItemLimit);
    },

    /**
     * Provide array of visible options,
     * these are the options which can't be collapsed
     * If no visible limit just return the full set
     *
     * @return {Array}
     */
    visibleOptions() {
      if (!this.options) {
        return [];
      }

      // If no limit visible options or total is below limit
      if (
        !this.visibleItemLimit ||
        this.options.length <= this.visibleItemLimit
      ) {
        return this.options;
      }

      return this.options.slice(0, this.visibleItemLimit);
    },
  },

  methods: {
    /**
     * remove selected option ID from selection and emit the update
     *
     * @param {Int} id
     * @param {Array} selection
     */
    deselectItem(id, selection) {
      if (selection.indexOf(id) !== -1) {
        selection.splice(selection.indexOf(id), 1);
      }
      this.$emit('input', selection);
    },

    /**
     * Check if item is selected
     *
     * @param {Int} id
     * @return {Boolean}
     */
    isItemSelected(id) {
      return this.value.indexOf(id) !== -1;
    },

    /**
     * item selection state has changed
     *
     * @param {Int} id
     * @param {Boolean} checked
     */
    itemStateChange(id, checked) {
      let selection = [].concat(this.value);
      if (!checked) {
        this.deselectItem(id, selection);
      } else {
        this.selectItem(id, selection);
      }
    },

    /**
     * Add selected option ID to selection and emit the update
     *
     * @param {Int} id
     * @param {Array} selection
     */
    selectItem(id, selection) {
      // If not already selected
      if (!this.value.includes(id)) {
        selection = selection.concat([id]);
        this.$emit('input', selection);
      }
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "a11y_more_filters",
    "show_less",
    "show_more"
  ]
}
</lang-strings>

<style lang="scss">
.tui-multiSelectFilter {
  & > * + * {
    margin-top: var(--gap-1);
  }

  &__collapsible {
    margin: 0;

    &-collapsed {
      & > * {
        margin-top: var(--gap-1);
      }
    }

    &-toggle {
      margin-left: var(--gap-1);
    }
  }
}
</style>
