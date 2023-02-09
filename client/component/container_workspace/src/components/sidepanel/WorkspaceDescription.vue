<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <ActionCard class="tui-workspaceDescription" :has-shadow="true">
    <div slot="card-body" class="tui-workspaceDescription__body">
      <p class="tui-workspaceDescription__time">
        {{ timeDescription }}
      </p>

      <div ref="description" v-html="description" />
    </div>
  </ActionCard>
</template>

<script>
import ActionCard from 'tui/components/card/ActionCard';
import tui from 'tui/tui';

export default {
  components: {
    ActionCard,
  },

  props: {
    timeDescription: {
      type: String,
      required: true,
    },

    description: {
      type: String,
      required: true,
    },
  },

  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    $_scan() {
      if (!this.$refs.description) {
        return;
      }

      let element = this.$refs.description;
      tui.scan(element);
    },
  },
};
</script>

<style lang="scss">
.tui-workspaceDescription {
  &__time {
    @include tui-font-body-small();
    margin-bottom: var(--gap-4);
  }
}
</style>
