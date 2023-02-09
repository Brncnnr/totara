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
  <Modal>
    <ModalContent
      :close-button="true"
      :title="$str('confirm_changes', 'mod_approval')"
    >
      <p>
        {{ $str('confirm_changes_detail', 'mod_approval') }}
      </p>
      <FormRow v-if="canEditWithoutInvalidating">
        <Checkbox
          :aria-label="$str('keep_approvals', 'mod_approval')"
          name="keep-approvals"
          @change="
            $send({ type: $e.TOGGLE_KEEP_APPROVALS, keepApprovals: $event })
          "
        >
          {{ $str('keep_approvals', 'mod_approval') }}
        </Checkbox>
      </FormRow>

      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true }"
            :loading="
              $matches({ confirmSavingApplication: 'savingApplication' })
            "
            :text="$str('save', 'mod_approval')"
            @click="$send($e.CONFIRM_SAVE)"
          />
          <CancelButton @click="$send($e.CANCEL)" />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import { MOD_APPROVAL__APPLICATION } from 'mod_approval/constants';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import CancelButton from 'tui/components/buttons/Cancel';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import FormRow from 'tui/components/form/FormRow';
import Checkbox from 'tui/components/form/Checkbox';

export default {
  name: 'ConfirmChangesModal',

  components: {
    ButtonGroup,
    Modal,
    ModalContent,
    Button,
    CancelButton,
    FormRow,
    Checkbox,
  },

  computed: {
    canEditWithoutInvalidating() {
      return this.$selectors.getCanEditWithoutInvalidating(this.$context);
    },
  },

  xState: {
    machineId: MOD_APPROVAL__APPLICATION,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "confirm_changes",
    "confirm_changes_detail",
    "keep_approvals",
    "save"
  ]
}
</lang-strings>
