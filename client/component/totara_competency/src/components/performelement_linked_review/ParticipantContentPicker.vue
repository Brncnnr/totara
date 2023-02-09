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
  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module totara_competency
-->

<template>
  <SelectContent
    :adder="getAdder()"
    :add-btn-text="$str('add_competencies', 'totara_competency')"
    :can-show-adder="canShowAdder"
    :cant-add-text="
      $str(
        'awaiting_selection_text',
        'totara_competency',
        coreRelationship[0].name
      )
    "
    :is-draft="isDraft"
    :participant-instance-id="participantInstanceId"
    :remove-text="$str('remove_competency', 'totara_competency')"
    :required="required"
    :section-element-id="sectionElementId"
    :user-id="userId"
    @unsaved-plugin-change="$emit('unsaved-plugin-change', $event)"
    @update="$emit('update', $event)"
  >
    <template v-slot:content-preview="{ content }">
      <component
        :is="previewComponent"
        :content="getItemData(content)"
        :subject-user="subjectUser"
      />
    </template>
  </SelectContent>
</template>

<script>
import AssignedCompetencyAdder from 'totara_competency/components/adder/AssignedCompetencyAdder';
import SelectContent from 'performelement_linked_review/components/SelectContent';

export default {
  components: {
    AssignedCompetencyAdder,
    SelectContent,
  },

  props: {
    canShowAdder: {
      type: Boolean,
      required: true,
    },
    coreRelationship: Array,
    isDraft: Boolean,
    participantInstanceId: {
      type: [String, Number],
      required: true,
    },
    previewComponent: [Function, Object],
    required: Boolean,
    sectionElementId: String,
    subjectUser: Object,
    userId: Number,
  },

  methods: {
    /**
     * Get adder component
     *
     * @return {Object}
     */
    getAdder() {
      return AssignedCompetencyAdder;
    },

    /**
     * Get data for competency preview component
     *
     * @param {Object} values
     * @return {Object}
     */
    getItemData(values) {
      return {
        competency: {
          display_name: values.competency.display_name,
          description: values.competency.description,
          id: values.competency.id,
        },
        assignment: {
          reason_assigned: values.reason_assigned,
        },

        achievement: {
          id: values.my_value.id,
          name: values.my_value.name,
          proficient: values.my_value.proficient,
        },
        scale_values: values.assignment.scale.values,
      };
    },
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "add_competencies",
    "awaiting_selection_text",
    "remove_competency"
  ]
}
</lang-strings>
