<?php
/*
Plugin Name: Tag Selector
Plugin URI:  http://tag-selector.robmarston.com
Description: Tag Selector allows you to select tags for your post/page much the same way you select categories, no more guessing tag names and waiting for them to pop up!
Version:     1.0.0
Author:      Rob Marston
Author URI:  http://robmarston.com
License:     GPL-3
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html
*/

function tag_selector_add_meta() {
  add_meta_box('tag_selector_meta','Tag Selector','tag_selector_meta_box',null,'side');
}

function tag_selector_meta_box($link) {
  wp_nonce_field('tag_selector_meta_action','tag_selector_meta_nonce');
  ?>
  <div id="taxonomy-linktag" class="categorydiv">
    <ul id="tag-tabs" class="category-tabs">
      <li class="tabs">All Tags</li>
    </ul>
    <div id="tags-all" class="tabs-panel">
      <ul id="tagchecklist" data-wp-lists="list:tag" class="categorychecklist form-no-clear">
        <?php
        if (isset($link->ID)) {
          tag_selector_link_tag_checklist($link->ID);
        } else {
          tag_selector_link_tag_checklist();
        }
        ?>
      </ul>
    </div>
  </div>
  <?php
}

function tag_selector_get_link_tags($link_id = 0) {
  $tags = wp_get_object_terms($link_id,'post_tag',array('fields'=>'ids'));
  return array_unique($tags);
}

function tag_selector_link_tag_checklist($link_id = 0) {
  $default = 1;
  $checked_tags = array();
  if ($link_id) {
    $checked_tags = tag_selector_get_link_tags($link_id);
    if (!count($checked_tags)) {
      $checked_tags[] = $default;
    }
  } else {
    $checked_tags[] = $default;
  }
  $tags = get_terms('post_tag',array('orderby'=>'name','hide_empty'=>0));
  if (empty($tags)) {
    return;
  }
  foreach ($tags as $tag) {
    $checked = in_array($tag->term_id,$checked_tags) ? ' checked="checked"' : '';
    echo '<li id="link-tag-', $tag->term_id, '"><label for="in-link-tag-', $tag->term_id, '" class="selectit"><input value="', $tag->slug, '" type="checkbox" name="link_tag[]" id="in-link-tag-', $tag->term_id, '"', $checked, '/> ', $tag->name, "</label></li>";
  }
}

function tag_selector_meta_save($post_id) {
  if (!isset($_POST['tag_selector_meta_nonce'])) {
    return;
  };
  if (!wp_verify_nonce($_POST['tag_selector_meta_nonce'],'tag_selector_meta_action')) {
    return;
  };
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  };
  if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
    if (!current_user_can('edit_page',$post_id)) {
      return;
    };
  } else {
    if (!current_user_can('edit_post',$post_id)) {
      return;
    }
  };
  foreach($_POST as $key => $linkTags) {
    if ($key == 'link_tag' && is_array($linkTags)) {
      $tags = '';
      foreach ($linkTags as $tag) {
        if (is_string($tag)) {
          $tags .= sanitize_text_field($tag) . ',';
        }
      }
      $tags = substr($tags,0,-1);
      if (strlen($tags) > 0) {
        wp_set_post_tags($post_id,$tags);
      }
    }
  }
}

add_action('add_meta_boxes','tag_selector_add_meta');
add_action('save_post','tag_selector_meta_save');

?>
