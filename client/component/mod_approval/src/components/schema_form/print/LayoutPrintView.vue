<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-layoutPrintView" :style="rootStyles">
    <template v-for="(section, i) in sections">
      <div
        v-if="section.exists"
        :key="i"
        class="tui-mod_approval-layoutPrintView__section"
        :class="{
          'tui-mod_approval-layoutPrintView__section--no-break':
            section.noBreak,
          'tui-mod_approval-layoutPrintView__section--break-after':
            section.breakAfter,
        }"
      >
        <LayoutPrintColumn :rows="section.rows" />
      </div>
    </template>
  </div>
</template>

<script>
import LayoutPrintColumn from 'mod_approval/components/schema_form/print/LayoutPrintColumn';

const BASE_FONT_SIZE = 14;

export default {
  components: {
    LayoutPrintColumn,
  },

  props: {
    schema: {
      type: Object,
      required: true,
    },
  },

  computed: {
    layout() {
      return this.schema.print_layout;
    },

    sections() {
      return this.layout.sections.map(this.$_processSection);
    },

    rootStyles() {
      const scale = this.layout.options.scale || 1;
      return {
        fontSize: BASE_FONT_SIZE * scale + 'px',
      };
    },
  },

  methods: {
    $_processSection(section) {
      return Object.assign({}, section.resolved, {
        rows: section.rows,
      });
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-layoutPrintView {
  padding: 0 0 var(--border-width-thin) var(--border-width-thin);
  color-adjust: exact; // ensure backgrounds are printed
  line-height: 1.2;

  &__section {
    &--no-break {
      break-inside: avoid;
    }

    &--break-after {
      page-break-after: always;
    }
  }
}
</style>
