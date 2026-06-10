<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('fu_inc_kses_admin_ruleset'))
{
  /**
   * Extend default KSES allowed post tags with form elements for use on Admin pages.
   * @return array An array of allowed HTML elements and attributes
   */
  function fu_inc_kses_admin_ruleset() : array
  {
    $kses_defaults = wp_kses_allowed_html( 'post' );

    $form_args = array(
      'input' => array(
        'type' => true,
        'id' => true,
        'name' => true,
        'value' => true,
        'class' => true,
        'style' => true,
        'pattern' => true,
        'readonly' => true,
        'min' => true,
        'max' => true,
        'checked' => true,
        'selected' => true,
      ),
      'select' => array(
        'id' => true,
        'name' => true,
        'class' => true,
        'style' => true,
        'disabled' => true,
      ),
      'option' => array(
        'value' => true,
        'selected' => true,
        'class' => true,
        'style' => true,
      ),
      'textarea' => array(
        'cols' => true,
        'rows' => true,
        'id' => true,
        'name' => true,
      ),
      'label' => array(
        'class' => true,
        'style' => true,
        'for' => true,
      ),
      'script' => array(
        'type' => true,
      )
    );
    return array_merge( $kses_defaults, $form_args );
  }
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
//  Admin Setting Field Callbacks
//

if (!function_exists('fu_inc_definput_callback'))
{
  function fu_inc_definput_callback($args)
  {
    // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
    $width = isset($args['width']) ? ' style="width:'.esc_attr($args['width']).';"' : "";
    $pattern = isset($args['pattern']) ? ' pattern="'.esc_attr($args['pattern']).'"' : "";
    $readonly = isset($args['readonly']) ? (wp_readonly(true, $args['readonly'], false)) : "";
    $html = '<input type="text" id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'" value="'.esc_attr(get_option($args['id'])).'"'.$width.$pattern.$readonly.'/>';
    $html .= fu_inc_addformlabel($args);
    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}

if (!function_exists('fu_inc_intinput_callback'))
{
  function fu_inc_intinput_callback($args)
  {
    // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
    $width = isset($args['width']) ? ' style="width:'.esc_attr($args['width']).';"' : "";
    $min = isset($args['min']) ? ' min="'.esc_attr($args['min']).'"' : "";
    $max = isset($args['max']) ? ' max="'.esc_attr($args['max']).'"' : "";
    $readonly = isset($args['readonly']) ? (wp_readonly(true, $args['readonly'], false)) : "";
    $html = '<input type="number" id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'" value="'.intval(get_option($args['id'])).'"'.$width.$min.$max.$readonly.'/>';
    $html .= fu_inc_addformlabel($args);
    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}

if (!function_exists('fu_inc_checkbox_callback'))
{
  function fu_inc_checkbox_callback($args)
  {
    // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
    $html = '<input type="checkbox" id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'"'. checked(true, get_option($args['id']), false) . ' value="1"/>';
    $html .= fu_inc_addformlabel($args);
    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}
 
if (!function_exists('fu_inc_textarea_callback'))
{
  function fu_inc_textarea_callback($args) 
  {    
    // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field  
    $html = '<textarea cols="80" rows="'.(isset($args['rows']) ? esc_attr($args['rows']) : 4).'" id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'">';
    $html .= esc_textarea(get_option($args['id']));
    $html .= '</textarea>';
    $html .= fu_inc_addformlabel($args);
    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}

if (!function_exists('fu_inc_url_callback'))
{
  function fu_inc_url_callback($args)
  {
    // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
    $width = isset($args['width']) ? ' style="width:'.esc_attr($args['width']).';"' : "";
    $readonly = isset($args['readonly']) ? (wp_readonly(true, $args['readonly'], false)) : "";
    $html = '<input type="url" id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'" value="'.esc_attr(get_option($args['id'])).'"'.$width.$readonly.'/>';
    $html .= fu_inc_addformlabel($args);
    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}

if (!function_exists('fu_inc_category_callback'))
{
  function fu_inc_category_callback($args)
  {
    // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
    $width = isset($args['width']) ? 'style="width:'.esc_attr($args['width']).';"' : "";
    $pattern = isset($args['pattern']) ? 'pattern="'.esc_attr($args['pattern']).'"' : "";
    $html = '<input type="text" id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'" class="'.$args['class'].'" value="'.esc_attr(get_option($args['id'])).'" '.$width.' '.$pattern.'/>';
    $html .= fu_inc_addformlabel($args);
    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}

if (!function_exists('fu_inc_addformselect_arr'))
{
  function fu_inc_addformselect_arr($args, $arr)
  {
    $option = get_option($args['id']);
    $disabled = isset($args['disabled']) ? (disabled(true, $args['disabled'], false)) : "";
    $html = '<select id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'"'.$disabled.'>';
    foreach ($arr as $value)
    {
      $html .= '<option value="'.esc_attr($value).'"' . selected($value, $option, false) . '>'.esc_html($value).'</option>';
    }
    $html .= '</select>';
    $html .= fu_inc_addformlabel($args);

    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}

if (!function_exists('fu_inc_addformselect_dict'))
{
  function fu_inc_addformselect_dict($args, $dict)
  {
    $option = get_option($args['id']);
    $disabled = isset($args['disabled']) ? (disabled(true, $args['disabled'], false)) : "";
    $html = '<select id="'.esc_attr($args['id']).'" name="'.esc_attr($args['id']).'"'.$disabled.'>';
    foreach ($dict as $value => $desc)
    {
      $html .= '<option value="'.esc_attr($value).'"' . selected($value, $option, false) . '>'.esc_html($desc).'</option>';
    }
    $html .= '</select>';
    $html .= fu_inc_addformlabel($args);

    echo wp_kses($html, fu_inc_kses_admin_ruleset());
  }
}

if (!function_exists('fu_inc_addformlabel'))
{
  function fu_inc_addformlabel($args) : string
  {
    if (isset($args['label']) && !empty($args['label'])) 
      return'<p><label for="'.esc_attr($args['id']).'"> '  . $args['label'] . '</label></p>';
    return "";
  }
}