{*
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
*}

{if !$url}
<strong>Keine unbescheinigten Zuwendungen f&uuml;r diesen Zeitraum.</strong>
{else}
<p>Zuwendungsbescheinigung f&uuml;r den Zeitraum vom {$from_date} bis {$to_date} erstellt.</p>

<p><a href='{$url}'>Bescheinigung herunterladen</a></p>

<script language='javascript'>window.opener.location.reload(true);</script>
{/if}
