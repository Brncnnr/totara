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
    <ModalContent
      :title="$str('edit_transition_title', 'mod_approval', interactionTitle)"
    >
      <div class="tui-mod_approval-editTransitionModal__content">
        <Loader
          v-if="$matches('navigation.interactions.editTransition.loading')"
          :loading="true"
        />
        <FormRow v-else :label="$str('move_to', 'mod_approval')">
          <Select
            :aria-label="$str('select_transition', 'mod_approval')"
            :options="moveToOptions"
            :value="value"
            :char-length="15"
            @input="selectedTransition = $event"
          />
        </FormRow>
      </div>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true }"
            :text="$str('save', 'totara_core')"
            :disabled="disabled"
            :loading="
              $matches(
                'navigation.interactions.editTransition.updatingTransition'
              )
            "
            @click="
              $send({
                type: $e.SAVE_TRANSITION,
                transition: selectedTransition,
              })
            "
          />
          <CancelButton
            :disabled="
              $matches(
                'navigation.interactions.editTransition.updatingTransition'
              )
            "
            @click="$send($e.CANCEL)"
          />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import FormRow from 'tui/components/form/FormRow';
import Select from 'tui/components/form/Select';
import Loader from 'tui/components/loading/Loader';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  components: {
    Button,
    ButtonGroup,
    CancelButton,
    Loader,
    Modal,
    ModalContent,
    FormRow,
    Select,
  },

  props: {
    interactionTitle: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      selectedTransition: null,
    };
  },

  computed: {
    value() {
      return (
        this.selectedTransition || this.$selectors.getTransition(this.$context)
      );
    },

    moveToOptions() {
      return this.$selectors
        .getMoveToOptions(this.$context)
        .map(({ name, value }) => ({ id: value, label: name }));
    },

    disabled() {
      return (
        !this.selectedTransition ||
        this.selectedTransition === this.$selectors.getTransition(this.$context)
      );
    },
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "edit_transition_title",
    "move_to",
    "select_transition"
  ],
  "totara_core": [
    "save"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-editTransitionModal {
  &__content {
    min-height: 6rem;
  }
}
</style>
