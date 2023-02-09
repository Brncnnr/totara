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
  <Modal size="large">
    <CreateAssignmentStep v-if="$matches('createAssignment')" />
    <EditStep v-else :approver-types="approverTypes" />
  </Modal>
</template>

<script>
import { MOD_APPROVAL__EDIT_OVERRIDES } from 'mod_approval/constants';
import Modal from 'tui/components/modal/Modal';
import EditStep from './EditStep';
import CreateAssignmentStep from './CreateAssignmentStep';

export default {
  components: {
    Modal,
    EditStep,
    CreateAssignmentStep,
  },

  props: {
    approverTypes: {
      required: true,
      type: Array,
    },
  },

  xState: {
    machineId: MOD_APPROVAL__EDIT_OVERRIDES,
  },
};
</script>
