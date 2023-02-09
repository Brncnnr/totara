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

  @module performelement_aggregation
-->

<template>
  <div class="tui-aggregationAdminView">
    <div v-if="hasExcludedValues">
      {{
        $str(
          'header_blurb_with_exclusions',
          'performelement_aggregation',
          excludedValuesCsv
        )
      }}
    </div>
    <div v-else class="tui-aggregationAdminView__blurb">
      {{ $str('header_blurb', 'performelement_aggregation') }}
    </div>
    <div>
      <div v-for="(preview, i) in aggregationTypesPreview" :key="i">
        {{ preview }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    data: Object,
    extraPluginConfigData: Object,
  },
  computed: {
    hasExcludedValues() {
      return this.excludedValues.length > 0;
    },
    excludedValuesCsv() {
      return this.excludedValues.join(', ');
    },
    excludedValues() {
      if (!this.data.excludedValues) {
        return [];
      }

      // Remove any empty entries.
      return this.data.excludedValues.filter(
        value => value !== null && value.trim() !== ''
      );
    },
    aggregationTypesPreview() {
      const value = this.$str(
        'calculated_value_preview_placeholder',
        'performelement_aggregation'
      );

      const options = this.extraPluginConfigData.calculations;

      return this.data.calculations.map(chosenCalculation => {
        const label = options.find(option => option.name === chosenCalculation)
          .label;

        return this.$str(
          'aggregated_response_display',
          'performelement_aggregation',
          { label, value }
        );
      });
    },
  },
};
</script>

<lang-strings>
  {
    "performelement_aggregation": [
      "aggregated_response_display",
      "header_blurb",
      "header_blurb_with_exclusions",
      "calculated_value_preview_placeholder"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-aggregationAdminView {
  > * + * {
    margin-top: var(--gap-8);
  }
}
</style>
