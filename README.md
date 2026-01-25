# Deep Clarity Plugin

WordPress Plugin für Deep Clarity.

**Website:** https://deepclarity.de/

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
