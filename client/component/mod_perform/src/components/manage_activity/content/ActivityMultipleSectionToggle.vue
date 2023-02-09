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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-activityMultipleSectionToggle">
    <Form>
      <FormRow
        v-slot="{ label }"
        :label="$str('multiple_sections', 'mod_perform')"
        :helpmsg="$str('multiple_sections_label_help', 'mod_perform')"
      >
        <ToggleSwitch
          v-model="value"
          :disabled="isSaving"
          :aria-label="label"
          :toggle-first="true"
          @input="openModal"
        />
      </FormRow>
    </Form>

    <ConfirmationModal
      :open="modalOpen"
      :title="$str('multiple_sections_confirmation_title', 'mod_perform')"
      :confirm-button-text="$str('modal_confirm', 'mod_perform')"
      @confirm="modalConfirmed"
      @cancel="modalCancelled"
    >
      {{
        value
          ? $str('multiple_sections_enabled_confirmation_text', 'mod_perform')
          : $str('multiple_sections_disabled_confirmation_text', 'mod_perform')
      }}
    </ConfirmationModal>
  </div>
</template>

<script>
import { notify } from 'tui/notifications';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import ToggleActivityMultiSectionSettingMutation from 'mod_perform/graphql/toggle_activity_multisection_setting';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';

export default {
  components: {
    ConfirmationModal,
    Form,
    FormRow,
    ToggleSwitch,
  },

  props: {
    activity: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      isSaving: false,
      modalOpen: false,
      value: this.activity.settings.multisection,
    };
  },
  methods: {
    /**
     * Opens modal when value changes.
     */
    openModal() {
      this.modalOpen = true;
    },

    /**
     * Action on modal cancel.
     */
    modalCancelled() {
      this.modalOpen = false;
      this.value = !this.value;
    },

    /**
     * Action on modal confirmation.
     */
    modalConfirmed() {
      this.modalOpen = false;
      this.save();
    },

    /**
     * Save changes to multisection settings.
     */
    async save() {
      this.isSaving = true;

      try {
        const { data: result } = await this.$apollo.mutate({
          mutation: ToggleActivityMultiSectionSettingMutation,
          variables: {
            input: {
              activity_id: this.activity.id,
              setting: this.value,
            },
          },
          refetchAll: false,
        });

        const newMultiSection = Object.assign({}, this.activity.settings, {
          multisection:
            result.mod_perform_toggle_activity_multisection_setting.settings
              .multisection,
        });
        const newSetting = Object.assign(
          {},
          result.mod_perform_toggle_activity_multisection_setting,
          {
            settings: newMultiSection,
          }
        );

        this.$emit('change', newSetting);

        notify({
          message: this.$str('toast_success_activity_update', 'mod_perform'),
          type: 'success',
        });
      } catch (e) {
        notify({
          message: this.$str('toast_error_generic_update', 'mod_perform'),
          type: 'error',
        });
      }

      this.isSaving = false;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "multiple_sections",
      "multiple_sections_disabled_confirmation_text",
      "multiple_sections_enabled_confirmation_text",
      "multiple_sections_confirmation_title",
      "multiple_sections_label_help",
      "modal_confirm",
      "toast_success_activity_update",
      "toast_error_generic_update"
    ]
  }
</lang-strings>
