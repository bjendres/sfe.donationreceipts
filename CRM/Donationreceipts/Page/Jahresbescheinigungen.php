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
    if (!file_exists("/usr/bin/pdftk"))
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

      /*
       * Create a merged document containing all the individual receipts.
       *
       * The actual merging is done by invoking an external tool (pdftk)
       * with all the individual receipt documents as command line parameters.
       *
       * However, as most operating systems (except GNU Hurd) have a limited maximal command line length,
       * trying to merge all documents in one go would cause an overflow if we have a lot of receipts.
       * Thus, we have to merge them in smaller batches first, before combining these into the final result.
       *
       * We do this in an iterative process, with potentially multiple intermediate levels.
       * This is most probably overkill -- but better safe than sorry :-)
       */

      define('BATCH_SIZE', 1000); // With a typical command line length limit of 64 KiB, this should give us enough leeway...

      $tempDir = CRM_Utils_File::tempdir('donationreceipts-');

      $config = CRM_Core_Config::singleton();

      // In the first pass, we start with the individual receipt documents we just generated.
      $inFiles = array_map(function ($elem) { return $elem['filename']; }, $result);
      $inDir = $config->customFileUploadDir;

      for ($pass = 1, $isFinalPass = false; !$isFinalPass; ++$pass) { // Do at least one pass; and further ones as necessary.
        $batches = array_chunk($inFiles, BATCH_SIZE);
        $isFinalPass = !(count($batches) > 1); // If the current input files fit into a single batch, we will be done after this pass; otherwise, we need further passes.

        $outFiles = array();

        foreach ($batches as $batchID => $filesInBatch) {
          // If this is the final pass, the current output will be the actual result file to save; otherwise, we generate temporary files for further merging.
          $outDir = $isFinalPass ? $config->customFileUploadDir : $tempDir;
          $baseName = $isFinalPass ? CRM_Utils_File::makeFileName("Jahresbescheinigungen-$year.pdf") : "$pass-$batchID.pdf";
          $outFile = $outDir . $baseName;

          $inputs = join(" ", $filesInBatch);
          system("cd $inDir && pdftk $inputs cat output $outFile");

          $outFiles[] = $baseName;

          // If our inputs are temporary results from the previous pass, we can drop them now.
          if ($inDir == $tempDir) {
            foreach ($filesInBatch as $file) {
              unlink($tempDir . $file);
            }
          }
        }

        // Outputs from this pass are inputs for next one.
        $inFiles = $outFiles;
        $inDir = $tempDir;
      } // for ($pass)

      rmdir($tempDir);

      if (file_exists($outFile)) {
        $session = CRM_Core_Session::singleton();
        $user = $session->get('userID');

        $file_id = saveDocument($user, $baseName, "application/pdf", "Jahresbescheinigungen", date("Y-m-d h:i:s"), $from_date, $to_date, "Sammeldatei Jahresbescheinigungen $year");

        $this->assign("url", CRM_Utils_System::url("civicrm/file", "reset=1&id=$file_id&eid=$user"));
      } else {
        CRM_Core_Error::fatal("Erstellen des Sammeldokuments fehlgeschlagen");
      }
    }    /* !empty($result) */

    parent::run();
  }
}
