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

require_once 'CRM/Core/Page.php';

class CRM_Donationreceipts_Page_Jahresbescheinigungen extends CRM_Core_Page {
  function run() {
    # Changed by Systopia:
    $pdftk = '/is/htdocs/wp11099116_VB0EJ63P75/bin/pdftk/pdftk_static';
    if (!file_exists($pdftk))
      CRM_Core_Error::fatal("'pdftk' nicht installiert, Erstellung des Sammel-PDF nicht moeglich");

    require_once 'backend.php';

    $year = CRM_Utils_Request::retrieve('year', 'Positive', $_ = null, true);

    CRM_Utils_System::setTitle("Jahresbescheinigungen $year");

    $from_date = "$year-01-01 00:00:00";

    if ($year == date("Y")) {
      $to_date = date("Y-m-d");
    } else {
      $to_date = "$year-12-31";
    }
    $to_date .= " 23:59:59";

    $params = array(
      "from_date"  => $from_date,
      "to_date"    => $to_date,
      "comment"    => "Jahresbescheinigung $year"
    );

    // Creating a lot of documents can take quite long...
    set_time_limit(0);

    $result = generate_receipts($params);

    if (!empty($result)) {
      $this->assign("have_result", true);

      // $result is already sorted by contact_id
      $docs = array_flip(array_map(function ($elem) { return $elem['filename']; }, $result));

      $config =& CRM_Core_Config::singleton( );

      // set up file names
      $basename = CRM_Utils_File::makeFileName("Jahresbescheinigungen-$year.pdf");
      $outfile = $config->customFileUploadDir . "$basename";

      $cmd = "cd " . $config->customFileUploadDir . "; " . $pdftk . " " . join(" ", array_keys($docs)) . " cat output $outfile";

      system($cmd);

      if (file_exists($outfile)) {
        $session = CRM_Core_Session::singleton();
        $user = $session->get('userID');

        $file_id = saveDocument($user, $basename, "application/pdf", "Jahresbescheinigungen", date("Y-m-d h:i:s"), $from_date, $to_date, "Sammeldatei Jahresbescheinigungen $year");

        $this->assign("url", CRM_Utils_System::url("civicrm/file", "reset=1&id=$file_id&eid=$user"));
      } else {
        CRM_Core_Error::fatal("Erstellen des Sammeldokuments fehlgeschlagen");
      }
    }    /* !empty($result) */

    parent::run();
  }
}
