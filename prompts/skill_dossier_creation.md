# Deep Clarity – Dossier Erstellung Skill

## ZWECK DIESES SKILLS

Dieses Dokument definiert **WAS** du erstellst:

- Kapitel-Struktur (11 Kapitel, 47 Content-Felder)
- Input-Daten (Strukturanalyse, DCPI, Transkript)
- Output-Format (JSON mit HTML-Content)
- Wortanzahl-Vorgaben (reduziert für mehr Prägnanz)
- Workflow und Qualitätskriterien

**Dies ist KEIN Tonalitäts-Dokument!** Für Tonalität, Formulierungen und Regeln siehe: **Dossier-Copywriting-Skill**

---

## ⚠️ KRITISCH: REIHENFOLGE EINHALTEN!

**WORKFLOW:**

```
1. COPYWRITING-SKILL lesen (Tonalität, Regeln, Formulierungen)
   ↓
2. DIESES DOKUMENT lesen (Struktur, Kapitel, JSON-Format)
   ↓
3. DOSSIER erstellen (mit voller Qualität)
```

**OHNE Copywriting-Skill:** Dossier wird abgelehnt!

---

## INPUT-DATEN

Du erhältst folgende Daten:

### 1. Client-Informationen
- `firstname`, `lastname`, `email`
- `session_date`, `session_id`
- `dossier_number`

### 2. Strukturanalyse (HAUPTQUELLE!)
- **Quelle:** `session_diagnosis`
- **Format:** Fachsprache (V-A-K, Introjekte, Nervensystem)
- **Aufgabe:** In Business-Sprache übersetzen!

### 3. DCPI-Scores
- `dcpi_deep_clarity_index` (0-100)
- `dcpi_dimension_1_score` bis `dcpi_dimension_5_score`
- `dcpi_data` (JSON mit Fragen/Antworten)

**DCPI-Dimensionen:**
1. Clarity Core – Innere Klarheit
2. Regulation & Nervous System – Stressregulation
3. Decision Architecture – Entscheidungsqualität
4. Leadership Presence – Führungsqualität
5. Performance & Risks – Leistungsfähigkeit

### 4. Session-Transkript
- **Quelle:** `session_transcript`
- **Nutzung:** FÜR 3-5 DIREKTE ZITATE!

---

## OUTPUT-FORMAT (VEREINFACHT!)

**JSON-Objekt** mit 47 Content-Feldern:

```json
{
  "CHAPTER_1_CONTENT": "<p>...</p>",
  "CHAPTER_2_1_CONTENT": "<p>...</p>",
  "CHAPTER_2_2_CONTENT": "<p>...</p>",
  ...
  "CHAPTER_11_4_CONTENT": "<p>...</p>"
}
```

**WICHTIG:**
- Genau diese Feldnamen verwenden!
- Keine zusätzlichen Meta-Felder
- Keine nested structures
- Nur HTML-Content als String

**HTML-Tags:**
- ✅ Verwende: `<p>`, `<strong>`, `<em>`, `<ul>`, `<li>`, `<blockquote>`
- ❌ KEINE: `<h1>`, `<h2>` (kommen aus Template!)

**Sprache:**
- ✅ IMMER "Sie" (nie "Du", nie "der Klient")
- ✅ Business-Sprache (keine Therapeuten-Begriffe ohne Übersetzung!)

---

## BEGRIFFE-MAPPING: FACHSPRACHE → BUSINESS-SPRACHE

**Die 20 wichtigsten Übersetzungen:**

| Fachsprache (Input) | Business-Sprache (Output) |
|---|---|
| Sympathikus-Dominanz | Dauerstress, Hochleistungsmodus |
| Parasympathische Kapazität | Erholungsfähigkeit |
| V-A-K-Kette | Verarbeitungssystem (visuell-auditiv-körperlich) |
| K-Zugang abgeschnitten | Kein Bauchgefühl verfügbar |
| K versackt | Im Gefühl gefangen, emotional überflutet |
| ID dominant (Innerer Dialog) | Gedankenkarussell, Überanalysieren |
| Introjekt | Internalisierte Erwartung, übernommene Botschaft |
| Beschädigte Kette | System unter Stress, Verarbeitung gestört |
| Ideale Kette | Regulierter Zustand, optimale Verarbeitung |
| Abwehrmechanismus | Bewältigungsstrategie, Schutzmechanismus |
| Nervensystem im Alarm | Dauerstress, chronische Anspannung |
| Bindungsmuster | Wie Sie Sicherheit herstellen |
| Aversion | Vermeidung, Schutzreaktion |
| Appetenz | Annäherung, Motivation |
| Implizites Gedächtnis | Unbewusstes Körpergedächtnis |
| Somatische Marker | Körpersignale, Bauchgefühl |
| Strukturarbeit | Arbeit an innerer Organisation |
| Polyvagaltheorie | Nervensystem-Regulation (Stephen Porges) |
| Freeze-Response | Erstarrung, Handlungsunfähigkeit |
| Fight-or-Flight | Kampf- oder Fluchtmodus |

**VOLLSTÄNDIGE Tabelle:** Siehe Copywriting-Skill!

**NIEMALS VERWENDEN:**
- ❌ Energie, Blockaden, Chakren, Schwingung
- ❌ Patient, Diagnose (medizinisch), Störung, Behandlung
- ❌ "Der Klient" (verwende "Sie")

---

## ZITATE AUS DEM TRANSKRIPT

**Setze 3-5 direkte Zitate ein:**

### WO:
- Kapitel 2.2 (Anlass für Deep Clarity)
- Kapitel 3 (Kernherausforderungen)
- Kapitel 4.3 / 5.2 (Introjekte – innerer Dialog)

### WIE:
- **Länge:** Max. 1-2 Sätze
- **Markierung:** `<em>"Zitat..."</em>` oder `<blockquote>Zitat</blockquote>`
- **Einleitung:** "Sie sagten:", "Sie beschrieben es so:"

### BEISPIELE:

✅ **Gut:**
```html
<p>Sie sagten im Gespräch: <em>"Ich weiß rational, was zu tun ist – aber ich kann es emotional nicht umsetzen."</em></p>
```

✅ **Gut:**
```html
<p>In kritischen Momenten hören Sie innerlich: <em>"Habe ich alles richtig gemacht? Was werden andere denken?"</em></p>
```

❌ **Zu lang:**
> "Ich sitze abends um 23 Uhr noch im Büro und denke über die Entscheidung nach, die ich morgens getroffen habe, und frage mich, ob ich nicht doch hätte anders entscheiden sollen..."

---

## KAPITEL-STRUKTUR & WORTANZAHL

**WICHTIG:** Alle Wortanzahlen wurden um ca. 10-15% reduziert für mehr Prägnanz!

---

### KAPITEL 1: EXECUTIVE SUMMARY

**CHAPTER_1_CONTENT (1.300-1.750 Wörter)**

**Struktur:**

1. **Einleitung (175 W)**
   - Direkter Einstieg: Position, Verantwortung, Team
   - Äußere Realität vs. innere Wahrheit

2. **Die drei zentralen Muster (800 W, je ~265 W)**
   - **Muster 1:** Wie zeigt es sich? Ursprung? Kosten?
   - **Muster 2:** ...
   - **Muster 3:** ...

3. **DCPI-Index Interpretation (175 W)**
   - Gesamtscore: [X]/100
   - Einordnung:
     - 0-30: Kritischer Zustand
     - 31-50: Kompensation kostet enorme Kraft
     - 51-70: Funktional mit hoher Reibung
     - 71-85: Stabil mit Optimierungspotenzial
     - 86-100: Souverän

4. **Transformationspotenzial (175 W)**
   - Was wird möglich?
   - Zeitrahmen: 6-12 Monate

**Business-Fokus:**
- Quantifiziere Kosten (€, Zeit, Mitarbeiter)
- Benenne konkrete Auswirkungen
- Zeige ROI-Potenzial

---

### KAPITEL 2: SITUATIONSBESCHREIBUNG

**CHAPTER_2_1_CONTENT (525-700 W) – Aktueller Kontext:**

- Berufliche Position, Team-Größe, Verantwortung
- Privatleben (Partner, Kinder) – wenn relevant
- Äußerer Erfolg vs. inneres Erleben
- Tagesablauf-Beispiel

**CHAPTER_2_2_CONTENT (440-610 W) – Anlass für Deep Clarity:**

- Was bringt Sie hierher?
- **ZITAT EINSETZEN:** Original-Worte aus Transkript
- Bisherige Lösungsversuche
- Warum greifen sie nicht?

---

### KAPITEL 3: KERNHERAUSFORDERUNGEN

**CHAPTER_3_1, 3_2, 3_3 (je 525-700 W) – Herausforderung 1, 2, 3:**

**Struktur pro Herausforderung:**

1. **Wie zeigt sie sich?** (150 W)
   - Konkrete Situationen
   - Verhalten/Reaktion

2. **ZITAT EINSETZEN:** Innerer Dialog (100 W)
   - Aus Transkript
   - Was hören Sie innerlich?

3. **Ursprung** (150 W)
   - Woher kommt das Muster?
   - Wann begann es?

4. **Kosten** (150 W)
   - Zeit: X Stunden/Woche
   - Geld: Kündigungen, Verzögerungen
   - Energie: Erschöpfung

5. **Potenzial bei Lösung** (100 W)
   - Was wird möglich?
   - Konkrete Verbesserungen

---

### KAPITEL 4: STRUKTURANALYSE

**CHAPTER_4_1 (440-610 W) – Verarbeitungskanäle (V-A-K):**

- Was sind V, A, K? (Kurze Erklärung!)
- Welche nutzen Sie?
- Welche fehlen oder sind eingeschränkt?
- Business-Relevanz

**CHAPTER_4_2 (525-700 W) – Informationskette:**

- Ideale Kette: V → A → K → ID
- Ihre beschädigte Kette
- Was kippt unter Stress?
- Alltags-Beispiel

**CHAPTER_4_3 (610-790 W) – Innerer Dialog & Introjekte:**

- **Was sind Introjekte?** (2-3 Sätze Erklärung!)
  > "Internalisierte Erwartungen anderer, die Sie als eigene Maßstäbe übernommen haben."
- **ZITAT EINSETZEN:** Original-Formulierung
- Welche Introjekte? Woher?
- Kosten im Alltag
- Business-Impact

**CHAPTER_4_4 (440-610 W) – Kinästhetischer Zugang:**

- Wie ist Ihr Körpergefühl?
- Bauchgefühl verfügbar?
- Business-Impact (Entscheidungen!)

**CHAPTER_4_5 (525-700 W) – Bewertungs- & Abwehrmuster:**

- Wie bewerten Sie? (Starr/flexibel, schwarz-weiß)
- Welche Abwehr nutzen Sie?
  - Rationalisierung, Verdrängung, Projektion?
- Zweck der Abwehr
- Kosten der Abwehr

**CHAPTER_4_6 (440-610 W) – Bindungsmuster:**

- Wie stellen Sie Sicherheit her?
  - Kontrolle? Distanz? Perfektion?
- Auswirkung auf Team/Partner
- Kosten im Führungskontext

**CHAPTER_4_7 (350-525 W) – Zeitmodell:**

- Sequenz-Typ (Zukunft-orientiert) oder Zustands-Typ (Gegenwart)?
- Konditionierung? (Immer schneller, immer mehr?)
- Business-Relevanz

**CHAPTER_4_8 (610-790 W) – Synthese & Kernmuster:**

- Zusammenfassung der Struktur
- Das zentrale Muster
- Business-Relevanz
- Was muss sich ändern?

---

### KAPITEL 5: URSACHENANALYSE

**CHAPTER_5_1 (525-700 W) – Frühe Prägungen:**

- Biografischer Kontext (VORSICHTIG!)
- Wann begann die "Rolle"?
- Welche Botschaften wurden vermittelt?
- Wie prägt es heute?

**CHAPTER_5_2 (525-700 W) – Introjekte (Vertiefung):**

- Welche internalisierten Botschaften?
- **ZITAT EINSETZEN:** Original-Worte
- Konkrete Beispiele aus Alltag
- Kosten heute

**CHAPTER_5_3 (525-700 W) – Nervensystem-Muster:**

- Wie reagiert Ihr Nervensystem?
- Chronischer Alarm?
- Freeze/Fight/Flight?
- Erholungsfähigkeit?

**CHAPTER_5_4 (525-700 W) – Bindungs- & Beziehungsmuster:**

- Wie wurden Beziehungen früh erlebt?
- Welche Strategie entwickelt?
- Auswirkung heute
- Team-Dynamik

**CHAPTER_5_5 (525-700 W) – Kompensationsstrategien:**

- Was tun Sie, um zu kompensieren?
  - Mehr arbeiten? Mehr kontrollieren?
- Warum funktioniert es nicht langfristig?
- Kosten der Kompensation

---

### KAPITEL 6: DCPI-AUSWERTUNG

**CHAPTER_6_1 (440-610 W) – DCPI-Gesamtindex:**

- Ihr Score: [X]/100
- Einordnung (0-30, 31-50, 51-70, 71-85, 86-100)
- Was bedeutet dieser Wert?
- Vergleich zu anderen High-Performern

**CHAPTER_6_2 bis 6_6 (je 525-700 W) – Dimensionen 1-5:**

**Struktur pro Dimension:**

1. **Was misst sie?** (90 W)
   - Kurze Erklärung der Dimension

2. **Ihr Score & Interpretation** (175 W)
   - Score: [X]/100
   - Was bedeutet das?

3. **Alltags-Beispiele** (175 W)
   - Wie zeigt sich das konkret?
   - Situationen aus dem Transkript

4. **Verknüpfung zur Strukturanalyse** (130 W)
   - Wie hängt es mit Ihrer Struktur zusammen?

5. **Business-Relevanz** (130 W)
   - Was kostet es?
   - Was wird möglich?

**Die 5 Dimensionen:**

1. **Clarity Core** – Innere Klarheit über Werte, Ziele, Identität
2. **Regulation & Nervous System** – Stressregulation, Erholung
3. **Decision Architecture** – Entscheidungsqualität, Klarheit
4. **Leadership Presence** – Führungsqualität, Wirkung
5. **Performance & Risks** – Leistungsfähigkeit, Burnout-Risiko

**CHAPTER_6_7 (700-875 W) – Verknüpfung mit Strukturanalyse:**

- Wie hängen alle 5 Dimensionen zusammen?
- Wo sind die Hebelpunkte?
- Was verändert sich zuerst?
- Prognose für 6-12 Monate

---

### KAPITEL 7: DEEP CLARITY METHODE

**CHAPTER_7_1 (350-525 W) – Die 5 Phasen im Überblick:**

- INSIGHT → ORIGIN → RELEASE → ALIGNMENT → CLARITY
- Kurze Beschreibung jeder Phase (je 2-3 Sätze)
- Zeitrahmen gesamt: 6-12 Monate

**CHAPTER_7_2 bis 7_6 (je 440-610 W) – Phasen 1-5:**

**Struktur pro Phase:**

1. **Was passiert?** (150 W)
2. **Warum notwendig?** (100 W)
3. **Dauer & Intensität** (100 W)
4. **Erwartbare Herausforderungen** (100 W)

**Die 5 Phasen:**

1. **INSIGHT** – Erkennen der Muster
2. **ORIGIN** – Ursprung verstehen
3. **RELEASE** – Altes loslassen
4. **ALIGNMENT** – Neu ausrichten
5. **CLARITY** – Stabilisierung

**CHAPTER_7_7 (610-790 W) – Anwendung auf Ihren Fall:**

- Welche Phasen besonders relevant?
- Zeitlicher Ablauf (Wochen/Monate)
- Erwartungen managen
- Was zwischen Sessions tun?

---

### KAPITEL 8: TRANSFORMATIONSPFAD

**CHAPTER_8_1 bis 8_4 (je 525-700 W) – Monate 1-3, 4-6, 7-9, 10-12:**

**Struktur pro Zeitabschnitt:**

1. **Focus** (100 W)
   - Woran wird gearbeitet?

2. **Erwartbare Veränderungen** (300 W)
   - Subjektiv: Was fühlen Sie?
   - Verhalten: Was tun Sie anders?
   - Messbar: Was lässt sich quantifizieren?

3. **Herausforderungen** (150 W)
   - Widerstände
   - Rückfall-Risiken

4. **Support-Strukturen** (100 W)
   - Was hilft?
   - Sessions, Protokolle, Tools

**CHAPTER_8_5 (525-700 W) – Erwartbare Herausforderungen:**

- Widerstände gegen Veränderung
- Rückfall-Muster
- "Es wird schlimmer, bevor es besser wird"
- Wie damit umgehen?

---

### KAPITEL 9: INTERVENTIONSFELDER

**CHAPTER_9_1 bis 9_3 (je 525-700 W) – Interventionsfeld 1, 2, 3:**

**Struktur pro Feld:**

1. **Problem** (100 W)
2. **Wo setzen wir an?** (150 W)
3. **Methoden & Zugänge** (200 W)
4. **Erwartbare Ergebnisse** (150 W)
5. **Zeitrahmen** (50 W)

**Typische Interventionsfelder:**
- Nervensystem-Regulation
- Introjekt-Arbeit
- Entscheidungsarchitektur

**CHAPTER_9_4 (525-700 W) – Methodische Zugänge:**

- Nervensystemregulation (Polyvagal)
- Strukturarbeit (Introjekte, Abwehr)
- Beziehungsarbeit
- "Auf Augenhöhe, keine Unterwerfung"

---

### KAPITEL 10: ERFOLGSKRITERIEN

**CHAPTER_10_1 (440-610 W) – Subjektive Kriterien:**

- Innere Ruhe
- Gedankenkarussell stoppt
- Schlaf verbessert sich
- Emotionale Stabilität

**CHAPTER_10_2 (525-700 W) – Verhaltens-Kriterien:**

- Entscheidungen fallen leichter
- Delegieren wird möglich
- Konflikte konstruktiver
- 1:1-Gespräche auf Augenhöhe

**CHAPTER_10_3 (525-700 W) – Messbare Kriterien:**

- Mitarbeiterfluktuation sinkt
- Entscheidungszeit reduziert
- Krankheitstage sinken
- Team-Zufriedenheit steigt
- Umsatz/EBITDA (wenn relevant)

---

### KAPITEL 11: BUSINESS-RELEVANZ UND ROI

**CHAPTER_11_1 (525-700 W) – Aktuelle Kosten (IST):**

**Quantifiziere konkret:**

- **Mitarbeiterfluktuation:**
  - [X] Wechsel in [Y] Monaten
  - Recruiting: €20.000 - €50.000 pro Position
  - Onboarding: 3-6 Monate Produktivitätsverlust
  - **Gesamt: €XXX.XXX**

- **Entscheidungsverzögerungen:**
  - [X] Stunden/Woche mit Grübeln
  - Bei Stundensatz €XXX = €XXX/Woche
  - **= €XXX.XXX/Jahr**

- **Gesundheitskosten:**
  - [X] Krankheitstage/Jahr
  - Reduzierte Produktivität
  - Burnout-Risiko

**Gesamt-IST-Kosten: €XXX.XXX**

**CHAPTER_11_2 (525-700 W) – Potenzielle Einsparungen (SOLL):**

- Fluktuation halbiert: €XXX gespart
- Entscheidungszeit halbiert: X Stunden gewonnen
- Krankheitstage reduziert
- Team-Produktivität steigt

**Gesamt-Einsparungen: €XXX.XXX**

**CHAPTER_11_3 (440-610 W) – ROI-Berechnung:**

**Template:**

```
Investment: €24.900 (12-Monats-Programm)
Einsparungen (24 Monate): €XXX.XXX
ROI: XXX%
Break-even: Nach X Monaten
```

**Beispiel:**
```
Investment: €24.900
Einsparungen: €180.000 (über 24 Monate)
ROI: 722%
Break-even: Nach 4 Monaten
```

**CHAPTER_11_4 (440-610 W) – Zeitliche Perspektive:**

- **Kurzfristig (Monate 1-3):**
  - Erste Veränderungen spürbar
  - Entscheidungen fallen leichter
  
- **Mittelfristig (Monate 4-9):**
  - Verhaltensmuster ändern sich
  - Team merkt Veränderung
  
- **Langfristig (Monate 10-12+):**
  - Neue Stabilität
  - Messbare Business-Ergebnisse

---

## WORKFLOW (8 SCHRITTE)

**Befolge diese Reihenfolge:**

```
1. Copywriting-Skill lesen → ZUERST!
2. Strukturanalyse lesen (session_diagnosis)
3. DCPI-Scores extrahieren & verstehen
4. Transkript durchsuchen → 3-5 Zitate identifizieren
5. Client-Kontext verstehen (Rolle, Team, Situation)
6. Alle 47 Platzhalter befüllen (Kapitel für Kapitel)
7. JSON validieren (Syntax-Check!)
8. Output zurückgeben (NUR das JSON!)
```

---

## QUALITÄTSKRITERIEN (20-PUNKTE-CHECKLISTE)

### ✅ Tonalität (5 Punkte)

- [ ] Sie-Form durchgehend (nie "Du"!)
- [ ] Business-Sprache (keine Therapeuten-Begriffe ohne Übersetzung)
- [ ] Präzision vor Emotion
- [ ] Respektvoll, auf Augenhöhe
- [ ] Keine Esoterik-Begriffe

### ✅ Inhalt (8 Punkte)

- [ ] Alle 47 Kapitel befüllt (keine Platzhalter!)
- [ ] Konkrete Bezüge zur Strukturanalyse
- [ ] DCPI-Scores interpretiert (nicht nur genannt!)
- [ ] Business-Impact quantifiziert (€€€)
- [ ] Alltags-Beispiele konkret
- [ ] 3-5 direkte Zitate aus Transkript eingesetzt
- [ ] Handlungsorientiert
- [ ] Wortanzahl eingehalten (siehe Vorgaben)

### ✅ Formatierung (4 Punkte)

- [ ] HTML-Tags korrekt (`<p>`, `<strong>`, `<ul>`, `<li>`)
- [ ] Keine `<h1>` oder `<h2>` (kommen aus Template!)
- [ ] Keine Markdown-Syntax
- [ ] Satzlänge 10-20 Wörter, max. 25

### ✅ JSON-Syntax (3 Punkte)

- [ ] Alle Keys/Values in Anführungszeichen
- [ ] Anführungszeichen als `\"` escaped
- [ ] Keine Trailing Commas
- [ ] JSON syntaktisch valide (mit Validator prüfen!)

**Bewertung:**
- 20/20: ✅ Perfekt
- 17-19/20: ✅ Gut
- <17/20: ❌ Überarbeitung nötig

---

## BEISPIEL-OUTPUT (STRUKTUR)

```json
{
  "CHAPTER_1_CONTENT": "<p>Sie führen ein mittelständisches Unternehmen mit 45 Mitarbeitern. Nach außen: Erfolg, Wachstum, Anerkennung. Innen: Dauerstress, Selbstzweifel, Erschöpfung.</p><p>Die Strukturanalyse zeigt drei zentrale Muster, die Sie in dieser Diskrepanz gefangen halten...</p>",
  
  "CHAPTER_2_1_CONTENT": "<p>Sie sind Geschäftsführer eines mittelständischen Unternehmens in der Tech-Branche. Sie führen ein Team von 45 Mitarbeitern und verantworten ein Jahresbudget von €8 Millionen...</p>",
  
  "CHAPTER_2_2_CONTENT": "<p>Sie sagten im Gespräch: <em>\"Ich treffe Entscheidungen und zweifle danach wochenlang. Rational weiß ich, was richtig ist – aber emotional kann ich es nicht abschließen.\"</em></p><p>Bisherige Lösungsversuche – Coaching, Meditation, Sport – greifen nur oberflächlich...</p>",
  
  "CHAPTER_3_1_CONTENT": "<p><strong>Entscheidungen unter Zeitdruck fallen Ihnen extrem schwer.</strong></p><p>Bei kritischen Budget-Entscheidungen über €50.000 erleben Sie ein Gedankenkarussell, das 3-4 Tage anhält. Sie wägen ab, analysieren, zweifeln...</p>",
  
  "... (43 weitere Felder)"
}
```

---

## FINALE ERINNERUNG

**Du schreibst FÜR DEN KLIENTEN:**

- ✅ Der Klient soll sich **verstanden** fühlen
- ✅ Der Klient soll **Business-Impact** sehen
- ✅ Der Klient soll **Hoffnung** haben (ohne Übertreibung)
- ✅ Der Klient soll **Commitment** entwickeln

**Du schreibst NICHT:**
- ❌ Für andere Therapeuten (keine Fachsprache!)
- ❌ Für Laien-Publikum (kein Allgemein-Ratgeber!)
- ❌ Für akademische Zwecke (keine Theorie!)

**Qualität vor Geschwindigkeit.**

**Der Klient hat €24.900 investiert – liefere entsprechend!**

---

## SUPPORT-RESSOURCEN

**Bei Unsicherheiten:**

1. **Copywriting-Skill nochmal lesen** (Tonalität, Formulierungen)
2. **Begriffe-Mapping checken** (Fachsprache → Business)
3. **Im Zweifel:** Business-Sprache wählen, konkret statt abstrakt

**Bei technischen Fragen:**
- JSON-Validator nutzen
- HTML-Tags prüfen
- Wortanzahl zählen

---

**ENDE DES STRUKTUR-SKILLS**
