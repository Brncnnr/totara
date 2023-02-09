<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module totara_oauth2
-->
<template>
  <Form class="tui-oauth2ProviderContent">
    <InputSizedText>
      {{ $str('oauth_url_title', 'totara_oauth2') }}
    </InputSizedText>
    <InputSizedText v-html="$str('oauth_url_desc', 'totara_oauth2')" />
    <InputSizedText class="tui-oauth2ProviderContent__url">
      {{ getOauthUrl() }}
    </InputSizedText>
    <InputSizedText v-html="$str('xapi_url_desc', 'totara_oauth2')" />
    <InputSizedText class="tui-oauth2ProviderContent__url">
      {{ getXapiUrl() }}
    </InputSizedText>
  </Form>
</template>

<script>
import Form from 'tui/components/form/Form';
import InputSizedText from 'tui/components/form/InputSizedText';
import { config } from 'tui/config';

export default {
  components: {
    Form,
    InputSizedText,
  },

  methods: {
    getOauthUrl() {
      return config.wwwroot + '/totara/oauth2/token.php';
    },

    getXapiUrl() {
      return config.wwwroot + '/totara/xapi/receiver.php';
    },
  },
};
</script>
<lang-strings>
  {
    "totara_oauth2": [
      "oauth_url_desc",
      "oauth_url_title",
      "xapi_url_desc"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-oauth2ProviderContent {
  margin-top: var(--gap-8);
  &__url {
    @include tui-wordbreak--hard();
    margin-top: 2px;
    font-family: Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New',
      monospace;
  }
}
</style>
