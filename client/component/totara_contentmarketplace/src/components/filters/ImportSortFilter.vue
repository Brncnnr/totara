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
  <div class="tui-contentMarketplaceImportSortFilter">
    <Label
      class="tui-contentMarketplaceImportSortFilter__label"
      :for-id="$id('sortBy')"
      :label="$str('filter_sort_by', 'totara_contentmarketplace')"
    />

    <Select
      :id="$id('sortBy')"
      char-length="10"
      :options="options"
      :value="sortBy"
      @input="$emit('filter-change', $event)"
    />
  </div>
</template>

<script>
import Label from 'tui/components/form/Label';
import Select from 'tui/components/form/Select';

export default {
  components: {
    Label,
    Select,
  },

  props: {
    options: {
      type: Array,
      required: true,
    },
    sortBy: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "totara_contentmarketplace": [
      "filter_sort_by"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-contentMarketplaceImportSortFilter {
  display: flex;
  flex-grow: 1;
  align-items: center;
  justify-content: flex-end;

  & > * + * {
    margin-left: var(--gap-2);
  }

  &__label {
    flex-shrink: 0;
    @include tui-font-body();
  }
}
</style>
