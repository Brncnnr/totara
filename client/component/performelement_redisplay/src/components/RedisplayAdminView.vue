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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_redisplay
-->

<template>
  <div class="tui-redisplayAdminView">
    <div v-if="data.activityName">
      {{
        $str('redisplayed_element_admin_preview', 'performelement_redisplay', {
          activity_name: data.activityName,
        })
      }}
    </div>

    <div class="tui-redisplayAdminView__cardArea">
      <Card class="tui-redisplayAdminView__card">
        <h4 v-if="data.elementTitle" class="tui-redisplayAdminView__card-title">
          {{ data.elementTitle }}
        </h4>
        <div
          v-if="data.relationships"
          class="tui-redisplayAdminView__card-content"
        >
          {{ data.relationships }}
        </div>
        <div
          v-if="isReferenceElementMissing"
          class="tui-redisplayAdminView__card-content"
        >
          {{ $str('source_activity_missing', 'performelement_redisplay') }}
        </div>
      </Card>
    </div>
  </div>
</template>

<script>
import Card from 'tui/components/card/Card';

export default {
  components: {
    Card,
  },

  inheritAttrs: false,

  props: {
    data: [Object, Array],
  },

  computed: {
    isReferenceElementMissing() {
      return !Object.keys(this.data).length;
    },
  },
};
</script>

<lang-strings>
  {
    "performelement_redisplay": [
      "redisplayed_element_admin_preview",
      "source_activity_missing"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-redisplayAdminView {
  &__cardArea {
    margin: var(--gap-4) 0 0 var(--gap-8);
  }

  &__card {
    flex-direction: column;
    padding: var(--gap-4);

    & > * + * {
      margin-top: var(--gap-4);
    }

    &-title {
      margin: 0;
      @include tui-font-heading-label();
    }
  }
}
</style>
