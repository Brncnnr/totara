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
  @module editor_weka
-->

<template>
  <div class="tui-fixture-wekaBasic">
    <Weka
      :usage-identifier="{
        component: 'editor_weka',
        area: 'behat',
      }"
      variant="standard"
      :extra-extensions="['mention', 'hashtag']"
      :context-id="contextId"
    />
  </div>
</template>

<script>
import Weka from 'editor_weka/components/Weka';

export default {
  components: {
    Weka,
  },

  props: {
    contextId: [Number, String],
  },
};
</script>
