<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<template>
  <div
    ref="content"
    class="tui-editor_weka-layoutColumn"
    :class="{ 'tui-editor_weka-layoutColumn--sidebar': isSidebar }"
  />
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';

export default {
  extends: BaseNode,

  computed: {
    isSidebar() {
      return this.attrs.type === 'sidebar';
    },
  },
};
</script>

<style lang="scss">
.tui-editor_weka-layoutColumn {
  // Switch to single column layout below 50rem
  // https://every-layout.dev/layouts/switcher/
  flex-basis: calc((50rem - 100%) * 999);
  flex-grow: 1;
  min-width: 0;
  margin-right: -1px;
  margin-bottom: -1px;
  padding: var(--gap-2);
  border: 1px dashed var(--color-neutral-5);

  > p:last-child {
    margin-bottom: 0;
  }

  &--sidebar {
    flex-grow: 0.4;
  }
}
</style>
