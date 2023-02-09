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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @package mod_perform
-->

<template>
  <div class="tui-performActivityParticipantSelector">
    <h3 class="tui-performActivityParticipantSelector__title">
      {{ instanceTitle }}
    </h3>

    <div
      v-if="$slots.meta"
      class="tui-performActivityParticipantSelector__meta"
    >
      <slot name="meta" />
    </div>

    <Uniform
      :initial-values="initialValues"
      :validate="validateExternal"
      input-width="full"
      class="tui-performActivityParticipantSelector__form"
      @change="updateHasChanges"
      @submit="submit"
    >
      <FormField v-if="validate" name="error" />

      <FormRow
        v-for="relationship in relationships"
        :key="relationship.id"
        :label="relationship.name"
        :required="requireInput"
      >
        <ExternalUserSelector
          v-if="isExternal(relationship)"
          :name="relationship.id"
        />

        <FormField
          v-else
          v-slot="{ value, update }"
          :name="relationship.id"
          :validate="validateInternal"
          :char-length="30"
        >
          <UserSelector
            v-model="value"
            :subject-instance-id="subjectInstance.id"
            :exclude-users="[subjectInstance.subject_user.id]"
            @input="update"
          />
        </FormField>
      </FormRow>

      <FormRow v-if="relationships.length > 0">
        <ButtonGroup>
          <Button
            :styleclass="{ primary: 'true' }"
            :text="$str('save', 'totara_core')"
            :disabled="isSaving"
            type="submit"
          />
          <slot name="buttons" />
        </ButtonGroup>
      </FormRow>
    </Uniform>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import ExternalUserSelector from 'mod_perform/components/user_activities/participant_selector/ExternalUserSelector';
import UserSelector from 'mod_perform/components/user_activities/participant_selector/UserSelector';
import { FormField, FormRow, Uniform } from 'tui/components/uniform';
import { RELATIONSHIP_PERFORM_EXTERNAL } from 'mod_perform/constants';

export default {
  components: {
    Button,
    ButtonGroup,
    ExternalUserSelector,
    FormField,
    FormRow,
    Uniform,
    UserSelector,
  },

  props: {
    subjectInstance: {
      required: true,
      type: Object,
    },
    relationships: {
      required: true,
      type: Array,
    },
    requireInput: {
      required: true,
      type: Boolean,
    },
    isSaving: {
      required: true,
      type: Boolean,
    },
    validate: {
      type: Function,
      default: null,
    },
  },

  data() {
    return {
      initialValues: this.getInitialValues(),
      hasChanges: false,
    };
  },

  computed: {
    /**
     * Get the title to display for this user's activity.
     *
     * @returns {String}
     */
    instanceTitle() {
      return this.$str('activity_title_for_subject', 'mod_perform', {
        activity: this.subjectInstance.activity.name,
        user: this.subjectInstance.subject_user.fullname,
      });
    },
  },

  mounted() {
    // Confirm navigation away if user has made a selection without saving.
    window.addEventListener('beforeunload', this.unloadHandler);
  },

  beforeDestroy() {
    // Remove the navigation warning otherwise it may appear despite this component not existing!
    window.removeEventListener('beforeunload', this.unloadHandler);
  },

  methods: {
    /**
     * Is the specified relationship for external participants?
     *
     * @param {Object|Number} relationship
     * @returns {Boolean}
     */
    isExternal(relationship) {
      if (!isNaN(relationship)) {
        // Find the relationship object from it's ID.
        relationship = this.relationships.find(r => r.id === relationship);
      }

      return relationship.idnumber === RELATIONSHIP_PERFORM_EXTERNAL;
    },

    /**
     * Handle submission of the subject instance participants.
     *
     * @param {Object} data The submitted form data.
     */
    async submit(data) {
      const participants = Object.keys(data).map(relationshipId => {
        return {
          relationship_id: relationshipId,
          users: data[relationshipId].map(user => {
            if (this.isExternal(relationshipId)) {
              return user;
            }
            return {
              user_id: user.id,
            };
          }),
        };
      });
      this.$emit('submit', participants);
    },

    /**
     * Get form errors if an internal relationship doesn't have at least one participant.
     *
     * @param {Object} values The submitted form data.
     * @returns {Object} validation errors
     */
    validateInternal(values) {
      if (this.requireInput && values.length === 0) {
        return this.$str('error_no_participants_selected', 'mod_perform');
      }
      return false;
    },

    /**
     * Get form errors if an external relationship has duplicate participant emails.
     *
     * @param {Object} values The submitted form data.
     * @returns {Object} validation errors
     */
    validateExternal(values) {
      let errors = {};
      if (this.validate != null) {
        errors = this.validate(values);
      }

      Object.keys(values).forEach(relationshipId => {
        if (this.isExternal(relationshipId)) {
          errors[relationshipId] = [];
          let externalValues = values[relationshipId];
          let emails = [];
          Object.keys(externalValues).forEach(formId => {
            errors[relationshipId][formId] = {};
            let user = externalValues[formId];
            if (emails.includes(user.email)) {
              errors[relationshipId][formId].email = this.$str(
                'error_external_participant_duplicate_email',
                'mod_perform'
              );
            } else {
              emails.push(user.email);
            }
          });
        }
      });
      return errors;
    },

    /**
     * Get the initial form data.
     *
     * Since a user can only select participants once, we don't need to set it to previously entered data.
     * So we just populate it with empty arrays.
     *
     * @returns {Object}
     */
    getInitialValues() {
      let values = {};
      this.relationships.forEach(relationship => {
        values[relationship.id] = [];
        if (this.requireInput && this.isExternal(relationship)) {
          values[relationship.id] = [
            {
              email: '',
              name: '',
            },
          ];
        }
      });
      return values;
    },

    /**
     * Check and store whether there have been changes.
     *
     * @param {Object} values The form data.
     * @returns {boolean}
     */
    updateHasChanges(values) {
      this.hasChanges = Object.keys(values).some(relationship => {
        if (this.isExternal(relationship)) {
          return values[relationship].some(user => {
            return user.name.length > 0 || user.email.length > 0;
          });
        } else {
          return values[relationship].length > 0;
        }
      });
    },

    /**
     * Displays a warning message if the user tries to navigate away without saving.
     *
     * @param {Event} e
     * @returns {String|void}
     */
    unloadHandler(e) {
      if (!this.hasChanges) {
        return;
      }

      // For older browsers that still show custom message.
      const discardUnsavedChanges = this.$str(
        'unsaved_changes_warning',
        'mod_perform'
      );
      e.preventDefault();
      e.returnValue = discardUnsavedChanges;
      return discardUnsavedChanges;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_title_for_subject",
      "error_external_participant_duplicate_email",
      "error_no_participants_selected",
      "external_user_email",
      "external_user_email_help",
      "external_user_name",
      "external_user_name_help",
      "unsaved_changes_warning"
    ],
    "totara_core": [
      "save"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performActivityParticipantSelector {
  padding: var(--gap-4);
  border: var(--border-width-thin) solid var(--card-border-color);

  &__title {
    @include tui-font-heading-small;
    margin: 0;
  }

  &__meta {
    margin-top: var(--gap-2);
  }

  &__form {
    margin-top: var(--gap-4);
  }
}
</style>
