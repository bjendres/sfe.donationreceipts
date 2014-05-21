<?php
/*
    sfe.donationreceipts extension for CiviCRM
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

class CRM_Donationreceipts_Page_JahresbescheinigungenConfirm extends CRM_Core_Page {
  function run() {
    $year = CRM_Utils_Request::retrieve('year', 'Positive', $_ = null, false, date("Y") - 1 /* last year */);

    CRM_Utils_System::setTitle("Jahresbescheinigungen $year");

    $this->assign('year', $year);
    $this->assign('confirm_url', CRM_Utils_System::url("civicrm/donationreceipts/jahresbescheinigungen", "year=$year"));

    parent::run();
  }
}
