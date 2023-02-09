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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performElementParticipantResponse">
    <slot name="content" />
  </div>
</template>

<script>
export default {
  props: {
    type: Object,
  },
};
</script>

<style lang="scss">
.tui-performElementParticipantResponse {
  display: flex;
  &__name {
    @include tui-font-body();
    margin-left: var(--gap-3);
  }
}
</style>
