== Installation ==

Die Spendenbescheinigungs-Funktionalität ist als eine "CiviCRM Extension"
implementiert. Die Installation erfolgt daher über die CiviCRM-eigene
Extension-Verwaltung, unter:

   Administer->Customize Data and Screens->Manage Extensions

=== Vorbereiten der Extension-Pfade ===

Falls bisher keine anderen Extensions installiert wurden, kann es sein, dass
die Pfade auch noch nicht eingerichtet sind -- beim Aufruf der
Extension-Verwaltung erscheint dann eine entsprechende Fehlermeldung. In dem
Fall muss dann -- wie angewiesen -- zunächst der (Dateisystm-)Pfad für ein
Extension-Verzeichnis eingestellt werden, zum Beispiel
"/var/www/civicrm-extensions".

Daraufhin erscheint dann eine Warnung, dass die Resource-URL für Extensions
nicht gesetzt ist. Das spielt eigentlich keine Rolle, da für die
Spendenbescheinigungs-Extension keine Resourcen benötigt werden; aber um die
nervige Warnung wegzubekommen, muss man trotzdem die URL konfigurieren --
beispielsweise "https://<domain>/civicrm-extensions".

=== Automatisierte Installation ===

In Zukunft soll die Extension über das offizielle Extension-Directory verfügbar
sein. Wenn das passiert ist, wird sie in der Extension-Verwaltung direkt zur
Installation angeboten; der Vorgang erfolgt dann weitgehend automatisch.

=== Manuelle Installation ===

Versionen, die noch nicht im offiziellen Extension-Directory verfügbar sind,
können stattdessen manuell heruntergeladen und installiert werden. Dafür muss
das heruntergeladene Archiv entpackt, und das gesammte Verzeichnis
"sfe.donationreceipts" in das konfigurierte Extension-Verzeichnis (also
beispielsweise "/var/www/civicrm-extensions") auf dem Webserver kopiert werden.

Danach steht die Extension dann ebenfalls in der Extension-Verwaltung zur
automatisierten Installation bereit.

== Konfiguration ==

Bevor Spendenbescheinigungen erzeugt werden können, müssen noch einige Dinge
beachtet werden.

=== Custom-Felder für die Dokumentablage ===

Bei der Installation wird für Kontakte automatisch eine benutzerdefinierte
Feldgruppe "Bescheinigungen" angelegt, in der erstelle Spendenbescheinigungen
gespeichert werden.

Dies kann eventuell fehlschlagen, falls es Namenskonflikte mit bereits
vorhandenen benutzerdefinierten Feldern gibt. In dem Fall sind wahrscheinlich
manuelle Eingriffe in der Datenbank nötig.

(Aufgrund einer etwas seltsamen Handhabung von Custom-Feldern in CiviCRM reicht
es derzeit *nicht* aus, die problematischen Felder oder Feldgruppen über die
Bedienoberfläche umzubenennen! Einzig das Löschen der betroffenen Felder oder
Feldgruppen -- mitsamt aller dort gespeicherten Daten -- würde den Konflikt
beheben...)

=== Zuwendungsarten ===

Zuwendungen werden auf den Bescheinigungen -- je nach Zuwendungsart -- entweder
als "Mitgliedsbeitrag" oder als "Geldzuwendung" ausgewiesen. Die Entscheidung
erfolgt anhand einer einfachen Heuristik: Wenn in der Bezeichnung der
Zuwendungsart das Wort "Mitgliedsbeitrag" (oder "mitgliedsbeitrag") vorkommt --
auch in Kombinationen wie "Mitgliedsbeitrag ermäßigt", oder
"Fördermitgliedsbeitrag" -- wird die Zuwendung als "Mitgliedsbeitrag"
ausgewiesen; in allen anderen Fällen -- zum Beispiel bei "Spende" -- hingegen
als "Geldzuwendung".

Falls Zuwendungsarten konfiguriert sind, die mit dieser Heuristik nicht richtig
zugeordnet werden, müssen diese entsprechend umbenannt werden...

Es werden nur Zuwendungsarten berücksichtigt, bei denen "Tax-deductible"
gesetzt ist; für andere Zuwendungsarten werden grundsätzlich keine
Spendenbescheinigungen erzeugt.

Die Verwaltung von Zuwendungsarten erfolgt unter:

   Administer->CiviContribute->Contribution Types

=== Bescheinigungs-Template ===

Um Bescheinigungen erzeugen zu können, muss zunächst deren Aussehen festgelegt
werden. Das erfolgt mit Hilfe eines HTML-Templates. Dieses wird beim Erzeugen
der Spendenbescheinigungen zunächst mit der Template-Engine "Smarty"
verarbeitet, um die Werte der variablen Felder einzusetzen. Das Ergebnis wird
dann in PDF-Dateien umgewandelt.

Das Template ist im System als ein "System Workflow Message Template" abgelegt,
und ist als solches unter

   Administer->Communications->Message Templates

unter dem Reiter "System Workflow Messages" verfügbar, als "Donationreceipts -
Zuwendungsbescheinigung".

Das mit der Extension mitinstallierte Default-Template ist nach den (früher
verbindlichen) Mustern der Finanzverwaltung gestaltet. Bevor es benutzt werden
kann, muss es aber an die eigene Organisation angepasst werden. Zumindest
grundlegende Daten wie die Adresse müssen unbedingt eingetragen werden; das
Template kann aber auch völlig anders gestaltet werden. Das mitgelieferte
Default-Template sollte in jedem Fall als Orientierung verwendet werden -- die
Platzhalter für die variablen Felder dürften damit einigermaßen selbsterklärend
sein.

Neben der Anpassung des eigentlichen Inhalts, muss in der Template-Verwaltung
auch das Papierformat für die Bescheinigungen ausgewählt werden. Verfügbare
Papierformate müssen dafür zunächst unter

  Administer->Communications->Print Page (PDF) Formats

eingerichtet werden.

Zu beachten ist, dass beim Erzeugen der Bescheinigungen immer nur die
HTML-Variante des Templates verwendet wird -- die Text-Variante wird ignoriert,
und sollte daher leer gelassen werden.

=== Menü ===

Solange die Extension aktiv ist, wird im Menü "Contributions" ("Zuwendungen")
am Ende der Punkt "Jahresbecheinigungen" angefügt. Achtung: Dieser Menüpunkt
kann *nicht* mit dem Menüeditor bearbeitet werden! Er erscheint *immer* an
dieser Stelle -- und er erscheint gar nicht, falls "Contributions" nicht
vorhanden ist.

== Bedienung ==

Die bei der Installation automatisch angelegte Benutzerdefinierte Feldgruppe
"Bescheinigungen" erscheint in der Kontaktansicht als eigener Reiter. Über
diesen werden alle Spendenbescheinigungen für den jeweiligen Kontakt verwaltet.

Bereits erstellte Bescheinigungen können hier angesehen, und bei Bedarf erneut
heruntergeladen werden. Zudem können fehlerhafte Bescheinigungen gelöscht
werden.

(Technisch ist es auch möglich, vorhandene Datensätze per Hand zu editieren,
oder gar neue zu erstellen... Dafür gibt es aber kaum sinnvolle Gründe.)

Außerdem werden am Anfang der Seite zwei Links eigeblendet, mit denen jederzeit
Bescheinigungen für das jeweils laufende sowie das vorhergehende Jahr erstellt
werden können. Wenn in dem gewählten Zeitraum mehrere Zuwendungen erfolgt sind,
wird eine Sammelbescheinigung über den gesamten Zeitraum ausgestellt. Wenn nur
eine einzelne Zuwendung vorliegt, wird eine Einzelbescheinigung für diese
Zuwendung erstellt. Wenn für den Kontakt gar keine Zuwendungen in dem Zeitraum
vorliegen, wird keine Bescheinigung erstellt, und es wird eine entsprechende
Meldung angezeigt.

Falls der jeweilige Kontakt für das Jahr bereits Bescheinigungen vorliegen hat,
wird nur der Zeitraum seit der letzen erstellten Bescheinigung betrachtet. So
können unterhalb des Jahres wiederholt Bescheinigungen ausgestellt werden, ohne
dass es zu Dopplungen kommt. Für einen bereits bescheinigten Zeitraum kann nur
dann erneut eine Bescheinigung erstellt werden, wenn man die bereits
hinterlegte Bescheinigung vorher explizit löscht.

=== Gesammelte Jahresbescheinigungen ===

Des Weiteren können über den Menüpunkt "Contributions->Jahresbescheinigungen"
für das vorherige Jahr automatisch in einem Durchlauf Bescheinigungen für alle
Kontakte erstellt werden, die noch unbescheinigte Zuwendungen haben, und für
die Adressdaten hinterlegt sind. (Kontakte ohne Adressdaten werden bei diesem
Batchlauf nicht berücksichtigt; es ist aber möglich, per Hand Bescheinigungen
für einzelne Kontakte auch ohne Adresse zu erstellen.) Die jeweiligen Kontakte
werden genauso behandelt wie bei einzeln erzeugten Bescheinigungen -- es werden
also nur Zuwendungen aufgenommen, für die noch keine Bescheinigung vorliegt.

Die so erstellten Bescheinigungen werden zum einen direkt bei den jeweiligen
Kontakten hinterlegt, genauso wie bei einzeln generierten Bescheinigungen. (Ein
spezieller Kommentar vermerkt, dass diese Bescheinigungen als Batchlauf erzeugt
wurden; so dass man einfach nach diesen suchen kann.) Zusätzlich wird am Ende
des Vorgangs eine Sammeldatei erzeugt, die alle beim Batchlauf erzeugten
Jahresbescheinigungen in einem großen PDF-Dokument zusammenfasst. Diese
Sammeldatei wird dann zum Download angeboten. Außerdem wird die Sammeldatei als
spezielle Bescheinigung bei dem Nutzer hinterlegt, der die Erzeugung dieser
Jahresbescheinigungen ausgelöst hat.

Innerhalb des Sammeldokuments sind die einzelnen Bescheinigungen nach den
CiviCRM-IDs der jeweiligen Kontakte sortiert. Da diese Sortierung garantiert
zuverlässig ist, können die so gedruckten Bescheinigungen beispielsweise bei
einem Lettershop automatisch mit ebenfalls aus CiviCRM exportierten Adressdaten
zusammengeführt werden.
