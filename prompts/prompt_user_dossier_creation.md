# USER PROMPT: Deep Clarity Dossier-Erstellung

Erstelle ein vollständiges Deep Clarity Executive Leadership Dossier.

---

## WICHTIGE HINWEISE VOR DEM START

1. **Lies ZUERST den Copywriting-Skill** (Tonalität, Sprache, Business-Fokus)
2. **Dann lies den Struktur-Skill** (Kapitel, Inhalte, Wortanzahl)
3. **Erst danach:** Beginne mit dem Schreiben

**Ohne Copywriting-Skill → Output wird abgelehnt!**

---

## KLIENT-INFORMATIONEN

**Name:** {{ $('Webhook: Dossier Request').item.json.body.client.firstname }} {{ $('Webhook: Dossier Request').item.json.body.client.lastname }}
**E-Mail:** {{ $('Webhook: Dossier Request').item.json.body.client.email }}
**Klienten-ID:** {{ $('Webhook: Dossier Request').item.json.body.client.client_id }}

---

## DOSSIER-METADATEN

**Dossier-Nummer:** {{ $('Webhook: Dossier Request').item.json.body.dossier_number }}
**Session-Datum:** {{ $('Webhook: Dossier Request').item.json.body.current_session.session_date }}
**Follow-up:** {{ $('Webhook: Dossier Request').item.json.body.dossier_followup }}

---

## INPUT-DATEN

### 1. STRUKTURANALYSE (Hauptquelle)

Die folgende Strukturanalyse ist die Grundlage für das Dossier:

```
{{ $('Check: Validation Passed?').item.json.strukturanalyse }}
```

**Aufgabe:** Übersetze Fachsprache in Business-Sprache!

---

### 2. ANAMNESE-DATEN

**Anamnese-ID:** {{ $('Webhook: Dossier Request').item.json.body.anamnese.anamnese_id }}

```
{{ $('Webhook: Dossier Request').item.json.body.anamnese.anamnese_data }}
```

---

### 3. SESSION-DATEN

**Session-ID:** {{ $('Webhook: Dossier Request').item.json.body.current_session.session_id }}

#### Transkript (für Zitate!):

```
{{ $('Webhook: Dossier Request').item.json.body.current_session.session_transcript }}
```

**Aufgabe:** Finde 3-5 direkte Zitate für das Dossier!

#### Timo's Diagnose:

```
{{ $('Webhook: Dossier Request').item.json.body.current_session.session_diagnosis }}
```

#### Zusätzliche Notizen:

```
{{ $('Webhook: Dossier Request').item.json.body.current_session.session_notes }}
```

---

### 4. DEEP CLARITY POTENTIAL INDEX (DCPI)

**DCPI-ID:** {{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_id }}

#### Gesamtindex:
**{{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_deep_clarity_index }}/100**

#### Dimensionen:
- **Dimension 1 (Clarity Core):** {{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_dimension_1_score }}/100
- **Dimension 2 (Regulation & Nervous System):** {{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_dimension_2_score }}/100
- **Dimension 3 (Decision Architecture):** {{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_dimension_3_score }}/100
- **Dimension 4 (Leadership Presence):** {{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_dimension_4_score }}/100
- **Dimension 5 (Performance & Risks):** {{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_dimension_5_score }}/100

#### DCPI Fragen & Antworten:

```
{{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_data }}
```

---

## SKILLS & VORLAGEN

### SKILL: Copywriting (Tonalität & Sprache)

```
{{ $('Webhook: Dossier Request').item.json.body.skills.setting_skill_copywriting_dossier }}
```

**→ LIES DIES ZUERST!**

### SKILL: Dossier-Struktur (Kapitel & Inhalte)

```
{{ $('Webhook: Dossier Request').item.json.body.skills.setting_skill_dossier_creation }}
```

### VORLAGE: Dossier-Template (HTML-Struktur, optional)

```
{{ $('Webhook: Dossier Request').item.json.body.skills.settings_template_dossier }}
```

---

## DEINE AUFGABE

### Schritt-für-Schritt:

1. **Copywriting-Skill lesen** (Tonalität, verbotene Begriffe, Business-Sprache)
2. **Struktur-Skill lesen** (11 Kapitel, 47 Abschnitte, Wortanzahl)
3. **Strukturanalyse durcharbeiten** (Kernerkenntnisse extrahieren)
4. **DCPI-Scores interpretieren** (nicht nur Zahlen nennen!)
5. **Transkript nach Zitaten durchsuchen** (3-5 direkte Zitate)
6. **Alle 47 Content-Felder befüllen** (siehe Output-Format unten)
7. **Business-Impact quantifizieren** (€, Zeit, Mitarbeiter)
8. **JSON validieren** (Syntax-Check)

---

## OUTPUT-FORMAT (KRITISCH!)

Du gibst **NUR** ein JSON-Objekt zurück mit genau **47 Feldern**:

```json
{
  "CHAPTER_1_CONTENT": "<p>HTML-Inhalt für Executive Summary...</p>",
  "CHAPTER_2_1_CONTENT": "<p>HTML-Inhalt für Aktueller Kontext...</p>",
  "CHAPTER_2_2_CONTENT": "<p>HTML-Inhalt für Anlass...</p>",
  "CHAPTER_3_1_CONTENT": "<p>HTML-Inhalt für Herausforderung 1...</p>",
  "CHAPTER_3_2_CONTENT": "<p>HTML-Inhalt für Herausforderung 2...</p>",
  "CHAPTER_3_3_CONTENT": "<p>HTML-Inhalt für Herausforderung 3...</p>",
  "CHAPTER_4_1_CONTENT": "<p>Verarbeitungskanäle...</p>",
  "CHAPTER_4_2_CONTENT": "<p>Informationskette...</p>",
  "CHAPTER_4_3_CONTENT": "<p>Innerer Dialog & Introjekte...</p>",
  "CHAPTER_4_4_CONTENT": "<p>Kinästhetischer Zugang...</p>",
  "CHAPTER_4_5_CONTENT": "<p>Bewertungs- & Abwehrmuster...</p>",
  "CHAPTER_4_6_CONTENT": "<p>Bindungsmuster...</p>",
  "CHAPTER_4_7_CONTENT": "<p>Zeitmodell...</p>",
  "CHAPTER_4_8_CONTENT": "<p>Synthese & Kernmuster...</p>",
  "CHAPTER_5_1_CONTENT": "<p>Frühe Prägungen...</p>",
  "CHAPTER_5_2_CONTENT": "<p>Introjekte...</p>",
  "CHAPTER_5_3_CONTENT": "<p>Nervensystem-Muster...</p>",
  "CHAPTER_5_4_CONTENT": "<p>Bindungs- & Beziehungsmuster...</p>",
  "CHAPTER_5_5_CONTENT": "<p>Kompensationsstrategien...</p>",
  "CHAPTER_6_1_CONTENT": "<p>DCPI-Gesamtindex...</p>",
  "CHAPTER_6_2_CONTENT": "<p>Dimension 1: Clarity Core...</p>",
  "CHAPTER_6_3_CONTENT": "<p>Dimension 2: Regulation...</p>",
  "CHAPTER_6_4_CONTENT": "<p>Dimension 3: Decision Architecture...</p>",
  "CHAPTER_6_5_CONTENT": "<p>Dimension 4: Leadership Presence...</p>",
  "CHAPTER_6_6_CONTENT": "<p>Dimension 5: Performance & Risks...</p>",
  "CHAPTER_6_7_CONTENT": "<p>Verknüpfung mit Strukturanalyse...</p>",
  "CHAPTER_7_1_CONTENT": "<p>Die 5 Phasen im Überblick...</p>",
  "CHAPTER_7_2_CONTENT": "<p>Phase 1: INSIGHT...</p>",
  "CHAPTER_7_3_CONTENT": "<p>Phase 2: ORIGIN...</p>",
  "CHAPTER_7_4_CONTENT": "<p>Phase 3: RELEASE...</p>",
  "CHAPTER_7_5_CONTENT": "<p>Phase 4: ALIGNMENT...</p>",
  "CHAPTER_7_6_CONTENT": "<p>Phase 5: CLARITY...</p>",
  "CHAPTER_7_7_CONTENT": "<p>Anwendung auf Ihren Fall...</p>",
  "CHAPTER_8_1_CONTENT": "<p>Monate 1-3...</p>",
  "CHAPTER_8_2_CONTENT": "<p>Monate 4-6...</p>",
  "CHAPTER_8_3_CONTENT": "<p>Monate 7-9...</p>",
  "CHAPTER_8_4_CONTENT": "<p>Monate 10-12...</p>",
  "CHAPTER_8_5_CONTENT": "<p>Erwartbare Herausforderungen...</p>",
  "CHAPTER_9_1_CONTENT": "<p>Interventionsfeld 1...</p>",
  "CHAPTER_9_2_CONTENT": "<p>Interventionsfeld 2...</p>",
  "CHAPTER_9_3_CONTENT": "<p>Interventionsfeld 3...</p>",
  "CHAPTER_9_4_CONTENT": "<p>Methodische Zugänge...</p>",
  "CHAPTER_10_1_CONTENT": "<p>Subjektive Kriterien...</p>",
  "CHAPTER_10_2_CONTENT": "<p>Verhaltens-Kriterien...</p>",
  "CHAPTER_10_3_CONTENT": "<p>Messbare Kriterien...</p>",
  "CHAPTER_11_1_CONTENT": "<p>Aktuelle Kosten (IST)...</p>",
  "CHAPTER_11_2_CONTENT": "<p>Potenzielle Einsparungen (SOLL)...</p>",
  "CHAPTER_11_3_CONTENT": "<p>ROI-Berechnung...</p>",
  "CHAPTER_11_4_CONTENT": "<p>Zeitliche Perspektive...</p>"
}
```

---

## KRITISCHE OUTPUT-REGELN

### ✅ ERLAUBT:
- Reines JSON-Objekt
- Genau 47 Felder mit exakt diesen Namen
- HTML-Content in jedem Feld
- Anführungszeichen als `\"` escaped

### ❌ ABSOLUT VERBOTEN:
- Markdown-Codeblock (```json ... ```)
- Erklärungen vor oder nach dem JSON
- Zusätzliche Meta-Felder
- Kommentare im JSON
- Trailing commas
- Leere Felder oder Platzhalter

---

## ERINNERUNG: WICHTIGSTE REGELN

1. **"Sie" statt "Du"** (AUSNAHMSLOS!)
2. **Business-Sprache** (keine Therapeuten-Fachbegriffe!)
3. **Keine Esoterik** (Energie, Blockaden = verboten!)
4. **Quantifiziere Kosten** (€, Zeit, Mitarbeiter)
5. **3-5 Zitate aus Transkript** einbauen
6. **Nur erlaubte HTML-Tags** (`<p>`, `<strong>`, `<em>`, `<ul>`, `<li>`)

---

## QUALITÄTS-CHECKLISTE (vor Abgabe)

- [ ] Copywriting-Skill befolgt?
- [ ] Struktur-Skill befolgt?
- [ ] Alle 47 Felder befüllt?
- [ ] Durchgehend "Sie" (nie "Du")?
- [ ] 3-5 Zitate aus Transkript?
- [ ] Business-Impact quantifiziert?
- [ ] Keine Fachbegriffe ohne Übersetzung?
- [ ] Keine verbotenen Begriffe? (leiden, Störung, Energie, Blockaden)
- [ ] HTML-Tags korrekt?
- [ ] JSON syntaktisch valide?

---

## KONTEXT FÜR DIE KI

**Klient:** {{ $('Webhook: Dossier Request').item.json.body.client.firstname }} {{ $('Webhook: Dossier Request').item.json.body.client.lastname }}
**Datum:** {{ $('Webhook: Dossier Request').item.json.body.current_session.session_date }}
**Dossier:** #{{ $('Webhook: Dossier Request').item.json.body.dossier_number }}
**DCPI:** {{ $('Webhook: Dossier Request').item.json.body.current_dcpi.dcpi_deep_clarity_index }}/100

---

**LOS GEHT'S!**

Gib jetzt NUR das JSON-Objekt zurück – nichts davor, nichts danach!

---

**ENDE USER PROMPT**
