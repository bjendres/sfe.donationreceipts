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

<style>
{literal}
p {
  margin: 1em;
}
{/literal}
</style>

{if !$have_result}
<p><strong>Keine unbescheinigten Zuwendungen f&uuml;r diesen Zeitraum.</strong></p>
<p>Jahresbescheinigungen vielleicht bereits erstellt?</p>
{else}
<p>Alle Jahresbescheinigungen erfolgreich erstellt.</p>
<p><a href='{$url}'>Sammeldatei herunterladen</a></p>
{/if}
