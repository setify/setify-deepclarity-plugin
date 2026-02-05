# Deep Clarity – Dossier Erstellung Skill

## ZWECK DIESES SKILLS

Dieses Dokument definiert **WAS** du erstellst:
- Kapitel-Struktur (11 Kapitel, 47 Content-Platzhalter)
- Input-Daten (Strukturanalyse, DCPI, Transkript)
- Output-Format (JSON mit HTML-Content)
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

### 2. Strukturanalyse
- **Quelle:** `session_diagnosis`
- **Format:** Fachsprache (V-A-K-ID, Introjekte, Nervensystem)
- **Aufgabe:** In Business-Sprache übersetzen!

### 3. DCPI-Scores
- `dcpi_deep_clarity_index` (0-100)
- `dcpi_dimension_1_score` bis `dcpi_dimension_5_score`
- `dcpi_data` (JSON mit Chart-URLs)

**DCPI-Dimensionen:**
1. Clarity Core – Innere Klarheit
2. Regulation & Nervous System – Stressregulation
3. Decision Architecture – Entscheidungsqualität
4. Leadership Presence – Führungsqualität
5. Performance & Risks – Leistungsfähigkeit

### 4. Session-Transkript
- **Quelle:** `session_transcript`
- **Nutzung:** Für 3-5 direkte Zitate

---

## OUTPUT-FORMAT

**JSON-Objekt** mit 47 Content-Platzhaltern:

```json
{
  "CHAPTER_1_CONTENT": "<p>...</p>",
  "CHAPTER_2_1_CONTENT": "<p>...</p>",
  "CHAPTER_2_2_CONTENT": "<p>...</p>",
  ...
  "CHAPTER_11_4_CONTENT": "<p>...</p>"
}
```

**HTML-Tags:**
- ✅ Verwende: `<p>`, `<strong>`, `<ul>`, `<li>`
- ❌ KEINE: `<h1>`, `<h2>` (kommen aus Template!)

**Sprache:**
- ✅ IMMER "Sie" (nie "Du", nie "der Klient")
- ✅ Business-Sprache (keine Therapeuten-Sprache!)

---

## BEGRIFFE-MAPPING: FACHSPRACHE → BUSINESS-SPRACHE

| Fachsprache (Input) | Business-Sprache (Output) |
|---------------------|---------------------------|
| Introjekt | Internalisierte Erwartung, übernommene Botschaft |
| K-Zugang abgeschnitten | Körpergefühl reduziert |
| K versackt | Im Gefühl gefangen |
| ID dominant | Gedankenkarussell, Überanalysieren |
| Beschädigte Kette | System unter Stress |
| Ideale Kette | Regulierter Zustand |
| Abwehrmechanismus | Bewältigungsstrategie |
| Nervensystem im Alarm | Dauerstress, chronische Anspannung |
| Sympathische Dominanz | Aktiver Stressmodus |
| Parasympathische Kapazität | Fähigkeit zur Beruhigung |
| Aversion | Vermeidung, Schutzreaktion |
| Appetenz | Annäherung, Motivation |
| Bindungsmuster | Wie Sicherheit hergestellt wird |
| Implizites Gedächtnis | Unbewusstes Körpergedächtnis |
| Somatische Marker | Körpersignale, Bauchgefühl |
| Strukturarbeit | Arbeit an innerer Organisation |
| Polyvagaltheorie | Nervensystem-Regulation (Stephen Porges) |

**NIEMALS VERWENDEN:**
- ❌ Energie, Blockaden, Chakren, Schwingung
- ❌ Patient, Diagnose, Störung, Behandlung
- ❌ "Der Klient" (verwende "Sie")

---

## ZITATE AUS DEM TRANSKRIPT

**Setze 3-5 direkte Zitate ein:**

### WO:
- Kapitel 2.2 (Anlass)
- Kapitel 3 (Kernherausforderungen)
- Kapitel 4.3 / 5.2 (Introjekte)

### WIE:
- **Länge:** Max. 1-2 Sätze
- **Markierung:** Anführungszeichen „..." oder *kursiv*
- **Einleitung:** "Sie sagten:", "Sie beschrieben es so:"

### BEISPIELE:

✅ "Sie sagten im Gespräch: 'Ich weiß rational, was zu tun ist – aber ich kann es emotional nicht umsetzen.'"

✅ "In Meetings hören Sie innerlich: 'Habe ich das richtig entschieden?'"

❌ Zu lang (3+ Sätze), zu häufig (>5)

---

## KAPITEL-STRUKTUR

### KAPITEL 1: EXECUTIVE SUMMARY

**CHAPTER_1_CONTENT (1.500-2.000 Wörter)**

**Struktur:**
1. Einleitung (200 W)
   - Direkter Einstieg: Position, Verantwortung, Team
   - Äußere Realität vs. innere Wahrheit
   
2. Die drei zentralen Muster (900 W, je 300)
   - Muster 1: Wie zeigt es sich? Ursprung? Kosten?
   - Muster 2: ...
   - Muster 3: ...

3. DCPI-Index Interpretation (200 W)
   - Gesamtscore: [X]/100
   - Einordnung: 0-30 kritisch, 31-50 Kompensation, 51-70 funktional mit Reibung, 71-85 stabil, 86-100 souverän

4. Transformationspotenzial (200 W)
   - Was ist möglich?
   - Zeitrahmen: 6-12 Monate

---

### KAPITEL 2: SITUATIONSBESCHREIBUNG

**CHAPTER_2_1_CONTENT (600-800 W) – Aktueller Kontext:**
- Berufliche Position, Team-Größe, Verantwortung
- Privatleben (Partner, Kinder)
- Äußerer Erfolg vs. inneres Erleben

**CHAPTER_2_2_CONTENT (500-700 W) – Anlass für Deep Clarity:**
- Was bringt Sie hierher?
- **ZITAT EINSETZEN:** Original-Worte aus Transkript
- Bisherige Lösungsversuche gescheitert

---

### KAPITEL 3: KERNHERAUSFORDERUNGEN

**CHAPTER_3_1, 3_2, 3_3 (je 600-800 W) – Herausforderung 1, 2, 3:**

**Struktur pro Herausforderung:**
1. Wie zeigt sie sich?
2. **ZITAT EINSETZEN:** Innerer Dialog (aus Transkript)
3. Ursprung
4. Kosten (Zeit, Geld, Energie)
5. Potenzial bei Lösung

---

### KAPITEL 4: STRUKTURANALYSE

**CHAPTER_4_1 (500-700 W) – Verarbeitungskanäle:**
- V, A, K – welche genutzt, welche fehlen?
- Business-Übersetzung

**CHAPTER_4_2 (600-800 W) – Informationskette:**
- Ideale vs. beschädigte Kette
- Was kippt unter Stress?

**CHAPTER_4_3 (700-900 W) – Innerer Dialog & Introjekte:**
- Was sind Introjekte? (2-3 Sätze Erklärung)
- **ZITAT EINSETZEN:** Original-Formulierung
- Welche Introjekte? Woher? Kosten?

**CHAPTER_4_4 (500-700 W) – Kinästhetischer Zugang:**
- Wie ist Körpergefühl?
- Business-Impact

**CHAPTER_4_5 (600-800 W) – Bewertungs- & Abwehrmuster:**
- Wie bewerten Sie? (Starr/flexibel)
- Welche Abwehr? Zweck? Kosten?

**CHAPTER_4_6 (500-700 W) – Bindungsmuster:**
- Wie stellen Sie Sicherheit her?
- Auswirkung auf Team/Partner

**CHAPTER_4_7 (400-600 W) – Zeitmodell:**
- Sequenz-Typ (Zukunft) oder Zustands-Typ (Gegenwart)?
- Konditionierung?

**CHAPTER_4_8 (700-900 W) – Synthese & Kernmuster:**
- Zusammenfassung
- Business-Relevanz

---

### KAPITEL 5: URSACHENANALYSE

**CHAPTER_5_1 (600-800 W) – Frühe Prägungen:**
- Biografischer Kontext (vorsichtig!)
- Wann begann die "Rolle"?

**CHAPTER_5_2 (600-800 W) – Introjekte:**
- Welche internalisierten Botschaften?
- **ZITAT EINSETZEN:** Original-Worte
- Konkrete Beispiele

**CHAPTER_5_3 (600-800 W) – Nervensystem-Muster:**
- Wie reagiert Nervensystem?
- Chronischer Alarm, Freeze, Fight/Flight?

**CHAPTER_5_4 (600-800 W) – Bindungs- & Beziehungsmuster:**
- Wie wurden Beziehungen früh erlebt?
- Auswirkung heute

**CHAPTER_5_5 (600-800 W) – Kompensationsstrategien:**
- Was tun Sie, um zu kompensieren?
- Warum funktioniert es nicht langfristig?

---

### KAPITEL 6: DCPI-AUSWERTUNG

**CHAPTER_6_1 (500-700 W) – DCPI-Gesamtindex:**
- Score: [X]/100
- Einordnung + Bedeutung

**CHAPTER_6_2 bis 6_6 (je 600-800 W) – Dimensionen 1-5:**

**Struktur pro Dimension:**
1. Was misst sie? (100 W)
2. Ihr Score & Interpretation (200 W)
3. Alltags-Beispiele (200 W)
4. Verknüpfung zur Strukturanalyse (150 W)
5. Business-Relevanz (150 W)

**CHAPTER_6_7 (800-1000 W) – Verknüpfung mit Strukturanalyse:**
- Wie hängen alle 5 Dimensionen zusammen?
- Hebelpunkte?
- Prognose?

---

### KAPITEL 7: DEEP CLARITY METHODE

**CHAPTER_7_1 (400-600 W) – Die 5 Phasen im Überblick:**
- INSIGHT → ORIGIN → RELEASE → ALIGNMENT → CLARITY

**CHAPTER_7_2 bis 7_6 (je 500-700 W) – Phasen 1-5:**
1. Was passiert?
2. Warum notwendig?
3. Dauer & Intensität
4. Erwartbare Herausforderungen

**CHAPTER_7_7 (700-900 W) – Anwendung auf Ihren Fall:**
- Welche Phasen besonders relevant?
- Zeitlicher Ablauf
- Erwartungen managen

---

### KAPITEL 8: TRANSFORMATIONSPFAD

**CHAPTER_8_1 bis 8_4 (je 600-800 W) – Monate 1-3, 4-6, 7-9, 10-12:**

**Struktur pro Zeitabschnitt:**
1. Focus
2. Erwartbare Veränderungen (subjektiv, Verhalten, messbar)
3. Herausforderungen
4. Support-Strukturen

**CHAPTER_8_5 (600-800 W) – Erwartbare Herausforderungen:**
- Widerstände
- Rückfall-Risiken

---

### KAPITEL 9: INTERVENTIONSFELDER

**CHAPTER_9_1 bis 9_3 (je 600-800 W) – Interventionsfeld 1, 2, 3:**

**Struktur pro Feld:**
1. Problem
2. Wo setzen wir an?
3. Methoden & Zugänge
4. Erwartbare Ergebnisse
5. Zeitrahmen

**CHAPTER_9_4 (600-800 W) – Methodische Zugänge:**
- Nervensystemregulation
- Strukturarbeit
- Introjekt-Arbeit
- "Auf Augenhöhe, keine Unterwerfung"

---

### KAPITEL 10: ERFOLGSKRITERIEN

**CHAPTER_10_1 (500-700 W) – Subjektive Kriterien:**
- Innere Ruhe
- Gedankenkarussell stoppt

**CHAPTER_10_2 (600-800 W) – Verhaltens-Kriterien:**
- Entscheidungen fallen leichter
- Delegieren wird möglich

**CHAPTER_10_3 (600-800 W) – Messbare Kriterien:**
- Mitarbeiterfluktuation sinkt
- Entscheidungszeit reduziert
- Krankheitstage sinken

---

### KAPITEL 11: BUSINESS-RELEVANZ UND ROI

**CHAPTER_11_1 (600-800 W) – Aktuelle Kosten (IST):**
- Mitarbeiterfluktuation: [X Wechsel] = €XXX
- Entscheidungsverzögerungen: [X Stunden/Woche]
- Gesundheitskosten

**CHAPTER_11_2 (600-800 W) – Potenzielle Einsparungen (SOLL):**
- Fluktuation halbiert: €X gespart
- Entscheidungszeit halbiert: X Stunden gewonnen

**CHAPTER_11_3 (500-700 W) – ROI-Berechnung:**
```
Investment: €24.900 (12M-Programm)
Einsparungen: €XXX
ROI: XXX%
Break-even: Nach X Monaten
```

**CHAPTER_11_4 (500-700 W) – Zeitliche Perspektive:**
- Kurzfristig (Monate 1-3)
- Mittelfristig (Monate 4-9)
- Langfristig (Monate 10-12+)

---

## WORKFLOW

**8 SCHRITTE:**

1. **Copywriting-Skill lesen** → ZUERST!
2. **Strukturanalyse lesen** (`session_diagnosis`)
3. **DCPI-Scores extrahieren**
4. **Transkript durchsuchen** → 3-5 Zitate identifizieren
5. **Client-Kontext verstehen**
6. **Alle 47 Platzhalter befüllen**
7. **JSON validieren**
8. **Output zurückgeben**

---

## QUALITÄTSKRITERIEN

### Checkliste (20 Punkte)

✅ **Tonalität:**
- [ ] Sie-Form durchgehend
- [ ] Business-Sprache (keine Therapeuten-Begriffe)
- [ ] Präzision vor Emotion

✅ **Inhalt:**
- [ ] Alle 47 Kapitel befüllt (keine Platzhalter!)
- [ ] Konkrete Bezüge zur Strukturanalyse
- [ ] DCPI-Scores interpretiert (nicht nur genannt!)
- [ ] Business-Impact quantifiziert (€€€)
- [ ] Alltags-Beispiele konkret
- [ ] 3-5 direkte Zitate aus Transkript eingesetzt
- [ ] Handlungsorientiert

✅ **Länge:**
- [ ] Wortzahl eingehalten (siehe Kapitel-Vorgaben)
- [ ] Mindestens 3-5 Sätze pro Absatz
- [ ] Keine Ein-Satz-Absätze

✅ **Formatierung:**
- [ ] HTML-Tags korrekt (`<p>`, `<strong>`, `<ul>`, `<li>`)
- [ ] Keine `<h1>` oder `<h2>` (kommen aus Template!)
- [ ] Keine Markdown-Syntax

✅ **JSON-Syntax:**
- [ ] Alle Keys/Values in Anführungszeichen
- [ ] Kommas korrekt
- [ ] Keine Trailing Commas
- [ ] JSON syntaktisch valide

✅ **Copywriting-Standards:**
- [ ] 10 Goldene Regeln befolgt
- [ ] Satzlänge 10-20 Wörter
- [ ] Absätze max. 4-5 Zeilen
- [ ] Keine Esoterik-Begriffe
- [ ] Keine Motivations-Phrasen

**Bewertung:**
- 20/20: ✅ Perfekt
- 17-19/20: ✅ Gut
- <17/20: ❌ Überarbeitung nötig

---

## FINALE ERINNERUNG

**Du schreibst FÜR DEN KLIENTEN:**

- ✅ Der Klient soll sich **verstanden** fühlen
- ✅ Der Klient soll **Business-Impact** sehen
- ✅ Der Klient soll **Hoffnung** haben (ohne Übertreibung)
- ✅ Der Klient soll **Commitment** entwickeln

**Qualität vor Geschwindigkeit.**

---

**ENDE DES ERSTELLUNGS-SKILLS**
