### Totara ###

This /clock directory has been directly copied from https://github.com/lcobucci/clock, using the tag 1.2.0 (13fda71)

This was done because the lcobucci/jwt library is dependent on lcobucci/clock,
which also has the limitation of not supporting PHP 7.3.

We've also forked clock and made modifications, but have opted to include the source code for it within the
totara jwt fork as it is a relatively small library, and it means we don't need to maintain two separate packages.

It is unlikely that we will need to update the clock code, but if we do, then it is simply a case of copying the
src and test directories in a stable tag from https://github.com/lcobucci/clock into this directory, and then make
modifications where necessary.
