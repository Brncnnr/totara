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

  @author Qingyang liu <qingyang.liu@totaralearning.com>
  @module totara_playlist
-->
<template>
  <div class="tui-playlistPopover">
    <Popover :triggers="['click']">
      <template v-slot:trigger>
        <Button
          :text="$str('addrating', 'totara_playlist')"
          :styleclass="{ small: true, transparent: true }"
        />
      </template>
      <slot name="content" />
      <template v-slot:buttons="{ close }">
        <div class="tui-playlistPopover__buttons">
          <Button
            class="tui-playlistPopover__yes"
            :text="$str('done', 'totara_engage')"
            :styleclass="{ primary: true, small: true }"
            @click="$_handleClick(close)"
          />
          <Button
            :text="$str('cancel', 'core')"
            :styleclass="{ small: true }"
            @click="close"
          />
        </div>
      </template>
    </Popover>
  </div>
</template>
<script>
import Popover from 'tui/components/popover/Popover';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Popover,
    Button,
  },

  methods: {
    $_handleClick(cb) {
      cb();
      this.$emit('rating');
    },
  },
};
</script>
<lang-strings>
  {
    "totara_playlist": [
      "addrating"
    ],

    "totara_engage": [
      "done"
    ],

    "core": [
      "cancel"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistPopover {
  &__buttons {
    display: flex;
    justify-content: flex-end;
  }

  &__yes {
    margin-right: var(--gap-2);
  }
}
</style>
