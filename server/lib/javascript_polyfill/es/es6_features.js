import 'core-js/features/array/includes'
import 'core-js/features/array/iterator'
import 'core-js/features/array/find'
import 'core-js/features/array/find-index'
import 'core-js/features/array/from'
import 'core-js/features/dom-collections/for-each'
import 'core-js/features/map'
import 'core-js/features/number/is-finite'
import 'core-js/features/number/is-integer'
import 'core-js/features/number/is-nan'
import 'core-js/features/number/parse-float'
import 'core-js/features/number/parse-int'
import 'core-js/features/object/assign'
import 'core-js/features/object/create'
import 'core-js/features/object/entries'
import 'core-js/features/object/values'
import 'core-js/features/set'
import 'core-js/features/string/starts-with'
import 'core-js/features/string/ends-with'
import 'core-js/features/string/from-code-point'
import 'core-js/features/string/includes'
import 'core-js/features/symbol'
import 'core-js/features/url'

// Intentionally do not use core-js polyfill for Promises, at least core-js
// 3.10.1 - 3.15.1 introduce a bug in the Promise polyfill that might be a race
// condition and hard to detect, so instead of importing the core-js Promise
// polyfill within es / es6_features.js, we will retain this older one.
//import 'core-js/features/promise'
