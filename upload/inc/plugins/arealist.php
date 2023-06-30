<?php
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// error_reporting ( -1 );
// ini_set ( 'display_errors', true );


function arealist_info()
{
  return array(
    "name"    => "Arealiste",
    "description"  => "Beim Erstellen eines Forums kann zusätzlich eine Beschreibung und ein Bild angegeben werden und optional eingestellt werden, ob diese in einer automatischen Arealiste angezeigt werden sollen.",
    "website"  => "https://github.com/katjalennartz/arealist",
    "author"  => "risuena",
    "authorsite"  => "https://github.com/katjalennartz",
    "version"  => "1.0",
    "compatibility" => "18*"
  );
}

function arealist_install()
{
  global $db, $cache, $mybb;

  arealist_uninstall();

  $db->query("ALTER TABLE `" . TABLE_PREFIX . "forums` 
  ADD `arealist_dscr` VARCHAR(2500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  ADD `arealist_img` VARCHAR(2500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '', 
  ADD `arealist_view` INT(1) NOT NULL DEFAULT '0',
  ADD `arealist_cat` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  ADD `arealist_order` int(10)  NOT NULL DEFAULT '1'
  ;");

  // Admin Einstellungen
  $setting_group = array(
    'name' => 'arealiste',
    'title' => 'Arealiste Einstellungen',
    'description' => 'Allgemeine Einstellungen für die Arealiste',
    'disporder' => 1, // The order your setting group will display
    'isdefault' => 0
  );
  $gid = $db->insert_query("settinggroups", $setting_group);
  $setting_array = array(
    'arealiste_list' => array(
      'title' => 'Ausgabe als Liste',
      'description' => 'Soll eine automatische Liste erstellt werden? (misc.php?action=arealiste_view)',
      'optionscode' => 'yesno',
      'value' => '1', // Default
      'disporder' => 1
    ),
    'arealiste_forumdisplay' => array(
      'title' => 'Ausgabe im Forum',
      'description' => 'Sollen de Infos im entsprechenden Forum angezeigt werden?',
      'optionscode' => 'yesno',
      'value' => '1', // Default
      'disporder' => 2
    ),

    //TODO BL Settings hinzufügen
    'arealiste_userwant' => array(
      'title' => 'Anfordern?',
      'description' => 'Können User eine Area anfordern?',
      'optionscode' => 'yesno',
      'value' => '1', // Default
      'disporder' => 2
    ),
    'arealiste_teamaccount' => array(
      'title' => 'Teamaccount?',
      'description' => 'Welcher accound(uid) soll in dem Fall eine PN bekommen?',
      'optionscode' => 'numeric',
      'value' => '1', // Default
      'disporder' => 2
    ),
  );
  foreach ($setting_array as $name => $setting) {
    $setting['name'] = $name;
    $setting['gid'] = $gid;
    $db->insert_query('settings', $setting);
  }
  rebuild_settings();

  //Templates erstellen
  // templategruppe
  $templategrouparray = array(
    'prefix' => 'arealist',
    'title'  => $db->escape_string('Arealiste'),
    'isdefault' => 1
  );

  $db->insert_query("templategroups", $templategrouparray);

  $template[0] = array(
    "title" => 'arealist_view',
    "template" => '
<html>
    <head>
    <title>{$mybb->settings[\\\'bbname\\\']} - {$lang->arealist_title}</title>
    {$headerinclude}
    </head>
    <body>
    {$header}
   
    <table width="100%" border="0" align="center" class="arealist">
    <tr>
      
      <td valign="top">
        <h1>{$lang->arealist_title}</h1>
        <div class="arealist-con">
        {$arealist_view_bit}
        </div>
        {$arealist_addarea}
      </td>
    </tr>
    </table>
  
    {$footer}
    </body>
    </html>
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[1] = array(
    "title" => 'arealist_bit',
    "template" => '
    {$category}
    <div class="arealist-con__item {$location}">
    {$arealist_img}
    {$arealist_dscr}
    {$arealist_link}
    </div>
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[2] = array(
    "title" => 'arealist_catbit',
    "template" => '
    <div class="arealist-category {$location}\">
    {$arealist_bit}
    </div>
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[3] = array(
    "title" => 'arealiste_categorytitle',
    "template" => '
    <div class="arealist-title">{$cats[\\\'arealist_cat\\\']}</div>
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  $template[4] = array(
    "title" => 'arealist_addarea',
    "template" => '
    <form action="misc.php?action=arealist_view" method="post">
    <div class="arealistform">
       <label for="area_img">Name der Area:</label>
      <input type="text" name="area_name" id="area_name" placeholder="Wie soll die Area heißen?"/>
      <label for="area_place">Einordnung</label>
      <select name="area_place" id="area_place">
          {$area_select}
      </select>
      <label for="area_img">Bild</label>
      <input type="url" name="area_img" id="area_img" placeholder="Ein Bild für die Area"/>
      <label for="area_descr">Beschreibung</label>
      <textarea name="area_descr"></textarea>
      <input type="submit" name="add_area" value="Area anfordern" id="area" class="bl-btn">
    </div>
      </form>
    ',
    "sid" => "-2",
    "version" => "1.0",
    "dateline" => TIME_NOW
  );

  foreach ($template as $row) {
    $db->insert_query("templates", $row);
  }


  $css = array(
    'name' => 'arealist.css',
    'tid' => 1,
    'attachedto' => '',
    "stylesheet" =>    '
    .arealist-con__item.arealist-forumdisplay {
      margin: 20px 10px;
      padding: 10px;
  }
  
  .arealist__item.arealist__dscr {
      width: 70%;
  }
  
  .arealist__item.arealist__link {
      width: 100%;
    text-align: center;
  }
  .arealist-con__item {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
    justify-content: center;
  }
  
  .arealist__img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      overflow: hidden;
      flex-shrink: 0;
      margin-right: 20px;
      grid-row: 1 / -1;
  }
  
  .arealist-forumdisplay__item.arealist__dscr {
      width: 80%;
  }
  
  .arealist-con__item.arealist__list {
      margin-bottom: 20px;
  }
  
  .arealist-con__item:nth-child(odd).arealist__list{
    flex-direction: row-reverse;
  }
  .bl-arealistform {
      display: grid;
      padding: 20px 50px;
      margin: 10px;
  }
  
  .bl-arealistform label {
      margin-top: 10px;
  }   
  ',
    'cachefile' => $db->escape_string(str_replace('/', '', 'arealist.css')),
    'lastmodified' => time()
  );

  require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

  $sid = $db->insert_query("themestylesheets", $css);
  $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

  $tids = $db->simple_select("themes", "tid");
  while ($theme = $db->fetch_array($tids)) {
    update_theme_stylesheet_list($theme['tid']);
  }
}

function arealist_activate()
{
  //Variablen einfügen
  include MYBB_ROOT . "/inc/adminfunctions_templates.php";

  //forumdisplay
  find_replace_templatesets("forumdisplay", "#" . preg_quote('{$rules}') . "#i", '{$rules} {$arealist_forumdisplay}');
}

function arealist_is_installed()
{
  global $db;
  if ($db->field_exists("arealist_dscr", "forums")) {
    return true;
  }
  return false;
}

function arealist_uninstall()
{
  global $db, $cache;
  // Spalten in Tabellen löschen
  if ($db->field_exists("arealist_dscr", "forums")) {
    $db->drop_column("forums", "arealist_dscr");
  }

  if ($db->field_exists("arealist_img", "forums")) {
    $db->drop_column("forums", "arealist_img");
  }
  if ($db->field_exists("arealist_view", "forums")) {
    $db->drop_column("forums", "arealist_view");
  }
  if ($db->field_exists("arealist_cat", "forums")) {
    $db->drop_column("forums", "arealist_cat");
  }
  if ($db->field_exists("arealist_order", "forums")) {
    $db->drop_column("forums", "arealist_order");
  }
  rebuild_settings();

  // Templates löschen
  $db->delete_query("templates", "title LIKE 'arealist%'");
  $db->delete_query("templategroups", "prefix = 'arealist'");

  //Einstellungen löschen
  $db->delete_query('settings', "name LIKE 'arealist%'");
  $db->delete_query('settinggroups', "name = 'arealist'");

  // CSS löschen
  require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
  $db->delete_query("themestylesheets", "name = 'application_ucp.css'");
  $query = $db->simple_select("themes", "tid");
  while ($theme = $db->fetch_array($query)) {
    update_theme_stylesheet_list($theme['tid']);
  }

  rebuild_settings();
}

function arealist_deactivate()
{
  //Variablen einfügen
  include MYBB_ROOT . "/inc/adminfunctions_templates.php";

  //forumdisplay
  find_replace_templatesets("forumdisplay", "#" . preg_quote('{$arealist_forumdisplay}') . "#i", '');
}

$plugins->add_hook("admin_formcontainer_output_row", "arealist_form");
function arealist_form($array)
{
  global $mybb, $db, $lang, $form_container, $forum_data, $form;
  //Hier bauen wir die Felder fürs ACP 
  $lang->load('forum_management');
  if ($array['title'] == $lang->misc_options && $lang->misc_options) {
    //wir basteln das Array für das Select
    $sel_array = array("new" => "Neue Kategorie", "none" => "Keine Kategorie");
    //vorhandene Kategorien suchen und in das Array hinzufügen, um es als Auswahlmöglichkeit zur Verfügung zu stellen
    $get_cats = $db->write_query("SELECT DISTINCT(arealist_cat) as cat 
    from " . TABLE_PREFIX . "forums order by cat ");
    while ($new = $db->fetch_array($get_cats)) {
      $catname = $new['cat'];
      $sel_array[$catname] = $catname;
    }
    //leere einträge rauswerfen
    $sel_array =  array_filter($sel_array);
    //hier erstellen wir jetzt die entsprechenden felder
    if (!empty($mybb->get_input('fid'))) {
      $arealist_view = $db->fetch_field($db->simple_select("forums", "arealist_view", "fid={$mybb->get_input('fid')}"), "arealist_view");

      if ($arealist_view == 1) {
        $arealist_view_yes = 1;
      } else {
        $arealist_view_yes = 0;
      }
    } else {
      $arealist_view_yes = 0;
    }
    $forum_rules = array(
      "Soll zur Arealiste hinzugefügt werden: <br /> \n" . $form->generate_yes_no_radio('arealist_view', $arealist_view_yes) . "<br />",
      // "Soll zur Arealiste hinzugefügt werden: <br /> \n" . $form->generate_radio_button('arealist_view', $arealist_view_yes, $lang->yes, array("checked" => $arealist_view_yes)) . "<br/>" . $form->generate_radio_button('arealist_view', $arealist_view_no, $lang->no, array("checked" => $arealist_view_no)) . "<br /><br />",
      "<br />Beschreibung für Arealiste: <br /> \n" . $form->generate_text_area('arealist_dscr', $forum_data['arealist_dscr'], array('id' => 'arealist_dscr')) . "<br />",
      "Link zum Bild für Arealiste: <br /> \n" . $form->generate_text_box('arealist_img', $forum_data['arealist_img'], array('id' => 'arealist_img')) . "<br />",
      "Anzeigereihenfolge in Liste: <br /> \n" . $form->generate_numeric_field('arealist_order', $forum_data['arealist_order'], array('id' => 'arealist_order')) . "<br />",
      "weitere Kategorie hinzufügen: <br /> \n" . $form->generate_text_box('arealist_cat_new', "", array('id' => 'arealist_cat_new')) . "<br />",
      "vorhandene Kategorie auswählen: <br /> \n" . $form->generate_select_box('arealist_cat', $sel_array, $forum_data['arealist_cat'], array('id' => 'arealist_cat'))  . "<br />",
    );
    //alles in einen Container packen
    $array['content'] .=     $form_container->output_row("Arealiste", "", "<div class=\"forum_settings_bit\">" . implode("</div><div class=\"forum_settings_bit\">", $forum_rules) . "</div>");
  }
  //rückgabe
  return $array;
}

//Hier speichern wir alles
$plugins->add_hook("admin_forum_management_add_commit", "arealist_commit");
$plugins->add_hook("admin_forum_management_edit_commit", "arealist_commit");
function arealist_commit()
{
  global $mybb, $cache, $db, $fid;
  if ($mybb->get_input('arealist_view') == "1") {
    $view = 1;
  } else {
    $view = 0;
  }
  if ($mybb->get_input('arealist_order') == "") {
    $order = 0;
  } else {
    $order = $mybb->get_input('arealist_order');
  }
  if ($mybb->get_input('arealist_cat_new') == "") {
    $cat = $mybb->get_input('arealist_cat');
  } elseif ($mybb->get_input('arealist_cat_new') == "none") {
    $cat = "";
  } else {
    $cat = $mybb->get_input('arealist_cat_new');
  }

  $update_array = array(
    "arealist_view" => $view,
    "arealist_dscr" => $db->escape_string($mybb->get_input('arealist_dscr')),
    "arealist_img" => $db->escape_string($mybb->get_input('arealist_img')),
    "arealist_cat" => $db->escape_string($cat),
    "arealist_order" => $order
  );

  $db->update_query("forums", $update_array, "fid='{$fid}'");
  $cache->update_forums();
}


//die Ausgabe auf der Übersichtsseite
$plugins->add_hook("misc_start", "arealist_view");
function arealist_view()
{
  global $mybb, $db, $templates, $header, $footer, $theme, $headerinclude, $lang, $location, $area_select;

  $lang->load('arealist');
  if ($mybb->input['action'] == "arealist_view" && $mybb->settings['arealiste_list']) {
    //parser
    require_once MYBB_ROOT . "inc/class_parser.php";
    $parser = new postParser;
    $options = array(
      "allow_html" => 1,
      "allow_mycode" => 1,
      "allow_smilies" => 0,
      "allow_imgcode" => 1,
      "filter_badwords" => 0,
      "nl2br" => 1,
      "allow_videocode" => 0,
    );

    $page = "";

    //Seitennavi bauen
    add_breadcrumb($lang->arealist_title, "misc.php?action=arealist_view");
    $location = "arealist__list";

    //Kategorien holen
    $arealist_cats = $db->simple_select("forums", "DISTINCT(arealist_cat)", "arealist_view = 1");
    while ($cats = $db->fetch_array($arealist_cats)) {
      if ($cats['arealist_cat'] == "") {
        $category = $lang->arealist_nocat;
      } else {

        $category = eval($templates->render('arealiste_categorytitle'));
      }

      $arealist_bit = "";
      //daten holen
      $arealist_data = $db->simple_select("forums", "fid, name, arealist_view, arealist_dscr, arealist_img", "arealist_cat = '{$cats['arealist_cat']}' and arealist_view = 1", array("order_by" => "arealist_order, name"));

      //daten durchgehen
      while ($data = $db->fetch_array($arealist_data)) {
        if (!empty($data['arealist_img'])) {
          $arealist_img = "<div class=\"arealist__item arealist__img\"><img src=\"{$data['arealist_img']}\"></div>";
        } else {
          $arealist_img = "";
        }
        $arealist_link = "<span class=\"arealist__item arealist__link\"><a href=\"forumdisplay.php?fid={$data['fid']}\" class=\"bl-btn\">{$data['name']}</a></span>";

        if (!empty($data['arealist_dscr'])) {
          $arealist_dscr = "<div class=\"arealist__item arealist__dscr\">" . $arealist_link . $parser->parse_message($data['arealist_dscr'], $options) . "</div>";
        } else {
          $arealist_dscr = "<div class=\"arealist__item arealist__dscr\">{$arealist_link}</div>";
        }


        eval("\$arealist_bit .= \"" . $templates->get("arealist_bit") . "\";");
        $category = "";
      }
      eval("\$arealist_view_bit .= \"" . $templates->get("arealist_catbit") . "\";");
    }
    if ($mybb->user['uid'] != 0 && $mybb->settings['arealiste_userwant']) {
      //TODO: SETTINGS und SELECT BAUEN
      $get_selects = $db->simple_select("DISTINCT(arealist_cat)", "forums");
      $area_select = "";
      while ($data = $db->fetch_array($get_selects)) {
        $area_select .= "<option value=\"" . $data['arealist_cat'] . "\">" . $data['arealist_cat'] . "</option>";
      }
      eval("\$arealist_addarea .= \"" . $templates->get("arealist_addarea") . "\";");

      if (isset($mybb->input['add_area'])) {
        //stuff for PM
        require_once MYBB_ROOT . "inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();
        $theuser = get_user($mybb->user['uid']);
        $userlink = build_profile_link($theuser['username'], $theuser['uid']);
        $message = $userlink . " möchte gerne eine Area.<br>
        <b>Name</b>" . $mybb->get_input('area_name') . "<br>
        <b>Bild</b>" . $mybb->get_input('area_img') . "<br>
        <b>Beschreibung:</b><br>
        [code]" . $mybb->get_input('area_descr') . "[/code]<br>
        In " . $mybb->get_input('area_place') . "
        ";

        $pm = array(
          "subject" => "Areawunsch",
          "message" =>  $message,
          "fromid" =>  $uid,
          "toid" => $mybb->settings['arealiste_teamaccount']
        );

        $pmhandler->set_data($pm);
        if (!$pmhandler->validate_pm()) {
          $pm_errors = $pmhandler->get_friendly_errors();
          return $pm_errors;
        } else {
          $pminfo = $pmhandler->insert_pm();
        }
      }
    }
    eval("\$page = \"" . $templates->get("arealist_view") . "\";");
    output_page($page);
  }
}

//die Ausgabe im Forumbit
$plugins->add_hook("forumdisplay_start", "arealist_forumdisplay");
function arealist_forumdisplay()
{
  global $mybb, $db, $templates, $header, $footer, $theme, $headerinclude, $lang, $location, $arealist_forumdisplay;
  if ($mybb->settings['arealiste_forumdisplay']) {

    //parser
    require_once MYBB_ROOT . "inc/class_parser.php";
    $parser = new postParser;
    $options = array(
      "allow_html" => 1,
      "allow_mycode" => 1,
      "allow_smilies" => 0,
      "allow_imgcode" => 1,
      "filter_badwords" => 0,
      "nl2br" => 1,
      "allow_videocode" => 0,
    );

    $lang->load('arealist');

    //daten holen
    $arealist_data = $db->simple_select("forums", "fid, name, arealist_view, arealist_dscr, arealist_img, arealist_cat", "arealist_view = 1");
    //daten durchgehen
    $location = "arealist-forumdisplay";
    while ($data = $db->fetch_array($arealist_data)) {
      $fid = $mybb->get_input('fid');

      if ($data['fid'] == $fid) {
        if (!empty($data['arealist_img'])) {
          $arealist_img = "<div class=\"arealist-forumdisplay__item arealist__img\"><img src=\"{$data['arealist_img']}\"></div>";
        } else {
          $arealist_img = "";
        }
        if (!empty($data['arealist_dscr'])) {
          $arealist_dscr = "<div class=\"arealist-forumdisplay__item arealist__dscr\"><span class=\"arealist_title\">{$data['name']}</span> " . $parser->parse_message($data['arealist_dscr'], $options) . "</div>";
        } else {
          $arealist_dscr = "";
        }
        eval("\$arealist_forumdisplay .= \"" . $templates->get("arealist_bit") . "\";");
      }
    }
  }
}

// ONLINE ANZEIGE - WER IST WO
$plugins->add_hook("fetch_wol_activity_end", "arealist_online_activity");
function arealist_online_activity($user_activity)
{

  global $parameters, $user;

  $split_loc = explode(".php", $user_activity['location']);
  if ($split_loc[0] == $user['location']) {
    $filename = '';
  } else {
    $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
  }

  switch ($filename) {
    case 'misc':
      if ($parameters['action'] == "arealist_view") {
        $user_activity['activity'] = "arealist";
      }
      break;
  }
  return $user_activity;
}

$plugins->add_hook("build_friendly_wol_location_end", "arealist_online_location");
function arealist_online_location($plugin_array)
{

  global $mybb, $theme, $lang;

  if ($plugin_array['user_activity']['activity'] == "arealist") {
    $plugin_array['location_name'] = $lang->arealist_online;
  }


  return $plugin_array;
}
