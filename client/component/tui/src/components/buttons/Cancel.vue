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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <Button
    :text="$str('cancel', 'core')"
    :disabled="disabled"
    @click="$emit('click', $event)"
  />
</template>

<script>
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Button,
  },
  props: {
    disabled: Boolean,
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
