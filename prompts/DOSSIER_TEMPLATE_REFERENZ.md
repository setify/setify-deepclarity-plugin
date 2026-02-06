# Dossier Template Platzhalter-Referenz

Diese Dokumentation beschreibt die Platzhalter im `DOSSIER_HTML_TEMPLATE.html` und wie sie mit den `dossier_segments` Daten befüllt werden.

## Variablen

| Platzhalter | Quelle | Beschreibung |
|-------------|--------|--------------|
| `{{CLIENT_NAME}}` | `cover.variables.CLIENT_NAME` | Kundenname |
| `{{DOSSIER_NUMBER}}` | `cover.variables.DOSSIER_NUMBER` | Dossier-Nummer |
| `{{CREATION_MONTH}}` | Dynamisch | Aktueller Monat (z.B. "Februar") |
| `{{CREATION_YEAR}}` | Dynamisch | Aktuelles Jahr (z.B. "2026") |
| `{{DCPI_INDEX}}` | `dossier_deep_clarity_index` | Deep Clarity Index Gesamtwert |
| `{{DCPI_DIM_1_SCORE}}` | `dossier_dimension_1_score` | Dimension 1: Clarity Core |
| `{{DCPI_DIM_2_SCORE}}` | `dossier_dimension_2_score` | Dimension 2: Regulation & Nervous System |
| `{{DCPI_DIM_3_SCORE}}` | `dossier_dimension_3_score` | Dimension 3: Decision Architecture |
| `{{DCPI_DIM_4_SCORE}}` | `dossier_dimension_4_score` | Dimension 4: Leadership Presence |
| `{{DCPI_DIM_5_SCORE}}` | `dossier_dimension_5_score` | Dimension 5: Performance & Risks |

---

## Kapitel-Titel Platzhalter

| Platzhalter | Quelle | Beispielwert |
|-------------|--------|--------------|
| `{{chapter_1_title}}` | `chapter_1.title` | "Executive Summary" |
| `{{chapter_2_title}}` | `chapter_2.title` | "Situationsbeschreibung" |
| `{{chapter_3_title}}` | `chapter_3.title` | "Kernherausforderungen" |
| `{{chapter_4_title}}` | `chapter_4.title` | "Strukturanalyse" |
| `{{chapter_5_title}}` | `chapter_5.title` | "Ursachenanalyse" |
| `{{chapter_6_title}}` | `chapter_6.title` | "DCPI-Auswertung" |
| `{{chapter_7_title}}` | `chapter_7.title` | "Deep Clarity Methode" |
| `{{chapter_8_title}}` | `chapter_8.title` | "Transformationspfad" |
| `{{chapter_9_title}}` | `chapter_9.title` | "Interventionsfelder" |
| `{{chapter_10_title}}` | `chapter_10.title` | "Erfolgskriterien" |
| `{{chapter_11_title}}` | `chapter_11.title` | "Business-Relevanz & ROI" |

---

## Section-Platzhalter

Jede Section hat zwei Platzhalter:
- `{{chapter_X_Y_title}}` - Der Titel der Section
- `{{chapter_X_Y}}` - Der HTML-Inhalt der Section

### Kapitel 1: Executive Summary

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_1_1_title}}` | `{{chapter_1_1}}` | `chapter_1.sections[0]` |

### Kapitel 2: Situationsbeschreibung

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_2_1_title}}` | `{{chapter_2_1}}` | `chapter_2.sections[0]` |
| `{{chapter_2_2_title}}` | `{{chapter_2_2}}` | `chapter_2.sections[1]` |

### Kapitel 3: Kernherausforderungen

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_3_1_title}}` | `{{chapter_3_1}}` | `chapter_3.sections[0]` |
| `{{chapter_3_2_title}}` | `{{chapter_3_2}}` | `chapter_3.sections[1]` |
| `{{chapter_3_3_title}}` | `{{chapter_3_3}}` | `chapter_3.sections[2]` |

### Kapitel 4: Strukturanalyse

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_4_1_title}}` | `{{chapter_4_1}}` | `chapter_4.sections[0]` |
| `{{chapter_4_2_title}}` | `{{chapter_4_2}}` | `chapter_4.sections[1]` |
| `{{chapter_4_3_title}}` | `{{chapter_4_3}}` | `chapter_4.sections[2]` |
| `{{chapter_4_4_title}}` | `{{chapter_4_4}}` | `chapter_4.sections[3]` |
| `{{chapter_4_5_title}}` | `{{chapter_4_5}}` | `chapter_4.sections[4]` |
| `{{chapter_4_6_title}}` | `{{chapter_4_6}}` | `chapter_4.sections[5]` |
| `{{chapter_4_7_title}}` | `{{chapter_4_7}}` | `chapter_4.sections[6]` |
| `{{chapter_4_8_title}}` | `{{chapter_4_8}}` | `chapter_4.sections[7]` |

### Kapitel 5: Ursachenanalyse

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_5_1_title}}` | `{{chapter_5_1}}` | `chapter_5.sections[0]` |
| `{{chapter_5_2_title}}` | `{{chapter_5_2}}` | `chapter_5.sections[1]` |
| `{{chapter_5_3_title}}` | `{{chapter_5_3}}` | `chapter_5.sections[2]` |
| `{{chapter_5_4_title}}` | `{{chapter_5_4}}` | `chapter_5.sections[3]` |
| `{{chapter_5_5_title}}` | `{{chapter_5_5}}` | `chapter_5.sections[4]` |

### Kapitel 6: DCPI-Auswertung

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_6_1_title}}` | `{{chapter_6_1}}` | `chapter_6.sections[0]` |
| `{{chapter_6_2_title}}` | `{{chapter_6_2}}` | `chapter_6.sections[1]` |
| `{{chapter_6_3_title}}` | `{{chapter_6_3}}` | `chapter_6.sections[2]` |
| `{{chapter_6_4_title}}` | `{{chapter_6_4}}` | `chapter_6.sections[3]` |
| `{{chapter_6_5_title}}` | `{{chapter_6_5}}` | `chapter_6.sections[4]` |
| `{{chapter_6_6_title}}` | `{{chapter_6_6}}` | `chapter_6.sections[5]` |
| `{{chapter_6_7_title}}` | `{{chapter_6_7}}` | `chapter_6.sections[6]` |

### Kapitel 7: Deep Clarity Methode

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_7_1_title}}` | `{{chapter_7_1}}` | `chapter_7.sections[0]` |
| `{{chapter_7_2_title}}` | `{{chapter_7_2}}` | `chapter_7.sections[1]` |
| `{{chapter_7_3_title}}` | `{{chapter_7_3}}` | `chapter_7.sections[2]` |
| `{{chapter_7_4_title}}` | `{{chapter_7_4}}` | `chapter_7.sections[3]` |
| `{{chapter_7_5_title}}` | `{{chapter_7_5}}` | `chapter_7.sections[4]` |
| `{{chapter_7_6_title}}` | `{{chapter_7_6}}` | `chapter_7.sections[5]` |
| `{{chapter_7_7_title}}` | `{{chapter_7_7}}` | `chapter_7.sections[6]` |

### Kapitel 8: Transformationspfad

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_8_1_title}}` | `{{chapter_8_1}}` | `chapter_8.sections[0]` |
| `{{chapter_8_2_title}}` | `{{chapter_8_2}}` | `chapter_8.sections[1]` |
| `{{chapter_8_3_title}}` | `{{chapter_8_3}}` | `chapter_8.sections[2]` |
| `{{chapter_8_4_title}}` | `{{chapter_8_4}}` | `chapter_8.sections[3]` |
| `{{chapter_8_5_title}}` | `{{chapter_8_5}}` | `chapter_8.sections[4]` |

### Kapitel 9: Interventionsfelder

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_9_1_title}}` | `{{chapter_9_1}}` | `chapter_9.sections[0]` |
| `{{chapter_9_2_title}}` | `{{chapter_9_2}}` | `chapter_9.sections[1]` |
| `{{chapter_9_3_title}}` | `{{chapter_9_3}}` | `chapter_9.sections[2]` |
| `{{chapter_9_4_title}}` | `{{chapter_9_4}}` | `chapter_9.sections[3]` |

### Kapitel 10: Erfolgskriterien

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_10_1_title}}` | `{{chapter_10_1}}` | `chapter_10.sections[0]` |
| `{{chapter_10_2_title}}` | `{{chapter_10_2}}` | `chapter_10.sections[1]` |
| `{{chapter_10_3_title}}` | `{{chapter_10_3}}` | `chapter_10.sections[2]` |

### Kapitel 11: Business-Relevanz & ROI

| Titel-Platzhalter | Inhalt-Platzhalter | Quelle |
|-------------------|-------------------|--------|
| `{{chapter_11_1_title}}` | `{{chapter_11_1}}` | `chapter_11.sections[0]` |
| `{{chapter_11_2_title}}` | `{{chapter_11_2}}` | `chapter_11.sections[1]` |
| `{{chapter_11_3_title}}` | `{{chapter_11_3}}` | `chapter_11.sections[2]` |
| `{{chapter_11_4_title}}` | `{{chapter_11_4}}` | `chapter_11.sections[3]` |

---

## n8n Expression für Template-Befüllung

```javascript
// Platzhalter aus dossier_segments extrahieren
const segments = $json.dossier_segments;
let placeholders = {};

// Cover-Variablen
const cover = segments.find(s => s.type === 'cover');
if (cover && cover.variables) {
  placeholders['CLIENT_NAME'] = cover.variables.CLIENT_NAME;
  placeholders['DOSSIER_NUMBER'] = cover.variables.DOSSIER_NUMBER;
}

// Datum
const now = new Date();
const months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
placeholders['CREATION_MONTH'] = months[now.getMonth()];
placeholders['CREATION_YEAR'] = now.getFullYear().toString();

// Kapitel durchgehen
segments.forEach(segment => {
  if (segment.type === 'chapter') {
    // Kapitel-Titel
    placeholders[`chapter_${segment.chapter_number}_title`] = segment.title;

    // Sections durchgehen
    if (segment.sections) {
      segment.sections.forEach((section, index) => {
        const sectionNum = index + 1;
        placeholders[`chapter_${segment.chapter_number}_${sectionNum}_title`] = section.title;
        placeholders[`chapter_${segment.chapter_number}_${sectionNum}`] = section.html;
      });
    }
  }
});

// DCPI Scores
placeholders['DCPI_INDEX'] = $json.dossier_deep_clarity_index;
placeholders['DCPI_DIM_1_SCORE'] = $json.dossier_dimension_1_score;
placeholders['DCPI_DIM_2_SCORE'] = $json.dossier_dimension_2_score;
placeholders['DCPI_DIM_3_SCORE'] = $json.dossier_dimension_3_score;
placeholders['DCPI_DIM_4_SCORE'] = $json.dossier_dimension_4_score;
placeholders['DCPI_DIM_5_SCORE'] = $json.dossier_dimension_5_score;

return placeholders;
```

---

## Struktur von dossier_segments

```json
[
  {
    "id": "cover",
    "type": "cover",
    "title": "Deckblatt",
    "html": "<div class=\"cover-page\">...</div>",
    "variables": {
      "CLIENT_NAME": "Philipp Walter",
      "DOSSIER_NUMBER": "1"
    }
  },
  {
    "id": "toc",
    "type": "table_of_contents",
    "title": "Inhaltsverzeichnis",
    "html": "<div class=\"toc\">...</div>"
  },
  {
    "id": "chapter_1",
    "type": "chapter",
    "chapter_number": 1,
    "title": "Executive Summary",
    "html": "",
    "sections": [
      {
        "id": "chapter_1_1",
        "title": "Ihre aktuelle Situation",
        "html": "<p>...</p>",
        "word_count": 180
      }
    ]
  }
  // ... weitere Kapitel
]
```
