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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <div>
    <Dropdown
      v-if="canCreateOnBehalf && hasWorkflowTypes"
      :separator="true"
      class="tui-mod_approval-newApplicationTrigger"
    >
      <template v-slot:trigger="{ toggle, isOpen }">
        <Button
          :styleclass="{ primary: true }"
          :aria-expanded="isOpen ? 'true' : 'false'"
          :caret="true"
          :text="$str('button_new_application', 'mod_approval')"
          @click="toggle"
        />
      </template>
      <DropdownItem
        :disabled="cannotCreateForOneSelf"
        @click="$send({ type: $e.NEW_APPLICATION, data: newApplicationData() })"
      >
        {{ $str('for_yourself', 'mod_approval') }}
      </DropdownItem>
      <DropdownItem @click="$send($e.ON_BEHALF)">{{
        $str('on_behalf', 'mod_approval')
      }}</DropdownItem>
    </Dropdown>
    <Button
      v-else-if="hasWorkflowTypes"
      :styleclass="{ primary: true }"
      :text="$str('button_new_application', 'mod_approval')"
      @click="$send({ type: $e.NEW_APPLICATION, data: newApplicationData() })"
    />
  </div>
</template>

<script>
import { MOD_APPROVAL__DASHBOARD } from 'mod_approval/constants';
import Button from 'tui/components/buttons/Button';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

export default {
  components: {
    Button,
    Dropdown,
    DropdownItem,
  },

  props: {
    canCreateOnBehalf: {
      type: Boolean,
      required: true,
    },
    currentUserId: {
      type: Number,
      required: true,
    },
  },

  computed: {
    cannotCreateForOneSelf() {
      return this.$selectors.getJobAssignments(this.$context).length === 0;
    },

    hasWorkflowTypes() {
      return this.$selectors.hasWorkflowTypes(this.$context);
    },
  },

  xState: {
    machineId: MOD_APPROVAL__DASHBOARD,
  },

  methods: {
    // ensure NEW_APPLICATION and event populated with data format expected by
    // creatingApplication state
    newApplicationData() {
      return {
        selectedUser: { id: this.currentUserId },
        selectedJobAssignment: this.$selectors.getDefaultJobAssignment(
          this.$context
        ),
      };
    },
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
    "button_new_application",
    "create_new_on_behalf",
    "for_yourself",
    "on_behalf",
    "select_job_assignment",
    "select_person"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-newApplicationTrigger {
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
