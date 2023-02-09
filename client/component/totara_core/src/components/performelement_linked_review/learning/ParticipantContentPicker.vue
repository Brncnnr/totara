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
  <SelectContent
    :adder="getAdder()"
    :add-btn-text="$str('add_learning', 'totara_core')"
    :can-show-adder="canShowAdder"
    :cant-add-text="
      $str('awaiting_selection_text', 'totara_core', coreRelationship[0].name)
    "
    :is-draft="isDraft"
    :participant-instance-id="participantInstanceId"
    :remove-text="$str('remove_learning', 'totara_core')"
    :required="required"
    :section-element-id="sectionElementId"
    :user-id="userId"
    :additional-content="['itemtype']"
    :get-id="content => ('unique_id' in content ? content.unique_id : null)"
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
import LearningAdder from 'totara_core/components/adder/LearningAdder';
import SelectContent from 'performelement_linked_review/components/SelectContent';

export default {
  components: {
    LearningAdder,
    SelectContent,
  },

  props: {
    canShowAdder: {
      type: Boolean,
      required: true,
    },
    coreRelationship: {
      type: Array,
      required: true,
    },
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
      return LearningAdder;
    },

    /**
     * Get data for learning preview component
     *
     * @param {Object} values
     * @return {Object}
     */
    getItemData(values) {
      // For now we don't need to map any values
      return values;
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "add_learning",
    "awaiting_selection_text",
    "remove_learning"
  ]
}
</lang-strings>
