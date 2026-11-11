# Formulare mit Schrittassistent einrichten (Backend-Anleitung)

Der Schrittassistent macht aus einem Contao-Formular einen Ablauf in Schritten mit Fortschrittsanzeige,
Fehlermeldungen direkt am Feld und einer Zusammenfassung vor dem Absenden. Alles, was dabei angezeigt
wird, pflegen Sie im Backend unter **Formulargenerator**. Im Code steht nichts Formularspezifisches.

Ohne JavaScript (oder wenn das Template nicht gewählt ist) bleibt das Formular ein ganz normales Contao-Formular.

## Einmalig nach dem Deployment

Auf dem Server `contao:migrate --migrations-only` ausführen (legt sieben neue Spalten an `tl_form_field`
an). Das macht das Deployment bzw. der Admin, nicht die Redaktion.

## Grundprinzip

| Im Formular | Wird zu |
|---|---|
| Ein **Fieldset** (Feldtyp „Fieldset Start" bis „Fieldset Ende") | ein **Schritt** |
| **Bezeichnung** des Fieldsets | Überschrift des Schritts |
| Neues Feld **Einleitung des Schritts** | Satz unter der Überschrift |
| Felder **vor** dem ersten Fieldset | gehören zum ersten Schritt |
| Felder **nach** einem Fieldset (z. B. Bemerkung) | gehören zum vorigen Schritt |
| Captcha und Absenden-Knopf | landen im letzten Schritt |
| **Zahlenfelder** (Eingabeprüfung „Ziffern" / „Betrag") | bekommen automatisch −/+ Knöpfe |
| Preise in der Feldbeschriftung (`= 15,00 € pro Person`) | werden zu Summe und Gesamtpreis |
| „zzgl. 3,95€" im Optionstext | wird als Aufpreis addiert |

Hat das Formular **kein** Fieldset, gibt es nur einen Schritt (Fehlermeldungen am Feld und
Zusammenfassung funktionieren trotzdem).

## Schritt für Schritt: ein Formular umstellen

Beispiel: **Gruppenangebote** (*Formulargenerator → Gruppenangebote*, Formular-ID 32).

### 1. Assistent einschalten
1. Formular **Gruppenangebote** öffnen.
2. Ganz unten **Template-Einstellungen** aufklappen.
3. **Template** → `form_wrapper_steps` wählen. Speichern.

> Das Template wird **am Formular** gewählt, nicht am Inhaltselement im Artikel.

### 2. Schritte festlegen
Gewünscht sind drei Schritte: *Spiel & Gruppe → Ansprechpartner → Versand & Bezahlung*.

1. **Schritt 1:** Das bisher **unveröffentlichte** Fieldset „Schule/Verein" (samt zugehörigem „Fieldset Ende")
   wiederverwenden:
   - Bezeichnung → `Spiel & Gruppe`
   - Das Feld **Einleitung des Schritts** (Bereich *Schrittassistent*) → `Wähle das Spiel und sag uns, wie viele Ihr seid.`
   - **Veröffentlichen** (beim Fieldset-Start und beim zugehörigen Fieldset-Ende; das Feld
     „Schule/Klasse/Verein" dazwischen bleibt unveröffentlicht).
2. In diesen Schritt gehören auch die Mengenfelder. Im Formular per Drag & Drop **„Anzahl an Vollzahler"**
   und **„Anzahl an Kinder/Jugendliche"** aus „Tickets und Bezahlung" hinter das Feld *Wir möchten bei
   folgendem Spiel …* ziehen, **vor** das Fieldset-Ende.
3. **Schritt 2:** Fieldset „Ansprechpartner" → *Einleitung des Schritts*: `Wer ist unser Ansprechpartner für die Buchung?`
4. **Schritt 3:** Fieldset „Tickets und Bezahlung" → Bezeichnung `Versand & Bezahlung`,
   *Einleitung*: `Wie sollen die Tickets zu Euch kommen, und wie wird bezahlt?`

Die Reihenfolge der Felder im Formular ist die Reihenfolge im Assistenten.

### 3. Mengen und Mindestsumme
Regel laut Teilnahmebedingungen: **mindestens 10 Personen, davon mindestens 5 Vollzahler.**

**Anzahl an Vollzahler** (Feld `anzahl_tickets`):
- *Eingabeprüfung* bleibt „Ziffern", **Minimalwert** → `5`.
- Bereich *Schrittassistent*:
  - **Meldung: Pflichtangabe fehlt** → `Bitte gib die Anzahl der Vollzahler an (mindestens 5).`
  - **Meldung: ungültige Angabe** → `Es werden mindestens 5 Vollzahler benötigt.`
  - **Summengruppe** → `personen`, **Mindestsumme** → `10`, **Einheit** → `Personen`

**Anzahl an Kinder/Jugendliche** (Feld `anzahl_tickets_kids`):
- **Standardwert** → `0` (Bereich *Experten-Einstellungen*), damit das Feld nicht leer starten muss.
- Bereich *Schrittassistent*:
  - **Meldung: Pflichtangabe fehlt** → `Bitte gib die Anzahl der Kinder und Jugendlichen an, gern auch 0.`
  - **Summengruppe** → `personen`, **Mindestsumme** → `10`, **Einheit** → `Personen`
  - **Meldung: Mindestsumme unterschritten** →
    `Für das Gruppenangebot braucht es insgesamt mindestens {min} {einheit} – es fehlen noch {fehlt}.`

So funktioniert die Summengruppe: Alle Zahlenfelder mit **derselben Kennung** werden addiert. Die Prüfung hängt am
**letzten** Feld der Gruppe. Platzhalter in der Meldung: `{min}` Mindestsumme, `{summe}` aktuelle Summe,
`{fehlt}` Differenz, `{einheit}`. Ohne Meldungstext gilt ein Standardtext.

### 4. Meldungen für die übrigen Felder
In jedem Feld im Bereich *Schrittassistent*. Leer lassen = Standardtext.

| Feld | Meldung: Pflichtangabe fehlt | Meldung: ungültige Angabe |
|---|---|---|
| Wir möchten bei folgendem Spiel … | `Bitte wähle ein Spiel aus.` | – |
| Nachname | `Bitte gib den Nachnamen an.` | – |
| Straße und Hausnummer | `Bitte gib Straße und Hausnummer an.` | – |
| Postleitzahl | `Bitte gib die Postleitzahl an.` | `Bitte eine gültige Postleitzahl angeben (4 bis 5 Ziffern).` |
| Ort | `Bitte gib den Ort an.` | – |
| E-Mail-Adresse | `Bitte gib eine E-Mail-Adresse an, damit wir Euch antworten können.` | `Das sieht noch nicht nach einer E-Mail-Adresse aus, z. B. name@beispiel.de.` |
| Telefonnummer | – | `Bitte eine gültige Telefonnummer angeben oder das Feld leer lassen.` |
| Ticketversand | `Bitte wähle, wie die Tickets zu Euch kommen sollen.` | – |
| Bezahlung | `Bitte wähle eine Bezahlart.` | – |

### 5. Postleitzahl genauer prüfen
Bisher akzeptiert das Feld alles aus Buchstaben und Ziffern. Für 4 bis 5 Ziffern:
- *Eingabeprüfung* → **Eigener regulärer Ausdruck**
- **Regulärer Ausdruck** → `/^\d{4,5}$/`
- **Fehlermeldung** (Contao-Feld) → derselbe Text wie oben.

### 6. Anrede nicht vorbelegen
Die Anrede steht bisher stillschweigend auf „Herr". Im Feld **Anrede** unter *Optionen* eine **neue erste
Option** einfügen: Wert **leer**, Bezeichnung `Bitte wählen`.

### 7. Testen
1. Seite `/gruppenangebote` neu laden (ggf. Contao-Cache leeren).
2. „Weiter" ohne Eingaben klicken: Meldungen aus Schritt 3/4 erscheinen.
3. 6 Vollzahler und 1 Kind eingeben: Hinweis, dass noch 3 Personen fehlen.
4. **Nicht** mit gültigen Daten absenden: Das Formular verschickt eine echte E-Mail an den Empfänger.

## Neues Formular nach demselben Muster
1. Template `form_wrapper_steps` am Formular wählen.
2. Felder in **Fieldsets** gliedern (ein Fieldset = ein Schritt), Fieldsets mit Bezeichnung und Einleitung versehen.
3. Pflichtfelder wie gewohnt als *Pflichtfeld* markieren.
4. Bei Bedarf Meldungen, Mindest-/Höchstwerte, Eingabeprüfung oder eine Summengruppe pflegen.

## Was automatisch passiert (ohne Konfiguration)
- Pflichtfelder prüfen, freiwillige Felder mit „optional" kennzeichnen.
- E-Mail, Telefon, Ziffern, eigener regulärer Ausdruck und Minimal-/Maximalwerte prüfen (je nach *Eingabeprüfung*).
- Autofill-Hinweise nach Feldnamen (`vorname`, `nachname`, `strasse`, `plz`, `ort`, `email`, `telefon`);
  das Contao-Feld *Autocomplete* hat Vorrang.
- Zusammenfassung vor dem Absenden mit „Ändern"-Links; Preise und Aufpreise werden aus den Beschriftungen gelesen.
- Entwurf in der Browser-Sitzung: ein versehentliches Neuladen kostet keine Eingaben.
- Die Prüfung läuft **auch auf dem Server** (auch ohne JavaScript) mit denselben Texten.

## Was bewusst nicht im Backend steht
- Die Optik (Farben, Abstände) – sie steckt in `templates/custom/form_steps.html.twig`.
- Bedingte Felder: Contao bringt eigene „bedingte Formularfelder" mit (Option am Fieldset). Das Zusammenspiel mit
  dem Schrittassistenten ist **noch nicht getestet**. Bis dahin Formulare mit bedingten Feldern nicht umstellen.
