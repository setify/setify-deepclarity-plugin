# Deep Clarity Plugin

WordPress Plugin für Deep Clarity.

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
