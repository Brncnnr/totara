<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Rodney Cruden-Powell <rodney.cruden-powell@totaralearning.com>
  @module mod_perform
-->
<template>
  <Form class="tui-performManageRelationshipParticipation">
    <FormRowStack spacing="large">
      <h3 class="tui-performManageRelationshipParticipation__heading">
        {{
          $str('perform_admin_sync_participant_instance_title', 'mod_perform')
        }}
      </h3>

      <!-- Override -->
      <FormRow
        :label="
          $str(
            'perform_admin_sync_participant_instance_override',
            'mod_perform'
          )
        "
        :helpmsg="
          $str(
            'perform_admin_sync_participant_instance_override_description',
            'mod_perform'
          )
        "
      >
        <ToggleSwitch
          v-model="override"
          :aria-label="
            $str(
              'perform_admin_sync_participant_instance_override',
              'mod_perform'
            )
          "
          :disabled="isSaving.override"
          :toggle-first="true"
          @input="updateRelationshipParticipation('override')"
        />
      </FormRow>

      <!-- Auto assign -->
      <FormRow
        v-if="override"
        :label="
          $str(
            'perform_admin_sync_participant_instance_auto_assign',
            'mod_perform'
          )
        "
        :helpmsg="
          $str(
            'perform_admin_sync_participant_instance_auto_assign_description',
            'mod_perform'
          )
        "
      >
        <ToggleSwitch
          v-model="autoAssign"
          :aria-label="
            $str(
              'perform_admin_sync_participant_instance_auto_assign',
              'mod_perform'
            )
          "
          :disabled="isSaving.assign"
          :toggle-first="true"
          @input="updateRelationshipParticipation('assign')"
        />
      </FormRow>

      <!-- Auto close -->
      <FormRow
        v-if="override"
        :label="
          $str(
            'perform_admin_sync_participant_instance_auto_close',
            'mod_perform'
          )
        "
        :helpmsg="
          $str(
            'perform_admin_sync_participant_instance_auto_close_description',
            'mod_perform'
          )
        "
      >
        <ToggleSwitch
          v-model="autoClose"
          :aria-label="
            $str(
              'perform_admin_sync_participant_instance_auto_close',
              'mod_perform'
            )
          "
          :disabled="isSaving.close"
          :toggle-first="true"
          @input="updateRelationshipParticipation('close')"
        />
      </FormRow>
    </FormRowStack>
  </Form>
</template>

<script>
// Imports
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import FormRowStack from 'tui/components/form/FormRowStack';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';

export default {
  components: {
    Form,
    FormRow,
    FormRowStack,
    ToggleSwitch,
  },

  props: {
    isSaving: {
      type: Object,
      required: true,
    },
    settings: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      override: this.settings.override_global_participation_settings,
      autoAssign: this.settings.sync_participant_instance_creation,
      autoClose: this.settings.sync_participant_instance_closure,
    };
  },

  methods: {
    /**
     * Update the current toggle states and emit them to parent component
     *
     * @param {Stirng} toggle which toggle was clicked
     */
    updateRelationshipParticipation(toggle) {
      const settings = {
        override: this.override,
        autoAssign: this.autoAssign,
        autoClose: this.autoClose,
      };

      const disabled = {
        override: toggle == 'override',
        assign: toggle == 'assign',
        close: toggle == 'close',
      };

      this.$emit('update', { settings: settings, disabled: disabled });
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "perform_admin_sync_participant_instance_auto_assign",
      "perform_admin_sync_participant_instance_auto_assign_description",
      "perform_admin_sync_participant_instance_auto_close",
      "perform_admin_sync_participant_instance_auto_close_description",
      "perform_admin_sync_participant_instance_override",
      "perform_admin_sync_participant_instance_override_description",
      "perform_admin_sync_participant_instance_title"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManageRelationshipParticipation {
  &__heading {
    margin: 0;
    @include tui-font-heading-small;
  }
}
</style>
