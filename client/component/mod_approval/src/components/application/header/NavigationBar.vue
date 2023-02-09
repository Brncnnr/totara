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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-navigationBar">
    <a class="tui-mod_approval-navigationBar__backLink" :href="backUrl">
      <BackArrow class="tui-mod_approval-navigationBar__backIcon" size="200" />
      <span>{{ backContent }}</span>
    </a>
  </div>
</template>

<script>
import BackArrow from 'tui/components/icons/BackArrow';

export default {
  components: {
    BackArrow,
  },
  props: {
    backUrl: {
      type: String,
      required: true,
    },
    backContent: {
      type: String,
      required: true,
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-navigationBar {
  display: flex;

  @media (max-width: $tui-screen-sm) {
    padding: var(--gap-4);
  }

  &__backLink {
    display: flex;
    align-items: center;
    &:hover {
      text-decoration: none;
    }
  }

  &__backIcon {
    margin-right: var(--gap-2);
  }
}
</style>
