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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module totara_perform
-->

<template>
  <Form class="tui-performManageActivityGeneralInfo">
    <!-- General -->
    <FormRowStack spacing="large">
      <h3 class="tui-performManageActivityGeneralInfo__heading">
        {{ $str('activity_general_tab_heading', 'mod_perform') }}
      </h3>

      <FormRow
        v-slot="{ id }"
        :label="$str('general_info_label_activity_title', 'mod_perform')"
        required
      >
        <InputText
          :id="id"
          v-model="form.name"
          :maxlength="ACTIVITY_NAME_MAX_LENGTH"
          char-length="50"
        />
      </FormRow>

      <FormRow
        v-slot="{ id }"
        :label="$str('general_info_label_activity_description', 'mod_perform')"
      >
        <Textarea
          :id="id"
          v-model="form.description"
          char-length="50"
          :rows="4"
        />
      </FormRow>

      <FormRow
        v-slot="{ id }"
        :label="$str('general_info_label_activity_type', 'mod_perform')"
      >
        <div>
          <span v-if="isActive" class="tui-performManageActivityGeneralInfo">{{
            value.type.display_name
          }}</span>
          <Select
            v-else
            :id="id"
            v-model="form.type_id"
            :aria-labelledby="id"
            :aria-describedby="$id('aria-describedby')"
            :options="activityTypes"
          />
        </div>
      </FormRow>
    </FormRowStack>

    <!-- Response attribution and visibility -->
    <FormRowStack spacing="large">
      <h3 class="tui-performManageActivityGeneralInfo__heading">
        {{
          $str('activity_general_response_attribution_heading', 'mod_perform')
        }}
      </h3>

      <FormRow
        :label="
          $str('activity_general_anonymous_responses_label', 'mod_perform')
        "
        :helpmsg="
          $str('activity_general_anonymous_responses_label_help', 'mod_perform')
        "
      >
        <div>
          <span v-if="isActive">
            {{ activeToggleText(value.anonymous_responses) }}
          </span>
          <ToggleSwitch
            v-else
            v-model="form.anonymousResponse"
            toggle-first
            :aria-label="
              $str('activity_general_anonymous_responses_label', 'mod_perform')
            "
            @input="anonymityValueChanged"
          />
        </div>
      </FormRow>

      <FormRow
        v-slot="{ labelId }"
        :label="$str('visibility_condition_label', 'mod_perform')"
        :helpmsg="$str('visibility_condition_label_help', 'mod_perform')"
      >
        <div>
          <RadioGroup
            v-if="!isAnonymousResponse"
            v-model="form.visibilityConditionValue"
            :aria-labelledby="labelId"
          >
            <Radio
              v-for="item in visibilityConditionOptions"
              :key="item.value"
              :value="item.value"
            >
              {{ item.name }}
            </Radio>
          </RadioGroup>
          <span v-else>
            {{ $str('visibility_condition_all_closed', 'mod_perform') }}
          </span>
        </div>
      </FormRow>

      <FormRow v-if="showWarning">
        <div class="tui-performManageActivityGeneralInfo__warning">
          <NotificationBanner
            type="warning"
            :message="
              $str(
                'visibility_condition_status_mismatch_warning',
                'mod_perform'
              )
            "
          />
        </div>
      </FormRow>
    </FormRowStack>

    <FormRow class="tui-performManageActivityGeneralInfo__buttons">
      <ButtonGroup>
        <Button
          :styleclass="{ primary: true }"
          :text="$str('save_changes', 'mod_perform')"
          :disabled="isSaving || hasNoTitle"
          type="submit"
          @click.prevent="trySave"
        />
        <Button
          :disabled="isSaving"
          :text="$str('cancel', 'core')"
          @click="resetChanges"
        />
      </ButtonGroup>
    </FormRow>
  </Form>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import FormRowStack from 'tui/components/form/FormRowStack';
import InputText from 'tui/components/form/InputText';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import Select from 'tui/components/form/Select';
import Textarea from 'tui/components/form/Textarea';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';
import {
  ACTIVITY_NAME_MAX_LENGTH,
  ACTIVITY_STATUS_ACTIVE,
  VISIBILITY_CONDITION_NONE,
  VISIBILITY_CONDITION_ALL_PARTICIPANT_CLOSED,
} from 'mod_perform/constants';
//GraphQL
import activityTypesQuery from 'mod_perform/graphql/activity_types';
import updateGeneralInfoMutation from 'mod_perform/graphql/update_activity';

export default {
  components: {
    Button,
    ButtonGroup,
    Form,
    FormRow,
    FormRowStack,
    InputText,
    NotificationBanner,
    Radio,
    RadioGroup,
    Select,
    Textarea,
    ToggleSwitch,
  },

  props: {
    value: {
      type: Object,
      required: true,
      validator(value) {
        let keys = Object.keys(value);

        return (
          keys.includes('edit_name') &&
          keys.includes('edit_description') &&
          keys.includes('anonymous_responses')
        );
      },
    },
    activityHasUnsavedChanges: Boolean,
  },

  data() {
    return {
      form: {
        name: this.value.edit_name,
        description: this.value.edit_description,
        type_id: this.value.type.id,
        anonymousResponse: this.value && this.value.anonymous_responses,
        visibilityConditionValue: this.value.settings.visibility_condition
          .value,
      },
      activityTypes: [
        {
          id: this.value.type.id,
          label: this.value.type.display_name,
        },
      ],
      isSaving: false,
      mutationError: null,
    };
  },

  computed: {
    /**
     * Is the title/name text empty.
     *
     * @return {boolean}
     */
    hasNoTitle() {
      return !this.form.name || this.form.name.trim().length === 0;
    },

    /**
     * check activity status is active
     * @return {boolean}
     */
    isActive() {
      if (!this.value) {
        return false;
      }
      return this.value.state_details.name === ACTIVITY_STATUS_ACTIVE;
    },

    /**
     * Is anonymous response setting enabled
     */
    isAnonymousResponse() {
      if (this.isActive) {
        return this.value.anonymous_responses;
      } else {
        return this.form.anonymousResponse;
      }
    },

    /**
     * Check if should show warning message for visibility condition
     */
    showWarning() {
      return (
        this.value.settings.visibility_condition.value !== null &&
        this.value.settings.visibility_condition.value !==
          VISIBILITY_CONDITION_NONE &&
        !this.value.settings.close_on_completion
      );
    },

    visibilityConditionOptions() {
      return this.value.visibility_condition_options
        .slice()
        .sort((a, b) => a.value - b.value);
    },
  },

  watch: {
    form: {
      deep: true,
      handler() {
        this.handleChanges(this.hasUnsavedChanges());
      },
    },
  },

  created() {
    this.ACTIVITY_NAME_MAX_LENGTH = ACTIVITY_NAME_MAX_LENGTH;
  },

  mounted() {
    // Confirm navigation away if user is currently editing.
    window.addEventListener('beforeunload', this.unloadHandler);
  },

  beforeDestroy() {
    // Modal will no longer exist so remove the navigation warning.
    window.removeEventListener('beforeunload', this.unloadHandler);
  },

  apollo: {
    activityTypes: {
      query: activityTypesQuery,
      variables() {
        return [];
      },
      update({ mod_perform_activity_types: types }) {
        return types
          .map(type => {
            return { id: type.id, label: type.display_name };
          })
          .sort((a, b) => a.label.localeCompare(b.label));
      },
    },
  },

  methods: {
    /**
     * Try to persist the activity to the back end.
     * Emitting events on success/failure.
     */
    async trySave() {
      this.isSaving = true;

      try {
        const savedActivity = await this.save();
        this.updateActivity(savedActivity);
        this.handleChanges(false);
        this.$emit('mutation-success', savedActivity);
      } catch (e) {
        this.$emit('mutation-error', e);
      }
      this.isSaving = false;
    },

    /**
     * @returns {Object}
     */
    async save() {
      let mutation = updateGeneralInfoMutation;

      let variables = {
        activity_id: this.value.id,
        name: this.form.name,
        description: this.form.description,
        with_relationships: false,
      };

      // Add draft only updates.
      if (!this.isActive) {
        if (this.value.anonymous_responses !== this.form.anonymousResponse) {
          variables.anonymous_responses = this.form.anonymousResponse;
        }
        if (this.value.type.id !== this.form.type_id) {
          variables.type_id = this.form.type_id;
        }
      }

      if (
        this.value.settings.visibility_condition.value !=
        this.form.visibilityConditionValue
      ) {
        variables.visibility_condition = this.form.visibilityConditionValue;
      }

      const { data: resultData } = await this.$apollo.mutate({
        mutation,
        variables,
        refetchAll: true,
      });

      return resultData.mod_perform_update_activity.activity;
    },

    /**
     * Get a textual representation of a toggle switch for an active activity (setting is no longer available).
     * @return {string}
     */
    activeToggleText(value) {
      return value
        ? this.$str('boolean_setting_text_enabled', 'mod_perform')
        : this.$str('boolean_setting_text_disabled', 'mod_perform');
    },

    hasUnsavedChanges() {
      return (
        this.form.name !== this.value.edit_name ||
        this.form.description !== this.value.edit_description ||
        this.form.type_id !== this.value.type.id ||
        this.form.anonymousResponse !== this.value.anonymous_responses ||
        this.form.visibilityConditionValue !==
          this.value.settings.visibility_condition.value
      );
    },

    /**
     * Emit an input event with an updated activity object, changes are patched into the existing value (activity).
     *
     * @param {object} update - The new values to patch into the activity object emitted.
     */
    updateActivity(update) {
      let activity = Object.assign({}, this.value, update);
      this.$emit('input', activity);
    },

    /**
     * Displays a warning message if the user tries to navigate away without saving.
     * @param {Event} e
     * @returns {String|void}
     */
    unloadHandler(e) {
      if (!this.activityHasUnsavedChanges) {
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

    /**
     * Reset changes to form.
     */
    resetChanges() {
      this.resetActivityChanges();
    },

    /**
     * revert to last saved changes
     */
    resetActivityChanges() {
      this.form.name = this.value.edit_name;
      this.form.description = this.value.edit_description;
      this.form.type_id = this.value.type.id;
      this.form.anonymousResponse = this.value.anonymous_responses;
    },

    /**
     * Set visibility condition edit/read-only mode base on the setting of anonymise responses
     */
    anonymityValueChanged() {
      if (this.form.anonymousResponse) {
        this.form.visibilityConditionValue = VISIBILITY_CONDITION_ALL_PARTICIPANT_CLOSED;
      }
    },

    /**
     * Emit has unsave changes to parent
     */
    handleChanges(hasUnsavedChanges) {
      this.$emit('unsaved-changes', hasUnsavedChanges);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_general_tab_heading",
      "activity_general_response_attribution_heading",
      "activity_general_anonymous_responses_label",
      "activity_general_anonymous_responses_label_help",
      "boolean_setting_text_enabled",
      "boolean_setting_text_disabled",
      "general_info_label_activity_description",
      "general_info_label_activity_title",
      "general_info_label_activity_type",
      "save_changes",
      "unsaved_changes_warning",
      "visibility_condition_all_closed",
      "visibility_condition_label",
      "visibility_condition_label_help",
      "visibility_condition_status_mismatch_warning"

    ],
    "core": [
      "cancel"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManageActivityGeneralInfo {
  & > * + * {
    margin-top: var(--gap-12);
  }

  &__buttons {
    margin-top: var(--gap-8);
  }

  &__heading {
    margin: 0;
    @include tui-font-heading-small();
  }

  &__description {
    margin-top: var(--gap-4);
  }

  &__warning {
    max-width: 712px;
  }
}
</style>
