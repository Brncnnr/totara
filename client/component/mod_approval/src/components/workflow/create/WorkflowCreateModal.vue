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
  <Modal
    size="large"
    :aria-labelled-by="$id('title-create')"
    :dismissable="false"
  >
    <DetailsStep v-if="$matches('details')" />
    <ChooseFormStep v-else-if="$matches('chooseForm')" />
    <ChooseAssignmentStep v-else />
  </Modal>
</template>

<script>
import { MOD_APPROVAL__WORKFLOW_CREATE } from 'mod_approval/constants';
import Modal from 'tui/components/modal/Modal';
import DetailsStep from './DetailsStep';
import ChooseFormStep from './ChooseFormStep';
import ChooseAssignmentStep from './ChooseAssignmentStep';

export default {
  components: {
    Modal,
    DetailsStep,
    ChooseFormStep,
    ChooseAssignmentStep,
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_CREATE,
  },
};
</script>
