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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module weka_notification_placeholder
-->

<template>
  <Placeholder :placeholder-key="placeholderKey" :display-name="displayName" />
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import Placeholder from 'totara_notification/components/json_editor/nodes/Placeholder';

export default {
  components: {
    Placeholder,
  },

  extends: BaseNode,

  computed: {
    placeholderKey() {
      const attrs = this.attrs;
      if (!attrs.key) {
        return '';
      }

      return attrs.key;
    },

    displayName() {
      const attrs = this.attrs;
      if (!attrs.label) {
        return '';
      }

      return attrs.label;
    },
  },
};
</script>
