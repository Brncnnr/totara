<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * gdlib.php - Collection of routines in Moodle related to
 * processing images using GD
 *
 * @package   core
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Copies a rectangular portion of the source image to another rectangle in the destination image
 *
 * This function calls imagecopyresampled() if it is available and GD version is 2 at least.
 * Otherwise it reimplements the same behaviour. See the PHP manual page for more info.
 *
 * @link http://php.net/manual/en/function.imagecopyresampled.php
 * @param resource $dst_img the destination GD image resource
 * @param resource $src_img the source GD image resource
 * @param int $dst_x vthe X coordinate of the upper left corner in the destination image
 * @param int $dst_y the Y coordinate of the upper left corner in the destination image
 * @param int $src_x the X coordinate of the upper left corner in the source image
 * @param int $src_y the Y coordinate of the upper left corner in the source image
 * @param int $dst_w the width of the destination rectangle
 * @param int $dst_h the height of the destination rectangle
 * @param int $src_w the width of the source rectangle
 * @param int $src_h the height of the source rectangle
 * @return bool tru on success, false otherwise
 */
function imagecopybicubic($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
    global $CFG;

    $dst_x = (int) $dst_x;
    $dst_y = (int) $dst_y;
    $dst_w = (int) $dst_w;
    $dst_h = (int) $dst_h;
    $src_x = (int) $src_x;
    $src_y = (int) $src_y;
    $src_w = (int) $src_w;
    $src_h = (int) $src_h;

    if (function_exists('imagecopyresampled')) {
        // Totara: Coordinates must be integers (cast any nulls/strings out)
        return imagecopyresampled(
            $dst_img,
            $src_img,
            (int)$dst_x,
            (int)$dst_y,
            (int)$src_x,
            (int)$src_y,
            (int)$dst_w,
            (int)$dst_h,
            (int)$src_w,
            (int)$src_h
        );
    }

    $totalcolors = imagecolorstotal($src_img);
    for ($i=0; $i<$totalcolors; $i++) {
        if ($colors = imagecolorsforindex($src_img, $i)) {
            imagecolorallocate($dst_img, $colors['red'], $colors['green'], $colors['blue']);
        }
    }

    $scalex = ($src_w - 1) / $dst_w;
    $scaley = ($src_h - 1) / $dst_h;

    $scalex2 = $scalex / 2.0;
    $scaley2 = $scaley / 2.0;

    for ($j = 0; $j < $dst_h; $j++) {
        $sy = $j * $scaley;

        for ($i = 0; $i < $dst_w; $i++) {
            $sx = $i * $scalex;

            $c1 = imagecolorsforindex($src_img, imagecolorat($src_img, (int)$sx, (int)$sy + $scaley2));
            $c2 = imagecolorsforindex($src_img, imagecolorat($src_img, (int)$sx, (int)$sy));
            $c3 = imagecolorsforindex($src_img, imagecolorat($src_img, (int)$sx + $scalex2, (int)$sy + $scaley2));
            $c4 = imagecolorsforindex($src_img, imagecolorat($src_img, (int)$sx + $scalex2, (int)$sy));

            $red = (int) (($c1['red'] + $c2['red'] + $c3['red'] + $c4['red']) / 4);
            $green = (int) (($c1['green'] + $c2['green'] + $c3['green'] + $c4['green']) / 4);
            $blue = (int) (($c1['blue'] + $c2['blue'] + $c3['blue'] + $c4['blue']) / 4);

            $color = imagecolorclosest($dst_img, $red, $green, $blue);
            imagesetpixel($dst_img, $i + $dst_x, $j + $dst_y, $color);
        }
    }
}

/**
 * Stores optimised icon images in icon file area.
 *
 * Since 2.9 this function will generate an icon in the same format as the original file when possible.
 * To counter that behaviour, you can use the argument $preferpng to generate a PNG icon.
 *
 * @param context $context
 * @param string $component
 * @param string filearea
 * @param int $itemid
 * @param string $originalfile
 * @param boolean $preferpng When true, it will try to generate a PNG file regardless of the original file.
 * @return mixed new unique revision number or false if not saved
 */
function process_new_icon($context, $component, $filearea, $itemid, $originalfile, $preferpng = false) {
    global $CFG;

    if (!is_file($originalfile)) {
        return false;
    }

    $imageinfo = getimagesize($originalfile);
    $imagefnc = '';

    if (empty($imageinfo)) {
        return false;
    }

    $image = new stdClass();
    $image->width  = $imageinfo[0];
    $image->height = $imageinfo[1];
    $image->type   = $imageinfo[2];

    $t = null;
    switch ($image->type) {
        case IMAGETYPE_GIF:
            if (function_exists('imagecreatefromgif')) {
                $im = imagecreatefromgif($originalfile);
            } else {
                debugging('GIF not supported on this server');
                return false;
            }
            // Guess transparent colour from GIF.
            $transparent = imagecolortransparent($im);
            if ($transparent != -1) {
                $t = imagecolorsforindex($im, $transparent);
            }
            break;
        case IMAGETYPE_JPEG:
            if (function_exists('imagecreatefromjpeg')) {
                $im = imagecreatefromjpeg($originalfile);
            } else {
                debugging('JPEG not supported on this server');
                return false;
            }
            // If the user uploads a jpeg them we should process as a jpeg if possible.
            if (!$preferpng && function_exists('imagejpeg')) {
                $imagefnc = 'imagejpeg';
                $imageext = '.jpg';
                $filters = null; // Not used.
                $quality = 90;
            }
            break;
        case IMAGETYPE_PNG:
            if (function_exists('imagecreatefrompng')) {
                $im = imagecreatefrompng($originalfile);
            } else {
                debugging('PNG not supported on this server');
                return false;
            }
            break;
        default:
            return false;
    }

    // The conversion has not been decided yet, let's apply defaults (png with fallback to jpg).
    if (empty($imagefnc)) {
        if (function_exists('imagepng')) {
            $imagefnc = 'imagepng';
            $imageext = '.png';
            $filters = PNG_NO_FILTER;
            $quality = 1;
        } else if (function_exists('imagejpeg')) {
            $imagefnc = 'imagejpeg';
            $imageext = '.jpg';
            $filters = null; // Not used.
            $quality = 90;
        } else {
            debugging('Jpeg and png not supported on this server, please fix server configuration');
            return false;
        }
    }

    if (function_exists('imagecreatetruecolor')) {
        $im1 = imagecreatetruecolor(100, 100);
        $im2 = imagecreatetruecolor(35, 35);
        $im3 = imagecreatetruecolor(512, 512);
        if ($image->type != IMAGETYPE_JPEG and $imagefnc === 'imagepng') {
            if ($t) {
                // Transparent GIF hacking...
                $transparentcolour = imagecolorallocate($im1 , $t['red'] , $t['green'] , $t['blue']);
                imagecolortransparent($im1 , $transparentcolour);
                $transparentcolour = imagecolorallocate($im2 , $t['red'] , $t['green'] , $t['blue']);
                imagecolortransparent($im2 , $transparentcolour);
                $transparentcolour = imagecolorallocate($im3 , $t['red'] , $t['green'] , $t['blue']);
                imagecolortransparent($im3 , $transparentcolour);
            }

            imagealphablending($im1, false);
            $color = imagecolorallocatealpha($im1, 0, 0,  0, 127);
            imagefill($im1, 0, 0,  $color);
            imagesavealpha($im1, true);

            imagealphablending($im2, false);
            $color = imagecolorallocatealpha($im2, 0, 0,  0, 127);
            imagefill($im2, 0, 0,  $color);
            imagesavealpha($im2, true);

            imagealphablending($im3, false);
            $color = imagecolorallocatealpha($im3, 0, 0,  0, 127);
            imagefill($im3, 0, 0,  $color);
            imagesavealpha($im3, true);
        }
    } else {
        $im1 = imagecreate(100, 100);
        $im2 = imagecreate(35, 35);
        $im3 = imagecreate(512, 512);
    }

    $cx = $image->width / 2;
    $cy = $image->height / 2;

    if ($image->width < $image->height) {
        $half = floor($image->width / 2.0);
    } else {
        $half = floor($image->height / 2.0);
    }

    imagecopybicubic($im1, $im, 0, 0, $cx - $half, $cy - $half, 100, 100, $half * 2, $half * 2);
    imagecopybicubic($im2, $im, 0, 0, $cx - $half, $cy - $half, 35, 35, $half * 2, $half * 2);
    imagecopybicubic($im3, $im, 0, 0, $cx - $half, $cy - $half, 512, 512, $half * 2, $half * 2);

    $fs = get_file_storage();

    $icon = array('contextid'=>$context->id, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>'/');

    ob_start();
    if ($filters === null) {
        if (!$imagefnc($im1, NULL, $quality)) {
            // keep old icons
            ob_end_clean();
            return false;
        }
    } else if (!$imagefnc($im1, NULL, $quality, $filters)) {
        // keep old icons
        ob_end_clean();
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($im1);
    $icon['filename'] = 'f1'.$imageext;
    $fs->delete_area_files($context->id, $component, $filearea, $itemid);
    $file1 = $fs->create_file_from_string($icon, $data);

    ob_start();
    if ($filters === null) {
        if (!$imagefnc($im2, NULL, $quality)) {
            ob_end_clean();
            $fs->delete_area_files($context->id, $component, $filearea, $itemid);
            return false;
        }
    } else if (!$imagefnc($im2, NULL, $quality, $filters)) {
        ob_end_clean();
        $fs->delete_area_files($context->id, $component, $filearea, $itemid);
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($im2);
    $icon['filename'] = 'f2'.$imageext;
    $fs->create_file_from_string($icon, $data);

    ob_start();
    if ($filters === null) {
        if (!$imagefnc($im3, NULL, $quality)) {
            ob_end_clean();
            $fs->delete_area_files($context->id, $component, $filearea, $itemid);
            return false;
        }
    } else if (!$imagefnc($im3, NULL, $quality, $filters)) {
        ob_end_clean();
        $fs->delete_area_files($context->id, $component, $filearea, $itemid);
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($im3);
    $icon['filename'] = 'f3'.$imageext;
    $fs->create_file_from_string($icon, $data);

    return $file1->get_id();
}


/**
 * Resize an image from an image path.
 *
 * This maintains the aspect ratio of the image.
 * This will not enlarge the image.
 *
 * @param string $filepath The full path to the original image file.
 * @param int|null $width The max width of the resized image, or null to only use the height.
 * @param int|null $height The max height of the resized image, or null to only use the width.
 * @param bool $forcecanvas Whether the final dimensions should be set to $width and $height.
 * @return string|bool False if a problem occurs, else the resized image data.
 */
function resize_image($filepath, $width, $height, $forcecanvas = false) {
    if (empty($filepath)) {
        return false;
    }

    // Fetch the image information for this image.
    $imageinfo = @getimagesize($filepath);
    if (empty($imageinfo)) {
        return false;
    }

    // Create a new image from the file.
    $original = @imagecreatefromstring(file_get_contents($filepath));

    // Generate the thumbnail.
    return resize_image_from_image($original, $imageinfo, $width, $height, $forcecanvas);
}

/**
 * Create a transparent image with the specific dimensions.
 *
 * @param integer $width
 * @param integer $height
 * @return resource|false an image resource on success, false on failure
 * @since Totara 13.1
 */
function imagecreatetransparent($width, $height) {
    if (($image = imagecreatetruecolor($width, $height)) !== false) {
        if (imagealphablending($image, false) && imagesavealpha($image, true)) {
            if (($transparent = imagecolorallocatealpha($image, 0, 0, 0, 127)) !== false) {
                imagefilledrectangle($image, 0, 0, $width, $height, $transparent);
                imagecolordeallocate($image, $transparent);
            }
        }
    }
    return $image;
}

/**
 * Resize an image from an image object.
 *
 * @param resource $original The image to work on. The resource will be imagedestroyed when the function succeeds.
 * @param array $imageinfo Contains [0] => originalwidth, [1] => originalheight.
 *                         Or ['width' => originalwidth, 'height' => originalheight, 'mimetype' => mimetype, 'orientation' => orientation].
 * @param int|null $width The max width of the resized image, or null to only use the height.
 * @param int|null $height The max height of the resized image, or null to only use the width.
 * @param bool $forcecanvas Whether the final dimensions should be set to $width and $height.
 * @return string|bool False if a problem occurs, else the resized image data.
 */
function resize_image_from_image($original, $imageinfo, $width, $height, $forcecanvas = false) {
    global $CFG;

    if (empty($width) && empty($height) || ($forcecanvas && (empty($width) || empty($height)))) {
        // We need do not have the required dimensions to work with.
        return false;
    }

    if (empty($imageinfo)) {
        return false;
    }

    // Totara: Try new format first.
    if (isset($imageinfo['width']) && isset($imageinfo['height'])) {
        $originalwidth = $imageinfo['width'];
        $originalheight = $imageinfo['height'];
        $orientation = (int)($imageinfo['orientation'] ?? false);
    } else {
        $originalwidth  = $imageinfo[0];
        $originalheight = $imageinfo[1];
    }
    if (empty($originalwidth) or empty($originalheight)) {
        return false;
    }

    // Exchange width and height for orientation 5 through 8.
    if (!empty($orientation) && 5 <= $orientation && $orientation <= 8) {
        [$width, $height] = [$height, $width];
    }

    if (function_exists('imagepng')) {
        $imagefnc = 'imagepng';
        $filters = PNG_NO_FILTER;
        $quality = 1;
    } else if (function_exists('imagejpeg')) {
        $imagefnc = 'imagejpeg';
        $filters = null;
        $quality = 90;
    } else {
        debugging('Neither JPEG nor PNG are supported at this server, please fix the system configuration.');
        return false;
    }

    if (empty($height)) {
        $ratio = $width / $originalwidth;
    } else if (empty($width)) {
        $ratio = $height / $originalheight;
    } else {
        $ratio = min($width / $originalwidth, $height / $originalheight);
    }

    if ($ratio < 1) {
        $targetwidth    = floor($originalwidth * $ratio);
        $targetheight   = floor($originalheight * $ratio);
    } else {
        // Do not enlarge the original file if it is smaller than the requested thumbnail size.
        $targetwidth    = $originalwidth;
        $targetheight   = $originalheight;
    }

    $canvaswidth = $targetwidth;
    $canvasheight = $targetheight;
    $dstx = 0;
    $dsty = 0;

    if ($forcecanvas) {
        $canvaswidth = $width;
        $canvasheight = $height;
        $dstx = floor(($width - $targetwidth) / 2);
        $dsty = floor(($height - $targetheight) / 2);
    }

    if (function_exists('imagecreatetruecolor')) {
        if ($imagefnc === 'imagepng') {
            $newimage = imagecreatetransparent($canvaswidth, $canvasheight);
        } else {
            $newimage = imagecreatetruecolor($canvaswidth, $canvasheight);
        }
    } else {
        $newimage = imagecreate($canvaswidth, $canvasheight);
    }

    imagecopybicubic($newimage, $original, $dstx, $dsty, 0, 0, $targetwidth, $targetheight, $originalwidth, $originalheight);

    // Totara: Handle the orientation information tag.
    // https://exiftool.org/TagNames/EXIF.html
    if (!empty($orientation) && 1 <= $orientation && $orientation <= 8) {
        if ($orientation === 3) {
            // Rotate 180deg.
            $rotate = imagerotate($newimage, 180, 0);
        } else if ($orientation === 6 || $orientation === 7) {
            // Rotate 90deg clockwise.
            $rotate = imagerotate($newimage, -90, 0);
        } else if ($orientation === 5 || $orientation === 8) {
            // Rotate 270deg clockwise.
            $rotate = imagerotate($newimage, -270, 0);
        }
        if (!empty($rotate)) {
            imagedestroy($newimage);
            $newimage = $rotate;
            imagesavealpha($newimage, true);
        }
        if ($orientation === 2) {
            // Mirror horizontally.
            imageflip($newimage, IMG_FLIP_HORIZONTAL);
        } else if (in_array($orientation, [4, 5, 7])) {
            // Mirror vertically.
            // NOTE: orientation #5 and #7 need to flip vertically because of rotation.
            imageflip($newimage, IMG_FLIP_VERTICAL);
        }
    }

    // Capture the image as a string object, rather than straight to file.
    ob_start();
    if (!$imagefnc($newimage, null, $quality, $filters)) {
        ob_end_clean();
        imagedestroy($newimage);
        return false;
    }
    $data = ob_get_clean();
    imagedestroy($original);
    imagedestroy($newimage);

    return $data;
}

/**
 * Generates a thumbnail for the given image
 *
 * If the GD library has at least version 2 and PNG support is available, the returned data
 * is the content of a transparent PNG file containing the thumbnail. Otherwise, the function
 * returns contents of a JPEG file with black background containing the thumbnail.
 *
 * @param string $filepath the full path to the original image file
 * @param int $width the width of the requested thumbnail
 * @param int $height the height of the requested thumbnail
 * @return string|bool false if a problem occurs, the thumbnail image data otherwise
 */
function generate_image_thumbnail($filepath, $width, $height) {
    return resize_image($filepath, $width, $height, true);
}

/**
 * Generates a thumbnail for the given image string.
 *
 * If the GD library has at least version 2 and PNG support is available, the returned data
 * is the content of a transparent PNG file containing the thumbnail. Otherwise, the function
 * returns contents of a JPEG file with black background containing the thumbnail.
 *
 * @param   string $filedata The image content as a string
 * @param   int $width the width of the requested thumbnail
 * @param   int $height the height of the requested thumbnail
 * @return  string|bool false if a problem occurs, the thumbnail image data otherwise
 */
function generate_image_thumbnail_from_string($filedata, $width, $height) {
    if (empty($filedata) or empty($width) or empty($height)) {
        return false;
    }

    // Fetch the image information for this image.
    $imageinfo = @getimagesizefromstring($filedata);
    if (empty($imageinfo)) {
        return false;
    }

    // Create a new image from the file.
    $original = @imagecreatefromstring($filedata);

    // Generate the thumbnail.
    return generate_image_thumbnail_from_image($original, $imageinfo, $width, $height);
}

/**
 * Generates a thumbnail for the given image string.
 *
 * If the GD library has at least version 2 and PNG support is available, the returned data
 * is the content of a transparent PNG file containing the thumbnail. Otherwise, the function
 * returns contents of a JPEG file with black background containing the thumbnail.
 *
 * @param   resource $original The image to work on. NOTE: this function will deallocate the image.
 * @param   array $imageinfo Contains [0] => originalwidth, [1] => originalheight.
 * @param   int $width The width of the requested thumbnail.
 * @param   int $height The height of the requested thumbnail.
 * @return  string|bool False if a problem occurs, the thumbnail image data otherwise.
 */
function generate_image_thumbnail_from_image($original, $imageinfo, $width, $height) {
    return resize_image_from_image($original, $imageinfo, $width, $height, true);
}

/**
 * Crop and Resize an image from the centre of an image object.
 *
 * @param resource $original The image to work on. NOTE: this function will deallocate the image.
 * @param array $imageinfo Contains ['width' => originalwidth, 'height' => originalheight, 'orientation' => orientation].
 * @param int|null $width The max width of the resized image
 * @param int|null $height The max height of the resized image
 * @return string|bool False if a problem occurs, else the resized image data.
 * @since Totara 13
 */
function crop_resize_image_from_image($original, array $imageinfo, int $width, int $height) {
    $original_width = $imageinfo['width'];
    $original_height = $imageinfo['height'];
    $orientation = (int)($imageinfo['orientation'] ?? 0);

    // Calculate crop size.
    $crop_x = 0;
    $crop_y = 0;
    $crop_width = $original_width;
    $crop_height = $original_height;

    // Exchange width and height for orientation 5 through 8.
    if (5 <= $orientation && $orientation <= 8) {
        $actual_width = $height;
        $actual_height = $width;
    } else {
        $actual_width = $width;
        $actual_height = $height;
    }

    if ($original_width / $original_height > $actual_width / $actual_height) {
        $crop_width = (int) ceil($actual_width * $original_height / $actual_height);
        $crop_x = (int) ceil(($original_width - $crop_width) / 2);
    } else {
        $crop_height = (int) ceil($actual_height * $original_width / $actual_width);
        $crop_y = (int) ceil(($original_height - $crop_height) / 2);
    }

    // Crop proportional area.
    if ($crop_x == 0 && $crop_y == 0 && $crop_width == $original_width && $crop_height == $original_height) {
        // Nothing to crop.
        $tempimage = null;
    } else if (function_exists('imagecreatetruecolor')) {
        // Crop image while keeping its transparency.
        $tempimage = imagecreatetransparent($crop_width, $crop_height);
        if ($tempimage) {
            imagecopy($tempimage, $original, 0, 0, $crop_x, $crop_y, $crop_width, $crop_height);
        }
    } else {
        $tempimage = imagecrop($original, [
            'x' => $crop_x,
            'y' => $crop_y,
            'width' => $crop_width,
            'height' => $crop_height
        ]);
    }
    if ($tempimage) {
        imagedestroy($original);
        $original = $tempimage;
    }

    $imageinfo['width'] = $crop_width;
    $imageinfo['height'] = $crop_height;

    // Generate the resized image.
    return resize_image_from_image($original, $imageinfo, $width, $height);
}
