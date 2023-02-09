<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Aaron Machin <aaron.machin@totaralearning.com>
  @module editor_weka
-->
<template>
  <figcaption ref="content" class="tui-editor_weka-figureCaption" />
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';

export default {
  extends: BaseNode,
};
</script>

<style lang="scss">
.tui-editor_weka-figureCaption {
  margin-bottom: var(--gap-4);
  padding: var(--gap-1);
  color: var(--color-neutral-7);
  background: var(--color-state-highlight);
  border-radius: 2px;
}
</style>
