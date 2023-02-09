# Step to generate in-product GraphQL documentation

In PHP container (from code root):

php ./server/totara/api/cli/prep_api_docs.php

This will produce output schema in {$dataroot}/api/, which will be named on the console, 
and is referred to as $output_directory below. Ensure this directory is readable by node process.

In node container (from extensions/api_docs folder):

npm ci

Then:

node build {$output_directory}
or
npm run build {$output_directory}

This will output docs file and metadata files:

extensions/api_docs/build/spectaql/out.html # Output docs, in single embeddable file
extensions/api_docs/build/spectaql/out.json # Metadata about build (time, hash of schema)

Ensure these files are readable by the web server.

It is possible to generate both schema and output docs in one shell process using:
php ./server/totara/api/cli/prep_api_docs.php -q | xargs node ./extensions/api_docs/build

# Steps to upgrade anvilco/spectaql package

See https://github.com/anvilco/spectaql

Our own totara theme in themes/totara is based on the spectaql "default" theme found here:
https://github.com/anvilco/spectaql/tree/main/src/themes/default

If a file is present in themes/totara, it will overwrite the file in the default theme of the same name.

On upgrade, these files should be updated to incorporate changes to the spectaql default theme:

main.scss should:

- Remove the Google font cdn (and any other external dependencies).
- Import our own styles from totara.scss at the end of the file.

main.js should:

- Remove the DOMContentLoaded event listener as it does not work with javascript modules and is unneccessary.
- Target the shadowRoot created in server/totara/api/amd/src/shadow_dom.js and pass it as an argument to
  toggleMenu, scrollSpy and totara.

toggleMenu.js and scroll-spy.js should:

- Take the shadowRoot as an argument.
- Replace document.querySelector etc. with container.querySelector to locate elements inside the shadow dom.
- Note that properties such as window.innerHeight and and event listeners such as
  window.addEventListener('scroll', handleScroll) are not impacted by the shadow dom and should stay the same.
- scroll-spy.js should add URL manipulation to update the URL hash on scroll.

custom.scss is a file provided by spectaql to make changes to their scss variables.

These files are a part of our implementation and are not present in the spectaql default theme:

totara.js should:

- Reimpliment page anchors with javascript as the shadow dom does not work with native page anchor behaviour.

totara.scss is for custom styling that can not be achieved by modifying scss variables.
