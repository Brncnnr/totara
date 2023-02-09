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
  @module totara_competency
-->

<template>
  <ParticipantContent :content="getPreviewData()" :preview="true" />
</template>

<script>
import ParticipantContent from 'totara_competency/components/performelement_linked_review/ParticipantContent';

export default {
  components: {
    ParticipantContent,
  },

  methods: {
    /**
     * Set placeholder data for preview view
     *
     */
    getPreviewData() {
      return {
        competency: {
          display_name: this.$str(
            'example_competency_title',
            'totara_competency'
          ),
          description: this.$str(
            'example_competency_description',
            'totara_competency'
          ),
        },
        assignment: {
          reason_assigned: this.$str(
            'assignment_reason_self',
            'totara_competency'
          ),
        },

        achievement: {
          id: '1',
          name: this.$str('rating_value', 'totara_competency', 1),
          proficient: true,
        },

        scale_values: [
          {
            id: '1',
            name: this.$str('rating_value', 'totara_competency', 1),
            proficient: true,
          },
          {
            id: '2',
            name: this.$str('rating_value', 'totara_competency', 2),
            proficient: false,
          },
          {
            id: '3',
            name: this.$str('rating_value', 'totara_competency', 3),
            proficient: false,
          },
        ],
      };
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "assignment_reason_self",
      "example_competency_description",
      "example_competency_title",
      "rating_value"
    ]
  }
</lang-strings>
