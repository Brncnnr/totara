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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @package mod_contentmarketplace
-->
<template>
  <Tree
    v-model="open"
    class="tui-linkedinActivityContentTree"
    :tree-data="treeData"
    @input="$emit('input', $event)"
  >
    <!-- Branch label -->
    <template v-slot:custom-label="{ label }">
      {{ label }}
    </template>

    <!-- Leaf label -->
    <template v-slot:content="{ content }">
      <div class="tui-linkedinActivityContentTree__contents">
        <template v-for="(item, i) in content.items">
          <div :key="i" class="tui-linkedinActivityContentTree__contents-item">
            {{ item }}
          </div>
        </template>
      </div>
    </template>
  </Tree>
</template>

<script>
import Tree from 'tui/components/tree/Tree';

export default {
  components: {
    Tree,
  },

  props: {
    /**
     * Tree data for contents
     */
    treeData: {
      type: Array,
      required: true,
    },
    /**
     * List of open branches
     */
    value: {
      type: Array,
      required: true,
    },
  },

  data() {
    return {
      open: this.value,
    };
  },
};
</script>

<style lang="scss">
.tui-linkedinActivityContentTree {
  &__contents {
    padding: var(--gap-2) var(--gap-4);

    & > * + * {
      margin-top: var(--gap-2);
    }

    &-item {
      @include tui-font-body-small();
    }
  }
}
</style>
