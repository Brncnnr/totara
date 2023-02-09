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
  <span class="tui-fileSize">
    {{ fileSize }}
  </span>
</template>

<script>
import { getReadableSize } from 'tui/file';
import { toVueRequirements } from 'tui/i18n';

export default {
  props: {
    size: {
      type: [String, Number],
      default: 0,
      validator(prop) {
        prop = parseInt(prop);
        return !isNaN(prop);
      },
    },
  },
  langStrings: toVueRequirements(getReadableSize.langStrings),
  data() {
    return {
      fileSize: '',
    };
  },

  async mounted() {
    this.fileSize = await getReadableSize(this.size);
  },
};
</script>
