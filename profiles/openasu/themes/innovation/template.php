<?php
/**
 * @file
 * innovation's primary theme functions and alterations.
 */

/**
 * Load Kalatheme dependencies.
 *
 * Implements template_preprocess_html().
 */
function innovation_preprocess_html(&$variables)
{

  // Add theme js file here rather than in .info to add a weight and ensure it loads last
  drupal_add_js(drupal_get_path('theme', 'innovation') . '/js/innovation.js', array('scope' => 'footer', 'group' => JS_THEME, 'weight' => 200));

  // Add IE meta tag to force IE rendering mode
  $meta_ie_render_engine = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'content' => 'IE=edge,chrome=1',
      'http-equiv' => 'X-UA-Compatible',
    )
  );
  drupal_add_html_head($meta_ie_render_engine, 'meta_ie_render_engine');
  // WEBSPARK-189 - add actual HTTP header along with meta tag
  drupal_add_http_header('X-UA-Compatible', 'IE=Edge,chrome=1');

  // Add conditional stylesheets for IE
  drupal_add_css(path_to_theme() . '/css/ie9.css', array('group' => CSS_THEME, 'browsers' => array('IE' => 'IE 9', '!IE' => FALSE), 'preprocess' => FALSE));
  drupal_add_css(path_to_theme() . '/css/ie8.css', array('group' => CSS_THEME, 'browsers' => array('IE' => 'IE 8', '!IE' => FALSE), 'preprocess' => FALSE));
}

/**
 * Implements hook_ctools_plugin_post_alter()
 */
function innovation_ctools_plugin_post_alter(&$plugin, &$info)
{
  if ($info['type'] == 'styles') {
    if ($plugin['name'] == 'kalacustomize') {
      $plugin['title'] = 'ASU Customize';
    }
  }
}

/**
 * Implements hook_ctools_content_subtype_alter()
 */
function innovation_ctools_content_subtype_alter(&$subtype, &$plugin)
{
  if ($plugin['module'] == 'views_content' && $subtype['title'] == 'Add content item') {
    $subtype['title'] = t('Add existing content');
  }
}

/**
 * Override or insert variables into the page template.
 *
 * Implements template_process_page().
 */
function innovation_preprocess_page(&$variables)
{
  // Make sure default picture gets responsive panopoly stylingz
  if (theme_get_setting('default_picture', 'innovation') && theme_get_setting('picture_path', 'innovation')) {
    $image_style = module_exists('asu_cas') ? 'asu_header_image' : 'panopoly_image_full';
    $variables['asu_picture'] = theme('image_style', array(
        'style_name' => $image_style,
        'path' => theme_get_setting('picture_path', 'innovation'),
      )
    );
  }

  /**
   * Adding to find the asu-degree template.
   */
  if (isset($variables['node'])) {
    if ($variables['node']->type == 'asu_degree') {
      $variables['theme_hook_suggestions'][] = 'page__asu_degree';
    }
  }
}

/**
 * Override or insert variables into the page template.
 *
 * Implements template_process_block().
 */
function innovation_preprocess_block(&$variables)
{
  $block = $variables['block'];
  if ($block->delta == 'main-menu' && $block->module == 'system' && $block->status == 1 && $block->theme = 'innovation') {
    // Get the entire main menu tree.
    $main_menu_tree = array();
    $main_menu_tree = menu_tree_all_data('main-menu', NULL, 2);
    // Add the rendered output to the $main_menu_expanded variable.
    //
    $main_menu_asu = menu_tree_output($main_menu_tree);
    $pri_attributes = array(
      'class' => array(
        'nav',
        'navbar-nav',
        'links',
        'clearfix',
      ),
    );
    $variables['content'] = theme('links__system_main_menu', array(
      'links' => $main_menu_asu,
      'attributes' => $pri_attributes,
      'heading' => array(
        'text' => t('Main menu'),
        'level' => 'h2',
        'class' => array('element-invisible'),
      ),
    ));
    $block->subject = '';
  }
}

/**
 * Implements hook_block_view_alter().
 *
 * We are using this to inject the bootstrap data-toggle/data-target attributes into the ASU
 * Header so that it can also activate the local menu.
 *
 */
function innovation_block_view_alter(&$data, $block)
{
  // Add the attributes if applicable
  if (($block->module == 'asu_brand') && ($block->delta == 'asu_brand_header')) {
    $data['content'] = str_replace('<a href="javascript:toggleASU();">', '<a href="javascript:toggleASU();" data-target=".navbar-collapse" data-toggle="collapse">', $data['content']);
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function innovation_form_panels_edit_style_settings_form_alter(&$form, &$form_state)
{
  // Add some extra ASU styles if extra styles are on
  if (isset($form['general_settings']['settings']['title'])) {
    $styles = array('title', 'content');
    foreach ($styles as $style) {
      $form['general_settings']['settings'][$style]['attributes']['#options'] += array(
        'featured-text' => 'ASU FEATURED TEXT',
      );
    }
  }
}

/**
 * Implements theme_links__system_main_menu.
 */
function innovation_links__system_main_menu($variables)
{
  $links = $variables['links'];
  $attributes = $variables['attributes'];
  $heading = $variables['heading'];
  $trail = menu_get_active_trail();
  array_shift($trail);
  unset($links['#sorted']);
  unset($links['#theme_wrappers']);
  global $language_url;
  $output = '';

  if (count($links) > 0) {
    $output = '';

    // Treat the heading first if it is present to prepend it to the
    // list of links.
    if (!empty($heading)) {
      if (is_string($heading)) {
        // Prepare the array that will be used when the passed heading
        // is a string.
        $heading = array(
          'text' => $heading,
          // Set the default level of the heading.
          'level' => 'h2',
        );
      }
      $output .= '<' . $heading['level'];
      if (!empty($heading['class'])) {
        $output .= drupal_attributes(array('class' => $heading['class']));
      }
      $output .= '>' . check_plain($heading['text']) . '</' . $heading['level'] . '>';
    }
    $output .= '<ul' . drupal_attributes($attributes) . '>';
    $num_links = count($links);
    $i = 1;
    foreach ($links as $key => $link) {
      $class = array($key);
      $link['#attributes'] = (isset($link['#localized_options']['attributes'])) ? $link['#localized_options']['attributes'] : array();
      // Add first/last/active classes to help out themers.
      if ($i == 1) {
        $class[] = 'first';
      }
      if ($i == $num_links) {
        $class[] = 'last';
      }
      if (isset($link['#href']) && ($link['#href'] == $_GET['q'] || ($link['#href'] == '<front>' && drupal_is_front_page()))
        && (empty($link['#language']) || $link['#language']->language == $language_url->language)
      ) {
        $class[] = 'active';
      }
      // Check for, and honor active states set by context menu reactions
      if (module_exists('context')) {
        $contexts = context_active_contexts();
        foreach ($contexts as $context) {
          if ((isset($context->reactions['menu']))) {
            if ($link['#href'] == $context->reactions['menu']) {
              $class[] = 'active';
            }
          }
        }
      }
      $options['attributes'] = $link['#attributes'];
      // Make list item a dropdown if we have child items.
      if (!empty($link['#below'])) {
        $class[] = 'dropdown';
        $class[] = 'clearfix';
      }
      if (!empty($trail) && isset($trail[0]['mlid']) && $link['#original_link']['mlid'] == $trail[0]['mlid']) {
        $class[] = 'active';
      }
      $output .= '<li' . drupal_attributes(array('class' => $class)) . '>';

      if (isset($link['#href'])) {
        // Pass in $link as $options, they share the same keys.
        $output .= l($link['#title'], $link['#href'], array('attributes' => $link['#attributes']));
      } elseif (!empty($link['#title'])) {
        // Wrap non-<a> links in <span> for adding title and class attributes.
        if (empty($link['#html'])) {
          $link['#title'] = check_plain($link['#title']);
        }
        $span_attributes = '';
        if (isset($link['#attributes'])) {
          $span_attributes = drupal_attributes($link['#attributes']);
        }
        $output .= '<span' . $span_attributes . '>' . $link['#title'] . '</span>';
      }

      // If link has child items, print a toggle and dropdown menu.
      if (!empty($link['#below'])) {
        $dropdown_id = 'main-menu-dropdown-' . $i;
        $output .= '<a href="#" class="dropdown-toggle pull-right" data-toggle="dropdown" id="' . $dropdown_id . '"><span class="caret"></span></a>';
        $output .= theme('links__system_main_menu', array(
          'links' => $link['#below'],
          'attributes' => array(
            'class' => array('dropdown-menu'),
            'role' => 'menu',
            'aria-labelledby' => $dropdown_id
          ),
        ));
      }
      $i++;
      $output .= "</li>\n";
    }
    $output .= '</ul>';
  }

  return $output;
}

/** More Web Standards transplant **/

/*********************************************************************
 *
 * INTERNAL
 *********************************************************************/

/*
 * Parses menuitem and returns true if there are
* active child menu items.

*/

function innovation_menuitem_has_active_children($menuitem)
{
  if (is_array($menuitem) && isset($menuitem['below']) && !empty($menuitem['below'])) {
    foreach ($menuitem['below'] as $child) {
      if (isset($child['link']) && $child['link']['access'] && ($child['link']['hidden'] == 0)) return true;
    }
  }
  return false;
}


/**
 * Overides theme_checkbox
 */
function innovation_checkbox($variables)
{
  $element = $variables['element'];
  $element['#attributes']['type'] = 'checkbox';
  element_set_attributes($element, array('id', 'name', '#return_value' => 'value'));

  // Unchecked checkbox has #value of integer 0.
  if (!empty($element['#checked'])) {
    $element['#attributes']['checked'] = 'checked';
  }
  _form_set_class($element, array('form-checkbox'));

  return '<input' . drupal_attributes($element['#attributes']) . ' /><span class="outer-wrap"><i class="fa fa-check"></i></span>';
}

/**
 * Overides theme_radio
 */
function innovation_radio($variables)
{
  $element = $variables['element'];
  $element['#attributes']['type'] = 'radio';
  element_set_attributes($element, array('id', 'name', '#return_value' => 'value'));

  if (isset($element['#return_value']) && $element['#value'] !== FALSE && $element['#value'] == $element['#return_value']) {
    $element['#attributes']['checked'] = 'checked';
  }
  _form_set_class($element, array('form-radio'));

  return '<input' . drupal_attributes($element['#attributes']) . ' /><span class="outer-wrap"><i class="fa fa-circle"></i></span>';
}

/**
 * Overrides theme_form_element().
 */
function innovation_form_element(&$variables)
{
  $element = &$variables['element'];
  $is_checkbox = FALSE;
  $is_radio = FALSE;

  // This function is invoked as theme wrapper, but the rendered form element
  // may not necessarily have been processed by form_builder().
  $element += array(
    '#title_display' => 'before',
  );

  // Add element #id for #type 'item'.
  if (isset($element['#markup']) && !empty($element['#id'])) {
    $attributes['id'] = $element['#id'];
  }

  // Check for errors and set correct error class.
  if (isset($element['#parents']) && form_get_error($element)) {
    $attributes['class'][] = 'error';
  }

  if (!empty($element['#type'])) {
    $attributes['class'][] = 'form-type-' . strtr($element['#type'], '_', '-');
  }
  if (!empty($element['#name'])) {
    $attributes['class'][] = 'form-item-' . strtr($element['#name'], array(
        ' ' => '-',
        '_' => '-',
        '[' => '-',
        ']' => '',
      ));
  }
  // Add a class for disabled elements to facilitate cross-browser styling.
  if (!empty($element['#attributes']['disabled'])) {
    $attributes['class'][] = 'form-disabled';
  }
  if (!empty($element['#autocomplete_path']) && drupal_valid_path($element['#autocomplete_path'])) {
    $attributes['class'][] = 'form-autocomplete';
  }
  $attributes['class'][] = 'form-item';

  // See http://getbootstrap.com/css/#forms-controls.
  if (isset($element['#type'])) {
    if ($element['#type'] == "radio") {
      $attributes['class'][] = 'radio';
      $is_radio = TRUE;
    } elseif ($element['#type'] == "checkbox") {
      $attributes['class'][] = 'checkbox';
      $is_checkbox = TRUE;
    } else {
      $attributes['class'][] = 'form-group';
    }
  }

  $description = FALSE;
  $tooltip = FALSE;
  // Convert some descriptions to tooltips.
  // @see bootstrap_tooltip_descriptions setting in _bootstrap_settings_form()
  if (!empty($element['#description'])) {
    $description = $element['#description'];
    if (theme_get_setting('bootstrap_tooltip_enabled') && theme_get_setting('bootstrap_tooltip_descriptions') && $description === strip_tags($description) && strlen($description) <= 200) {
      $tooltip = TRUE;
      $attributes['data-toggle'] = 'tooltip';
      $attributes['title'] = $description;
    }
  }

  $output = '<div' . drupal_attributes($attributes) . '>' . "\n";

  // If #title is not set, we don't display any label or required marker.
  if (!isset($element['#title'])) {
    $element['#title_display'] = 'none';
  }

  $prefix = '';
  $suffix = '';
  if (isset($element['#field_prefix']) || isset($element['#field_suffix'])) {
    // Determine if "#input_group" was specified.
    if (!empty($element['#input_group'])) {
      $prefix .= '<div class="input-group">';
      $prefix .= isset($element['#field_prefix']) ? '<span class="input-group-addon">' . $element['#field_prefix'] . '</span>' : '';
      $suffix .= isset($element['#field_suffix']) ? '<span class="input-group-addon">' . $element['#field_suffix'] . '</span>' : '';
      $suffix .= '</div>';
    } else {
      $prefix .= isset($element['#field_prefix']) ? $element['#field_prefix'] : '';
      $suffix .= isset($element['#field_suffix']) ? $element['#field_suffix'] : '';
    }
  }

  switch ($element['#title_display']) {
    case 'before':
    case 'invisible':
      $output .= ' ' . theme('form_element_label', $variables);
      $output .= ' ' . $prefix . $element['#children'] . $suffix . "\n";
      break;

    case 'after':
      if ($is_radio || $is_checkbox) {
        $variables['#children'] = ' ' . $prefix . $element['#children'] . $suffix;

      } else {
        $output .= ' ' . $prefix . $element['#children'] . $suffix;
      }
      $output .= ' ' . theme('form_element_label', $variables) . "\n";
      break;

    case 'none':
    case 'attribute':
      // Output no label and no required marker, only the children.
      $output .= ' ' . $prefix . $element['#children'] . $suffix . "\n";
      break;
  }

  if ($description && !$tooltip) {
    $output .= '<p class="help-block">' . $element['#description'] . "</p>\n";
  }

  $output .= "</div>\n";

  return $output;
}


/**
 * Overrides theme_form_element_label().
 */
function innovation_form_element_label(&$variables)
{
  $element = $variables['element'];

  // This is also used in the installer, pre-database setup.
  $t = get_t();

  // Determine if certain things should skip for checkbox or radio elements.
  $skip = (isset($element['#type']) && ('checkbox' === $element['#type'] || 'radio' === $element['#type']));

  // If title and required marker are both empty, output no label.
  if ((!isset($element['#title']) || $element['#title'] === '' && !$skip) && empty($element['#required'])) {
    return '';
  }

  // If the element is required, a required marker is appended to the label.
  $required = !empty($element['#required']) ? theme('form_required_marker', array('element' => $element)) : '';

  $title = filter_xss_admin($element['#title']);

  $attributes = array();

  // Style the label as class option to display inline with the element.
  if ($element['#title_display'] == 'after' && !$skip) {
    $attributes['class'][] = $element['#type'];
  } // Show label only to screen readers to avoid disruption in visual flows.
  elseif ($element['#title_display'] == 'invisible') {
    $attributes['class'][] = 'element-invisible';
  }

  if (!empty($element['#id'])) {
    $attributes['for'] = $element['#id'];
  }

  // Insert radio and checkboxes inside label elements.
  $output = '';
  if (isset($variables['#children'])) {
    $output .= $variables['#children'];
  }

  // Append label.
  $output .= $t('!title !required', array('!title' => $title, '!required' => $required));

  // The leading whitespace helps visually separate fields from inline labels.
  return ' <label' . drupal_attributes($attributes) . '>' . $output . "</label>\n";
}
