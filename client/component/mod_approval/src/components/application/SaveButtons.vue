<!--
 - This file is part of Totara Enterprise Extensions.
 -
 - Copyright (C) 2021 onwards Totara Learning Solutions LTD
 -
 - Totara Enterprise Extensions is provided only to Totara
 - Learning Solutions LTD's customers and partners, pursuant to
 - the terms and conditions of a separate agreement with Totara
 - Learning Solutions LTD or its affiliate.
 -
 - If you do not have an agreement with Totara Learning Solutions
 - LTD, you may not access, use, modify, or distribute this software.
 - Please contact [licensing@totaralearning.com] for more information.
 -
 - @author Kunle Odusan <kunle.odusan@totaralearning.com>
 - @module mod_approval
  -->

<template>
  <ButtonGroup v-if="$selectors.getIsBeforeSubmission($context)">
    <Button
      :styleclass="{ primary: true }"
      :text="$str('submit', 'mod_approval')"
      :disabled="disabled"
      @click="$send($e.SUBMIT)"
    />
    <Button
      :text="$str('save_draft', 'mod_approval')"
      :loading="$matches('savingDraftApplication')"
      :disabled="disabled"
      @click="$send({ type: $e.SAVE, draft: true })"
    />
  </ButtonGroup>
  <ButtonGroup v-else>
    <Button
      :styleclass="{ primary: true }"
      :text="$str('save', 'mod_approval')"
      :disabled="disabled"
      @click="$send($e.SAVE)"
    />
    <Button
      :text="$str('cancel', 'core')"
      :disabled="disabled"
      @click="$send({ type: $e.CANCEL })"
    />
  </ButtonGroup>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';

export default {
  components: {
    Button,
    ButtonGroup,
  },

  props: {
    machineId: {
      type: String,
      required: true,
    },
  },

  computed: {
    disabled() {
      return (
        this.$matches('loading') ||
        this.$matches('ready.actionsTab.approvingApplication.refetchingSchema')
      );
    },
  },

  xState: {
    machineId() {
      return this.machineId;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "cancel"
  ],
  "mod_approval": [
    "save",
    "save_draft",
    "submit"
  ]
}
</lang-strings>
