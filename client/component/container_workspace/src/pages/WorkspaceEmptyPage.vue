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
<!-- This is mainly for user who does not have ability to view workspace -->
<template>
  <div class="tui-workspaceEmptyPage">
    <p class="tui-workspaceEmptyPage__text">
      {{ getContentMessage }}
    </p>
  </div>
</template>
<script>
export default {
  props: {
    notFound: {
      type: Boolean,
      default: false,
    },
    type: {
      type: String,
      default: 'workspace',
      validator(prop) {
        return ['workspace', 'discussion'].includes(prop);
      },
    },
  },
  computed: {
    getContentMessage() {
      if (!this.notFound) {
        return this.$str('error:view_workspace', 'container_workspace');
      }
      return this.type == 'workspace'
        ? this.$str('error:workspace_not_found', 'container_workspace')
        : this.$str('error:discussion_not_found', 'container_workspace');
    },
  },
};
</script>

<lang-strings>
{
  "container_workspace": [
    "error:view_workspace",
    "error:discussion_not_found",
    "error:workspace_not_found"
  ]
}
</lang-strings>

<style lang="scss">
.tui-workspaceEmptyPage {
  display: flex;
  width: 100%;
  margin-top: var(--gap-6);
  padding: 0 var(--gap-6);

  &__text {
    @include tui-font-body();
  }
}
</style>
