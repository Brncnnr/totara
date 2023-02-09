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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module editor_weka
-->

<template>
  <span class="tui-wekaMention">
    <Mention :user-id="userId" :fullname="displayName" @click.native.prevent />
  </span>
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import Mention from 'tui/components/json_editor/nodes/Mention';

export default {
  components: {
    Mention,
  },
  extends: BaseNode,

  computed: {
    displayName() {
      let attrs = this.attrs;

      if (!attrs.display) {
        return '';
      }

      return attrs.display;
    },

    userId() {
      let attrs = this.attrs;

      if (!attrs.id) {
        return 0;
      }

      return attrs.id;
    },
  },
};
</script>

<style lang="scss">
.tui-wekaMention {
  display: inline-block;
  white-space: normal;
}
</style>
