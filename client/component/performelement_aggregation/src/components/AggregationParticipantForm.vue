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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @package mod_perform
-->

<template>
  <div class="tui-aggregationParticipantForm">
    <div v-if="hasExcludedValues">
      {{
        $str(
          'header_blurb_with_exclusions',
          'performelement_aggregation',
          excludedValuesCsv
        )
      }}
    </div>
    <div v-else>
      {{ $str('header_blurb', 'performelement_aggregation') }}
    </div>

    <ElementParticipantFormContent
      v-bind="$attrs"
      :participant-can-answer="sectionElement.can_respond"
      :element="element"
      :section-element="sectionElement"
      :from-print="false"
      :is-draft="isDraft"
    >
      <template v-slot:content="{ labelId }">
        <ElementParticipantResponse>
          <template v-slot:content>
            <ResponseDisplay
              :element="element"
              :data="sectionElement.response_data_formatted_lines"
            />
          </template>
        </ElementParticipantResponse>
      </template>
    </ElementParticipantFormContent>
  </div>
</template>

<script>
import ElementParticipantResponse from 'mod_perform/components/element/ElementParticipantResponse';
import ResponseDisplay from 'mod_perform/components/element/participant_form/ResponseDisplay';
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';

export default {
  components: {
    ElementParticipantFormContent,
    ElementParticipantResponse,
    ResponseDisplay,
  },
  props: {
    path: [String, Array],
    error: String,
    isDraft: Boolean,
    element: {
      type: Object,
      required: true,
    },
    participantInstanceId: {
      type: Number,
      required: false,
    },
    sectionElement: {
      type: Object,
      required: true,
    },
  },
  computed: {
    hasExcludedValues() {
      return this.excludedValues.length > 0;
    },
    excludedValuesCsv() {
      return this.excludedValues.join(', ');
    },
    excludedValues() {
      if (!this.element.data.excludedValues) {
        return [];
      }

      // Remove any empty entries.
      return this.element.data.excludedValues.filter(
        value => value !== null && String(value).trim() !== ''
      );
    },
  },
};
</script>

<style lang="scss">
.tui-aggregationParticipantForm {
  & > * + * {
    margin-top: var(--gap-4);
  }
}
</style>

<lang-strings>
{
  "performelement_aggregation": [
    "header_blurb",
    "header_blurb_with_exclusions"
  ]
}
</lang-strings>
