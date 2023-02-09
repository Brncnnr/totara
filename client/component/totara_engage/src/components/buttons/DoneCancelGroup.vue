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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_engage
-->

<template>
  <ButtonGroup class="tui-doneCancelGroup">
    <LoadingButton
      :loading="loading"
      :disabled="disabled"
      :small="true"
      :primary="true"
      :text="$str('done', 'totara_engage')"
      @click="$emit('done', $event)"
    />

    <Button
      :disabled="loading"
      :styleclass="{ small: true }"
      :text="$str('cancel', 'core')"
      @click="$emit('cancel', $event)"
    />
  </ButtonGroup>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';

export default {
  components: {
    Button,
    ButtonGroup,
    LoadingButton,
  },
  props: {
    loading: Boolean,
    disabled: Boolean,
  },
};
</script>

<lang-strings>
  {
    "core": [
      "cancel"
    ],
    "totara_engage": [
      "done"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-doneCancelGroup {
  display: flex;
  justify-content: flex-end;

  margin-top: var(--gap-2);
}
</style>
