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
  @module mod_approval
-->

<template>
  <Card class="tui-mod_approval-approvalsLoading">
    <div class="tui-mod_approval-approvalsLoading__display">
      <Loading aria-hidden="true" />
      <slot />
    </div>
  </Card>
</template>

<script>
import Card from 'tui/components/card/Card';
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    Card,
    Loading,
  },
};
</script>

<style lang="scss">
.tui-mod_approval-approvalsLoading {
  display: flex;
  flex-direction: column;
  align-items: center;
  // heights are hard-coded to match the height of the <AppovalLevel /> compnent at various screen sizes
  height: 193px;
  padding: var(--gap-4) var(--gap-6);

  &__display {
    position: sticky;
    top: var(--gap-6);
    bottom: var(--gap-6);
    display: flex;
    color: var(--color-neutral-6);
    font-size: var(--font-size-15);

    .tui-iconLoading {
      position: relative;
      top: 3px;
      margin-right: var(--gap-1);
    }
  }

  @media (min-width: $tui-screen-sm) {
    height: 173px;
  }

  @media (min-width: $tui-screen-md) {
    height: 145px;
  }
}
</style>
