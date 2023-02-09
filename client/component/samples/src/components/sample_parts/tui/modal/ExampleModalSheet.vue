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
  @module samples
-->

<template>
  <Modal size="sheet" :aria-labelledby="$id('title')">
    <div>
      <h1 :id="$id('title')">
        Arbitrary content
      </h1>
      goes here
    </div>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';

export default {
  components: {
    Modal,
  },
};
</script>
