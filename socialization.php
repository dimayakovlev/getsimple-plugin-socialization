<?php
/*
Plugin Name: Socialization
Description: Add support for main social meta tags
Version: 1.0
Author: Dmitry Yakovlev
Author URI: http://dimayakovlev.ru/
*/

$thisfile = basename(__FILE__, ".php");

if (!is_frontend()) {
  i18n_merge($thisfile) || i18n_merge($thisfile, 'en_US');
  if (myself(false) == 'edit.php' || myself(false) == 'settings.php') add_action('footer', 'pluginSocializationJS', array($thisfile));
  add_action('edit-meta', 'pluginSocializationGUI', array($thisfile));
  add_action('settings-website-extras', 'pluginSocializationGUIWebsiteSettings', array($thisfile));
  add_action('settings-website', 'pluginSocializationSaveWebsiteData');
  add_filter('pagesavexml', 'pluginSocializationSavePageData');
  add_filter('draftsavexml', 'pluginSocializationSavePageData');
} else {
  add_action('theme-header', 'pluginSocializationCreateMeta');
}

register_plugin(
  $thisfile,
  i18n_r($thisfile.'/TITLE'),
  '1.0',
  i18n_r($thisfile.'/AUTHOR'),
  'http://dimayakovlev.ru',
  i18n_r($thisfile.'/DESCRIPTION'),
  '',
  ''
);

function pluginSocializationGUI($plugin_name) {
  global $id, $data_edit;
  $social_enable = $social_title = $social_desc = $social_img = '';
  $social_og_type = ''; # http://ogp.me/
  $social_twitter_card_type = ''; # https://dev.twitter.com/cards/types

  if ($id) {
    if ($data_edit) {
      $social_enable = (string)$data_edit->socEnable;
      $social_title = stripslashes($data_edit->socTitle);
      $social_desc = stripslashes($data_edit->socDesc);
      $social_img = (string)$data_edit->socImg;
      $social_img_draft = (string)$data_edit->socImgDraft;
      $social_og_type = (string)$data_edit->socOGType;
      $social_twitter_card_type = (string)$data_edit->socTwiCard;
    }
  } else {
    // Add fields prefill
  }

  $social_enable = $social_enable == '1' ? 'checked' : '';
?>
<div id="socialization">
    <div class="wideopt">
      <p class="inline clearfix">
        <input type="checkbox" id="post-socializationEnable" name="post-socializationEnable" <?php echo $social_enable; ?> />
        <label class="checkbox" for="post-socializationEnable"><?php i18n($plugin_name.'/USE_TAGS'); ?></label>
      </p>
    </div>
    <div class="wideopt">
      <p class="clearfix">
      	<label for="post-socializationTitle"><?php i18n($plugin_name.'/SOCIAL_META_TTILE'); ?>:</label>
        <input class="text short" id="post-socializationTitle" name="post-socializationTitle" type="text" value="<?php echo $social_title; ?>" />
      </p>
    </div>
    <div class="wideopt">
      <p class="clearfix">
      	<label for="post-socializationImageDraft"><?php i18n($plugin_name.'/SOCIAL_META_IMAGE_URL'); ?>:<span class="right" id="socializationImagePreview"></span></label>
        <input class="text short" id="post-socializationImageDraft" name="post-socializationImageDraft" type="text" value="<?php echo $social_img_draft; ?>" />
        <input type="hidden" id="post-socializationImage" name="post-socializationImage" vale="<?php echo $social_img; ?>" />
      </p>
    </div>
    <div class="wideopt">
      <p class="clearfix">
        <label for="post-socializationDesc" class="clearfix"><?php i18n($plugin_name.'/SOCIAL_META_DESC'); ?>:</label>
        <textarea class="text short" id="post-socializationDesc" name="post-socializationDesc" ><?php echo $social_desc; ?></textarea>
      </p>
    </div>
    <div class="leftopt">
      <p class="clearfix">
        <label for="post-socializationOgType" class="clearfix"><?php i18n($plugin_name.'/OG_TYPE_OBJECT'); ?>:</label>
        <select class="text" id="post-socializationOgType" name="post-socializationOgType">
          <option value="article"<?php if ($social_og_type == 'article') echo ' selected'; ?>>Article</a>
          <option value="product"<?php if ($social_og_type == 'product') echo ' selected'; ?>>Product</a>
          <option value="website"<?php if ($social_og_type == 'website') echo ' selected'; ?>>Website</a>
        </select>
      </p>
    </div>
    <div class="rightopt">
      <p class="clearfix">
        <label for="post-socializationTwitterCardType" class="clearfix"><?php i18n($plugin_name.'/TWITTER_CARD_TYPE'); ?>:</label>
        <select class="text" id="post-socializationTwitterCardType" name="post-socializationTwitterCardType">
          <option value="summary"<?php if ($social_og_type == 'summary') echo ' selected'; ?>>Summary Card</a>
          <option value="summary_large_image"<?php if ($social_og_type == 'summary_large_image') echo ' selected'; ?>>Summary Card with Large Image</a>
        </select>
      </p>
    </div>
    <div class="clear"></div>
</div>
<?php
}

function pluginSocializationSavePageData($xml) {
  $fields = array('post-socializationTitle' => 'socTitle', 'post-socializationImage' => 'socImg', 'post-socializationImageDraft' => 'socImgDraft', 'post-socializationDesc' => 'socDesc', 'post-socializationOgType' => 'socOGType', 'post-socializationTwitterCardType' => 'socTwiCard');
  $xml->addCDataChild('socEnable', (string)isset($_POST['post-socializationEnable']));
  foreach($fields as $key => $field) {
    $xml->addCDataChild($field, isset($_POST[$key]) ? safe_slash_html($_POST[$key]) : '');
  }
  return $xml;
}

function pluginSocializationCreateMeta() {
  global $data_index, $dataw;
  if ((string)$dataw->socEnable != '1' || (string)$data_index->socEnable != '1') return;
  $social_title = (string)$data_index->socTitle ? encode_quotes(strip_decode($data_index->socTitle)) : get_page_title(false);
  $social_desc = (string)$data_index->socDesc ? encode_quotes(strip_decode($data_index->socDesc)) : get_page_meta_desc(false);
  $social_img = (string)$data_index->socImg ?: (string)$dataw->socImg;

  /*
  if ($social_img && filter_var($social_img, FILTER_FLAG_HOST_REQUIRED) === false) {
    $social_img = suggest_site_path().ltrim($social_img, '/');
  }
  */

  $social_og_type = (string)$data_index->socOGType;
  # Now website URL must contain domain name!
  $social_og_url = exec_filter('linkcanonical', get_page_url(true));

  $social_twitter_card_type = (string)$data_index->socTwiCard;  
  $social_twitter_site = (string)$dataw->socTwiSite;

  $social_fb_app_id = (string)$dataw->socFBAppID;
  $social_fb_admins = (string)$dataw->socFBAdmins;
  
  // Open Graph Meta Tags
  echo '<meta property="og:title" content="'.$social_title.'" />'.PHP_EOL;
  echo '<meta property="og:description" content="'.$social_desc.'" />'.PHP_EOL;
  echo '<meta property="og:site_name" content="'.get_site_name(false).'" />'.PHP_EOL;
  echo '<meta property="og:type" content="'.$social_og_type.'" />'.PHP_EOL;
  echo '<meta property="og:url" content="'.$social_og_url.'" />'.PHP_EOL;
  if ($social_img) echo '<meta property="og:image" content="'.$social_img.'" />'.PHP_EOL;
  // Twitter Card Meta Tags
  if ($social_twitter_site) {
    echo '<meta name="twitter:title" content="'.$social_title.'" />'.PHP_EOL;
    echo '<meta name="twitter:description" content="'.$social_desc.'" />'.PHP_EOL;
    echo '<meta name="twitter:card" content="'.$social_twitter_card_type.'" />'.PHP_EOL;
    echo '<meta name="twitter:site" content="'.$social_twitter_site.'" />'.PHP_EOL;
    if ($social_img) echo '<meta name="twitter:image" content="'.$social_img.'" />
  '.PHP_EOL;
  }
  // Facebook Meta Tags
  if ($social_fb_app_id) echo '<meta property="fb:app_id" content="'.$social_fb_app_id.'" />'.PHP_EOL;
  if ($social_fb_admins) {
    #foreach(preg_split("/[\s,;|]+/", $social_fb_admins, null, PREG_SPLIT_NO_EMPTY) as $fb_admin) echo '<meta property="fb:admins" content="'.$fb_admin.'" />'.PHP_EOL;
    foreach(tagsToAry($social_fb_admins) as $fb_admin) {
      if ($fb_admin) echo '<meta property="fb:admins" content="'.$fb_admin.'" />'.PHP_EOL;
    }
  }

}

// Global plugin settings

function pluginSocializationGUIWebsiteSettings($plugin_name) {
  i18n_merge($plugin_name) || i18n_merge($plugin_name, 'en_US'); # Temporary solution
  $dataw = getXML(GSDATAOTHERPATH .GSWEBSITEFILE,false); # Temporary solution
  $social_disable = (string)$dataw->socEnable == '1' ? 'checked' : '';
  $social_img = (string)$dataw->socImg;
  $social_img_draft = (string)$dataw->socImgDraft;
  $social_twitter_site = (string)$dataw->socTwiSite;
  $social_fb_app_id = (string)$dataw->socFBAppID;
  $social_fb_admins = (string)$dataw->socFBAdmins;
?>
<style>
</style>
<div id="socialization">
  <div class="widesec">
  <!--<h3><?php i18n($plugin_name.'/SETTINGS_TITLE'); ?></h3>-->
    <p class="inline clearfix">
      <input type="checkbox" id="post-socializationEnable" name="post-socializationEnable" <?php echo $social_disable; ?> /> &nbsp;
      <label for="post-socializationEnable"><?php i18n($plugin_name.'/USE_TAGS'); ?></label>
    </p>
  </div>
  <div class="widesec">
    <p>
      <label for="post-socializationImageDraft"><?php i18n($plugin_name.'/SOCIAL_META_IMAGE_URL'); ?>:<span class="right" id="socializationImagePreview"></span></label>
      <input class="text" id="post-socializationImageDraft" name="post-socializationImageDraft" value="<?php echo $social_img_draft; ?>" />
      <input type="hidden" id="post-socializationImage" name="post-socializationImage" vale="<?php echo $social_img; ?>" />
    </p>
  </div>
  <div class="widesec">
    <p>
      <label for="post-socializationTwitterSite"><?php i18n($plugin_name.'/TWITTER_ACCOUNT'); ?>:</label>
      <input class="text" id="post-socializationTwitterSite" name="post-socializationTwitterSite" value="<?php echo $social_twitter_site; ?>" />
    </p>
  </div>
  <div class="widesec">
    <p>
      <label for="post-socializationFBAppID"><?php i18n($plugin_name.'/FB_APP_ID'); ?>:</label>
      <input class="text" id="post-socializationFBAppID" name="post-socializationFBAppID" value="<?php echo $social_fb_app_id; ?>" />
    </p>
  </div>
  <div class="widesec">
    <p>
      <label for="post-socializationTwitterSite"><?php i18n($plugin_name.'/FB_ADMINS'); ?>:</label>
      <input class="text" id="post-socializationFBAdmins" name="post-socializationFBAdmins" value="<?php echo $social_fb_admins; ?>" />
    </p>
  </div>
</div>
<?php
}

function pluginSocializationSaveWebsiteData() {
  global $xmls;
  $fields = array('post-socializationImage' => 'socImg', 'post-socializationImageDraft' => 'socImgDraft', 'post-socializationTwitterSite' => 'socTwiSite', 'post-socializationFBAppID' =>'socFBAppID', 'post-socializationFBAdmins' => 'socFBAdmins');
  $xmls->addCDataChild('socEnable', (string)isset($_POST['post-socializationEnable']));
  foreach($fields as $key => $field) $xmls->addCDataChild($field, isset($_POST[$key]) ? safe_slash_html($_POST[$key]) : '');
}

function pluginSocializationJS($plugin_name) {
?>
<script>
  $(document).ready(function() {
    var socImageInput = $('#post-socializationImage');
    var socImageInputDraft = $('#post-socializationImageDraft');
    var socImagePreview = $('#socializationImagePreview');

    socImageInputDraft.change(function() {
      socImageInput.val(null);
      socImagePreview.empty();
      var val = $(this).val().trim();
      if (val !== '') {
        $("<img>", {
            src: val,
            error: function() {
              socImagePreview.prepend('<span style="margin: 0;" class="input-warning"><?php i18n($plugin_name.'/SOCIAL_META_IMAGE_WARNING'); ?></span>');
            },
            load: function() {
              socImagePreview.prepend('<a rel="fancybox_i" href="' + val + '"><?php i18n($plugin_name.'/SOCIAL_META_IMAGE_VIEW'); ?></a>')
              socImageInput.val(val);
            }
        });
      }
    });

    socImageInputDraft.trigger("change");

  });
</script>
<?php
}