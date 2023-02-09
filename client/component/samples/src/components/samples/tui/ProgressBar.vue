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
    <div class="tui-testProgressBar">
      <Progress :value="count" />
      <Button
        text="Set"
        :styleclass="{ small: true }"
        @click="count = Math.random() * 100"
      />
      <br />
      <Progress :value="3" />
      <br />
      <Progress :value="25" />
      <br />
      <Progress :value="50" :hide-value="true" />
      <br />
      <Progress :value="74" format="number" />
      <br />
      <Progress :value="96" :hide-value="true" :small="true" />
      <br />
      <Progress :value="100" />
      <br />
      <Progress :value="100" :completed-text="true" />
    </div>
    <br />
    <div>
      <Progress :value="1" />
      <br />
      <Progress :value="2" />
      <br />
      <Progress :value="3" />
      <br />
      <Progress :value="5" />
      <br />
      <Progress :value="25" :min="10" :max="50" format="number" />
      <br />
      <Progress :value="25" :min="10" :max="50" />
      <br />
      <Progress :value="50" :hide-value="true" />
      <br />
      <Progress :value="96" :hide-value="true" :small="true" />
      <br />
      <Progress :value="100" />
      <br />
      <Progress :value="100" :completed-text="true" />
      <br />
      <Progress
        :value="20"
        :hide-background="true"
        :small="true"
        format="number"
        :show-empty-state="true"
      />
      <br />
      <Progress
        :value="0"
        :hide-background="true"
        :small="true"
        format="number"
        :show-empty-state="true"
      />
    </div>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Progress from 'tui/components/progress/Progress';

export default {
  components: {
    Button,
    Progress,
  },
  data() {
    return {
      count: 0,
    };
  },
};
</script>

<style lang="scss">
.tui-testProgressBar {
  width: 400px;
  padding: 1rem;
  background-color: #767676;
}
</style>
