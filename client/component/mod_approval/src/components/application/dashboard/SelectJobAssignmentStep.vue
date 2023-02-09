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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module mod_approval
-->
<template>
  <ModalContent
    class="tui-mod_approval-selectJobAssignmentStep"
    :title="title"
    :title-id="titleId"
  >
    <FormRow
      v-slot="{ id }"
      class="tui-mod_approval-selectJobAssignmentStep__formRow"
      :label="$str('select_job_assignment', 'mod_approval')"
    >
      <Select
        :id="id"
        class="tui-mod_approval-selectJobAssignmentStep__select"
        :value="selectedJobAssignmentId"
        :options="jobAssignmentOptions"
        :show-label="true"
        @input="jobAssignmentId => $send({ type: $e.SELECT, jobAssignmentId })"
      />
    </FormRow>
    <template v-slot:footer-content>
      <div class="tui-mod_approval-selectJobAssignmentStep__buttons">
        <Button
          v-if="
            !$selectors.getForYourself($context) ||
              workflowTypeOptions.length > 1
          "
          :text="$str('button_back', 'mod_approval')"
          @click="$send($e.BACK)"
        />
        <ButtonGroup
          class="tui-mod_approval-selectJobAssignmentStep__buttonsRight"
        >
          <Button
            :text="$str('button_create', 'mod_approval')"
            :styleclass="{ primary: true }"
            :disabled="!selectedJobAssignmentId"
            @click="$send($e.CREATE)"
          />
          <Button
            :text="$str('button_cancel', 'mod_approval')"
            @click="$send($e.CANCEL)"
          />
        </ButtonGroup>
      </div> </template
  ></ModalContent>
</template>

<script>
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import FormRow from 'tui/components/form/FormRow';
import Select from 'tui/components/form/Select';

import { MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL } from 'mod_approval/constants';

export default {
  components: {
    ButtonGroup,
    Button,
    ModalContent,
    FormRow,
    Select,
  },

  props: {
    title: {
      required: true,
      type: String,
    },
    titleId: {
      required: true,
      type: String,
    },
  },

  computed: {
    jobAssignmentOptions() {
      return this.$selectors.getJobAssignmentOptions(this.$context);
    },

    selectedJobAssignmentId() {
      return this.$selectors.getSelectedJobAssignmentId(this.$context);
    },

    workflowTypeOptions() {
      return this.$selectors.getWorkflowTypeOptions(this.$context);
    },
  },

  xState: {
    machineId: MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "button_back",
    "button_create",
    "button_cancel",
    "button_next",
    "create_new_on_behalf",
    "create_new",
    "select_job_assignment"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-selectJobAssignmentStep {
  min-height: 360px;
  &__formRow {
    margin-top: var(--gap-4);
  }

  &__select {
    margin-top: var(--gap-1);
  }

  &__buttons {
    display: flex;
    flex: 1;
  }

  &__buttonsRight {
    margin-left: auto;
  }
}
</style>
