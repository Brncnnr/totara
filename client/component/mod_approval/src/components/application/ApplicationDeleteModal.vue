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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module mod_approval
-->

<template>
  <ConfirmationModal
    :open="open"
    :title="$str('delete_warning_title', 'mod_approval')"
    :confirm-button-text="$str('delete', 'core')"
    @confirm="$emit('confirm')"
    @cancel="$emit('cancel')"
  >
    <p>
      {{ $str('delete_warning_message_1', 'mod_approval', title) }}
    </p>
    <p>
      {{ $str('delete_warning_message_2', 'mod_approval') }}
    </p>
  </ConfirmationModal>
</template>

<script>
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';

export default {
  components: {
    ConfirmationModal,
  },

  props: {
    open: Boolean,
    title: String,
  },
};
</script>

<lang-strings>
{
  "core": [
    "delete"
  ],
  "mod_approval": [
    "delete_warning_title",
    "delete_warning_message_1",
    "delete_warning_message_2"
  ]
}
</lang-strings>
