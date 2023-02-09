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

  @author Cody Finegan <cody.finegan@totaralearning.com>
  @module engage_article
-->

<template>
  <div class="tui-engageArticleImage">
    <img
      class="tui-engageArticleImage__img"
      :src="image"
      :alt="$str('image_alt', 'engage_article', name)"
    />
  </div>
</template>

<script>
import { imageMixin } from 'totara_engage/index';

export default {
  mixins: [imageMixin],
};
</script>

<lang-strings>
  {
    "engage_article": [
      "image_alt"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticleImage {
  &__img {
    width: 100%;
    height: 100%;
  }
}
</style>
