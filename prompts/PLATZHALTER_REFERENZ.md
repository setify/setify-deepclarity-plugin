# Platzhalter-Referenz für n8n

Diese Tabelle zeigt alle `XXX_VAR_XXX` Platzhalter und welche Werte aus dem Webhook-Payload eingesetzt werden müssen.

## Klient-Daten

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_CLIENT_FIRSTNAME_XXX` | `{{ $json.client.firstname }}` | Vorname des Klienten |
| `XXX_CLIENT_LASTNAME_XXX` | `{{ $json.client.lastname }}` | Nachname des Klienten |
| `XXX_CLIENT_EMAIL_XXX` | `{{ $json.client.email }}` | E-Mail des Klienten |
| `XXX_CLIENT_ID_XXX` | `{{ $json.client.client_id }}` | Klienten-ID |

## Dossier-Informationen

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_DOSSIER_NUMBER_XXX` | `{{ $json.dossier_number }}` | Nummer des Dossiers (1, 2, 3...) |
| `XXX_SESSION_DATE_XXX` | `{{ $json.current_session.session_date }}` | Datum der Session |
| `XXX_DOSSIER_FOLLOWUP_XXX` | `{{ $json.dossier_followup }}` | true/false |

## Strukturanalyse

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_STRUKTURANALYSE_CONTENT_XXX` | `{{ $json.strukturanalyse }}` | Vollständige Strukturanalyse (Markdown) aus Webhook 1 |

## Anamnese

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_ANAMNESE_ID_XXX` | `{{ $json.anamnese.anamnese_id }}` | Anamnese-ID |
| `XXX_ANAMNESE_DATA_XXX` | `{{ $json.anamnese.anamnese_data }}` | Anamnese Fragen & Antworten |

## Session-Daten

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_SESSION_ID_XXX` | `{{ $json.current_session.session_id }}` | Session-ID |
| `XXX_SESSION_TRANSCRIPT_XXX` | `{{ $json.current_session.session_transcript }}` | Transkript der Session |
| `XXX_SESSION_DIAGNOSIS_XXX` | `{{ $json.current_session.session_diagnosis }}` | Timo's Diagnose-Notizen |
| `XXX_SESSION_NOTES_XXX` | `{{ $json.current_session.session_notes }}` | Zusätzliche Notizen |

## DCPI (Deep Clarity Potential Index)

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_DCPI_ID_XXX` | `{{ $json.current_dcpi.dcpi_id }}` | DCPI-ID |
| `XXX_DCPI_DIMENSION_1_SCORE_XXX` | `{{ $json.current_dcpi.dossier_dimension_1_score }}` | Clarity Core |
| `XXX_DCPI_DIMENSION_2_SCORE_XXX` | `{{ $json.current_dcpi.dossier_dimension_2_score }}` | Regulation & Nervous System |
| `XXX_DCPI_DIMENSION_3_SCORE_XXX` | `{{ $json.current_dcpi.dossier_dimension_3_score }}` | Decision Architecture |
| `XXX_DCPI_DIMENSION_4_SCORE_XXX` | `{{ $json.current_dcpi.dossier_dimension_4_score }}` | Leadership Presence |
| `XXX_DCPI_DIMENSION_5_SCORE_XXX` | `{{ $json.current_dcpi.dossier_dimension_5_score }}` | Performance & Risks |
| `XXX_DCPI_DEEP_CLARITY_INDEX_XXX` | `{{ $json.current_dcpi.dossier_deep_clarity_index }}` | Gesamt-Index |
| `XXX_DCPI_DATA_XXX` | `{{ $json.current_dcpi.dcpi_data }}` | DCPI Fragen & Antworten |

## Vorherige Daten (Follow-up)

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_PREVIOUS_STRUKTURANALYSE_XXX` | `{{ $json.previous_session?.strukturanalyse ?? 'Keine vorherigen Daten' }}` | Vorherige Strukturanalyse |
| `XXX_PREVIOUS_DCPI_XXX` | `{{ $json.previous_dcpi?.dcpi_data ?? 'Keine vorherigen Daten' }}` | Vorheriger DCPI |

## Skills & Vorlagen

| Platzhalter | n8n Expression | Beschreibung |
|-------------|----------------|--------------|
| `XXX_SKILL_COPYWRITING_DOSSIER_XXX` | `{{ $json.skills_data.setting_skill_copywriting_dossier }}` | Copywriting Skill von ACF Options |
| `XXX_SKILL_DOSSIER_CREATION_XXX` | `{{ $json.skills_data.setting_skill_dossier_creation }}` | Dossier-Erstellung Skill |
| `XXX_TEMPLATE_DOSSIER_XXX` | `{{ $json.skills_data.settings_template_dossier }}` | Dossier-Template |

---

## n8n Code Node Beispiel

```javascript
// Ersetze alle Platzhalter im User Prompt
const userPromptTemplate = `
Erstelle ein vollständiges Deep Clarity Dossier.

**Klient:** XXX_CLIENT_FIRSTNAME_XXX XXX_CLIENT_LASTNAME_XXX
**Dossier-Nummer:** XXX_DOSSIER_NUMBER_XXX
...
`;

const replacements = {
  'XXX_CLIENT_FIRSTNAME_XXX': $json.client.firstname,
  'XXX_CLIENT_LASTNAME_XXX': $json.client.lastname,
  'XXX_CLIENT_EMAIL_XXX': $json.client.email,
  'XXX_CLIENT_ID_XXX': $json.client.client_id,
  'XXX_DOSSIER_NUMBER_XXX': $json.dossier_number,
  'XXX_SESSION_DATE_XXX': $json.current_session.session_date,
  'XXX_DOSSIER_FOLLOWUP_XXX': $json.dossier_followup,
  'XXX_STRUKTURANALYSE_CONTENT_XXX': $json.strukturanalyse || '',
  'XXX_ANAMNESE_ID_XXX': $json.anamnese.anamnese_id,
  'XXX_ANAMNESE_DATA_XXX': $json.anamnese.anamnese_data,
  'XXX_SESSION_ID_XXX': $json.current_session.session_id,
  'XXX_SESSION_TRANSCRIPT_XXX': $json.current_session.session_transcript,
  'XXX_SESSION_DIAGNOSIS_XXX': $json.current_session.session_diagnosis,
  'XXX_SESSION_NOTES_XXX': $json.current_session.session_notes || '',
  'XXX_DCPI_ID_XXX': $json.current_dcpi.dcpi_id,
  'XXX_DCPI_DIMENSION_1_SCORE_XXX': $json.current_dcpi.dossier_dimension_1_score,
  'XXX_DCPI_DIMENSION_2_SCORE_XXX': $json.current_dcpi.dossier_dimension_2_score,
  'XXX_DCPI_DIMENSION_3_SCORE_XXX': $json.current_dcpi.dossier_dimension_3_score,
  'XXX_DCPI_DIMENSION_4_SCORE_XXX': $json.current_dcpi.dossier_dimension_4_score,
  'XXX_DCPI_DIMENSION_5_SCORE_XXX': $json.current_dcpi.dossier_dimension_5_score,
  'XXX_DCPI_DEEP_CLARITY_INDEX_XXX': $json.current_dcpi.dossier_deep_clarity_index,
  'XXX_DCPI_DATA_XXX': $json.current_dcpi.dcpi_data,
  'XXX_PREVIOUS_STRUKTURANALYSE_XXX': $json.previous_session?.strukturanalyse || 'Keine vorherigen Daten',
  'XXX_PREVIOUS_DCPI_XXX': $json.previous_dcpi?.dcpi_data || 'Keine vorherigen Daten',
  'XXX_SKILL_COPYWRITING_DOSSIER_XXX': $json.skills_data?.setting_skill_copywriting_dossier || '',
  'XXX_SKILL_DOSSIER_CREATION_XXX': $json.skills_data?.setting_skill_dossier_creation || '',
  'XXX_TEMPLATE_DOSSIER_XXX': $json.skills_data?.settings_template_dossier || ''
};

let userPrompt = userPromptTemplate;
for (const [placeholder, value] of Object.entries(replacements)) {
  userPrompt = userPrompt.replace(new RegExp(placeholder, 'g'), value);
}

return {
  json: {
    userPrompt: userPrompt
  }
};
```

---

## Hinweise

1. **Optionale Felder:** Einige Felder können leer sein (z.B. `session_notes`, `previous_*`). Verwende Fallback-Werte.
2. **Skills:** Die Skills werden über ACF Options im WordPress Backend gepflegt und via Webhook übergeben.
3. **Strukturanalyse:** Diese kommt von Webhook 1 und muss im WordPress Backend gespeichert und bei Webhook 2 mitgesendet werden.
