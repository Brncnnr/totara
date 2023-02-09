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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-cardFootnotes">
    <template v-for="(footnote, i) in footnotes">
      <component
        :is="footnote.component"
        :key="`footnote_${i}`"
        v-bind="getFootnoteProps(footnote)"
      />
    </template>
  </div>
</template>

<script>
import tui from 'tui/tui';

const has = Object.prototype.hasOwnProperty;

export default {
  props: {
    /**
     * An array of footnote objects that needs to display below card.
     * Each footnote object requires 3 properties:
     *   component......: unique name of the Vue component
     *   tuicomponent...: absolute path of the TUI Vue component
     *   props..........: JSON encoded string you want bound to the component
     */
    footnotes: {
      type: Array,
      required: false,
      default: () => [],
      validator: footnotes =>
        footnotes.every(footnote =>
          ['component', 'tuicomponent', 'props'].every(prop => prop in footnote)
        ),
    },
  },

  created() {
    this.footnotes.forEach(({ component, tuicomponent }) => {
      if (!has.call(this.$options.components, component)) {
        this.$options.components[component] = tui.asyncComponent(tuicomponent);
      }
    });
  },

  methods: {
    getFootnoteProps(footnote) {
      return JSON.parse(footnote.props);
    },
  },
};
</script>

<style lang="scss">
.tui-cardFootnotes {
  margin: var(--gap-2) 0;
}
</style>
