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
  <Emoji :short-code="shortCode" class="tui-wekaEmoji" />
</template>

<script>
import Emoji from 'tui/components/json_editor/nodes/Emoji';
import BaseNode from 'editor_weka/components/nodes/BaseNode';

export default {
  components: {
    Emoji,
  },

  extends: BaseNode,

  computed: {
    shortCode() {
      if (!this.attrs.shortcode) {
        return undefined;
      }

      return this.attrs.shortcode;
    },
  },
};
</script>
