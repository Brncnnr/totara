Format standard
==============

The only reason that this format exists is that it is to prevent any writes to the DB via different format type(s).

This should be a default format for the base container and its children (except course and site).
Ideally the new container should be relying on the front-end to decide the layout of itself or implements
its own sub-plugins system for its layout(s) rather than using any of these formats.