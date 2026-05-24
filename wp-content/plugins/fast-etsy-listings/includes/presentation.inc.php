<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Handle presentation of apicall results
//
//////////////////////////////////////////////////////////////////////////

if (!defined("FU_MAXCOLUMNS"))  define("FU_MAXCOLUMNS", 8);

if (!function_exists('fu_inc_kses_extended_ruleset'))
{
  /**
   * Extend default KSES allowed post tags with SVG elements
   * @return array An array of allowed HTML elements and attributes
   */
  function fu_inc_kses_extended_ruleset() : array
  {
    $kses_defaults = wp_kses_allowed_html( 'post' );
    $ext_args = array(
      // JS stuff needed for slideshows etc.
      'span' => array(
        'class'   => true,
        'id'      => true,
        'onclick' => true,
      ),
      'script' => array(
        'type' => true
      ),
      // SVG stuff
      'svg'   => array(
          'class'           => true,
          'aria-hidden'     => true,
          'aria-labelledby' => true,
          'role'            => true,
          'xmlns'           => true,
          'width'           => true,
          'height'          => true,
          'viewbox'         => true, // <= Must be lower case!
      ),
      'g'     => array( 'fill' => true ),
      'title' => array( 'title' => true ),
      'path'  => array(
          'd'    => true,
          'fill' => true,
      ),
    );
    return array_merge( $kses_defaults, $ext_args );
  }
}

if (!class_exists('fuPresentationItemGroup'))
{
class fuPresentationItemGroup
{
  public string $name;
  public $items;
  
  public function __construct(string $name, $items)
  {
    $this->name = $name;
    $this->items = $items;
  }  

  static function compare($a, $b)
  {
      return strtolower($a->name) <=> strtolower($b->name);
  }  
}
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// fuPresentation 
// Manage the presentation of many items in a table and slideshow.
//
if (!class_exists('fuPresentation'))
{
class fuPresentation
{
  // Tailor per plugin
  protected string $wpOptionPrefix = "fu";
  protected string $cssPrefix = "fu_";

  // Config
  protected string $loadMoreText = "Load more";
  protected string $gotoTopText = "Go to Top";

  // Track global slide index
  private static int $SlideIndex = 0;

  // Class Properties.
  public int $limit = -1;
  public int $columns = -1;
  public int $rows = -1;
  
  private int $slideId = -1;
  private int $slideLimit = -1;
  public string $slideShow = "";
  public int $slides = -1;
  
  private $itemGroups = array();
  private $itemGroupsNames = array();
  private int $nItems = 0;
  private int $nSlides = 0;
  
  public function __construct(string $wpOptionPrefix = "fu", string $cssPrefix = "fu_")
  {
    $this->wpOptionPrefix = $wpOptionPrefix;
    $this->cssPrefix = $cssPrefix;
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Getters / Setters
  public function setTableSize($columns, $rows)
  {
    $this->columns = is_numeric($columns) ? intval($columns) : -1;
    if ($this->columns < 1) $this->columns = intval(get_option("{$this->wpOptionPrefix}DefColumns"));
    if ($this->columns < 1) $this->columns = 1;
    if ($this->columns > FU_MAXCOLUMNS) $this->columns = FU_MAXCOLUMNS;
    
    $this->rows = is_numeric($rows) ? intval($rows) : -1;
    if ($this->rows < 1) $this->rows = intval(get_option("{$this->wpOptionPrefix}DefRows"));
    if ($this->rows < 1) $this->rows = 1;
    
    // Calculate a slideLimit value from 
    if ($this->rows > 0) $this->slideLimit = $this->columns * $this->rows;
    else $this->slideLimit = $this->columns;  
  }
  
  public function setSlideShow($slideShow, $slides = -1)
  {
    $this->slideShow = $slideShow;
    $this->slides = is_numeric($slides) ? intval($slides) : -1;
    if ($this->slides <= 0)
    { 
      $this->slides = intval(get_option("{$this->wpOptionPrefix}DefNumSlides"));
    }
    
    if (empty($this->slideShow) || $this->slideShow == "-1" || 
        stristr($this->slideShow, "true") !== false ||
        stristr($this->slideShow, "default") !== false)
    {
      $this->slideShow = get_option("{$this->wpOptionPrefix}DefSlideshowStyle");
    }

    // If no slideShow, no point having limit beyond what we'll present 
    switch ($this->slideShow)
    {
      case -1:
      case fuSlideShowStyle::None:
      case fuSlideShowStyle::CategoryGroups:
      case false:
        $this->slides = 1;
        if ($this->limit > $this->columns * $this->rows)
          $this->limit = $this->columns * $this->rows;
        break;
      default:
        if ($this->slides > 0)
          $this->limit = $this->columns * $this->rows * $this->slides;
    }
  }
  
  public function deferredLoadingData() : array
  {
    return [
      'fu_cols' => $this->columns,
      'fu_rows' => $this->rows,
      'fu_slideshow' => $this->slideShow,
      'fu_slides' => $this->slides,
      'fu_slideId' => $this->slideId,
    ];
  }

  // Used for deferred loading, load and santize presentation params from query args
  public function loadPresentationFromParams()
  {
    if (isset($_POST['fu_cols']) && isset($_POST['fu_rows']))
    {
      $this->setTableSize(
        intval($_POST['fu_cols']), 
        intval($_POST['fu_rows'])
      );
    }
    if (isset($_POST['fu_slideshow']) && isset($_POST['fu_slides']))
    { 
      $this->setSlideShow(
        fu_inc_sanitize_text_in_dict_keys($_POST['fu_slideshow'], fuSlideShowStyle::$Labels, ""), 
        intval($_POST['fu_slides'])
      );
    }

    // override slide ids to avoid clashing with other slides on pages.
    $this->slideId = intval($_POST['fu_slideId']);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Validation
  public function validate()
  {
    if ($this->columns < 1) $this->columns = intval(get_option("{$this->wpOptionPrefix}DefColumns}"));
    if ($this->columns < 1) $this->columns = 1;    
    if ($this->rows < 1) $this->rows = intval(get_option("{$this->wpOptionPrefix}DefRows}"));
    if ($this->rows < 1) $this->rows = 1;
    
    if ($this->limit < 1)
      $this->limit = $this->columns * $this->rows;
    
    if ($this->slides <= 0) $this->slides = intval(get_option("{$this->wpOptionPrefix}DefNumSlides"));
    if ($this->slides <= 0) $this->slides = 1;
    if ($this->slideShow == "") 
    {
      $this->slideShow = 
        fu_inc_sanitize_text_in_dict_keys(
          get_option("{$this->wpOptionPrefix}DefSlideshowStyle", ""), 
          fuSlideShowStyle::$Labels, 
          fuSlideShowStyle::Manual);
    }  

    // Ensure we have a valid slide limit, should be multiple of columns.
    $this->slideLimit = intval($this->slideLimit);
    if ($this->slideLimit < 1) $this->slideLimit = $this->columns;
    if (($this->slideLimit % $this->columns) > 0) $this->slideLimit += $this->columns - ($this->slideLimit % $this->columns);
    if (fuSlideShowStyle::$ValidateMultipleSlides[$this->slideShow] && $this->slideLimit == $this->limit) $this->slideShow = fuSlideShowStyle::None;
  }
  
  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Methods to generate output table

  /**
   * Generate the start of results table.
   * @param string $title - title text
   * @param string $seachUrl - URL for title text link.
   * @return string the html.
   */
  public function genTableStart(string $title, string $searchUrl = "") : string
  {
    if ($this->slideId < 0) $this->slideId = self::$SlideIndex++;
    $result = "";
    $result .= "<!-- Presentation: cols = {$this->columns}, rows = {$this->rows}, slideshow = {$this->slideShow}, slides = {$this->slides}, slideId = ".$this->slideId." -->";

    $this->nItems = 0;
    $this->nSlides = 0;
    $colourStyle = get_option("{$this->wpOptionPrefix}ColourStyle", fuColourStyle::Default);
    
    // Start table.
    $result .= "<div class=\"{$this->cssPrefix}results_container " . fuColourStyle::Classes[$colourStyle] . "\">" . PHP_EOL;
    $result .= "<h5 class=\"{$this->cssPrefix}results_title\" style=\"display: block !important;\">" . PHP_EOL;
    $result .= "<a name=\"fu_slidetop_".$this->slideId."\"></a>";

    // Add any logos required 
    $result .= $this->getLogo($searchUrl);

    if ( !empty($title) )
    {
      $result .= htmlentities($title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, null, false);
    }
    $result .= "</h5>" . PHP_EOL; // fu_xxx_results_title

    // Ad disclosure text at top of each table.
    if (get_option("{$this->wpOptionPrefix}AdDisclosurePlacement", fuAdDisclosurePlacement::None) == fuAdDisclosurePlacement::Top)
      $result .= $this->GetAdDisclosureText("{$this->cssPrefix}results_addisclosure");

    return $result;
  }

  /**
   * Generate the end of results table.
   * @param string $itemHtml the HTML generated for the item. 
   * @return string the html.
   */
  public function genTableItem(string $itemHtml) : string
  {
    $result = "";
    $slideShowClasses = $this->slideShow != fuSlideShowStyle::None ? " fu_slide_".$this->slideId." fu_slidefade" : "";
    
    // Clamp titles to max of two lines when using a slideshow, to minimise vertical movement when transitioning between slides. 
    if (fuSlideShowStyle::$ClampTitlesLines[$this->slideShow])
    {
      $itemHtml = str_replace("class=\"{$this->cssPrefix}title\"", "class=\"{$this->cssPrefix}title {$this->cssPrefix}maxtwolines\"", $itemHtml);
    }
    
    // Start new slide?
    if (($this->nItems % $this->slideLimit) == 0 )
    {
      $result .= "<div class=\"{$this->cssPrefix}results{$slideShowClasses}\">" . PHP_EOL;
    }
    
    // Start new row?
    if (($this->nItems % $this->columns) == 0)
    {
      $result .= "<div class=\"{$this->cssPrefix}results_row\">" . PHP_EOL;
    }
  
    $result .= "<div class=\"{$this->cssPrefix}results_cellcommon {$this->cssPrefix}results_cell{$this->columns}\" style=\"\" >" . PHP_EOL;
    $result .= $itemHtml;
    $result .= "\r\n</div>" . PHP_EOL;
  
    ++$this->nItems;
  
    // Close the row?
    if (($this->nItems % $this->columns) == 0)
    {
      $result .= "</div>" . PHP_EOL; // fu_xxx_results_row
    }

    // Close the slide slide?
    if (($this->nItems % $this->slideLimit) == 0)
    {
      $result .= "</div> <!-- .fu_xxx_results -->" . PHP_EOL; // fu_xxx_results
      ++$this->nSlides;
    }
    
    return $result;
  }

  /**
   * Store items into groups.
   */
  public function genTableItemIntoGroups(string $itemHtml, array $groups)
  {
    foreach ($groups as $group)
    {
      if (!array_key_exists($group->id, $this->itemGroups)) 
        $this->itemGroups[$group->id] = new fuPresentationItemGroup($group->name, [$itemHtml]);
      else
        $this->itemGroups[$group->id]->items[] = $itemHtml;
    }
  }
  
  /**
   * Generate full table of items in their groups.
   */
  protected function genTableItemGroups() : string
  {
    $result = "";
    $currentGroup = "";
    $slideShowClasses = $this->slideShow != fuSlideShowStyle::None ? " fu_slide_".$this->slideId." fu_slidefade" : "";
    
    // sort the itemGroups by group name
    usort($this->itemGroups, [fuPresentationItemGroup::class, "compare"]);

  // Start new slide?
    //if (($this->nItems % $this->slideLimit) == 0 )
    {
      $result .= "<div class=\"{$this->cssPrefix}results{$slideShowClasses}\">" . PHP_EOL;
    }
  
    foreach ($this->itemGroups as $group => $itemGroup)
    {
      // Start new group? 
      if ($currentGroup != $group)
      {
        // Close a partial row?
        if (($this->nItems % $this->columns) != 0) 
          $result .= "</div>" . PHP_EOL; // fu_xxx_results_row
        $result .= "<div class=\"{$this->cssPrefix}results_row\">" . PHP_EOL;
        $result .= "<a name=\"Group_" . $this->slideId . "_" . esc_attr($group) . "\"></a>";
        $result .= "<h6 class=\"{$this->cssPrefix}results_group\">" . esc_html($itemGroup->name) . "</h6>" . PHP_EOL;
        $this->nItems = 0;
      }

      foreach ($itemGroup->items as $itemHtml)
      {
        // Start new row?
        if (($this->nItems % $this->columns) == 0 || $currentGroup == $group)
        {
          $result .= "<div class=\"{$this->cssPrefix}results_row\">" . PHP_EOL;
        }
      
        $result .= "<div class=\"{$this->cssPrefix}results_cellcommon {$this->cssPrefix}results_cell{$this->columns}\" style=\"\" >" . PHP_EOL;
        $result .= $itemHtml;
        $result .= "\r\n</div>" . PHP_EOL;
      
        ++$this->nItems;
      
        // Close the row?
        if (($this->nItems % $this->columns) == 0)
        {
          $result .= "</div>" . PHP_EOL; // fu_xxx_results_row
        }
      }
    }

    // Close the slide slide?
    //if (($this->nItems % $this->slideLimit) == 0)
    {
      $result .= "</div> <!-- .fu_xxx_results -->" . PHP_EOL; // fu_xxx_results
      ++$this->nSlides;
    }
    
    return $result;


  }

  /**
   * Generate the end of results table.
   * @return string the html.
   */
  public function genTableEnd() : string
  {
    $closeNeeded = false;
    $result = "";

    // If items are not in $result and being group, generate the table now.
    if (count($this->itemGroups))
      $result .= $this->genTableItemGroups();
    
    // Close last row if needed.
    if (($this->nItems % $this->columns) != 0)
    {
      $result .= "</div>" . PHP_EOL; // fu_xxx_results_row
      $closeNeeded = true;
    }

    // Close last slide if needed.
    if (($this->nItems % ($this->columns * $this->rows)) != 0)
    {
      $closeNeeded = true;
    }

    if ($closeNeeded)
      ++$this->nSlides;

    if (($this->nItems % $this->slideLimit) != 0)
    {
      $result .= "</div> <!-- .fu_xxx_results -->" . PHP_EOL; // fu_xxx_results
    }

    $result .= $this->genSlideshow(); 

    // Ad disclosure text at bottom of each table.
    if (get_option("{$this->wpOptionPrefix}AdDisclosurePlacement", fuAdDisclosurePlacement::None) == fuAdDisclosurePlacement::Bottom)
      $result .= $this->GetAdDisclosureText("{$this->cssPrefix}results_addisclosure");
  
    $result .= $this->GetFELLinkHtml();
    $result .= "</div>" . PHP_EOL; // fu_xxx_results_container
    
    return $result;
  }  
  
  public function limitHit(): int
  {
    return $this->nItems >= $this->limit;
  }
  
 
  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Protected Helper Functions
  //

  // Can be overridden to return a logo to display in Table title bar. 
  protected function getLogo(string $searchUrl) : string
  {
    return "";
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Slideshow Logic
  private function genSlideshow() : string
  {
    $result = "";

    // Slideshow dots along bottom
    if ($this->slideShow != fuSlideShowStyle::None && $this->nSlides > 1)
    {
      $result .= "<div style=\"text-align:center\">";

      switch ($this->slideShow)
      {
        case fuSlideShowStyle::Manual:
          $result .= $this->genSlideshowDots();
          break;

        case fuSlideShowStyle::Auto:
          $result .= $this->genSlideshowDots();
          $result .= "<script type=\"text/javascript\">fuStartSlideShow(".$this->slideId.");</script>";
          break;

        case fuSlideShowStyle::LoadMore:
          $result .= $this->genSlideshowLoadMore();
          break;

        case fuSlideShowStyle::InfiniteScroll:
          $result .= $this->genSlideshowInfiniteScroll();
          break;
        }

      $result .= "</div>";
    }
    return $result;
  }

  // Add slideshow dots to show each slide.
  private function genSlideshowDots() : string
  {
    $result = "<span class=\"fu_slideprev\" onclick=\"fuPlusSlides(-1, ".$this->slideId.")\">&#10094;</span>";
    for ($i=0; $i<$this->nSlides; ++$i)
    {
      $result .= "<span class=\"fu_slidedot fu_slidedot_".$this->slideId."\" onclick=\"fuCurrentSlide($i, ".$this->slideId.")\"></span>";
    }
    $result .= "<span class=\"fu_slidenext\" onclick=\"fuPlusSlides(1, ".$this->slideId.")\">&#10095;</span>";

    // Trigger first slide to be shown.
    $result .= "<script type=\"text/javascript\">fu_slideIndex[".$this->slideId."]=0;fuShowSlide(0,".$this->slideId.");</script>";
    return $result;
  }

  // Add slideshow load more button
  private function genSlideshowLoadMore() : string
  {    
    // Trigger first slide to be shown.
    $result = "<script type=\"text/javascript\">fu_slideIndex[".$this->slideId."]=0;fuShowSlide(0,".$this->slideId.");</script>";

    $result .= "<span class=\"fu_slideloadmore fu_slideloadmore_{".$this->slideId."}\" onclick=\"fuLoadNextSlide(".$this->slideId.")\">";
    $result .= esc_html($this->loadMoreText);
    $result .= "</span>";
    $result .= "<span class=\"fu_slidegototop\" onclick=\"fuGoToAnchor('fu_slidetop_".$this->slideId."')\" ";
    $result .= "title=\"".esc_attr($this->gotoTopText)."\">";
    $result .= "&#9650;";
    $result .= "</span>";
    return $result;
  }
  
  // Add slideshow infinite scroll
  private function genSlideshowInfiniteScroll() : string
  {
    // TODO Not implemented yet.
    $result = "<span class=\"fu_slidesinfinitescroll fu_slidesinfinitescroll_".$this->slideId."\">";
    $result .= "scroll-sentinel</span>";
    return $result;
  }


  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Methdos to be overriden in specific plugins

  public function GetFELLinkHtml() : string
  {
    return "";
  }  
    
  public function GetAdDisclosureText() : string
  {
    return "";
  }
}
}
