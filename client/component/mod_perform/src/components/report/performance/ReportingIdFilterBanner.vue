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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performReportPerformanceReportingIdBanner">
    <strong>{{ $str('element_identifier', 'mod_perform') }}</strong>
    <Tag
      v-for="reportingId in reportingIds"
      :key="reportingId"
      :text="reportingId"
      class="tui-performReportPerformanceReportingIdBanner__tag"
    />
  </div>
</template>
<script>
import Tag from 'tui/components/tag/Tag';

export default {
  components: {
    Tag,
  },
  props: {
    reportingIds: {
      type: Array,
      required: true,
    },
  },
};
</script>
<lang-strings>
{
  "mod_perform": [
    "element_identifier"
  ]
}
</lang-strings>

<style lang="scss">
.tui-performReportPerformanceReportingIdBanner {
  margin-bottom: var(--gap-8);
  padding: var(--gap-2) var(--gap-3);
  background: var(--color-neutral-3);
  border: none;
  border-radius: var(--border-radius-normal);

  &__tag {
    margin-left: var(--gap-2);
  }
}
</style>
