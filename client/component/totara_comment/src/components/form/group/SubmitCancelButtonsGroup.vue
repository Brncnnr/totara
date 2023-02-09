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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_comment
-->
<template>
  <ButtonGroup class="tui-commentSubmitCancelButtonGroup">
    <Button
      :text="submitText"
      :styleclass="{ primary: true, small: isSmall }"
      :disabled="disableSubmit"
      class="tui-commentSubmitCancelButtonGroup__submit"
      @click.prevent="$emit('click-submit')"
    />

    <Button
      :text="$str('cancel', 'core')"
      :styleclass="{ small: isSmall }"
      :disabled="disableCancel"
      class="tui-commentSubmitCancelButtonGroup__cancel"
      @click.prevent="$emit('click-cancel')"
    />
  </ButtonGroup>
</template>

<script>
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import { SIZE_SMALL, isValid } from 'totara_comment/size';

export default {
  components: {
    ButtonGroup,
    Button,
  },

  props: {
    disableSubmit: {
      type: Boolean,
      default: false,
    },

    disableCancel: {
      type: Boolean,
      default: false,
    },

    submitText: {
      type: String,
      required: true,
    },

    size: {
      type: String,
      default() {
        return SIZE_SMALL;
      },

      validator(prop) {
        return isValid(prop);
      },
    },
  },

  computed: {
    isSmall() {
      return SIZE_SMALL == this.size;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "cancel"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-commentSubmitCancelButtonGroup {
  display: flex;
  justify-content: flex-end;
  margin-top: var(--gap-4);
}
</style>
