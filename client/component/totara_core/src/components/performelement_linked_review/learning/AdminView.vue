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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module totara_core
-->

<template>
  <ParticipantContent :content="getPreviewData()" :preview="true" />
</template>

<script>
import ParticipantContent from 'totara_core/components/performelement_linked_review/learning/ParticipantContent';

export default {
  components: {
    ParticipantContent,
  },

  props: {
    data: {
      type: Object,
      required: true,
    },
  },

  methods: {
    /**
     * Set placeholder data for preview view
     *
     */
    getPreviewData() {
      return {
        fullname: this.$str('example_learning_fullname', 'totara_core'),
        description: this.$str('example_learning_description', 'totara_core'),
        image_src: this.data.content_type_settings.default_image,
        itemtype: 'course',
        progress: 25,
      };
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "example_learning_fullname",
      "example_learning_description"
    ]
  }
</lang-strings>
