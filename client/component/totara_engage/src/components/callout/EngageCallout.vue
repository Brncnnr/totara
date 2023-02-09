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

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-engageCallout">
    <div class="tui-engageCallout__warning">
      <warning size="800" :custom-class="'tui-icon--warning'" />
    </div>
    <span class="tui-engageCallout__info">
      {{ $str('tag_warning', 'totara_engage') }}
    </span>
  </div>
</template>

<script>
import Warning from 'tui/components/icons/Warning';

export default {
  components: {
    Warning,
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "tag_warning"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageCallout {
  display: flex;
  padding: var(--gap-4);
  border-top: var(--border-width-thin) solid var(--color-neutral-5);
  border-right: var(--border-width-thin) solid var(--color-neutral-5);
  border-bottom: var(--border-width-thin) solid var(--color-neutral-5);
  border-left: var(--gap-1) solid var(--color-prompt-warning);
  border-radius: 6px;

  &__info {
    @include tui-font-body();
    margin-left: var(--gap-4);
  }
}
</style>
