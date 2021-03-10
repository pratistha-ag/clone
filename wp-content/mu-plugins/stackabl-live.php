<?php
/*
Plugin Name: Public Link By Stackabl
Plugin URI: https://stackabl.io/
Description: Rewrite Wordpress URL For Public Link.
Version: 0.0.1
Author: Stackabl
Author URI: https://stackabl.io/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2015 Automattic, Inc.
*/


// If this file is called directly, abort!!!
if (!defined('ABSPATH')) {
  die;
}

class PublicLinkHelper
{
  public $homeUrl;
  public function __construct()
  {
    if (empty($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
      return;
    }
    $home  = get_option('home');
    $host = str_replace('/^www./', '', parse_url($home, PHP_URL_HOST));
    if (parse_url($home, PHP_URL_PORT)) {
      $host = $host . ':' . parse_url($home, PHP_URL_PORT);
    }

    $this->homeUrl = $host;

    add_action('send_headers', array($this, 'sendCacheHeader'), 9999);
    add_action('send_headers', array($this, 'sendLocalhostHeader'), 9999);
    remove_action('template_redirect', 'redirect_canonical');


    $publicLinkRewrite = array(
      'the_author_posts_link',
      'bloginfo_url',
      'the_content_more_link',
      'get_rest_url',
      'the_permalink',
      'wp_list_pages',
      'the_tags',
      'get_shortlink',
      'wp_redirect',
      'page_link',
      'day_link',
      'wp_list_categories',
      'post_type_archive_link',
      'post_link',
      'get_pagenum_link',
      'get_comments_pagenum_link',
      'term_link',
      'search_link',
      'blog_option_siteurl',
      'attachment_link',
      'admin_url',
      'month_link',
      'post_type_link',
      'network_site_url',
      'option_siteurl',
      'option_home',
      'year_link',
      'get_site_url',
      'home_url',
      'includes_url',
      'site_url',
      'site_option_siteurl',
      'get_admin_url',
      'network_home_url',
      'wp_login_url',
      'get_the_author_url',
      'get_locale_stylesheet_uri',
      'get_comment_link',
      'network_admin_url',
      'wp_get_attachment_image_src',
      'wp_get_attachment_thumb_url',
      'plugins_url',
      'wp_get_attachment_url',
      'wp_logout_url',
      'wp_lostpassword_url',
      'get_stylesheet_uri',
      'get_theme_root_uri',
      'stylesheet_directory_uri',
      'script_loader_src',
      'style_loader_src',
      'theme_root_uri',
      'template_directory_uri',
    );

    foreach ($publicLinkRewrite as $hooks) {
      add_filter($hooks, array($this, 'publicLinkReplacement'));
    }

    add_filter('pre_update_option', array($this, 'hostLinkReplacement'));
    add_filter('wp_insert_post_data', array($this, 'hostLinkPostReplacement'), 9999);
  }

  public function getHostUrl()
  {
    return $this->homeUrl;
  }

  public function getLocalhost()
  {
    $localhost = str_replace('/^www./', '', $_SERVER['HTTP_HOST']);
    if ($localhost === 'localhost') {
      $localhost .= ':' . $_SERVER['SERVER_PORT'];
    }
    return $localhost;
  }

  public function getPublicLink()
  {
    return $_SERVER['HTTP_X_ORIGINAL_HOST'];
  }




  public function sendCacheHeader()
  {
    header('Cache-Control: private');
  }

  public function sendLocalhostHeader()
  {
    header('X-Local-Host: ' . $this->getLocalhost());
  }

  public function searchAndReplace($old, $new, $subject)
  {
    $subject = str_replace('www.' . $old, $new, $subject);
    $subject = str_replace($old, $new, $subject);

    return $subject;
  }

  public function publicLinkReplacement($str)
  {
    $localhostName = $this->getLocalhost();
    $publichostName = $this->getPublicLink();
    $hostName = $this->getHostUrl();
    $str = $this->searchAndReplace($localhostName, $publichostName, $str);
    $str = $this->searchAndReplace($hostName, $publichostName, $str);
    $str = str_replace('http://' . $publichostName, 'https://' . $publichostName, $str);
    return $str;
  }

  public function hostLinkReplacement($str)
  {
    return $this->searchAndReplace($this->getPublicLink(), $this->getHostUrl(), $str);
  }

  public function hostLinkPostReplacement($post)
  {
    $post->post_content = $this->hostLinkReplacement($post->post_content);

    return $post;
  }
}

new PublicLinkHelper();
