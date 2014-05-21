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

class CRM_Donationreceipts_Page_Zuwendungsbescheinigung extends CRM_Core_Page {
  function run() {
    require_once 'backend.php';

    $year = CRM_Utils_Request::retrieve('year', 'Positive', $_ = null, true);
    $contact_id = CRM_Utils_Request::retrieve('cid', 'Positive', $_ = null, true);

    $from_date = "$year-01-01 00:00:00";

    if ($year == date("Y")) {
      $to_date = date("Y-m-d");
    } else {
      $to_date = "$year-12-31";
    }
    $to_date .= " 23:59:59";

    $params = array(
      "contact_id" => $contact_id,
      "from_date"  => $from_date,
      "to_date"    => $to_date
    );

    $result = generate_receipts($params);

    if (!empty($result)) {
      $this->assign("from_date", date("j.n.", strtotime($result[$contact_id]['from_date'])));
      $this->assign("to_date", date("j.n.Y",strtotime($result[$contact_id]['to_date'])));
      $this->assign("url", $result[$contact_id]['url']);
    }

    parent::run();
  }
}
