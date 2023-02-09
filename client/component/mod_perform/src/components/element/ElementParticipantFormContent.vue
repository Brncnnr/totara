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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performElementParticipantFormContent">
    <!-- If participant has/had permission to respond to the element  -->
    <div
      v-if="participantCanAnswer && !viewOnly"
      class="tui-performElementParticipantFormContent__response"
    >
      <!-- If element is in a state where the participant can't provide a response
      (Complete, closed, print view)  -->
      <FormRow
        v-if="activeSectionIsClosed || fromPrint"
        :label="$str('your_response', 'mod_perform')"
        :vertical="!activeSectionIsClosed"
      >
        <div class="tui-performElementParticipantFormContent__response-output">
          <!-- If section is closed and form no longer editable display the response -->
          <component
            :is="responseComponent(elementComponents)"
            v-if="activeSectionIsClosed"
            :data="sectionElement.response_data_formatted_lines"
            :element="element"
          />

          <!-- If on print view show printable version of element -->
          <component
            :is="printComponent(elementComponents)"
            v-else
            :data="sectionElement.response_data_formatted_lines"
            :element="element"
          />
        </div>
      </FormRow>

      <!-- The participant has permission to provide a response and the element is still editable -->
      <template v-else>
        <div v-if="error">{{ error }}</div>
        <FormRow
          v-slot="{ labelId }"
          :accessible-label="element.title"
          :aria-describedby="groupId"
          :label="$str('your_response', 'mod_perform')"
          :optional="optional"
          :required="element.required"
        >
          <slot name="content" :labelId="labelId" />
        </FormRow>
      </template>
    </div>

    <!-- Display other participant responses for this element -->
    <OtherParticipantResponses
      v-if="sectionElement"
      v-show="showOtherResponse"
      :anonymous-responses="anonymousResponses"
      :section-element="sectionElement"
      :view-only="viewOnly"
    />
  </div>
</template>

<script>
import FormRow from 'tui/components/form/FormRow';
import OtherParticipantResponses from 'mod_perform/components/user_activities/participant/OtherParticipantResponses';

export default {
  components: {
    FormRow,
    OtherParticipantResponses,
  },

  props: {
    activeSectionIsClosed: Boolean,
    anonymousResponses: Boolean,
    element: Object,
    elementComponents: Object,
    error: String,
    fromPrint: Boolean,
    groupId: String,
    optional: Boolean,
    participantCanAnswer: Boolean,
    sectionElement: Object,
    showOtherResponse: Boolean,
    viewOnly: Boolean,
  },

  methods: {
    /**
     * Returns print component
     *
     * @param {Object} components
     * @return {Function}
     */
    printComponent(components) {
      return tui.asyncComponent(components.participant_print_component);
    },

    /**
     * Returns response component
     *
     * @param {Object} components
     * @return {Function}
     */
    responseComponent(components) {
      return tui.asyncComponent(components.participant_response_component);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "your_response"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performElementParticipantFormContent {
  & > * + * {
    margin-top: var(--gap-8);
  }

  &__response {
    &-output {
      padding-top: var(--gap-1);
    }
  }
}
</style>
