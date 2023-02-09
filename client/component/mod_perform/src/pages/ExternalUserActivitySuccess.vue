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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performUserActivitySuccess">
    <PageHeading
      :title="$str('external_participation_success_heading', 'mod_perform')"
    />

    <div>
      {{ $str('external_participation_success_message', 'mod_perform') }}
    </div>
    <div v-if="!isClosed">
      <a :href="reviewUrl">
        {{
          $str('external_participation_success_review_link_text', 'mod_perform')
        }}
      </a>
    </div>
    <div v-else>
      {{ $str('external_participation_success_message_closed', 'mod_perform') }}
    </div>
  </div>
</template>

<script>
import PageHeading from 'tui/components/layouts/PageHeading';

export default {
  components: {
    PageHeading,
  },

  props: {
    reviewUrl: {
      required: true,
      type: String,
    },
    isClosed: {
      required: true,
      type: Boolean,
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "external_participation_success_heading",
      "external_participation_success_message",
      "external_participation_success_message_closed",
      "external_participation_success_review_link_text"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivitySuccess {
  @include tui-font-body();

  & > * + * {
    margin-top: var(--gap-6);
  }
}
</style>
