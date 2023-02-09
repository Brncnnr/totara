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

  @author Simon Tegg <simon.teg@totaralearning.com>
  @module mod_approval
-->
<template>
  <ModalContent
    :title="$str('create_workflow', 'mod_approval')"
    :title-id="$id('title-create')"
  >
    <div class="tui-mod_approval-detailsStep__content">
      <!-- initialValues set in computed (rather than selectors) to take advantage of loaded langString -->
      <!-- This means it is difficult to check uniqueness of default id_number on initialisation -->
      <Uniform
        ref="form"
        :initial-values="initialValues"
        :errors="$selectors.getErrors($context)"
        validation-mode="submit"
        vertical
        @submit="handleFormSubmit"
      >
        <FormRow
          ref="nameRow"
          :label="$str('workflow_name', 'mod_approval')"
          required
        >
          <FormText
            name="name"
            char-length="full"
            :validations="v => [v.required()]"
          />
        </FormRow>
        <FormRow :label="$str('workflow_description', 'mod_approval')">
          <FormTextarea name="description" :rows="4" char-length="full" />
        </FormRow>

        <FormRow
          :is-stacked="false"
          :label="$str('type', 'mod_approval')"
          required
        >
          <FormSelect
            name="workflow_type_id"
            :options="[
              {
                id: '',
                label: $str('empty_select_option_hint', 'mod_approval'),
              },
              ...$context.workflowTypeOptions,
            ]"
            :size="1"
            :validations="v => [v.required()]"
          />
        </FormRow>

        <FormRow
          :label="$str('workflow_id', 'mod_approval')"
          class="tui-mod_approval-detailsStep__idNumber"
          :is-stacked="false"
          required
        >
          <FormText
            name="id_number"
            :validations="v => [v.required()]"
            @input="handleIdNumberInput"
          />
          <div
            v-if="
              $matches('details.checkingUniqueness') &&
                !$selectors.getErrors($context)
            "
            class="tui-mod_approval-detailsStep__checkingUniqueness"
          >
            <Loading aria-hidden="true" />
          </div>
        </FormRow>
        <p>{{ $str('workflow_id_number_help', 'mod_approval') }}</p>

        <!-- make pressing enter work -->
        <input
          v-show="false"
          type="submit"
          :disabled="!$matches('details.ready')"
        />
      </Uniform>
    </div>

    <template v-slot:buttons>
      <Button
        :text="$str('next', 'core')"
        :styleclass="{ primary: true }"
        :disabled="!$matches('details.ready')"
        @click="$refs.form.submit()"
      />
      <Button
        :text="$str('cancel', 'core')"
        @click="$send({ type: $e.CANCEL })"
      />
    </template>
  </ModalContent>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ModalContent from 'tui/components/modal/ModalContent';
import Loading from 'tui/components/icons/Loading';
import {
  FormRow,
  FormText,
  FormSelect,
  FormTextarea,
  Uniform,
} from 'tui/components/uniform';
import { MOD_APPROVAL__WORKFLOW_CREATE } from 'mod_approval/constants';

export default {
  components: {
    Button,
    ModalContent,
    FormRow,
    FormText,
    FormTextarea,
    Uniform,
    Loading,
    FormSelect,
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_CREATE,
  },

  computed: {
    initialValues() {
      return (
        this.$context.details || {
          name: '',
          description: '',
          workflow_type_id: '',
          id_number: this.$str(
            'workflow_default_id_number',
            'mod_approval',
            Date.now()
          ),
        }
      );
    },
  },

  mounted() {
    // double nextTick to work around Modal focusing itself after mounting
    this.$nextTick(() => {
      this.$nextTick(() => {
        const nameRow = this.$refs.nameRow;
        if (nameRow && nameRow.$el) {
          const name = nameRow.$el.querySelector('input[name=name]');
          name.focus();
        }
      });
    });
  },

  methods: {
    handleIdNumberInput(idNumber) {
      this.$send({ type: this.$e.UPDATE_WORKFLOW_ID, idNumber });
    },

    handleFormSubmit(details) {
      this.$send({ type: this.$e.NEXT, details });
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "cancel",
    "next"
  ],
  "mod_approval": [
    "create_workflow",
    "error:workflow_id_not_unique",
    "empty_select_option_hint",
    "type",
    "workflow_default_id_number",
    "workflow_description",
    "workflow_id",
    "workflow_id_number_help",
    "workflow_name"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-detailsStep {
  &__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    height: 50rem;
    padding-top: var(--gap-6);
  }

  &__checkingUniqueness {
    margin-left: var(--gap-2);
    color: var(--color-neutral-6);
    line-height: 3rem;
  }
}
</style>
