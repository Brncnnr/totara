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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module mod_approval
-->
<!-- TODO TL-33917: shift to core -->
<template>
  <div class="tui-mod_approval-transitionCard">
    <Card
      class="tui-mod_approval-transitionCard__card"
      :clickable="clickable"
      :no-border="noBorder"
      :has-hover-shadow="hasHoverShadow"
      :has-shadow="hasShadow"
    >
      <div class="tui-mod_approval-transitionCard__icon">
        <slot name="left-icon" />
      </div>
      <div class="tui-mod_approval-transitionCard__items">
        <template v-for="(item, index) in items">
          <div :key="index" class="tui-mod_approval-transitionCard__item">
            <div class="tui-mod_approval-transitionCard__item-header">
              {{ item.header }}
            </div>
            <div class="tui-mod_approval-transitionCard__item-text">
              {{ item.text }}
            </div>
          </div>
        </template>
      </div>
      <slot name="buttons" />
    </Card>
  </div>
</template>

<script>
import Card from 'tui/components/card/Card';

export default {
  name: 'TransitionCard',
  components: {
    Card,
  },
  props: {
    items: {
      type: Array,
      required: true,
    },
    clickable: Boolean,
    hasHoverShadow: Boolean,
    hasShadow: Boolean,
    noBorder: Boolean,
  },
};
</script>

<style lang="scss">
.tui-mod_approval-transitionCard {
  display: flex;
  &__card {
    display: flex;
    align-items: center;
    padding: var(--gap-2);
  }
  &__icon {
    margin: var(--gap-2);
    margin-right: var(--gap-4);
    color: var(--color-neutral-6);
  }
  &__items {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin: calc(var(--gap-1) * -1) 0;
  }
  &__item {
    margin: var(--gap-1) 0;
    margin-right: var(--gap-10);
    &:last-child {
      margin-right: var(--gap-2);
    }
  }
  &__item-header {
    @include tui-font-body-small();
    margin-bottom: var(--gap-1);
    color: var(--color-neutral-6);
    line-height: var(--font-body-line-height);
  }
  &__item-text {
    @include tui-font-body();
    color: var(--color-neutral-6);
    font-weight: bold;
  }
}
</style>
