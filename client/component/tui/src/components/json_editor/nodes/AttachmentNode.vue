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
  @module totara_core
-->

<template>
  <FileCard
    v-bind="$props"
    class="tui-attachmentNode"
    :class="{
      'tui-attachmentNode--downloadable': !!downloadUrl,
    }"
  />
</template>

<script>
import FileCard from 'tui/components/file/FileCard';

export default {
  components: {
    FileCard,
  },

  props: {
    fileSize: {
      type: [String, Number],
      required: true,
    },

    filename: {
      type: String,
      required: true,
    },

    downloadUrl: {
      type: String,
      default: null,
    },

    option: {
      type: Object,
      default() {
        return {};
      },
    },
  },
};
</script>
