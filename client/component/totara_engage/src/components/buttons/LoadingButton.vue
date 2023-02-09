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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->

<template>
  <ButtonIcon
    v-if="loading"
    :aria-label="ariaLabelCompute"
    :styleclass="{ primary, small }"
    :disabled="disabled || loading"
    :text="labelText"
    :type="type"
    @click="$emit('click', $event)"
  >
    <Loading />
  </ButtonIcon>

  <Button
    v-else
    :aria-label="ariaLabelCompute"
    :styleclass="{ primary, small }"
    :disabled="disabled || loading"
    :text="labelText"
    :type="type"
    @click="$emit('click', $event)"
  />
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Loading from 'tui/components/icons/Loading';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    ButtonIcon,
    Loading,
    Button,
  },

  props: {
    loading: {
      type: Boolean,
      required: true,
    },
    text: {
      type: String,
      required: true,
    },
    type: {
      type: String,
      default: 'button',
      validator: t => ['button', 'submit', 'reset'].includes(t),
    },
    ariaLabel: {
      type: String,
      default: '',
    },
    primary: Boolean,
    small: Boolean,
    disabled: Boolean,
  },

  computed: {
    ariaLabelCompute() {
      if ('' === this.ariaLabel) {
        if (this.loading) {
          return this.$str('loading', 'totara_engage');
        }

        return this.text;
      }

      return this.ariaLabel;
    },

    labelText() {
      if (!this.loading) {
        return this.text;
      }

      return '';
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "loading"
    ]
  }
</lang-strings>
