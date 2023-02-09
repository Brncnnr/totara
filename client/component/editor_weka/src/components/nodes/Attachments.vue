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
  <AttachmentNodeCollection class="tui-wekaAttachments">
    <div ref="content" class="tui-wekaAttachments__content">
      <!-- Attachments collection. A content placeholder that will hold the list of the attachments in here. -->
    </div>
  </AttachmentNodeCollection>
</template>

<script>
import AttachmentNodeCollection from 'tui/components/json_editor/nodes/AttachmentNodeCollection';

export default {
  components: {
    AttachmentNodeCollection,
  },
};
</script>

<style lang="scss">
.tui-wekaAttachments {
  margin: 0 0 var(--paragraph-gap);

  &__content {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    width: 100%;
    white-space: normal;

    > .tui-wekaAttachment {
      // Doing the margin for the single attachment.
      margin-top: var(--gap-1);
      margin-right: var(--gap-1);
      margin-bottom: var(--gap-1);

      @media (min-width: 720px) {
        margin-right: var(--gap-4);
      }
    }
  }
}
</style>
