VideoJS 7.18.1
-------------
https://github.com/videojs/video.js

Instructions to import VideoJS player into Moodle:

1. Download the latest release from https://github.com/videojs/video.js/releases
   (do not choose "Source code")
2. copy 'video.js' into 'amd/src/video-lazy.js'
3. copy 'font/' into 'fonts/' folder
4. copy 'video-js.css' into 'styles.css', retaining everything below the comment "Modifications of player made by Moodle"
   Add stylelint-disable in the beginning.
5. copy 'LICENSE' and 'lang/' into 'videojs/' subfolder

Import plugins:

1. Copy https://github.com/videojs/videojs-youtube/blob/master/dist/Youtube.js into 'amd/src/Youtube-lazy.js'
   In the beginning of the js file replace
     define(['videojs']
   with
     define(['media_videojs/video-lazy']
