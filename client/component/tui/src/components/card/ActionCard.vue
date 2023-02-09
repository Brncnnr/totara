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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <Card
    class="tui-actionCard"
    :class="{
      'tui-actionCard--shaded': shaded,
      'tui-actionCard--stacked': stacked,
    }"
    :has-shadow="hasShadow"
    :no-border="noBorder"
  >
    <div class="tui-actionCard__body">
      <slot name="card-body" />
    </div>
    <div v-if="this.$slots['card-action']" class="tui-actionCard__action">
      <slot name="card-action" />
    </div>
  </Card>
</template>

<script>
import Card from 'tui/components/card/Card';

export default {
  components: {
    Card,
  },

  props: {
    hasShadow: Boolean,
    noBorder: Boolean,
    shaded: Boolean,
    stacked: Boolean,
  },
};
</script>

<style lang="scss">
.tui-actionCard {
  flex-direction: column;
  padding: var(--gap-4);

  &__body {
    display: flex;
    flex-grow: 1;
    flex-shrink: 1;
    flex-wrap: wrap;
    align-items: center;
  }

  &__action {
    display: flex;
    flex-shrink: 0;
    align-items: normal;
    padding: var(--gap-4) 0 0;
  }

  &--shaded {
    background: var(--color-neutral-3);
  }
}

@media screen and (min-width: $tui-screen-sm) {
  .tui-actionCard {
    flex-direction: row;
    &--stacked {
      flex-direction: column;
    }

    &:not(&--stacked) &__action {
      align-items: center;
      padding: 0 0 0 var(--gap-4);
    }
  }
}
</style>
