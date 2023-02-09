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
  <Paging
    v-if="totalItems"
    class="tui-contentMarketplaceImportSelectionPaging"
    :items-per-page="itemsPerPage"
    :page="currentPage"
    :total-items="totalItems"
    @count-change="$emit('items-per-page-change', $event)"
    @page-change="$emit('page-change', $event)"
  />
</template>

<script>
// Components
import Paging from 'tui/components/paging/Paging';

export default {
  components: {
    Paging,
  },

  props: {
    currentPage: {
      type: Number,
      required: true,
    },
    itemsPerPage: {
      type: Number,
      default: 20,
    },
    totalItems: {
      type: Number,
      required: true,
    },
  },
};
</script>
