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

  @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
  @module performelement_static_content
-->

<template>
  <div class="tui-staticContentAdminView">
    <div ref="content" v-html="data.content" />
  </div>
</template>

<script>
// Utils
import tui from 'tui/tui';

export default {
  inheritAttrs: false,

  props: {
    data: Object,
  },

  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    $_scan() {
      this.$nextTick().then(() => {
        let content = this.$refs.content;
        if (!content) {
          return;
        }

        tui.scan(content);
      });
    },
  },
};
</script>
