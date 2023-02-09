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
  @module engage_article
-->
<template>
  <Form class="tui-engageEditArticleTitleForm">
    <InputText
      v-model="innerTitle"
      name="title"
      :disabled="submitting"
      :maxlength="60"
      :placeholder="$str('entertitle', 'engage_article')"
      :aria-label="$str('articletitle', 'engage_article')"
      class="tui-engageEditArticleTitleForm__input"
      @submit="$emit('submit', innerTitle)"
    />

    <DoneCancelGroup
      :loading="submitting"
      :disabled="submitting || !innerTitle"
      @done="$emit('submit', innerTitle)"
      @cancel="$emit('cancel')"
    />
  </Form>
</template>

<script>
import Form from 'tui/components/form/Form';
import InputText from 'tui/components/form/InputText';
import DoneCancelGroup from 'totara_engage/components/buttons/DoneCancelGroup';

export default {
  components: {
    DoneCancelGroup,
    Form,
    InputText,
  },

  props: {
    submitting: {
      type: Boolean,
      default: false,
    },

    title: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      // Caching the inner title, as we will emit the event to update it.
      innerTitle: this.title,
    };
  },
};
</script>

<lang-strings>
  {
    "engage_article": [
      "articletitle",
      "entertitle"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageEditArticleTitleForm {
  width: 100%;
}
</style>
