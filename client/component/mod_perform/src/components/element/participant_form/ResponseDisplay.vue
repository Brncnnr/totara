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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-participantFormResponseDisplay">
    <template v-if="data.length > 0">
      <div v-for="(responseLine, i) in data" :key="i">
        {{ responseLine }}
      </div>
    </template>
    <NoResponseSubmitted v-else />
  </div>
</template>

<script>
import NoResponseSubmitted from 'mod_perform/components/element/participant_form/NoResponseSubmitted';

export default {
  components: { NoResponseSubmitted },
  props: {
    data: Array,
  },
};
</script>
