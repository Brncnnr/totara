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
  @module engage_survey
-->

<template>
  <div class="tui-engageSurveyImage">
    <SurveyIcon
      :image-alt="$str('image_alt', 'engage_survey', name)"
      class="tui-engageSurveyImage__img"
    />
  </div>
</template>

<script>
import { imageMixin } from 'totara_engage/index';
import SurveyIcon from 'engage_survey/components/icon/SurveyIcon';

export default {
  components: {
    SurveyIcon,
  },

  mixins: [imageMixin],
};
</script>

<lang-strings>
  {
    "engage_survey": [
      "image_alt"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyImage {
  &__img {
    width: 100%;
    height: 100%;
  }
}
</style>
