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
  <ModalPresenter :open="isOpen" @request-close="$send($e.CANCEL)">
    <Modal :aria-labelledby="$id('title')">
      <SelectTypeStep
        v-if="$matches('selectType')"
        :title="title"
        :title-id="$id('title')"
      />
      <SelectUserStep
        v-else-if="$matches('selectUser')"
        :title="title"
        :title-id="$id('title')"
      />
      <SelectJobAssignmentStep
        v-else-if="$matches('selectJobAssignment')"
        :title="title"
        :title-id="$id('title')"
      />
    </Modal>
  </ModalPresenter>
</template>

<script>
import { MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL } from 'mod_approval/constants';

import Modal from 'tui/components/modal/Modal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import SelectTypeStep from './SelectTypeStep';
import SelectUserStep from './SelectUserStep';
import SelectJobAssignmentStep from './SelectJobAssignmentStep';

export default {
  name: 'NewApplicationModal',

  components: {
    Modal,
    ModalPresenter,
    SelectTypeStep,
    SelectUserStep,
    SelectJobAssignmentStep,
  },

  props: {
    isOpen: Boolean,
  },

  xState: {
    machineId: MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
  },

  computed: {
    title() {
      if (this.$selectors.getForYourself(this.$context)) {
        return this.$str('create_new', 'mod_approval');
      }
      return this.$str('create_new_on_behalf', 'mod_approval');
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "create_new_on_behalf",
    "create_new"
  ]
}
</lang-strings>
