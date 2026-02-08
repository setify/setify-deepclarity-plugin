# Deep Clarity Plugin

WordPress Plugin für Deep Clarity.

**Website:** https://deepclarity.de/

## Shortcodes

### Client

| Shortcode | Parameter | Beschreibung |
|-----------|-----------|--------------|
| `[client_fullname]` | `post_id` | Vor- und Nachname des Clients |
| `[client_firstname]` | `post_id` | Vorname des Clients |
| `[client_lastname]` | `post_id` | Nachname des Clients |
| `[client_birthday_until]` | `post_id` | Zeit bis zum nächsten Geburtstag (z.B. "6 Monate 18 Tage") |
| `[client_birthday_until_raw]` | `post_id` | Tage bis zum nächsten Geburtstag (Zahl) |

### Session

| Shortcode | Parameter | Beschreibung |
|-----------|-----------|--------------|
| `[session_client_name]` | `field_first`, `field_last`, `separator`, `fallback` | Name des verknüpften Clients |
| `[session_client_link]` | — | URL zum Client-Profil der Session |
| `[session_client_id]` | — | Post-ID des verknüpften Clients |

### Dossier

| Shortcode | Parameter | Beschreibung |
|-----------|-----------|--------------|
| `[dossier_pdf_url]` | `post_id` | URL der generierten PDF-Datei |
| `[dossier_structural_analysis]` | `post_id`, `empty_message` | Strukturanalyse als HTML (Markdown → HTML) |
| `[dossier_client_link]` | — | URL zum Client-Profil des Dossiers |

### ACF-Felder

| Shortcode | Parameter | Beschreibung |
|-----------|-----------|--------------|
| `[acf field="feldname"]` | `field` (Pflicht), `post_id` | Beliebiges ACF-Feld ausgeben |
| `[acf_field field="feldname"]` | `field` (Pflicht), `post_id`, `date_format`, `fallback` | ACF-Feld mit Datumsformatierung und Fallback |

### Formulare & Daten

| Shortcode | Parameter | Beschreibung |
|-----------|-----------|--------------|
| `[form_url page_id="123"]` | `page_id` (Pflicht), `field_first`, `field_last` | URL zur Formularseite mit Client-Daten als Query-Parameter |
| `[client_forms_list]` | `empty_message` | Liste der eingereichten Formulare eines Clients |
| `[notes_client_list]` | `empty_message` | Liste der Notizen eines Clients mit Bearbeiten/Löschen |
| `[check_url_client_id]` | — | Prüft ob `client_id` URL-Parameter gültig ist ("true" oder leer) |

### Utility

| Shortcode | Parameter | Beschreibung |
|-----------|-----------|--------------|
| `[post_id]` | — | ID des aktuellen Posts |
| `[edit_url page_id="123"]` | `page_id` (Pflicht), `param` | URL zu einer Seite mit aktueller Post-ID als Parameter |
| `[dc_signature_generator]` | — | Interaktiver E-Mail-Signatur-Generator |

## Buttons

### HTML Struktur

```html
<div class="elementor-element elementor-widget elementor-widget-button">
    <a class="elementor-button elementor-button-link elementor-size-sm" href="#">
        <span class="elementor-button-content-wrapper">
            <span class="elementor-button-icon">
                <!-- SVG Icon hier -->
            </span>
            <span class="elementor-button-text">Button Text</span>
        </span>
    </a>
</div>
```

### Größen

| Klasse | Beschreibung |
|--------|--------------|
| (keine) | Standard Größe |
| `button-small` | Kleine Größe |
| `button-tiny` | Sehr kleine Größe |

Die Größen-Klasse wird auf dem äußeren `<div>` Element hinzugefügt:

```html
<div class="elementor-element button-small elementor-widget elementor-widget-button">
```

### Farben

| Klasse | Beschreibung |
|--------|--------------|
| (keine) | Standard (Primary) |
| `elementor-button-info` | Info (Blau) |
| `elementor-button-success` | Erfolg (Grün) |
| `elementor-button-warning` | Warnung (Gelb) |
| `elementor-button-danger` | Gefahr (Rot) |

Die Farb-Klasse wird auf dem äußeren `<div>` Element hinzugefügt:

```html
<div class="elementor-element elementor-button-info elementor-widget elementor-widget-button">
```

### Button ohne Icon

Wenn der Button kein Icon haben soll, entfällt das `elementor-button-icon` Element:

```html
<div class="elementor-element elementor-widget elementor-widget-button">
    <a class="elementor-button elementor-button-link elementor-size-sm" href="#">
        <span class="elementor-button-content-wrapper">
            <span class="elementor-button-text">Button Text</span>
        </span>
    </a>
</div>
```

### Vollständiges Beispiel

Button mit Icon, kleine Größe, Info-Farbe:

```html
<div class="elementor-element button-small elementor-button-info elementor-widget elementor-widget-button">
    <a class="elementor-button elementor-button-link elementor-size-sm" href="https://example.com">
        <span class="elementor-button-content-wrapper">
            <span class="elementor-button-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <!-- SVG Pfad -->
                </svg>
            </span>
            <span class="elementor-button-text">Neue Sitzung</span>
        </span>
    </a>
</div>
```

## Mail Compose Modal

Das Plugin bietet ein modernes E-Mail Compose Modal, das über SweetAlert2 geöffnet wird.

### Verwendung

Füge die Klasse `mail-swal` zu einem beliebigen Element hinzu:

```html
<button class="mail-swal">E-Mail schreiben</button>
```

### Vorausgefüllte Werte

Über Data-Attribute können Empfänger, Betreff und Nachricht vorausgefüllt werden:

| Attribut | Beschreibung |
|----------|--------------|
| `data-mail-to` | E-Mail-Adresse des Empfängers |
| `data-mail-subject` | Betreff der E-Mail |
| `data-mail-message` | Nachricht (HTML erlaubt) |

### Beispiele

Einfacher Button:

```html
<button class="mail-swal">Kontakt aufnehmen</button>
```

Mit vorausgefülltem Empfänger:

```html
<button class="mail-swal" data-mail-to="info@example.com">
    Support kontaktieren
</button>
```

Vollständig vorausgefüllt:

```html
<button class="mail-swal"
    data-mail-to="kunde@example.com"
    data-mail-subject="Ihre Anfrage"
    data-mail-message="<p>Sehr geehrte Damen und Herren,</p>">
    E-Mail senden
</button>
```

Als Link:

```html
<a href="#" class="mail-swal" data-mail-to="sales@example.com">
    Angebot anfordern
</a>
```

Als Container mit Link (z.B. für Elementor Buttons):

```html
<div class="mail-swal" data-mail-to="kunde@example.com" data-mail-subject="Anfrage">
    <a href="#" class="button">Mail Button</a>
</div>
```

### Features

- Modernes, schlichtes Design (wie eine E-Mail App)
- WYSIWYG Editor mit H1, H2, H3, Fett, Kursiv, Listen
- Drag & Drop Dateianhänge
- Validierung aller Felder
- Erfolgs-/Fehlermeldungen
- Responsive Design

### PHP API

E-Mails können auch programmatisch versendet werden:

```php
// Einfache E-Mail
deep_clarity()->mail->send(
    'empfaenger@example.com',
    'Betreff',
    '<p>Nachricht als HTML</p>'
);

// Mit Anhängen
deep_clarity()->mail->send(
    'empfaenger@example.com',
    'Betreff',
    '<p>Nachricht</p>',
    array('/pfad/zur/datei.pdf')
);
```
