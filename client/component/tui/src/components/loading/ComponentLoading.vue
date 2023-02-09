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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-componentLoading">
    <Loading :alt="$str('loading', 'core')" />
  </div>
</template>

<script>
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    Loading,
  },
};
</script>

<lang-strings>
{
  "core": [
    "loading"
  ]
}
</lang-strings>

<style lang="scss">
.tui-componentLoading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--gap-3);
  color: var(--color-neutral-6);
}
</style>
