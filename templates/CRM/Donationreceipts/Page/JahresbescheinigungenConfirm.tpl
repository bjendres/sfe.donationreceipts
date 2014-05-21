{*
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
*}

<style>
{literal}
p {
  margin: 1em;
}
{/literal}
</style>

<p><strong>Achtung: Es gibt derzeit keinen einfachen Weg, das Erzeugen der Jahresbescheinigungen f&uuml;r alle Kontakte r&uuml;ckg&auml;ngig zu machen!</strong></p>

<p><a href="{$confirm_url}">Jahresbescheinigungen {$year} erstellen</a></p>

<p><strong>Hinweis:</strong> Das Erzeugen aller Jahresbescheinigungen kann sehr lange dauern. Der Browser sollte w&auml;hrend dieser Zeit anzeigen, dass er auf den Server wartet. Das ist <em>kein</em> Fehler -- bitte nicht versuchen, den Link erneut anzuklicken...</p>

<p>Sollte der Browser mit einem Timeout abbrechen, wird der Vorgang auf dem Server im Hintergrund fortgesetzt. Sobald alle Bescheinigungen erstellt sind, steht das fertige Sammeldokument bei den Bescheinigungen des ausl&ouml;senden Nutzers zur Verf&uuml;gung.</p>
