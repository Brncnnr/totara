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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <ButtonGroup>
    <ButtonOk @click="$emit('ok')" />
    <ButtonCancel @click="$emit('cancel')" />
  </ButtonGroup>
</template>

<script>
// Components
import ButtonCancel from 'tui/components/buttons/Cancel';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import ButtonOk from 'tui/components/buttons/Ok';

export default {
  components: {
    ButtonCancel,
    ButtonGroup,
    ButtonOk,
  },
};
</script>
