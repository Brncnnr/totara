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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @package totara_evidence
-->

<template>
  <SelectContent
    :adder="adder"
    :add-btn-text="$str('add_evidence', 'totara_evidence')"
    :can-show-adder="canShowAdder"
    :cant-add-text="cantAddText"
    :is-draft="isDraft"
    :participant-instance-id="participantInstanceId"
    :remove-text="$str('remove_evidence', 'totara_evidence')"
    :required="required"
    :section-element-id="sectionElementId"
    :user-id="userId"
    @unsaved-plugin-change="$emit('unsaved-plugin-change', $event)"
    @update="$emit('update', $event)"
  >
    <template v-slot:content-preview="{ content }">
      <component :is="previewComponent" :content="getItemData(content)" />
    </template>
  </SelectContent>
</template>

<script>
import EvidenceAdder from 'totara_evidence/components/adder/EvidenceAdder';
import SelectContent from 'performelement_linked_review/components/SelectContent';

export default {
  components: {
    EvidenceAdder,
    SelectContent,
  },

  props: {
    canShowAdder: {
      type: Boolean,
      required: true,
    },
    contentType: String,
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

  computed: {
    cantAddText() {
      return this.$str(
        'awaiting_selection_text',
        'totara_evidence',
        this.coreRelationship[0].name
      );
    },

    /**
     * Get adder component
     *
     * @return {Object}
     */
    adder() {
      return EvidenceAdder;
    },
  },

  methods: {
    /**
     * Get data for competency preview component
     *
     * @param {Object} values
     * @return {Object}
     */
    getItemData({ created_at, fields, id, name, type }) {
      return {
        display_name: name,
        id: id,
        fields: fields,
        type: type,
        content_type: this.contentType,
        created_at: created_at,
      };
    },
  },
};
</script>

<lang-strings>
{
  "totara_evidence": [
    "add_evidence",
    "awaiting_selection_text",
    "remove_evidence"
  ]
}
</lang-strings>
