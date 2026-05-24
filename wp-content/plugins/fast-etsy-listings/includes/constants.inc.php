<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once "currencies.inc.php";

if (!defined("FU_CATEGORYMAXDEPTH"))  define("FU_CATEGORYMAXDEPTH", 4);
if (!defined("FU_DEFSLIDESHOWTIMER")) define("FU_DEFSLIDESHOWTIMER", 10);

// Common bots/crawler useragents. Avoid making a API calls for these to keep page loads fast and not overload external servers.
if (!defined("FU_BOT_USERAGENTS"))
  define('FU_BOT_USERAGENTS', array('googlebot','bot','crawl','lighthouse','insights','spider','slurp','baidu','bing','msn','teoma','yandex','java','wget','curl','commons-httpclient','python-urllib','libwww','httpunit','nutch','biglotron','convera','gigablast','archive','webmon','httrack','grub','netresearchserver','speedy','fluffy','bibnum','findlink','panscient','ioi','ips-agent','yanga','voyager','cyberpatrol','postrank','page2rss','linkdex','ezooms','heritrix','findthatfile','aboundex','summify','ec2linkfinder','slack','pinterest','reddit','twitter','whatsapp','yeti','retrevopageanalyzer','sogou','wotbox','ichiro','drupact','coccoc','integromedb','siteexplorer','proximic','changedetection','wesee','scrape','scaper','g00g1e','binlar','indexer','megaindex','ltx71','bubing','qwantify','lipperhey','y!j-asr','addthis'));
$fuBotsExportedToJS = false;

if (!class_exists('fuGlobal'))
{
  class fuGlobal
  {

  }
}

if (!class_exists('fuColourStyle'))
{
class fuColourStyle
{
  const Default = "Default";
  const White = "White";
  const Black = "Black";
  
  public static $Labels;

  const Classes = array(
    self::Default => "",
    self::White => "fu_blackonwhite",
    self::Black => "fu_whiteonblack",
  );
}
}

if (!class_exists('fuSlideShowStyle'))
{
class fuSlideShowStyle
{
  const None = "None";
  const Manual = "Manual";
  const Auto = "Auto";
  const LoadMore = "LoadMore";
  const InfiniteScroll = "InfiniteScroll";
  const CategoryGroups = "CategoryGroups";
  
  public static $Labels;

  // Define behaviour of each slide show style.
  public static $ValidateMultipleSlides = array(
    self::None => false,
    self::Manual => true,
    self::Auto => true,
    self::LoadMore => true,
    self::InfiniteScroll => true,
    self::CategoryGroups => false,
  );
  public static $ClampTitlesLines = array(
    self::None => false,
    self::Manual => true,
    self::Auto => true,
    self::LoadMore => false,
    self::InfiniteScroll => false,
    self::CategoryGroups => false,
  );
  public static $GroupProcessing = array(
    self::None => false,
    self::Manual => false,
    self::Auto => false,
    self::LoadMore => false,
    self::InfiniteScroll => false,
    self::CategoryGroups => true,
  );  
}
}

if (!class_exists('fuPictureAspect'))
{
class fuPictureAspect
{
  const Freeform = "Freeform";
  const Square = "Square";
  const FourThree = "4x3";
  
  public static $Labels;
}
}

if (!class_exists('fuArrangement'))
{
class fuArrangement
{
  const TitleBelow = 0;
  const TitleTop = 1;
  const ImageLeft = 2;
}
}

if (!class_exists('fuSubStates'))
{
class fuSubStates
{
  const Unsubscribed = "Unsubscribed";
  const Expired = "Expired";
  const GracePeriod = "Grace period";
  const Subscriber = "Subscriber";
  
  public static $Labels;
}
}

if (!class_exists('fuAdDisclosurePlacement'))
{
class fuAdDisclosurePlacement
{
  const None = "None";
  const PageTop = "PageTop";
  const Top = "Top";
  const Bottom = "Bottom";
  const PageBottom = "PageBottom";
  
  public static $Labels;
}
}

if (!class_exists('fuDeferedLoading'))
{
class fuDeferedLoading
{
  const Always = "Always";
  const NotCached = "NotCached";
  const Never = "Never";
  
  public static $Labels;
}
}

if (!class_exists('fuGeoTargetting'))
{
class fuGeoTargetting
{
  const Never = "Never";
  const NoSeller = "NoSeller";
  const Always = "Always";

  public static $Labels;
}
}

if (!class_exists('fuItemLocation'))
{
class fuItemLocation
{
  const Nationwide = "Nationwide";
  const Internationally = "Internationally";
  
  public static $Labels;
}
}

if (!class_exists('fuDisplayFields'))
{
class fuDisplayFields
{
  const Title = "TITLE";
  const ShortDesc = "SHORTDESC";
  const Image = "IMAGE";
  const Price = "PRICE";
  const Bids = "BIDS";
  const EndTime = "ENDTIME";
  const Feedback = "FEEDBACK";
  const Location = "LOCATION";
  const Seller = "SELLER";
  const Store = "STORE";
  const Condition = "CONDITION";
  const CompanyLogo = "COMPANYLOGO";
  const VisitLink = "VISITLINK";
  
  public static $Labels;

  public static $ListingDefault = [
    self::Title, 
    self::Image, 
    self::Price, 
    self::Bids, 
    self::EndTime, 
    self::Store, 
    self::VisitLink
  ];
  public static $ItemDefault = [
    self::Title, 
    self::Image, 
    self::Price, 
    self::Bids, 
    self::EndTime, 
    self::Store, 
    self::Feedback, 
    self::Location, 
    self::VisitLink
  ];
}
}
