/*
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module tui
 */

import { showSessionExpiredModal } from '../error_info';

export default {
  name: 'require_login',

  process(graphQLErrors) {
    let hasLoginCategory = graphQLErrors.find(
      x => x.extensions && x.extensions.category === this.name
    );

    if (hasLoginCategory) {
      showSessionExpiredModal(this.name);
    }
  },
};
