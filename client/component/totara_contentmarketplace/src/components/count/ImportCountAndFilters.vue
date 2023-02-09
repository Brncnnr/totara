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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @package totara_contentmarketplace
-->

<template>
  <div class="tui-contentMarketplaceImportCountAndFilters" aria-live="polite">
    <template v-if="activeFilters">
      {{
        $str('item_count_and_filters', 'totara_contentmarketplace', {
          count: formattedCount,
          filters: activeFilters,
        })
      }}
    </template>
    <template v-else>
      {{
        $str('item_count', 'totara_contentmarketplace', {
          count: formattedCount,
        })
      }}
    </template>
  </div>
</template>

<script>
export default {
  props: {
    // Count of active filters
    count: {
      type: Number,
      required: true,
    },
    // Active filters
    filters: Array,
  },

  computed: {
    /**
     * Return a concatenated string of active filters
     * e.g. "Data science" AND "Videos"
     *
     * @return {String}
     */
    activeFilters() {
      if (!this.filters.length) {
        return '';
      }
      let text = '';
      this.filters.forEach((filter, index) => {
        text +=
          index === 0
            ? this.$str(
                'active_filter_first',
                'totara_contentmarketplace',
                filter
              )
            : index === this.filters.length - 1
            ? this.$str(
                'active_filter_last',
                'totara_contentmarketplace',
                filter
              )
            : this.$str('active_filter', 'totara_contentmarketplace', filter);
      });

      return text;
    },

    /**
     * Format count number
     *
     * @return {String}
     */
    formattedCount() {
      return this.count.toLocaleString();
    },
  },
};
</script>

<lang-strings>
  {
    "totara_contentmarketplace": [
      "active_filter",
      "active_filter_first",
      "active_filter_last",
      "item_count",
      "item_count_and_filters"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-contentMarketplaceImportCountAndFilters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  @include tui-font-heading-x-small();
  @include tui-wordbreak--hard();

  & > * + * {
    margin-left: var(--gap-1);
  }
}
</style>
