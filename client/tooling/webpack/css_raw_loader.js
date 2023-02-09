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

/**
 * Like css-loader, but doesn't do any processing of the CSS.
 *
 * This is needed as css-loader doesn't like being passed SCSS, and we need
 * css-loader or a loader with similar output to provide to
 * mini-css-extract-plugin.
 */

const {
  getOptions,
  getRemainingRequest,
  getCurrentRequest,
} = require('loader-utils');
const { SourceListMap, SourceNode } = require('source-list-map');

const matchLength = (regex, str) => {
  const newlinesMatch = regex.exec(str);
  return newlinesMatch ? newlinesMatch[0].length : 0;
};

module.exports = function(content) {
  const options = getOptions(this) || {};

  // normalize line endings
  content = content.replace(/\r\n/g, '\n');

  // generate line-based sourcemap
  let map;
  if (options.sourceMap) {
    // count \n
    const leadingNewlines = matchLength(/^\n+/, content);
    const trailingNewlines = matchLength(/\n+$/, content);

    const cssRequest = getRemainingRequest(this);
    const request = getCurrentRequest(this);

    let sourceMap = new SourceListMap();
    sourceMap.add(
      new SourceNode(
        content.substring(leadingNewlines, content.length - trailingNewlines) +
          '\n',
        cssRequest,
        content,
        // lines start at 1
        1 + leadingNewlines
      )
    );
    const res = sourceMap.toStringWithSourceMap({ file: request });
    content = res.source;
  } else {
    content = content.trim() + '\n';
    map = null;
  }

  this.callback(
    null,
    `module.exports = [[module.id, ${JSON.stringify(
      content
    )}, "", ${JSON.stringify(map)}]];
module.exports.toString = function() { return module.exports[0][1]; }`
  );
};
