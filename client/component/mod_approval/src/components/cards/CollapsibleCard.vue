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
  <Card
    class="tui-mod_approval-collapsibleCard"
    :clickable="clickable"
    :has-hover-shadow="hasHoverShadow"
    :has-shadow="hasShadow"
    :no-border="noBorder"
  >
    <Collapsible
      :id="id"
      class="tui-mod_approval-collapsibleCard__collapsible"
      :label="label"
      :initial-state="initialState"
      :value="value"
      variant="minimal"
      padding="large"
      @input="$emit('input', $event)"
    >
      <template v-if="$scopedSlots.actions" v-slot:collapsible-side-content>
        <slot name="actions" />
      </template>
      <div class="tui-mod_approval-collapsibleCard__content">
        <slot name="content" />
      </div>
    </Collapsible>
  </Card>
</template>

<script>
import Card from 'tui/components/card/Card';
import Collapsible from 'tui/components/collapsible/Collapsible';

export default {
  name: 'CollapsibleCard',
  components: {
    Card,
    Collapsible,
  },
  props: {
    label: {
      type: String,
      required: true,
    },
    clickable: Boolean,
    hasHoverShadow: Boolean,
    hasShadow: Boolean,
    noBorder: Boolean,
    alwaysRender: Boolean,
    id: {
      type: [String, Number],
    },
    initialState: {
      default: false,
      type: Boolean,
    },
    value: {
      default: undefined,
      type: Boolean,
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-collapsibleCard {
  &__collapsible {
    flex-grow: 1;
  }
}
</style>
