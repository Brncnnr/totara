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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module samples
-->

<template>
  <div>
    <div>
      <Avatar :src="url1" alt="theAlt1" size="large" />
      <Avatar :src="url1" alt="theAlt2" size="medium" />
      <Avatar :src="url1" alt="theAlt3" size="small" />
      <Avatar :src="url1" alt="theAlt4" size="xsmall" />
    </div>
    <br />
    <div>
      <Avatar :src="url2" alt="theAlt1" size="large" />
      <Avatar :src="url2" alt="theAlt2" size="medium" />
      <Avatar :src="url2" alt="theAlt3" size="small" />
      <Avatar :src="url2" alt="theAlt4" size="xsmall" />
    </div>
  </div>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import { createSilhouetteImage } from '../../../../../tui/src/js/internal/placeholder_generator.js';

export default {
  components: {
    Avatar,
  },

  data() {
    return {
      url1: createSilhouetteImage('#00f'),
      url2: createSilhouetteImage('#f09'),
    };
  },
};
</script>
