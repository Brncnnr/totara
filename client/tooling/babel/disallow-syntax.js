/**
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module totara_core
 */

const generatorError =
  'Generators are not allowed due to performance considerations in IE 11.';

function checkGenerator(path) {
  if (path.node.generator) {
    const err = path.buildCodeFrameError(generatorError);
    err.stack = '';
    throw err;
  }
}

module.exports = function() {
  return {
    visitor: {
      FunctionExpression(path) {
        checkGenerator(path);
      },
      FunctionDeclaration(path) {
        checkGenerator(path);
      },
    },
  };
};
