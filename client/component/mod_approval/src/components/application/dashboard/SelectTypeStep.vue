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
    class="tui-mod_approval-selectTypeStep"
    :title="title"
    :title-id="titleId"
  >
    <FormRow
      v-slot="{ id }"
      class="tui-mod_approval-selectTypeStep__formRow"
      :label="$str('select_application_type', 'mod_approval')"
    >
      <Select
        :id="id"
        class="tui-mod_approval-selectTypeStep__select"
        :value="selectedWorkflowTypeId"
        :options="workflowTypeOptions"
        :show-label="true"
        @input="id => $send({ type: $e.SELECT, id })"
      />
      <Loader v-if="$matches('selectType.loading')" :loading="true" />
    </FormRow>
    <template v-slot:footer-content>
      <div class="tui-mod_approval-selectTypeStep__buttons">
        <ButtonGroup class="tui-mod_approval-selectTypeStep__buttonsRight">
          <div>
            <Button
              v-if="
                $selectors.getForYourself($context) &&
                  jobAssignmentOptions.length === 1
              "
              :text="$str('button_create', 'mod_approval')"
              :styleclass="{ primary: true }"
              :disabled="$matches('selectType.loading')"
              @click="$send($e.CREATE)"
            />
            <Button
              v-else
              :text="$str('button_next', 'mod_approval')"
              :styleclass="{ primary: true }"
              :disabled="$matches('selectType.loading')"
              @click="$send($e.NEXT)"
            />
          </div>
          <Button
            :text="$str('button_cancel', 'mod_approval')"
            @click="$send($e.CANCEL)"
          />
        </ButtonGroup>
      </div>
    </template>
  </ModalContent>
</template>

<script>
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import FormRow from 'tui/components/form/FormRow';
import Loader from 'tui/components/loading/Loader';
import Select from 'tui/components/form/Select';

import { MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL } from 'mod_approval/constants';

export default {
  components: {
    ButtonGroup,
    Button,
    ModalContent,
    FormRow,
    Loader,
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
    selectedWorkflowTypeId() {
      return this.$selectors.getSelectedWorkflowTypeId(this.$context);
    },

    workflowTypeOptions() {
      return this.$selectors.getWorkflowTypeOptions(this.$context);
    },

    jobAssignmentOptions() {
      return this.$selectors.getJobAssignmentOptions(this.$context);
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
    "button_create",
    "button_cancel",
    "button_next",
    "create_new_on_behalf",
    "create_new",
    "select_application_type"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-selectTypeStep {
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
