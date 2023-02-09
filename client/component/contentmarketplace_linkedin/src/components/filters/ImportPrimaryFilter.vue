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
  @package contentmarketplace_linkedin
-->

<template>
  <div class="tui-linkedInImportPrimaryFilter">
    <Label
      class="tui-linkedInImportPrimaryFilter__label"
      :for-id="$id('languageSelect')"
      :label="$str('language_filter_label', 'contentmarketplace_linkedin')"
    />
    <Select
      :id="$id('languageSelect')"
      char-length="full"
      :options="options"
      :value="selected"
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
    selected: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "contentmarketplace_linkedin": [
      "language_filter_label"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-linkedInImportPrimaryFilter {
  display: flex;
  align-items: center;

  & > * + * {
    margin-left: var(--gap-4);
  }

  &__label {
    flex-shrink: 0;
  }
}
</style>
