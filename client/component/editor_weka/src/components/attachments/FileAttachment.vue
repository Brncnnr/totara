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
  <!-- Component for displaying draft file as attachment -->
  <div>
    <AttachmentNode
      :filename="filename"
      :file-size="fileSize"
      :item-id="draftId"
      component="user"
      area="draft"
    />

    <NodeBar
      v-if="!disabled"
      :actions="actions"
      :aria-label="$str('actions_menu_for', 'editor_weka', filename)"
    />
  </div>
</template>

<script>
import AttachmentNode from 'tui/components/json_editor/nodes/AttachmentNode';
import NodeBar from 'editor_weka/components/toolbar/NodeBar';
import AttachmentMixin from 'editor_weka/mixins/attachment_mixin';

export default {
  components: {
    AttachmentNode,
    NodeBar,
  },

  mixins: [AttachmentMixin],

  computed: {
    actions() {
      let actions = [
        {
          label: this.$str('remove', 'core'),
          action: () => {
            this.$emit('delete');
          },
        },
      ];

      if (this.hasDownloadUrl) {
        actions.push({
          label: this.$str('download', 'core'),
          action: () => {
            this.$emit('download');
          },
        });
      }

      return actions;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "remove",
      "download"
  ],
    "editor_weka": [
      "actions_menu_for"
    ]
  }
</lang-strings>
