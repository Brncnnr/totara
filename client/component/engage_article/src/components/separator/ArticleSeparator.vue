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
  <div class="tui-engageArticleSeparator" />
</template>

<style lang="scss">
:root {
  --engage-article-separator-width: 40px;
}
.tui-engageArticleSeparator {
  display: block;
  width: var(--engage-article-separator-width);

  margin-top: var(--gap-4);
  margin-bottom: var(--gap-8);
  border-bottom: solid var(--border-width-thick) var(--color-primary);
}
</style>
