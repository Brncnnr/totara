<?php
/**
 * Copyright (C) 2010-2018 Graham Breach
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * For more information, please contact <graham@goat1000.com>
 */

require_once 'SVGGraphGridGraph.php';
require_once 'SVGGraphMarkers.php';

/**
 * Abstract base class for graphs which use markers
 */
abstract class PointGraph extends GridGraph {

  private $markers = array();
  private $marker_ids = array();
  private $marker_link_ids = array();
  private $marker_types = array();

  /**
   * Changes to crosshair cursor by overlaying a transparent rectangle
   */
  protected function CrossHairs()
  {
    return '';
    /* disabled for now - prevents linked shapes working
    $rect = array(
      'width' => $this->width, 'height' => $this->height,
      'opacity' => 0.0, 'cursor' => 'crosshair'
    );
    return $this->Element('rect', $rect);
    */
  }


  /**
   * Adds a marker to the list
   */
  public function AddMarker($x, $y, $item, $extra = NULL, $set = 0)
  {
    $m = new Marker($x, $y, $item, $extra);
    if($this->SpecialMarker($set, $item))
      $m->id = $this->CreateSingleMarker($set, $item);

    $this->markers[$set][] = $m;
    $index = count($this->markers[$set]) - 1;

    // index 0 for now
    $legend_info = array('dataset' => $set, 'index' => $index);
    $this->SetLegendEntry($set, $index, $item, $legend_info);
  }

  /**
   * Draws (linked) markers on the graph
   */
  public function DrawMarkers()
  {
    if($this->marker_size == 0 || count($this->markers) == 0)
      return '';

    $this->CreateMarkers();

    $markers = '';
    foreach($this->markers as $set => $data) {
      if($this->marker_ids[$set] && count($data))
        $markers .= $this->DrawMarkerSet($set, $data);
    }

    if($this->semantic_classes)
      $markers = $this->Element('g', array('class' => 'series'), NULL, $markers);
    return $markers;
  }

  /**
   * Draws a single set of markers
   */
  protected function DrawMarkerSet($set, &$marker_data)
  {
    $markers = '';
    foreach($marker_data as $m)
      $markers .= $this->GetMarker($m, $set);
    return $markers;
  }


  /**
   * Returns a marker element
   */
  private function GetMarker($marker, $set)
  {
    $id = isset($marker->id) ? $marker->id : $this->marker_ids[$set];
    $use = array('x' => $marker->x, 'y' => $marker->y);

    if(is_array($marker->extra))
      $use = array_merge($marker->extra, $use);
    if($this->semantic_classes)
      $use['class'] = "series{$set}";
    if($this->show_tooltips)
      $this->SetTooltip($use, $marker->item, $set, $marker->key, $marker->value);
    if($this->show_context_menu)
      $this->SetContextMenu($use, $set, $marker->item);

    if($this->GetLinkURL($marker->item, $marker->key)) {
      $id = $this->marker_link_ids[$id];
      $element = $this->GetLink($marker->item, $marker->key,
        $this->symbols->UseSymbol($id, $use));
    } else {
      $element = $this->symbols->UseSymbol($id, $use);
    }

    return $element;
  }

  /**
   * Return a centred marker for the given set
   */
  public function DrawLegendEntry($x, $y, $w, $h, $entry)
  {
    if(!isset($entry->style['dataset']))
      return '';

    $dataset = $entry->style['dataset'];
    $index = $entry->style['index'];
    $marker = $this->markers[$dataset][$index];
    if(isset($marker->id))
      $id = $marker->id;
    elseif(isset($this->marker_ids[$dataset]))
      $id = $this->marker_ids[$dataset];
    else
      return ''; // no marker!

    // if the standard marker is unused, must be a link marker
    if(!$this->symbols->UseCount($id))
      $id = $this->marker_link_ids[$id];

    // use data stored with legend to look up marker
    $m = array('x' => $x + $w/2, 'y' => $y + $h/2);
    return $this->symbols->UseSymbol($id, $m);
  }

  /**
   * Creates a single marker element and its link version
   */
  private function CreateMarker($type, $size, $fill, $stroke_width,
    $stroke_colour, $opacity, $angle)
  {
    $m_key = "$type:$size:$fill:$stroke_width:$stroke_colour:$opacity:$angle";
    if(isset($this->marker_types[$m_key]))
      return $this->marker_types[$m_key];

    $markers = new SVGGraphMarkers($this);
    $extra = array('cursor' => 'crosshair');
    $id = $markers->Create($type, $size, $fill, $stroke_width,
      $stroke_colour, $opacity, $angle, $extra);

    // add link version
    $link_id = $markers->Create($type, $size, $fill, $stroke_width,
      $stroke_colour, $opacity, $angle);
    $this->marker_link_ids[$id] = $link_id;

    // save this marker style for reuse
    $this->marker_types[$m_key] = $id;
    return $id;
  }

  /**
   * Returns true if a marker is different to others in its set
   */
  private function SpecialMarker($set, &$item)
  {
    $null_item = null;
    if($this->GetFromItemOrMember('marker_colour', $set, $item, 'colour') !=
      $this->GetFromItemOrMember('marker_colour', $set, $null_item))
      return true;

    $vlist = array('marker_type', 'marker_size', 'marker_stroke_width',
      'marker_stroke_colour', 'marker_angle', 'marker_opacity');
    foreach($vlist as $value)
      if($this->GetFromItemOrMember($value, $set, $item) !=
        $this->GetFromItemOrMember($value, $set, $null_item))
        return true;
    return false;
  }

  /**
   * Creates a single marker for the data set
   */
  private function CreateSingleMarker($set, &$item = null)
  {
    $type = $this->GetFromItemOrMember('marker_type', $set, $item);
    $size = $this->GetFromItemOrMember('marker_size', $set, $item);
    $angle = $this->GetFromItemOrMember('marker_angle', $set, $item);
    $opacity = $this->GetFromItemOrMember('marker_opacity', $set, $item);
    $stroke_colour = $this->GetFromItemOrMember('marker_stroke_colour', $set,
      $item);
    $stroke_width = '';
    if(!empty($stroke_colour) && $stroke_colour != 'none') {
      $stroke_width = $this->GetFromItemOrMember('marker_stroke_width', $set,
        $item);
    }

    $mcolour = $this->GetFromItemOrMember('marker_colour', $set, $item, 'colour');
    if(!empty($mcolour)) {
      $fill = $this->SolidColour($mcolour);
    } else {
      $fill = $this->GetColour(null, 0, $set, true);
    }

    return $this->CreateMarker($type, $size, $fill, $stroke_width,
      $stroke_colour, $opacity, $angle);
  }

  /**
   * Creates the marker types
   */
  private function CreateMarkers()
  {
    foreach(array_keys($this->markers) as $set) {
      // set the ID for this data set to use
      $this->marker_ids[$set] = $this->CreateSingleMarker($set);
    }
  }

  /**
   * Returns the position for a data label
   */
  public function DataLabelPosition($dataset, $index, &$item, $x, $y, $w, $h,
    $label_w, $label_h)
  {
    list($pos, $target) = parent::DataLabelPosition($dataset, $index, $item,
      $x, $y, $w, $h, $label_w, $label_h);

    // labels don't fit inside markers
    $pos = str_replace(array('inner','inside'), '', $pos);
    if(strpos($pos, 'middle') !== FALSE && strpos($pos, 'right') === FALSE &&
      strpos($pos, 'left') === FALSE)
      $pos = str_replace('middle', 'top', $pos);
    if(strpos($pos, 'centre') !== FALSE && strpos($pos, 'top') === FALSE &&
      strpos($pos, 'bottom') === FALSE)
      $pos = str_replace('centre', 'top', $pos);
    $pos = 'outside ' . $pos;
    return array($pos, $target);
  }

  /**
   * Add a marker label
   */
  public function MarkerLabel($dataset, $index, &$item, $x, $y)
  {
    if(!$this->GetOption(array('show_data_labels', $dataset)))
      return false;
    $s = $this->GetFromItemOrMember('marker_size', 0, $item);
    $s2 = $s / 2;
    $dummy = array();
    $label = $this->AddDataLabel($dataset, $index, $dummy, $item,
      $x - $s2, $y - $s2, $s, $s, NULL);

    if(isset($dummy['id']))
      return $dummy['id'];

    return NULL;
  }

  /**
   * Returns a pair of best fit lines, above and below
   */
  protected function BestFitLines()
  {
    $lines_above = $lines_below = '';
    foreach($this->markers as $dataset => $mset) {

      $start = null;
      $end = null;
      $range = $this->GetOption(array('best_fit_range', $dataset));
      if(!is_array($range))
        $range = $this->best_fit_range;
      if(is_array($range)) {
        if(count($range) !== 2)
          throw new Exception('Best fit range must contain start and end values');
        $start = array_shift($range);
        $end = array_shift($range);

        if(!is_null($start) && !is_numeric($start))
          throw new Exception('Best fit range start not numeric or NULL');
        if(!is_null($end) && !is_numeric($end))
          throw new Exception('Best fit range end not numeric or NULL');
        if(!is_null($start) && !is_null($end) && $end <= $start)
          throw new Exception('Best fit range start >= end');
      }

      $bftype = $this->GetOption(array('best_fit', $dataset));
      $project = $this->GetOption(array('best_fit_project', $dataset));
      $project_start = $project == 'start' || $project == 'both';
      $project_end = $project == 'end' || $project == 'both';
      list($best_fit, $projection) = $this->BestFit($bftype, $dataset, $start,
        $end, $project_start, $project_end);

      if($best_fit !== '') {
        $colour = $this->GetOption(array('best_fit_colour', $dataset));
        $stroke_width = $this->GetOption(array('best_fit_width', $dataset));
        $dash = $this->GetOption(array('best_fit_dash', $dataset));
        $opacity = $this->GetOption(array('best_fit_opacity', $dataset));
        $above = $this->GetOption(array('best_fit_above', $dataset));
        $path = array(
          'd' => $best_fit,
          'stroke' => empty($colour) ? '#000' : $colour,
        );
        if($stroke_width != 1 && $stroke_width > 0)
          $path['stroke-width'] = $stroke_width;
        if(!empty($dash))
          $path['stroke-dasharray'] = $dash;
        if($opacity != 1)
          $path['opacity'] = $opacity;

        $line = $this->Element('path', $path);

        if($projection !== '') {
          $path['d'] = $projection;
          $p_colour = $this->GetOption(array('best_fit_project_colour', $dataset));
          $p_stroke_width = $this->GetOption(array('best_fit_project_width', $dataset));
          $p_dash = $this->GetOption(array('best_fit_project_dash', $dataset));
          $p_opacity = $this->GetOption(array('best_fit_project_opacity', $dataset));

          if(!empty($p_colour))
            $path['stroke'] = $p_colour;
          if($p_stroke_width > 0)
            $path['stroke-width'] = $p_stroke_width;
          if(!empty($p_dash))
            $path['stroke-dasharray'] = $p_dash;
          if($p_opacity > 0)
            $path['opacity'] = $p_opacity;

          $line .= $this->Element('path', $path);
        }
        if($above)
          $lines_above .= $line;
        else
          $lines_below .= $line;
      }
    }
    if($this->semantic_classes) {
      $cls = array('class' => 'bestfit');
      if(!empty($lines_below))
        $lines_below = $this->Element('g', $cls, NULL, $lines_below);
      if(!empty($lines_above))
        $lines_above = $this->Element('g', $cls, NULL, $lines_above);
    }
    return array($lines_above, $lines_below);
  }

  /**
   * Find the best fit line for the data points
   * Returns array of two paths: best fit and projection
   */
  protected function BestFit($type, $dataset, $start, $end, $project_start,
    $project_end)
  {
    $line = array('', '');

    // only straight lines supported for now
    if($type != 'straight')
      return $line;

    // use markers for data
    if(!isset($this->markers[$dataset]))
      return $line;

    $sum_x = $sum_y = $sum_x2 = $sum_xy = 0;
    $count = 0;
    $assoc = $this->values->AssociativeKeys();
    foreach($this->markers[$dataset] as $k => $v) {
      if(!is_null($start) && $start > ($assoc ? $k : $v->key))
        continue;
      if(!is_null($end) && $end < ($assoc ? $k : $v->key))
        continue;
      $x = $v->x - $this->pad_left;
      $y = $this->height - $this->pad_bottom - $v->y;

      $sum_x += $x;
      $sum_y += $y;
      $sum_x2 += pow($x, 2);
      $sum_xy += $x * $y;
      ++$count;
    }

    // can't draw a line through fewer than 2 points
    if($count < 2)
      return $line;
    $mean_x = $sum_x / $count;
    $mean_y = $sum_y / $count;

    // initialize min and max points of line
    $x_min = is_null($start) ? 0 : max($this->UnitsX($start), 0);
    $x_max = is_null($end) ? $this->g_width :
      min($this->UnitsX($end), $this->g_width);
    $y_min = 0;
    $y_max = $this->g_height;

    if($sum_x2 == $sum_x * $mean_x) {
      // line is vertical!
      $coords = array(
        'x2' => $mean_x,
        'x1' => $mean_x,
        'y1' => $y_min,
        'y2' => $y_max
      );
    } else {
      $slope = ($sum_xy - $sum_x * $mean_y) / ($sum_x2 - $sum_x * $mean_x);
      $y_int = $mean_y - $slope * $mean_x;
      $coords = $this->BoxLine($x_min, $x_max, $y_min, $y_max, $slope, $y_int);

      if($project_end) {
        $pcoords = $this->BoxLine($coords['x2'], $this->g_width, $y_min, $y_max,
          $slope, $y_int);
        if(!is_null($pcoords)) {
          $x1 = $pcoords['x1'] + $this->pad_left;
          $x2 = $pcoords['x2'] + $this->pad_left;
          $y1 = $this->height - $this->pad_bottom - $pcoords['y1'];
          $y2 = $this->height - $this->pad_bottom - $pcoords['y2'];
          $line[1] .= "M$x1 {$y1}L$x2 $y2";
        }
      }
      if($project_start) {
        $pcoords = $this->BoxLine(0, $coords['x1'], $y_min, $y_max,
          $slope, $y_int);
        if(!is_null($pcoords)) {
          $x1 = $pcoords['x1'] + $this->pad_left;
          $x2 = $pcoords['x2'] + $this->pad_left;
          $y1 = $this->height - $this->pad_bottom - $pcoords['y1'];
          $y2 = $this->height - $this->pad_bottom - $pcoords['y2'];
          $line[1] .= "M$x1 {$y1}L$x2 $y2";
        }
      }
    }
    $x1 = $coords['x1'] + $this->pad_left;
    $x2 = $coords['x2'] + $this->pad_left;
    $y1 = $this->height - $this->pad_bottom - $coords['y1'];
    $y2 = $this->height - $this->pad_bottom - $coords['y2'];
    $line[0] = "M$x1 {$y1}L$x2 $y2";
    return $line;
  }

  /**
   * Returns the coordinates of a line that passes through a box
   */
  protected function BoxLine($x_min, $x_max, $y_min, $y_max, $slope, $y_int)
  {
    $x1 = $x_min;
    $y1 = $slope * $x1 + $y_int;
    $x2 = $x_max;
    $y2 = $slope * $x2 + $y_int;

    if($slope != 0) {
      if($y1 < 0) {
        $x1 = -$y_int / $slope;
        $y1 = $y_min;
      } elseif($y1 > $y_max) {
        $x1 = ($y_max - $y_int) / $slope;
        $y1 = $y_max;
      }

      if($y2 < 0) {
        $x2 = - $y_int / $slope;
        $y2 = $y_min;
      } elseif($y2 > $y_max) {
        $x2 = ($y_max - $y_int) / $slope;
        $y2 = $y_max;
      }
    }
    if($x1 == $x2 && $y1 == $y2)
      return NULL;
    return compact('x1','y1','x2','y2');
  }

  /**
   * Override to show key and value
   */
  protected function FormatTooltip(&$item, $dataset, $key, $value)
  {
    if($this->datetime_keys) {
      $dt = new DateTime("@{$key}");
      $text = $dt->Format($this->tooltip_datetime_format);
    } elseif(is_numeric($key)) {
      $text = $this->units_before_tooltip_key . Graph::NumString($key) .
        $this->units_tooltip_key;
    } else {
      $text = $key;
    }

    $text .= ', ' . $this->units_before_tooltip . Graph::NumString($value) .
      $this->units_tooltip;
    return $text;
  }

  /**
   * Returns TRUE if the item is visible on the graph
   */
  public function IsVisible($item, $dataset = 0)
  {
    // non-null values should be visible
    return !is_null($item->value);
  }
}

class Marker {

  public $x, $y, $key, $value, $extra, $item;

  public function __construct($x, $y, &$item, $extra)
  {
    $this->x = $x;
    $this->y = $y;
    $this->key = $item->key;
    $this->value = $item->value;
    $this->extra = $extra;
    $this->item = &$item;
  }
}

