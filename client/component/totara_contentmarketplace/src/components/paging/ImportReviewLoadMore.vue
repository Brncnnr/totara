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
  <div class="tui-contentMarketplaceImportReviewPaging">
    <Button
      v-if="!lastPage"
      :text="$str('load_more', 'totara_contentmarketplace')"
      @click="$emit('next-page')"
    />
  </div>
</template>

<script>
// Components
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Button,
  },

  props: {
    lastPage: Boolean,
  },
};
</script>

<lang-strings>
{
  "totara_contentmarketplace": [
    "load_more"
  ]
}
</lang-strings>

<style lang="scss">
.tui-contentMarketplaceImportReviewPaging {
  display: flex;
  justify-content: center;
}
</style>
