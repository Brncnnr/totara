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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module pathway_manual
-->

<template>
  <InfoIconButton :is-help-for="$str('last_rating_given', 'pathway_manual')">
    <template v-if="isForSelf">
      {{ $str('last_rating_given_self_tooltip', 'pathway_manual') }}
    </template>
    <template v-else>
      {{ $str('last_rating_given_other_tooltip', 'pathway_manual') }}
    </template>
  </InfoIconButton>
</template>

<script>
import InfoIconButton from 'tui/components/buttons/InfoIconButton';

export default {
  components: { InfoIconButton },

  props: {
    isForSelf: {
      required: false,
      type: Boolean,
      default: false,
    },
  },
};
</script>

<lang-strings>
  {
    "pathway_manual": [
      "last_rating_given",
      "last_rating_given_other_tooltip",
      "last_rating_given_self_tooltip"
    ]
  }
</lang-strings>
