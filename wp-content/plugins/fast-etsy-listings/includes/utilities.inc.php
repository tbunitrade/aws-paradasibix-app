<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////////////////////////////
// fuUtils
// Static class of helper methods
//
if (!class_exists('fuUtils'))
{
  class fuUtils
  {
    public static function GetExtLink(string $url, string $title, bool $newWindow = true, string $content = "", string $class = ""): string
    {
      // Build up anchor element.
      $result = "<a href=\"".esc_url($url)."\" title=\"".esc_attr($title)."\" rel=\"nofollow\"";
      if (!empty($class)) $result .= " class=\"".esc_attr($class)."\"";
      if ($newWindow) $result .= " target=\"_blank\"";
      $result .= ">".wp_kses_post($content)."</a>";
      return $result;	
    }
    
    public static function GetSecureUrl(string $url): string
    {
      if (is_ssl())
        return str_replace("http://", "https://", $url);
      else 
        return $url;
    }

    // Gets the contents of an external file
    // Checks if there is a minified version of an external file.
    public static function GetLocalFileContents(string $file): string
    {
      $minifiedFile = substr_replace($file, ".min", strrpos($file, "."), 0);
      if (file_exists($minifiedFile)) 
        return file_get_contents($minifiedFile);
      if (file_exists($file)) 
        return file_get_contents($file);
      return "";
    }

    public static function GetBotUserAgentsJS(): string 
    {
      global $fuBotsExportedToJS;
      $scriptContents = "";
      if (!$fuBotsExportedToJS)
      {
        $scriptContents .= PHP_EOL . "const fu_bot_useragents=[";
        foreach (FU_BOT_USERAGENTS as $fu_bot_useragent) $scriptContents .= "'{$fu_bot_useragent}',";
        $scriptContents .= "];" . PHP_EOL;
        $fuBotsExportedToJS = true;
      }
      return $scriptContents;
    }

  }
}


////////////////////////////////////////////////////////////////////////////////////////////////////////
// Other utility methods

if (!function_exists('fu_inc_sanitize_text_in_array')) 
{
  /**
   * Sanitizes a given string is a key within the given array.
   * @param string $str 
   * @param array $array
   * @param string $fallback
   * @return string the original string, or the fallback string if $str is unsanitary. 
   */
  function fu_inc_sanitize_text_in_array(string $str, array $array, string $fallback) : string
  {
    return in_array($str, $array) ? $str : $fallback;
  }
}

if (!function_exists('fu_inc_sanitize_text_in_dict_keys')) 
{
  /**
   * Sanitizes a given string is a key within the given dictionary.
   * @param string $str 
   * @param array $dict
   * @param string $fallback
   * @return string the original string, or the fallback string if $str is unsanitary. 
   */
  function fu_inc_sanitize_text_in_dict_keys(string $str, array $dict, string $fallback) : string
  {
    return array_key_exists($str, $dict) ? $str : $fallback;
  }
}

if (!function_exists('fu_inc_sanitize_delimitedtext_in_dict_keys')) 
{
  /**
   * Sanitizes a given delimited items in a string are a key within the given dictionary.
   * @param string $delimiter
   * @param string $str 
   * @param array $dict
   * @param string $fallback
   * @return string the original string, or the fallback string if $str is unsanitary. 
   */
  function fu_inc_sanitize_delimitedtext_in_dict_keys(string $delimiter, string $str, array $dict, string $fallback) : string
  {
    $sanitizedItems = array_intersect(explode($delimiter, $str), $dict);
    return implode($delimiter, $sanitizedItems);
  }
}

