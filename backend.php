<?php
/*
    sfe.donationreceipts extension for CiviCRM
    Copyright (C) 2011,2012 digitalcourage e.V.
    Copyright (C) 2012 Software fuer Engagierte e.V.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define('CUSTOM_TABLE_NAME', 'sfe_donationreceipts');

function get_custom_fields_meta()
{
  return array(
    'field_filetype' => array(
      'name' => 'sfe_donationreceipts_type',
      'label' => 'Art',
      'data_type' => 'String',
      'html_type' => 'Text',
      'weight' => '1',
      'is_active' => '1',
    ),
    'field_file' => array(
      'name' => 'sfe_donationreceipts_file',
      'label' => 'Datei',
      'data_type' => 'File',
      'html_type' => 'File',
      'weight' => '2',
      'is_active' => '1',
    ),
    'field_date' => array(
      'name' => 'sfe_donationreceipts_date_created',
      'label' => 'erstellt am',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '3',
      'is_active' => '1',
      'time_format' => '2',
    ),
    'field_from' => array(
      'name' => 'sfe_donationreceipts_timespan_from',
      'label' => 'Zeitraum von',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '4',
      'is_active' => '1',
      'time_format' => '0',    /* Work around API bug: "time_format" doesn't return to default automatically) */
    ),
    'field_to' => array(
      'name' => 'sfe_donationreceipts_timespan_to',
      'label' => 'Zeitraum bis',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'weight' => '5',
      'is_active' => '1',
    ),
    'field_comment' => array(
      'name' => 'sfe_donationreceipts_comment',
      'label' => 'Kommentar',
      'data_type' => 'Memo',
      'html_type' => 'TextArea',
      'weight' => '6',
      'is_active' => '1',
    ),
  );
}

/* Create custom data group and field for the receipts, unless the group already exists. */
function setup_custom_data()
{
  $existing = civicrm_api("CustomGroup", "get", array('version' => '3', 'name' => CUSTOM_TABLE_NAME));
  if (!$existing['count']) {
    $fields = array_values(get_custom_fields_meta());    /* Strip symbolic keys, as these confuse the API. */
    $new = civicrm_api(
      "CustomGroup",
      "create",
      array(
        'version' => '3',
        'name' => CUSTOM_TABLE_NAME,
        'title' => 'Bescheinigungen',
        'extends' => 'Contact',
        'style' => 'Tab',
        'collapse_display' => '0',
        'is_active' => '1',
        'is_multiple' => '1',
        'created_date' => date('Y-m-d h:i:s'),
        'api.CustomField.create' => $fields    /* Chained API call, using custom_group_id created by outer call. */
      )
    );
    if (civicrm_error($new))
      CRM_Core_Error::fatal("Benutzerdefinierte Gruppe und Felder konnten nicht angelegt werden: {$new['error_message']}");

    /* Ugly ugly workaround for broken API always setting 'name' same as 'label'... */
    foreach ($new['values'][$new['id']]['api.CustomField.create'] as $result) {
      $result_field = $result['values'][0];
      foreach ($fields as $field) {
        if ($result_field['label'] == $field['label'] && $result_field['name'] != $field['name']) {
          CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET name='{$field['name']}' WHERE id='{$result_field['id']}'");
        }
      }
    }

  }    /* if !$existing */
}    /* setup_custom_data() */

/**
 * Get custom table and field DB names for custom group "Bescheinigungen"
 */
function get_docs_table()
{
  /* Custom field mapping: symbolic keys we use locally to refer to fields => names by which the fields are known to CiviCRM. */
  $field_mappings = array_map(function ($field) { return $field['name']; }, get_custom_fields_meta());

  $result = civicrm_api(
    'CustomGroup',
    'get',
    array(
      'version' => '3',
      'name' => CUSTOM_TABLE_NAME,
      'api.CustomField.get' => array(    /* Chained API call, using custom_group_id retrieved by outer call. */
        'name' => array('IN' => $field_mappings)    /* Only get relevant fields, filtering by the custom field name. */
      )
    )
  );

  if (!$result['count'])
    CRM_Core_Error::fatal('Benuterdefinierte Feldgruppe "'.CUSTOM_TABLE_NAME.'" nicht gefunden.');

  $result_group = $result['values'][$result['id']];

  $docs = array();    /* Custom field mapping: local keys => DB table/field names. */
  $docs['table'] = $result_group['table_name'];

  foreach ($result_group['api.CustomField.get']['values'] AS $result_field) {
    $field_key = array_search($result_field['name'], $field_mappings);
    $docs[$field_key] = $result_field['column_name'];
  }

  $missing = array_diff_key($field_mappings, $docs);
  if ($missing)
    CRM_Core_Error::fatal('Benutzerdefiniertes Feldgruppe "'.CUSTOM_TABLE_NAME.'" unvollstaendig: "' . implode('", "', $missing) . '" nicht gefunden.');

  return $docs;
}

function getMessageTemplateBAOClass() {
  if (version_compare(CRM_Utils_System::version(), '4.4') >= 0) {
    return CRM_Core_BAO_MessageTemplate;
  } else {
    return CRM_Core_BAO_MessageTemplates;
  }
}

define('OPTION_GROUP_NAME', 'msg_tpl_workflow_donationreiceipts');

/* Create or upgrade receipt template.
 * Create the template (along with matching custom values and a custom group), if none exist yet;
 * otherwise, replace with the variant we are shipping, which might differ from any previously installed one.
 * When upgrading, if the active template has not been modified by the admin, replace it too;
 * otherwise, replace only the fallback copy used for reverting. */
function setup_template()
{
  $messageTemplateBAOClass = getMessageTemplateBAOClass();

  $new_html = file_get_contents(__DIR__ . '/templates/receipt.html');

  $existing = civicrm_api(
    'OptionGroup',
    'get',
    array(
      'version' => 3,
      'name' => OPTION_GROUP_NAME,
      'api.OptionValue.get' => array()
    )
  );
  if (!$existing['count']) {    /* Don't have any template stored yet => install it from scratch. */

    /* First, create the option group and value. */
    $new = civicrm_api(
      'OptionGroup',
      'create',
      array(
        'version' => 3,
        'name' => OPTION_GROUP_NAME,
        'title' => 'Templates fuer Zuwendungsbescheinigungen',
        'description' => 'Templates fuer Zuwendungsbescheinigungen',
        'is_reserved' => 1,
        'is_active' => 1,
        'api.OptionValue.create' => array(    /* Chained API call, using option_group_id created by outer call. */
          array(
            'name' => 'donationreceipt',
            'label' => 'Zuwendungsbescheinigung',
          ),
        )
      )
    );
    if (civicrm_error($new))
      CRM_Core_Error::fatal("Optionsgruppe und -felder fuer Bescheinigungs-Template konnten nicht angelegt werden: {$new['error_message']}");

    /* There was no API for adding message templates before CiviCRM 4.4 -- so need to do it "by hand" through BAO. */
    $new_value = $new['values'][$new['id']]['api.OptionValue.create'][0];
    /* Create an active (editable) entry, as well as a reserved fallback copy for the "Revert to Default" functionality. */
    foreach (array(false, true) as $reserved) {
      $params = array(
        'msg_title' => "Donationreceipts - {$new_value['values'][0]['label']}",
        'msg_html' => $new_html,
        'is_active' => 1,
        'workflow_id' => $new_value['id'],
        'is_default' => !$reserved,    /* The editable (non-reserved) entry is the one actually used/visible in the application. */
        'is_reserved' => $reserved,
      );
      $messageTemplateBAOClass::add($params);
    }

  } else {    /* Already have some template stored => upgrade to the version we are shipping. */

    $messageTemplates = new $messageTemplateBAOClass();
    $messageTemplates->workflow_id = $existing['values'][$existing['id']]['api.OptionValue.get']['values'][0]['id'];
    $messageTemplates->find();
    while ($messageTemplates->fetch()) {
      if ($messageTemplates->is_reserved) {    /* Fallback copy for reverting -- always upgrade this one. */
        $old_fallback_html = $messageTemplates->msg_html;
        $messageTemplates->msg_html = $new_html;
        $messageTemplates->save();
      } else {    /* Active template, possibly modified by user. */
        $old_active_html = $messageTemplates->msg_html;
        $active_id = $messageTemplates->id;
      }
    }
    if ($old_active_html == $old_fallback_html) {    /* Template has not been modified by user => also upgrade active template. */
      $messageTemplateBAOClass::revert($active_id);
    }
  }    /* have $existing */
}    /* setup_templates() */

/*
 * Retrieve the receipt template from DB.
 *
 * Returns the HTML part of the template (for PDF generation), as well as the PDF format associated with the template.
 */
function get_template()
{
  $messageTemplateBAOClass = getMessageTemplateBAOClass();

  $result = civicrm_api(
    'OptionGroup',
    'get',
    array(
      'version' => 3,
      'name' => OPTION_GROUP_NAME,
      'api.OptionValue.get' => array(    /* Chained API call, using option_group_id retrieved by outer call. */
        'name' => 'donationreceipt',
      )
    )
  );
  if (!$result['count'])
    CRM_Core_Error::fatal("Template 'donationreceipt' nicht gefunden");

  $workflow_id = $result['values'][$result['id']]['api.OptionValue.get']['id'];

  $params = array('workflow_id' => $workflow_id);
  $template = $messageTemplateBAOClass::retrieve($params, $_);

  return array($template->msg_html, $template->pdf_format_id);
}

function saveDocument($contact_id, $filename, $mimetype, $filetype, $date, $date_from, $date_to, $comment)
{
  $docs = get_docs_table();

  $file = new CRM_Core_DAO_File();
  $file->mime_type = $mimetype;
  $file->uri = $filename;
  $file->upload_date = date('Ymd');
  $file->save();

  $entityFile = new CRM_Core_DAO_EntityFile();
  $entityFile->file_id = $file->id;
  $entityFile->entity_id = $contact_id;
  $entityFile->entity_table = $docs['table'];
  $entityFile->save();

  $stmt = "INSERT INTO $docs[table]
                   SET $docs[field_filetype] = '$filetype'
                     , $docs[field_date]     = '$date'
                     , $docs[field_from]     = '$date_from'
                     , $docs[field_to]       = '$date_to'
                     , $docs[field_comment]  = '$comment'
                     , $docs[field_file]     =  {$file->id}
                     , entity_id = $contact_id
          ";
  $res = & CRM_Core_DAO::executeQuery( $stmt );

  return $file->id;
}

function generate_receipts($params)
{
  $docs = get_docs_table();

  $result = array();

  $from_date = CRM_Utils_Array::value('from_date',   $params);
  $to_date   = CRM_Utils_Array::value('to_date',     $params);
  $ids       = CRM_Utils_Array::value('contact_id',  $params);
  $comment   = CRM_Utils_Array::value('comment',     $params);

  $from_ts = strtotime($from_date);
  $to_ts   = strtotime($to_date);

  $date_range = date("d.m.Y", $from_ts)." - ".date("d.m.Y", $to_ts);

  // all contact IDs or only specified ones?
  if (!empty($ids)) {
    $and_contact_ids = "AND p.id IN (".join(",", (array)$ids).")";
  } else {
    // Only create receipts for contacts having an address.
    $and_contact_ids = "AND a.id IS NOT NULL";
  }

  /* Name of table with contribution types differs between CiviCRM versions */
  if (version_compare(CRM_Utils_System::version(), '4.3') >= 0) {
    $financial_type = 'financial_type';
  } else {
    $financial_type = 'contribution_type';
  }

  $query = " SELECT p.id
                  , p.addressee_display
                  , p.display_name
                  , p.first_name
                  , p.last_name
                  , a.street_address
                  , a.supplemental_address_1
                  , a.supplemental_address_2
                  , a.postal_code
                  , a.city
                  , a.country_id
                  , s.name as country
                  , DATE(c.receive_date) AS date
                  , c.total_amount
                  , IF(
                      {$financial_type}_id IN (
                        SELECT id FROM civicrm_{$financial_type} WHERE name LIKE '%Mitgliedsbeitrag%' OR name LIKE '%mitgliedsbeitrag%'
                      ),
                      'Mitgliedsbeitrag',
                      'Geldzuwendung'
                    ) AS art
               FROM civicrm_contact      p
               JOIN civicrm_contribution c
                 ON p.id = c.contact_id
                AND c.contribution_status_id = 1
          LEFT JOIN civicrm_address      a
                 ON p.id = a.contact_id
                AND a.is_primary = 1
          LEFT JOIN civicrm_country s
                 ON a.country_id = s.id
          LEFT JOIN $docs[table] docs
                 ON p.id = docs.entity_id
                AND c.receive_date BETWEEN docs.$docs[field_from] AND docs.$docs[field_to]
              WHERE p.is_deleted = 0
                AND docs.id IS NULL
                AND c.receive_date BETWEEN '$from_date' AND '$to_date'
                AND c.{$financial_type}_id IN (SELECT id FROM civicrm_{$financial_type} WHERE is_deductible)
               $and_contact_ids
           ORDER BY c.contact_id
                  , c.receive_date
        
                  ";
  $last_id = -1;

  $res = CRM_Core_DAO::executeQuery( $query );
  $num_rows = $res->numRows();
  $row_count = 0;
  while ($res->fetch()) {
    $row_count++;
    if ($res->id != $last_id) {
      if ($last_id >= 0) {
        // contact_id different than last one -> render receipt for now completely
        // fetched last/previous contact
	$result[$last_id] = render_beleg_pdf($last_id, $address, $total, $items, $from_date, $to_date, $comment);
      }

      // fetch new contact/address data 
      $last_id = $res->id; 
      $total   = 0.0;
      $items   = array();
	
      $street_address = ""; 
      $tail = "";
      if (!empty($res->supplemental_address_1)) {
        $street_address .= $res->supplemental_address_1."<br/>";
      } else {
        $tail .= "<br/>";
      }
      if (!empty($res->supplemental_address_2)) {
        $street_address .= $res->supplemental_address_2."<br/>";
      } else {
        if ($res->country != "Germany") {
          if (empty($tail)) $tail .= "<br/>";
          $tail .= strtoupper($res->country);
        } else {
          $tail .= "<br/>";
        }
      }
      $street_address .= $res->street_address;

      $name = trim($res->addressee_display);
      if (empty($name)) $name = trim($res->display_name);
      if (empty($name)) $name = trim($res->first_name)." ".trim($res->last_name);

      // look up translated country
      $ts_country = CRM_Core_PseudoConstant::country($res->country_id);
      if (empty($ts_country)) $ts_country = '';

      $address = array(
           "contact_id"           => $res->id,
           "street_address_plain" => $res->street_address,
           "street_address"       => $street_address,
           "city"                 => $res->city.$tail,
           "city_plain"           => $res->city,
           "country"              => $res->country,
           "country_ts"           => $ts_country,
           "postal_code"          => $res->postal_code,
           "name"                 => $name
		       );
    }

    // update total sum for this contact
    $total += $res->total_amount;

    // update item list for this contact
    $items[] = array("date"   => $res->date, 
		     "amount" => $res->total_amount,
		     "art"    => $res->art
		     );
      
  }

  // all done? finish up last found contact
  if ($last_id >= 0) {
    $result[$last_id] = render_beleg_pdf($last_id, $address, $total, $items, $from_date, $to_date, $comment);
  }

  return $result;
}

function render_beleg_pdf($contact_id, $address, $total, $items, $from_date, $to_date, $comment)
{
  global $civicrm_root;

  $docs = get_docs_table();

  $config = CRM_Core_Config::singleton(true,true );

  /* If receipts already exist for a date range overlapping the requested range, readjust the from date for the new receipt to follow the lastest date for which receipts were already generated. */
  $query = "SELECT GREATEST(MAX(DATE_ADD($docs[field_to], INTERVAL 1 DAY)), '$from_date' ) AS from_date
              FROM $docs[table]
             WHERE entity_id = $contact_id
               AND {$docs['field_from']} < '$to_date'    -- Ignore existing receipts for a date range beginning after the end of the requested range.
           ";

  $from_ts = strtotime($from_date);
  $to_ts   = strtotime($to_date);

  $res = CRM_Core_DAO::executeQuery( $query );
  $res->fetch();
  if ($res->from_date) {
    $from_date = $res->from_date;
  }

  $from_ts = strtotime($from_date);
  $to_ts   = strtotime($to_date);

  $template = CRM_Core_Smarty::singleton();

  list($html, $page_format) = get_template();

  // select and set up template type
  if (count($items) > 1) {
    // more than one payment -> "Sammelbescheinigung" with itemized list
    $item_table = array();
    foreach ($items as $item) {
      $item_table[] = array(
        'date' => date("j.n.Y", strtotime($item["date"])),
        'art' => $item["art"],
        'amount' => number_format($item["amount"],2,',','.'),
        'amounttext' => num_to_text($item["amount"]),
      );
    }
    $template->assign("items", $item_table);
  } else {
    // one payment only -> "Einzelbescheinigung"
    $template->assign("items", null);    /* When generating multiple receipts in a batch (Jahresbescheinigungen), the smarty object is reused between the individual receipts (singleton) -- so need to reset this explicitly! */
    $template->assign("date", date("d.m.Y",strtotime($items[0]["date"])));
  }

  // fill further template fields
  if (date("m-d",$from_ts) == "01-01" && date("m-d",$to_ts) == "12-31") {
    $daterange = date("Y",$from_ts);
  } else {
    $daterange = date("j.n.",$from_ts) . " bis " . date("j.n.Y",$to_ts);
  }
  $template->assign("daterange", $daterange);
  $template->assign("donor", $address);
  $template->assign("total", number_format($total,2,',','.'));
  $template->assign("totaltext", num_to_text($total));

  $template->assign("today", date("j.n.Y", time()));

  if (date("m-d",$from_ts) == "01-01" && date("m-d",$to_ts) == "12-31") {
    $rangespec = date("Y",$from_ts);
  } else {
    $rangespec = date("Y-m-d",$from_ts) . "_" . date("m-d",$to_ts);
  }

  $domain = CRM_Core_BAO_Domain::getDomain();
  $domain_tokens = array();
  foreach (array('name', 'address') as $token) {
    $domain_tokens[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain, true, true);
  }
  $domain_tokens['address'] = str_replace('> <', '>&nbsp;<', $domain_tokens['address']);    /* Hack to work around (yet another) bug in dompdf... */
  $template->assign('organisation', $domain_tokens);

  $html = $template->fetch("string:$html");

  // set up file names
  $basename = CRM_Utils_File::makeFileName("Zuwendungen_".$rangespec."_".$contact_id.".pdf");
  $outfile = $config->customFileUploadDir;
  $outfile.= "/$basename";

  // render PDF receipt
  file_put_contents($outfile, CRM_Utils_PDF_Utils::html2pdf($html, null, true, $page_format));

  $file_id = saveDocument($contact_id, $basename, "application/pdf", "Spendenbescheinigung", date("Y-m-d h:i:s"), $from_date, $to_date, $comment);

  // return summary data and CiviCRM URL to generated file
  return array("contact_id"   => $contact_id, 
	       "file_id"      => $file_id,
	       "from_date"    => $from_date, 
	       "to_date"      => $to_date, 
	       "total_amount" => $total,
	       "filename"     => "$basename",
	       "url"          => CRM_Utils_System::url("civicrm/file", "reset=1&id=$file_id&eid=$contact_id"));
}	

/**
 * single digit to text
*/
function num_to_text_digits($num)
{
  $digit_text = array("null","eins","zwei","drei","vier","fünf","sechs","sieben","acht","neun");

  $digits = array();

  $num = floor($num);

  while ($num > 0) {
    $rest = $num % 10;
    $num = floor($num / 10);

    echo "$rest, $num\n";

    $digits[] = $digit_text[$rest];
  }

  $digits = array_reverse($digits);

  $result =  "- ".join(" - ", $digits)." - ";

  return $result;
}

/**
* 0-999 to text
*/
function _num_to_text($num)
{
  $hundert = floor($num / 100);
  $zehn    = floor(($num - $hundert *100 ) / 10);
  $eins    = $num % 10;

  $digit_1 = array("","ein","zwei","drei","vier","fünf","sechs","sieben","acht","neun");
  $digit_10 = array("","zehn","zwanzig","dreißig","vierzig","fünfzig","sechzig","siebzig","achtzig","neunzig");

  $str = "";

  if ($hundert > 0) {
    $str .= $digit_1[$hundert]."hundert ";
  }

  if ($zehn == 0) {
    $str .= $digit_1[$eins];
  } else if ($zehn == 1) {
    if ($eins == 0) {
      $str .= "zehn";
    } else if ($eins == 1) {
      $str .= "elf";
    } else if ($eins == 2){
      $str .= "zwölf";
    } else {
      $str .= $digit_1[$eins]."zehn";
    }
  } else {
    if ($eins == 0) {
      $str .= $digit_10[$zehn];
    } else {
      $str .= $digit_1[$eins]."und".$digit_10[$zehn];
    }
  }

  return $str;
}

/** 
* general number to text conversion 
*/
function num_to_text($num)
{
  static $max_len = 1;

  $strs = array();

  while ($num > 0) {
    $strs[] = _num_to_text($num % 1000);
    $num = floor($num / 1000);
  }

  $str = "";

  if (isset($strs[2])) {
    $str .= $strs[2] . " millionen ";
  }
  if (isset($strs[1])) {
    $str .= $strs[1] . " tausend ";
  }
  if (isset($strs[0])) {
    $str .= $strs[0];
  }

  $result =  $str == "" ? "null" : trim($str);

  $len = strlen($result);
  if ($len > $max_len) {
    $max_len = $len;
  }

  return $result;
}

