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
  <div class="tui-sidePanelNavGroup">
    <div v-if="title" class="tui-sidePanelNavGroup__heading">
      <h2
        :id="$id('side-panel-nav-group-heading-title')"
        class="tui-sidePanelNavGroup__heading-title"
      >
        {{ title }}
      </h2>

      <div class="tui-sidePanelNavGroup__heading-side">
        <slot name="heading-side" />
      </div>
    </div>
    <ul
      class="tui-sidePanelNavGroup__items"
      :aria-labelledby="
        title ? $id('side-panel-nav-group-heading-title') : null
      "
    >
      <PropsProvider :provide="provide">
        <slot />
      </PropsProvider>
    </ul>
  </div>
</template>

<script>
import PropsProvider from 'tui/components/util/PropsProvider';

export default {
  components: {
    PropsProvider,
  },

  props: {
    active: [Boolean, Number, String],
    title: [Boolean, String],
  },

  methods: {
    provide() {
      return {
        props: {
          active: this.active,
        },
        listeners: {
          select: this.$_handleSelect,
        },
      };
    },

    $_handleSelect(value) {
      this.$emit('select', value);
    },
  },
};
</script>

<style lang="scss">
.tui-sidePanelNavGroup {
  & > * + * {
    margin-top: var(--gap-2);
  }

  &__heading {
    display: flex;
    align-items: center;
    padding: 0 var(--gap-4);
    padding-left: var(--sidepanel-navigation-item-padding-left);

    &-title {
      margin: 0;
      @include tui-font-heading-label();
    }

    &-side {
      margin-left: auto;
      padding-left: var(--gap-2);
    }
  }

  &__items {
    margin-left: 0;
    list-style: none;
  }
}
</style>
