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

  @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module performelement_long_text
-->

<template>
  <!-- Handle the different view switching (read only / print / form),
  populate form content if editable and display others responses -->
  <ElementParticipantFormContent
    v-bind="$attrs"
    :element="element"
    :error="error"
    :is-draft="isDraft"
    :section-element="sectionElement"
  >
    <template v-slot:content>
      <FormScope v-if="loaded" :path="path" :process="process">
        <FormField
          v-slot="{ value: formValue, update: formUpdate }"
          name="response"
          :validations="validations"
          :char-length="50"
          :error="error"
        >
          <WekaWrapper
            v-slot="{ value, update }"
            :value="formValue"
            @update="formUpdate"
          >
            <Weka
              :value="value"
              :usage-identifier="{
                component: 'performelement_long_text',
                area: 'response',
                instanceId: sectionElement.id,
              }"
              variant="description"
              :file-item-id="draftFileId"
              :is-logged-in="!isExternalParticipant"
              @input="update"
            />
          </WekaWrapper>
        </FormField>
      </FormScope>
    </template>
  </ElementParticipantFormContent>
</template>

<script>
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';
import { FormField } from 'tui/components/uniform';
import FormScope from 'tui/components/reform/FormScope';
import Weka from 'editor_weka/components/Weka';
import WekaWrapper from 'performelement_long_text/components/WekaWrapper';
import { v as validation } from 'tui/validation';
// GraphQL queries
import getDraftId from 'performelement_long_text/graphql/get_draft_id';

export default {
  components: {
    ElementParticipantFormContent,
    FormField,
    FormScope,
    Weka,
    WekaWrapper,
  },

  props: {
    element: Object,
    error: String,
    isDraft: Boolean,
    isExternalParticipant: Boolean,
    participantInstanceId: {
      type: [String, Number],
      required: false,
    },
    subjectInstanceId: {
      type: [String, Number],
      required: false,
    },
    path: {
      type: [String, Array],
      default: '',
    },
    sectionElement: Object,
  },

  data() {
    return {
      draftFileId: 0,
    };
  },

  apollo: {
    /**
     * Get the draft file area id to be used for temporarily storing uploaded files.
     */
    draftFileId: {
      query: getDraftId,
      variables() {
        return {
          section_element_id: this.sectionElement.id,
          participant_instance_id: this.participantInstanceId,
        };
      },
      update({ draft_id: draftFileId }) {
        return draftFileId;
      },
      skip() {
        // File upload is problematic for external participants
        // and it is not needed for the view-only form (no participant instance id).
        return this.isExternalParticipant || !this.participantInstanceId;
      },
    },
  },

  computed: {
    /**
     * Have the required queries been loaded?
     * @return {Boolean}
     */
    loaded() {
      return this.draftFileId || this.isExternalParticipant;
    },

    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @return {(function|object)[]}
     */
    validations() {
      if (!this.isDraft && this.element && this.element.is_required) {
        return [validation.required()];
      }

      return [];
    },
  },

  methods: {
    /**
     * Process the form values.
     *
     * @param {Object} value
     * @return {Object|null}
     */
    process(value) {
      if (!value || !value.response) {
        return null;
      }

      return {
        draft_id: this.draftFileId,
        weka: value.response,
      };
    },
  },
};
</script>
