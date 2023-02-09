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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @module samples
-->

<template>
  <div id="testSeparator">
    <Separator />
    <br />
    <Separator :normal="true" />
    <br />
    <Separator :thick="true" />
    <br />
    <Separator :spread="true" />
    <br />
    <Separator :normal="true" :spread="true" />
    <br />
    <Separator :thick="true" :spread="true" />
    <br />
    <Separator :normal="true" :spread="true">
      hello world
    </Separator>
    <Separator :thick="true" :spread="true">
      <img
        src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mM03f3sPwAF2gLXke5VKgAAAABJRU5ErkJggg=="
        width="32"
        height="32"
      />
    </Separator>
  </div>
</template>

<script>
import Separator from 'tui/components/decor/Separator';
export default {
  components: {
    Separator,
  },
};
</script>
