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
  <div class="tui-mod_approval-printField">
    <div v-if="showLabel" class="tui-mod_approval-printField__label">
      <FontSizeLimit :min-font-size="minFontSize" :max-height="maxLabelHeight">
        {{ label }}
      </FontSizeLimit>
    </div>
    <div class="tui-mod_approval-printField__value">
      <template v-if="valueComponent">
        <component
          :is="valueComponent.component"
          v-bind="valueComponent.props"
        />
      </template>
      <FontSizeLimit
        v-else
        :min-font-size="minFontSize"
        :max-height="maxContentHeight"
      >
        <div class="tui-mod_approval-printField__value-text">
          {{ valueText }}
        </div>
      </FontSizeLimit>
    </div>
  </div>
</template>

<script>
import FontSizeLimit from 'mod_approval/components/schema_form/print/FontSizeLimit';

export default {
  components: {
    FontSizeLimit,
  },

  props: {
    field: Object,
    valueText: String,
    valueComponent: Object,
    showLabel: {
      type: Boolean,
      default: true,
    },
    minFontSize: {
      type: Number,
      default: 9,
    },
    maxLabelHeight: Number,
    maxContentHeight: Number,
    showLineNumber: Boolean,
  },

  computed: {
    label() {
      let str = this.field.label;
      if (this.showLineNumber && this.field.line) {
        str = this.field.line + '. ' + str;
      }
      return str;
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-printField {
  padding: 0.26em;

  &__label {
    margin-bottom: 1px;
    font-size: 0.8em;
    line-height: 1.1;
  }

  &__value-text {
    white-space: pre-line;
  }
}
</style>
