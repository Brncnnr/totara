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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_linked_review
-->
<template>
  <div class="tui-linkedReviewParticipantPrint">
    <div v-if="hasBeenAnswered">{{ data[0] }}</div>
    <NotepadLines v-else :lines="6" />
  </div>
</template>

<script>
import NotepadLines from 'tui/components/form/NotepadLines';

export default {
  components: {
    NotepadLines,
  },
  props: {
    data: {
      type: Array,
      required: true,
    },
  },
  computed: {
    /**
     * Has this question been answered.
     *
     * @return {boolean}
     */
    hasBeenAnswered() {
      return this.data.length > 0;
    },
  },
};
</script>
