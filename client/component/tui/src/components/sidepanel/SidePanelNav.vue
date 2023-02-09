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
  <nav class="tui-sidePanelNav" :aria-label="ariaLabel">
    <PropsProvider :provide="provide">
      <slot />
    </PropsProvider>
  </nav>
</template>

<script>
import PropsProvider from 'tui/components/util/PropsProvider';

export default {
  components: {
    PropsProvider,
  },

  props: {
    ariaLabel: [Boolean, String],
    value: [Boolean, Number, String],
  },

  methods: {
    provide() {
      return {
        props: {
          active: this.value,
        },
        listeners: {
          select: this.$_handleSelect,
        },
      };
    },

    $_handleSelect(selected) {
      this.$emit('input', selected.id);
      this.$emit('change', selected);
    },
  },
};
</script>

<style lang="scss">
.tui-sidePanelNav {
  padding: var(--gap-4) 0;
  background: var(--side-panel-nav-bg-color);

  & > * + * {
    margin-top: var(--gap-7);
  }
}
</style>
