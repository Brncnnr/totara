<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totara.com] for more information.

  @author Simon Chester <simon.chester@totara.com>
  @module totara_useraction
-->

<template>
  <LayoutOneColumn
    class="tui-totara_useraction-editScheduledAction"
    :title="
      isNew
        ? $str('add_scheduled_action', 'totara_useraction')
        : $str('edit_scheduled_action', 'totara_useraction')
    "
    :loading="loading"
  >
    <template v-if="bannerError" v-slot:feedback-banner>
      <NotificationBanner
        ref="errorBanner"
        type="error"
        :dismissable="true"
        :message="bannerError"
        @dismiss="bannerError = null"
      />
    </template>

    <template v-if="!loading" v-slot:content>
      <Uniform :initial-values="initialValues" @submit="handleSubmit">
        <FormRowStack>
          <div aria-hidden="true">
            <span
              class="tui-totara_useraction-editScheduledAction__requiredStar"
            >
              *
            </span>
            {{ $str('required_fields', 'totara_core') }}
          </div>

          <h3 class="tui-totara_useraction-editScheduledAction__sectionHeading">
            {{ $str('general', 'core') }}
          </h3>

          <FormRow :label="$str('action_name', 'totara_useraction')" required>
            <FormText
              name="name"
              :validations="v => [v.required(), v.maxLength(255)]"
            />
          </FormRow>

          <FormRow :label="$str('description', 'core')">
            <FormTextarea name="description" :rows="5" />
          </FormRow>

          <FormRow
            :label="$str('action_type', 'totara_useraction')"
            required
            :helpmsg="$str('action_type_help', 'totara_useraction')"
          >
            <FormSelect
              name="action"
              :options="actionOptions"
              :validations="v => [v.required()]"
            />
          </FormRow>

          <FormRow
            :label="$str('status', 'core')"
            :helpmsg="$str('status_help', 'totara_useraction')"
          >
            <InputSizedText>
              <FormCheckbox name="enabled">
                {{ $str('enabled', 'totara_core') }}
              </FormCheckbox>
            </InputSizedText>
          </FormRow>

          <h3 class="tui-totara_useraction-editScheduledAction__sectionHeading">
            {{ $str('criteria', 'totara_useraction') }}
          </h3>

          <FormRow :label="$str('applies_to', 'totara_useraction')" required>
            <FormField
              v-slot="{ labelId, value, update }"
              name="audiences"
              required
            >
              <AppliesToSelector
                :aria-labelledby="labelId"
                :value="value"
                @input="update"
              />
            </FormField>
          </FormRow>

          <FormRow
            :label="$str('user_status', 'totara_useraction')"
            required
            :helpmsg="$str('user_status_help', 'totara_useraction')"
          >
            <FormSelect
              name="userStatus"
              :options="userStatusOptions"
              :validations="v => [v.required()]"
            />
          </FormRow>

          <FormRow
            :label="$str('data_source', 'totara_useraction')"
            required
            :helpmsg="$str('data_source_help', 'totara_useraction')"
          >
            <FormSelect
              name="durationSource"
              :options="durationSourceOptions"
              :validations="v => [v.required()]"
            />
          </FormRow>

          <FormRow
            :label="$str('duration', 'totara_useraction')"
            required
            :helpmsg="$str('duration_help', 'totara_useraction')"
          >
            <FormField
              v-slot="{ labelId, value, update }"
              name="duration"
              required
              :validations="v => [v.required(), validateDuration]"
            >
              <DurationInput
                :aria-labelledby="labelId"
                :value="value"
                @input="update"
              />
            </FormField>
          </FormRow>

          <FormRow class="tui-totara_useraction-editScheduledAction__actions">
            <ButtonGroup>
              <Button
                type="submit"
                :text="saveButtonText"
                :styleclass="{ primary: 'true' }"
                :loading="submitting"
              />

              <Button
                :text="$str('cancel', 'core')"
                :disabled="submitting"
                @click="handleCancel"
              />
            </ButtonGroup>
          </FormRow>
        </FormRowStack>
      </Uniform>
    </template>

    <template v-slot:modals>
      <ModalPresenter
        :open="cancelModalVisible"
        @request-close="handleCancelCancel"
      >
        <Modal>
          <ModalContent
            :title="$str('discard_changes', 'totara_useraction')"
            @dismiss="handleCancelCancel"
          >
            <p>
              {{ $str('exit_without_saving', 'totara_useraction') }}
            </p>
            <p>
              {{ $str('unsaved_changes_message', 'totara_useraction') }}
            </p>

            <template v-slot:buttons>
              <ButtonGroup>
                <Button
                  :styleclass="{ primary: 'true' }"
                  :text="$str('yes', 'core')"
                  @click="handleCancelConfirm"
                />
                <Button
                  :text="$str('no', 'core')"
                  @click="handleCancelCancel"
                />
              </ButtonGroup>
            </template>
          </ModalContent>
        </Modal>
      </ModalPresenter>
    </template>
  </LayoutOneColumn>
</template>

<script>
import { langString } from 'tui/i18n';
import LayoutOneColumn from 'tui/components/layouts/LayoutOneColumn';
import {
  FormCheckbox,
  FormField,
  FormRow,
  FormRowStack,
  FormSelect,
  FormText,
  FormTextarea,
  Uniform,
} from 'tui/components/uniform';
import InputSizedText from 'tui/components/form/InputSizedText';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import AppliesToSelector from 'totara_useraction/components/scheduled_rules/AppliesToSelector';
import DurationInput from 'totara_useraction/components/scheduled_rules/DurationInput';
import itemQuery from 'totara_useraction/graphql/scheduled_rule_for_editing';
import createMutation from 'totara_useraction/graphql/create_scheduled_rule';
import updateMutation from 'totara_useraction/graphql/update_scheduled_rule';

export default {
  components: {
    LayoutOneColumn,
    FormCheckbox,
    FormField,
    FormRow,
    FormRowStack,
    FormSelect,
    FormText,
    FormTextarea,
    Uniform,
    InputSizedText,
    ButtonGroup,
    Button,
    Modal,
    ModalContent,
    ModalPresenter,
    NotificationBanner,
    AppliesToSelector,
    DurationInput,
  },

  props: {
    id: Number,
    actions: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      submitting: false,
      actionData: null,
      cancelModalVisible: false,
      bannerError: null,
      userStatusOptions: [
        { id: 'SUSPENDED', label: this.$str('suspended', 'core') },
      ],
      durationSourceOptions: [
        {
          id: 'DATE_SUSPENDED',
          label: this.$str('date_suspended', 'totara_useraction'),
        },
      ],
    };
  },

  computed: {
    loading() {
      return !this.isNew && !this.actionData;
    },

    isNew() {
      return this.id == null;
    },

    saveButtonText() {
      return this.isNew
        ? this.$str('add', 'totara_core')
        : this.$str('save', 'totara_core');
    },

    initialValues() {
      const data = this.actionData;
      if (!data) {
        return {
          action:
            this.actionOptions.length === 1 ? this.actionOptions[0].id : null,
          userStatus: this.userStatusOptions[0].id,
          durationSource: this.durationSourceOptions[0].id,
          duration: {
            value: '1',
            unit: 'YEAR',
          },
          audiences: null,
        };
      }

      const { duration } = data.filters;

      return {
        name: data.name,
        idNumber: data.id_number,
        description: data.description,
        enabled: data.status,
        action: data.action && data.action.identifier,
        userStatus: data.filters.user_status,
        durationSource: duration.source,
        duration: {
          unit: duration.unit,
          value: parseInt(duration.value),
        },
        audiences: data.filters.applies_to.audiences || null,
      };
    },

    actionOptions() {
      return Object.entries(this.actions).map(([id, label]) => ({ id, label }));
    },
  },

  apollo: {
    actionData: {
      query: itemQuery,
      update: result => result.rule,
      skip() {
        return this.isNew;
      },
      variables() {
        return {
          id: this.id,
        };
      },
    },
  },

  methods: {
    validateDuration(duration) {
      if (String(duration.value).includes('.')) {
        return langString('validation_invalid_integer', 'totara_core');
      } else if (parseInt(duration.value) < 1) {
        return this.$str('error_must_be_at_least_1', 'totara_useraction');
      }
    },

    async handleSubmit(values) {
      this.bannerError = null;

      const input = {
        name: values.name,
        description: values.description,
        status: values.enabled,
        action: values.action,
        filter_user_status: values.userStatus,
        filter_duration: {
          source: values.durationSource,
          unit: values.duration.unit,
          value: parseInt(values.duration.value),
        },
        filter_applies_to: {
          audiences: values.audiences ? values.audiences.map(x => x.id) : null,
        },
      };

      if (!this.isNew) {
        input.id = this.id;
      }

      this.submitting = true;

      try {
        await this.$apollo.mutate({
          mutation: this.isNew ? createMutation : updateMutation,
          variables: { input },
        });

        window.location = this.$url(
          `/totara/useraction/scheduled_actions.php?edit_success=1`
        );
      } catch (e) {
        const gqlError = e.graphQLErrors && e.graphQLErrors[0];
        if (
          gqlError &&
          gqlError.extensions &&
          gqlError.extensions.category === 'totara_useraction/validation'
        ) {
          this.bannerError = gqlError.message;
          this.$nextTick(() => {
            if (this.$refs.errorBanner) {
              this.$refs.errorBanner.$el.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
              });
            }
          });
        } else {
          throw e;
        }
      } finally {
        this.submitting = false;
      }
    },

    handleCancel() {
      this.cancelModalVisible = true;
    },

    handleCancelCancel() {
      this.cancelModalVisible = false;
    },

    handleCancelConfirm() {
      window.location = this.$url('/totara/useraction/scheduled_actions.php');
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "cancel",
    "description",
    "general",
    "no",
    "status",
    "suspended",
    "yes"
  ],
  "totara_core": [
    "add",
    "enabled",
    "required_fields",
    "save"
  ],
  "totara_useraction": [
    "action_name",
    "action_type_help",
    "action_type",
    "add_scheduled_action",
    "applies_to",
    "criteria",
    "data_source",
    "data_source_help",
    "date_suspended",
    "discard_changes",
    "duration",
    "duration_help",
    "edit_scheduled_action",
    "error_must_be_at_least_1",
    "exit_without_saving",
    "id",
    "status_help",
    "unsaved_changes_message",
    "user_status",
    "user_status_help"
  ]
}
</lang-strings>

<style lang="scss">
.tui-totara_useraction-editScheduledAction {
  @include tui-font-body();

  padding-bottom: var(--gap-8);

  &__requiredStar {
    color: var(--color-prompt-alert);
  }

  &__sectionHeading {
    margin-top: var(--gap-12);
  }

  &__actions {
    margin-top: var(--gap-8);
  }
}
</style>
