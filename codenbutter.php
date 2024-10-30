<?php

/**
 * Plugin Name: CodenButter: Easily Apply Pop-Ups and Banners to Your Store Without Developers
 * Description: 사이트 방문자를 고객으로 만들고 싶다면? 코드앤버터로 팝업, 배너, 설문을 설계해 고객을 행동하게 만들어보세요!
 * Author: Purple IO
 * Version: 0.1.0
 * Author URI: https://www.codenbutter.com
 * License: GPLv2 or later
 * Text Domain: codenbutter
 * Requires at least: 6.4
 * Requires PHP: 7.0
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
*/

if (!defined('ABSPATH')) {
  exit;
}

$jsonString = file_get_contents(plugin_dir_path(__FILE__) . '/env.json');
if ($jsonString === false) {
  die('env.json 파일을 넣어주세요!!');
} else {
  $data = json_decode($jsonString, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    define('CODENBUTTER_SCRIPT_URL', $data['scriptUrl']);
    define('CODENBUTTER_TO_AUTHORIZE_URL', $data['frontAppUrl'] . '/i/wordpress/to-authorize');
    define('CODENBUTTER_MANUAL_UPDATE_URL', $data['frontAppUrl'] . '/api/wordpress/manual-update');
    define('CODENBUTTER_IS_DEV', $data['isDev'] === 'true' ? true : false);
  } 
}

add_action('admin_menu', 'codenbutter_plugin_create_menu');
add_action('admin_init', 'codenbutter_register_plugin_settings');
add_action('wp_enqueue_scripts', 'codenbutter_plugin_init');
add_action('plugins_loaded', 'codenbutter_init_integration_id');

function codenbutter_init_integration_id()
{
  $integration_id = get_option('codenbutter_integration_id');
  if ($integration_id === false || $integration_id === '') {
    $random_value = codenbutter_generate_random_string(20);
    update_option('codenbutter_integration_id', $random_value);
  }
}

function codenbutter_generate_random_string($length = 20)
{
  // Generate random bytes and encode it as base64
  $random_bytes = random_bytes($length);
  $base64_string = base64_encode($random_bytes);

  // Replace characters that are not URL-friendly
  $url_friendly_string = str_replace(['+', '/', '='], ['-', '_', ''], $base64_string);

  // Return the substring of the desired length
  return substr($url_friendly_string, 0, $length);
}


function codenbutter_plugin_create_menu()
{
  add_menu_page('코드앤버터 설정', '코드앤버터', 'administrator', 'codenbutter', 'codenbutter_plugin_settings_page', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4NCjxwYXRoIGQ9Ik0yMyA4LjA2MzQ4TDIzIDguMDYxOThMMTIuMzc2OSAxLjQ0MzA2TDExLjg3MDQgNC4xMDEyOUwxMy40MjMyIDMuMTQ2NjhDMTMuNTg3MSAzLjA0NjE1IDEzLjc3NyAyLjk5NDgzIDEzLjk2OTMgMi45OTkyNkMxNC4xNjE4IDMuMDAzNjkgMTQuMzQ4OSAzLjA2MzU3IDE0LjUwODEgMy4xNzE2OEwyMC41NiA3LjIzMDg5TDIwLjU2MTEgNy4yMzE2MkMyMC42OTYzIDcuMzIzMjIgMjAuODA3IDcuNDQ2NTUgMjAuODgzNSA3LjU5MDgyQzIwLjk2MDMgNy43MzU0OCAyMS4wMDAyIDcuODk2NzUgMjEgOC4wNjA0N0wyMSA4LjA2MTk4VjEwLjAzNDdMMjMgOC4wNjM0OFoiIGZpbGw9IiNBN0FBQUQiLz4NCjxwYXRoIGQ9Ik04LjM2ODE1IDIyLjQ4Mkw4LjM3MDA0IDIyLjQ4MzNMOS44ODc5MyAyMC45ODcyQzkuNzQ2NTYgMjAuOTYzNCA5LjYxMTM3IDIwLjkwOTUgOS40OTE4NiAyMC44MjgzTDguNzc1MDcgMjAuMzQ2NEw4LjM2ODE1IDIyLjQ4MloiIGZpbGw9IiNBN0FBQUQiLz4NCjxwYXRoIGQ9Ik0xLjM0OTQxIDE3LjMzNTVDMS41Nzk1OCAxNy43Njk0IDEuOTEyODcgMTguMTQwMiAyLjMyIDE4LjQxNTNMOC4zNjgxNSAyMi40ODJMOC43NzUwNyAyMC4zNDY0TDMuNDQgMTYuNzU5MUwzLjQzOTE1IDE2Ljc1ODVDMy4zMDM4MyAxNi42NjY5IDMuMTkzMDMgMTYuNTQzNSAzLjExNjQ3IDE2LjM5OTJDMy4wMzk3NSAxNi4yNTQ1IDIuOTk5NzUgMTYuMDkzMyAzIDE1LjkyOTVMMyAxMC4xMTE2QzMuMDAwMjcgOS45NDE0MyAzLjA0NCA5Ljc3NDEzIDMuMTI3MDYgOS42MjU1OEMzLjIxMDE0IDkuNDc2OTggMy4zMzAwOCA5LjM1MTg5IDMuNDc1IDkuMjYyNDhMMTEuODcwNCA0LjEwMTI5TDEyLjM3NjkgMS40NDMwNkwyLjQyNSA3LjU2MTEyQzEuOTg5ODggNy44MjkzOSAxLjYzMDU3IDguMjA0MzYgMS4zODExNyA4LjY1MDQyQzEuMTMxNzcgOS4wOTY0OSAxLjAwMDU3IDkuNTk4ODYgMSAxMC4xMDk4TDEgMTUuOTI3NEMwLjk5OTM5OCAxNi40MTgyIDEuMTE5MzggMTYuOTAxOCAxLjM0OTQxIDE3LjMzNTVaIiBmaWxsPSIjQTdBQUFEIi8+DQo8cGF0aCBkPSJNMTQuMDE1NCAxLjAwMDhDMTMuNDM4IDAuOTg3NDk5IDEyLjg2OTEgMS4xNDEwNyAxMi4zNzY5IDEuNDQzMDZMMjMgOC4wNjE5OEMyMy4wMDA1IDcuNTcxMzMgMjIuODgwNSA3LjA4ODA1IDIyLjY1MDYgNi42NTQ1NEMyMi40MjA0IDYuMjIwNTggMjIuMDg3MSA1Ljg0OTc4IDIxLjY4IDUuNTc0NzNMMTUuNjMxOSAxLjUxODAyTDE1LjYyOTMgMS41MTYzMkMxNS4xNTIxIDEuMTkzMDYgMTQuNTkxOCAxLjAxNDA3IDE0LjAxNTQgMS4wMDA4WiIgZmlsbD0iI0E3QUFBRCIvPg0KPHBhdGggZD0iTTguMzcwMDQgMjIuNDgzM0M4Ljg0NzQgMjIuODA2OCA5LjQwNzk2IDIyLjk4NTkgOS45ODQ1OSAyMi45OTkyQzEwLjU2MiAyMy4wMTI1IDExLjEzMDkgMjIuODU4OSAxMS42MjMyIDIyLjU1NjlMMjEuNTc1IDE2LjQzODlDMjIuMDEwMSAxNi4xNzA2IDIyLjM2OTQgMTUuNzk1NiAyMi42MTg4IDE1LjM0OTZDMjIuODY4MiAxNC45MDM1IDIyLjk5OTQgMTQuNDAxMSAyMyAxMy44OTAyTDIzIDguMDYzNDhMMjEgMTAuMDM0N1YxMy44ODhDMjAuOTk5OCAxNC4wNTgzIDIwLjk1NjEgMTQuMjI1NyAyMC44NzI5IDE0LjM3NDRDMjAuNzg5OSAxNC41MjI5IDIwLjY2OTggMTQuNjQ4MSAyMC41MjUgMTQuNzM3NUwxMC41NzYyIDIwLjg1MzdDMTAuNDEyMyAyMC45NTQxIDEwLjIyMjkgMjEuMDA1MiAxMC4wMzA3IDIxLjAwMDdDOS45ODI2MyAyMC45OTk2IDkuOTM0OTQgMjAuOTk1MSA5Ljg4NzkzIDIwLjk4NzJMOC4zNzAwNCAyMi40ODMzWiIgZmlsbD0iI0E3QUFBRCIvPg0KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xLjQwMjQ0IDguNjE5NTVDMS42OTU0MiA4LjE1MTM4IDIuMzEyNDYgOC4wMDkzNiAyLjc4MDYyIDguMzAyMzRMMTAuNTMwNiAxMy4xNTIzQzEwLjgyMjcgMTMuMzM1MSAxMS4wMDAxIDEzLjY1NTUgMTEuMDAwMSAxNFYyMkMxMS4wMDAxIDIyLjU1MjMgMTAuNTUyNCAyMyAxMC4wMDAxIDIzQzkuNDQ3ODUgMjMgOS4wMDAxMyAyMi41NTIzIDkuMDAwMTMgMjJWMTQuNTUzOUwxLjcxOTY0IDkuOTk3NzNDMS4yNTE0OCA5LjcwNDc0IDEuMTA5NDYgOS4wODc3MSAxLjQwMjQ0IDguNjE5NTVaIiBmaWxsPSIjQTdBQUFEIi8+DQo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTIyLjYzMzggNi42MjU5NUMyMi45MTIyIDcuMTAyOTIgMjIuNzUxMiA3LjcxNTI4IDIyLjI3NDMgNy45OTM2OUwxMC41MDQzIDE0Ljg2MzdDMTAuMDI3MyAxNS4xNDIxIDkuNDE0OTIgMTQuOTgxMSA5LjEzNjUyIDE0LjUwNDFDOC44NTgxMSAxNC4wMjcyIDkuMDE5MDkgMTMuNDE0OCA5LjQ5NjA2IDEzLjEzNjRMMjEuMjY2MSA2LjI2NjRDMjEuNzQzIDUuOTg3OTkgMjIuMzU1NCA2LjE0ODk3IDIyLjYzMzggNi42MjU5NVoiIGZpbGw9IiNBN0FBQUQiLz4NCjwvc3ZnPg0K', 80);
}

// function manual_update_on_codenbutter_site_id_update($old_value, $value, $option)
// {
//   $integration_id = get_option('codenbutter_integration_id');

//   $args = array(
//     'body' => json_encode(
//       array(
//         'site_id' => $value,
//         'integration_id' => $integration_id,
//       )
//     ),
//     'headers' => array(
//       'Content-Type' => 'application/json',
//     ),
//     'timeout' => 60,
//     'redirection' => 5,
//     'blocking' => true,
//     'httpversion' => '1.0',
//     'sslverify' => CODENBUTTER_IS_DEV ? false : true,
//     'data_format' => 'body',
//   );

//   $response = wp_remote_post(CODENBUTTER_MANUAL_UPDATE_URL, $args);

//   if (is_wp_error($response)) {
//     error_log("Error sending POST request: " . $response->get_error_message());
//   } else {
//     error_log('POST request sent successfully. Response: ' . wp_remote_retrieve_body($response));
//   }
// }

// add_action("update_option_codenbutter_site_id", "manual_update_on_codenbutter_site_id_update", 10, 3);

function codenbutter_plugin_settings_page()
{
  $siteUrl = get_option('home');
  if (!$siteUrl) {
    $siteUrl = get_site_url();
  }
  $toAuthorizeUrl = CODENBUTTER_TO_AUTHORIZE_URL . '?site_url=' . urlencode($siteUrl) . '&site_name=' . urlencode(get_bloginfo('name')) . '&integration_id=' . get_option('codenbutter_integration_id');

  ?>
  <div class="wrap">
    <h1>코드앤버터 설정</h1>

    <form id="codenbutter-form" method="post" action="options.php">
      <?php settings_fields('codenbutter-settings-group'); ?>
      <?php do_settings_sections('codenbutter-settings-group'); ?>
      <!-- <p><?php echo esc_html(get_option('codenbutter_integration_id')) ?></p> -->
      <table class="form-table">
        <tr valign="top">
          <th scope="row">사이트 ID</th>
          <td>
            <input type="hidden" name="codenbutter_integration_id"
              value="<?php echo esc_attr(get_option('codenbutter_integration_id')); ?>" />
            <input type="text" name="codenbutter_site_id" placeholder="사이트 아이디를 입력해주세요" style="min-width: 350px;"
              value="<?php echo esc_attr(get_option('codenbutter_site_id')); ?>" />
          </td>
        </tr>
      </table>

      <p class="description">
        <a href="<?php echo esc_attr($toAuthorizeUrl); ?>" target="_blank">코드앤버터</a>에 회원 가입을 하면 사이트 ID를 확인할 수 있습니다.
      </p>

      <p class="description">
        <a href="<?php echo esc_attr($toAuthorizeUrl); ?>" target="_blank">코드앤버터의 서비스 페이지</a>에서 다양한 캠페인을 제작할 수 있습니다.
      </p>

      <?php submit_button(); ?>
    </form>
  </div>
<?php }

function codenbutter_register_plugin_settings()
{
  register_setting('codenbutter-settings-group', 'codenbutter_site_id');
  register_setting('codenbutter-settings-group', 'codenbutter_integration_id');
}



/** 
 * @see https://developer.wordpress.org/reference/functions/wp_register_script/ 
 * @see https://developer.wordpress.org/reference/functions/wp_localize_script/
 * @see https://developer.wordpress.org/reference/functions/wp_enqueue_script/
 */
function codenbutter_plugin_init()
{
  wp_register_script('codenbutter-script-js', plugins_url('/codenbutter_script.js', __FILE__), array(), '0.1.0', true);

  if (is_user_logged_in()) {
    $current_user = wp_get_current_user();

    // meta data
    $mobile_number = get_user_meta(get_current_user_id(), 'billing_phone', true);

    $codenbutter_options = array(
      'codenbutter_site_id' => sanitize_text_field(get_option('codenbutter_site_id')),
      'script_url' => CODENBUTTER_SCRIPT_URL,
      'login' => true,
      'id' => $current_user->ID,
      'display_name' => $current_user->display_name,
      'user_email' => $current_user->user_email,
      'mobile_number' => $mobile_number,
    );
  } else {
    $codenbutter_options = array(
      'codenbutter_site_id' => sanitize_text_field(get_option('codenbutter_site_id')),
      'script_url' => CODENBUTTER_SCRIPT_URL,
      'login' => false,
    );
  }
  wp_localize_script('codenbutter-script-js', 'codenbutter_options', $codenbutter_options);
  wp_enqueue_script('codenbutter-script-js');
}

// Hook activation

add_action('activated_plugin', 'codenbutter_activation_redirect');

function codenbutter_activation_redirect($plugin)
{
  if ($plugin == plugin_basename(__FILE__)) {
    exit(esc_url(wp_redirect(admin_url('admin.php?page=codenbutter'))));
  }
}
?>