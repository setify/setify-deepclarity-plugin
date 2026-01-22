/**
 * Deep Clarity - Frontend JavaScript
 *
 * @package DeepClarity
 */

(function ($) {
  "use strict";

  /**
   * Mail Compose Module
   */
  const MailCompose = {
    attachments: [],

    /**
     * Initialize mail compose triggers
     */
    init: function () {
      this.bindTriggers();
    },

    /**
     * Bind click events to mail-swal elements
     */
    bindTriggers: function () {
      // Direct click on .mail-swal element
      $(document).on("click", ".mail-swal", function (e) {
        // Skip if click was on a child anchor (handled separately)
        if ($(e.target).closest("a").length && !$(this).is("a")) {
          return;
        }
        e.preventDefault();
        const $el = $(this);
        MailCompose.open({
          to: $el.data("mail-to") || "",
          subject: $el.data("mail-subject") || "",
          message: $el.data("mail-message") || "",
        });
      });

      // Click on anchor inside .mail-swal container
      $(document).on("click", ".mail-swal a", function (e) {
        e.preventDefault();
        const $el = $(this).closest(".mail-swal");
        MailCompose.open({
          to: $el.data("mail-to") || "",
          subject: $el.data("mail-subject") || "",
          message: $el.data("mail-message") || "",
        });
      });
    },

    /**
     * Open mail compose modal
     */
    open: function (options) {
      const self = this;
      self.attachments = [];

      Swal.fire({
        title: null,
        html: self.getTemplate(options),
        showConfirmButton: false,
        showCancelButton: false,
        width: "640px",
        padding: 0,
        customClass: {
          popup: "dc-mail-popup",
          htmlContainer: "dc-mail-container",
        },
        didOpen: function () {
          self.initEditor();
          self.initDropzone();
          self.bindModalEvents();
        },
      });
    },

    /**
     * Get mail compose template
     */
    getTemplate: function (options) {
      return `
                <div class="dc-mail-compose">
                    <div class="dc-mail-header">
                        <span class="dc-mail-title">Neue E-Mail</span>
                        <button type="button" class="dc-mail-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
                    </div>
                    <div class="dc-mail-body">
                        <div class="dc-mail-field">
                            <label>An</label>
                            <input type="email" id="dc-mail-to" value="${this.escapeHtml(
                              options.to
                            )}" placeholder="empfaenger@example.com">
                        </div>
                        <div class="dc-mail-field">
                            <label>Betreff</label>
                            <input type="text" id="dc-mail-subject" value="${this.escapeHtml(
                              options.subject
                            )}" placeholder="Betreff eingeben...">
                        </div>
                        <div class="dc-mail-field dc-mail-field-editor">
                            <div class="dc-mail-toolbar">
                                <button type="button" data-command="formatBlock" data-value="h1" title="Überschrift 1">H1</button>
                                <button type="button" data-command="formatBlock" data-value="h2" title="Überschrift 2">H2</button>
                                <button type="button" data-command="formatBlock" data-value="h3" title="Überschrift 3">H3</button>
                                <span class="dc-mail-toolbar-divider"></span>
                                <button type="button" data-command="bold" title="Fett"><strong>B</strong></button>
                                <button type="button" data-command="italic" title="Kursiv"><em>I</em></button>
                                <span class="dc-mail-toolbar-divider"></span>
                                <button type="button" data-command="insertUnorderedList" title="Aufzählung">• Liste</button>
                                <button type="button" data-command="insertOrderedList" title="Nummerierung">1. Liste</button>
                            </div>
                            <div id="dc-mail-editor" contenteditable="true" placeholder="Ihre Nachricht...">${
                              options.message
                            }</div>
                        </div>
                        <div class="dc-mail-field">
                            <div class="dc-mail-dropzone" id="dc-mail-dropzone">
                                <div class="dc-mail-dropzone-content">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                    <span>Dateien hierher ziehen oder <strong>klicken</strong></span>
                                </div>
                                <input type="file" id="dc-mail-files" multiple style="display:none">
                            </div>
                            <div class="dc-mail-attachments" id="dc-mail-attachments"></div>
                        </div>
                    </div>
                    <div class="dc-mail-footer">
                        <button type="button" class="dc-mail-btn dc-mail-btn-cancel button-secondary">Abbrechen</button>
                        <button type="button" class="dc-mail-btn dc-mail-btn-send">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M17.33 3H6.66c-.89-.01-1.6-.01-2.17.04 -.59.04-1.1.14-1.58.38 -.76.38-1.37.99-1.75 1.74 -.24.47-.35.98-.39 1.57C.72 7.3.72 8.01.72 8.89v2.33c0 .41.33.75.75.75 .41 0 .75-.34.75-.75l0-2.49 7.46 5.68c.58.44 1.05.8 1.57.94 .46.12.95.12 1.41 0 .52-.15.99-.5 1.57-.95l7.46-5.69v6.28c0 .92-.01 1.56-.05 2.07 -.05.49-.12.78-.24 1.01 -.24.47-.63.85-1.1 1.09 -.23.11-.52.19-1.02.23 -.51.04-1.16.04-2.08.04H4.9c-.42 0-.75.33-.75.75 0 .41.33.75.75.75h12.33c.88 0 1.59 0 2.16-.05 .58-.05 1.09-.15 1.57-.39 .75-.39 1.36-1 1.74-1.75 .24-.48.34-.99.38-1.58 .04-.58.04-1.29.04-2.17V8.82c0-.64 0-1.19-.02-1.66 0-.02-.01-.04-.01-.06 -.01-.16-.02-.31-.03-.46 -.05-.59-.15-1.1-.39-1.58 -.39-.76-1-1.37-1.75-1.75 -.48-.24-.99-.35-1.58-.39 -.58-.05-1.29-.05-2.17-.05Zm4.37 3.9l-.01-.04c-.05-.5-.12-.79-.24-1.02 -.24-.48-.63-.86-1.1-1.1 -.23-.12-.52-.2-1.02-.24 -.51-.05-1.16-.05-2.08-.05H6.65c-.93 0-1.57 0-2.08.04 -.5.04-.79.11-1.02.23 -.48.23-.86.62-1.1 1.09 -.12.22-.2.51-.24 1.01l-.01.03 8.22 6.26c.74.57.95.71 1.16.76 .21.05.43.05.64 0 .2-.06.41-.2 1.16-.77l8.22-6.27Z"></path><g><path d="M5 15H1.5c-.42 0-.75-.34-.75-.75 0-.42.33-.75.75-.75H5c.41 0 .75.33.75.75 0 .41-.34.75-.75.75Z"></path><path d="M3.5 16.5c-.42 0-.75.33-.75.75 0 .41.33.75.75.75h6c.41 0 .75-.34.75-.75 0-.42-.34-.75-.75-.75h-6Z"></path></g></svg>
                            E-Mail senden
                        </button>
                    </div>
                </div>
            `;
    },

    /**
     * Initialize WYSIWYG editor
     */
    initEditor: function () {
      const $toolbar = $(".dc-mail-toolbar");

      $toolbar.on("click", "button", function (e) {
        e.preventDefault();
        const command = $(this).data("command");
        const value = $(this).data("value") || null;

        if (command === "formatBlock" && value) {
          document.execCommand(command, false, "<" + value + ">");
        } else {
          document.execCommand(command, false, value);
        }

        $("#dc-mail-editor").focus();
      });
    },

    /**
     * Initialize dropzone
     */
    initDropzone: function () {
      const self = this;
      const $dropzone = $("#dc-mail-dropzone");
      const $fileInput = $("#dc-mail-files");

      $dropzone.on("click", function (e) {
        // Prevent infinite loop - don't trigger if click came from file input
        if (e.target.id === "dc-mail-files") {
          return;
        }
        $fileInput.click();
      });

      $dropzone.on("dragover dragenter", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass("dc-mail-dropzone-active");
      });

      $dropzone.on("dragleave dragend drop", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass("dc-mail-dropzone-active");
      });

      $dropzone.on("drop", function (e) {
        const files = e.originalEvent.dataTransfer.files;
        self.addFiles(files);
      });

      $fileInput.on("change", function () {
        self.addFiles(this.files);
        $(this).val("");
      });
    },

    /**
     * Add files to attachments
     */
    addFiles: function (files) {
      const self = this;
      const $container = $("#dc-mail-attachments");

      Array.from(files).forEach(function (file) {
        const id =
          "attachment-" +
          Date.now() +
          "-" +
          Math.random().toString(36).substr(2, 9);
        self.attachments.push({ id: id, file: file });

        const $item = $(`
                    <div class="dc-mail-attachment" data-id="${id}">
                        <span class="dc-mail-attachment-name">${self.escapeHtml(
                          file.name
                        )}</span>
                        <span class="dc-mail-attachment-size">${self.formatFileSize(
                          file.size
                        )}</span>
                        <button type="button" class="dc-mail-attachment-remove"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
                    </div>
                `);

        $item.find(".dc-mail-attachment-remove").on("click", function () {
          self.removeAttachment(id);
          $item.remove();
        });

        $container.append($item);
      });
    },

    /**
     * Remove attachment
     */
    removeAttachment: function (id) {
      this.attachments = this.attachments.filter(function (a) {
        return a.id !== id;
      });
    },

    /**
     * Bind modal events
     */
    bindModalEvents: function () {
      const self = this;

      $(".dc-mail-close, .dc-mail-btn-cancel").on("click", function () {
        Swal.close();
      });

      $(".dc-mail-btn-send").on("click", function () {
        self.send();
      });
    },

    /**
     * Send email
     */
    send: function () {
      const self = this;
      const to = $("#dc-mail-to").val().trim();
      const subject = $("#dc-mail-subject").val().trim();
      const message = $("#dc-mail-editor").html();

      // Validation
      if (!to) {
        self.showError("Bitte geben Sie eine E-Mail-Adresse ein.");
        return;
      }

      if (!subject) {
        self.showError("Bitte geben Sie einen Betreff ein.");
        return;
      }

      if (!message || message === "<br>") {
        self.showError("Bitte geben Sie eine Nachricht ein.");
        return;
      }

      // Build form data
      const formData = new FormData();
      formData.append("action", "deep_clarity_send_mail");
      formData.append("nonce", deepClarityFrontend.nonce);
      formData.append("to", to);
      formData.append("subject", subject);
      formData.append("message", message);

      self.attachments.forEach(function (attachment) {
        formData.append("attachments[]", attachment.file);
      });

      // Show loading
      $(".dc-mail-btn-send").prop("disabled", true).html("Wird gesendet...");

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Gesendet!",
              text: response.data.message,
              timer: 2000,
              showConfirmButton: false,
            });
          } else {
            self.showError(response.data.message);
            $(".dc-mail-btn-send").prop("disabled", false).html(`
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M17.33 3H6.66c-.89-.01-1.6-.01-2.17.04 -.59.04-1.1.14-1.58.38 -.76.38-1.37.99-1.75 1.74 -.24.47-.35.98-.39 1.57C.72 7.3.72 8.01.72 8.89v2.33c0 .41.33.75.75.75 .41 0 .75-.34.75-.75l0-2.49 7.46 5.68c.58.44 1.05.8 1.57.94 .46.12.95.12 1.41 0 .52-.15.99-.5 1.57-.95l7.46-5.69v6.28c0 .92-.01 1.56-.05 2.07 -.05.49-.12.78-.24 1.01 -.24.47-.63.85-1.1 1.09 -.23.11-.52.19-1.02.23 -.51.04-1.16.04-2.08.04H4.9c-.42 0-.75.33-.75.75 0 .41.33.75.75.75h12.33c.88 0 1.59 0 2.16-.05 .58-.05 1.09-.15 1.57-.39 .75-.39 1.36-1 1.74-1.75 .24-.48.34-.99.38-1.58 .04-.58.04-1.29.04-2.17V8.82c0-.64 0-1.19-.02-1.66 0-.02-.01-.04-.01-.06 -.01-.16-.02-.31-.03-.46 -.05-.59-.15-1.1-.39-1.58 -.39-.76-1-1.37-1.75-1.75 -.48-.24-.99-.35-1.58-.39 -.58-.05-1.29-.05-2.17-.05Zm4.37 3.9l-.01-.04c-.05-.5-.12-.79-.24-1.02 -.24-.48-.63-.86-1.1-1.1 -.23-.12-.52-.2-1.02-.24 -.51-.05-1.16-.05-2.08-.05H6.65c-.93 0-1.57 0-2.08.04 -.5.04-.79.11-1.02.23 -.48.23-.86.62-1.1 1.09 -.12.22-.2.51-.24 1.01l-.01.03 8.22 6.26c.74.57.95.71 1.16.76 .21.05.43.05.64 0 .2-.06.41-.2 1.16-.77l8.22-6.27Z"></path><g><path d="M5 15H1.5c-.42 0-.75-.34-.75-.75 0-.42.33-.75.75-.75H5c.41 0 .75.33.75.75 0 .41-.34.75-.75.75Z"></path><path d="M3.5 16.5c-.42 0-.75.33-.75.75 0 .41.33.75.75.75h6c.41 0 .75-.34.75-.75 0-.42-.34-.75-.75-.75h-6Z"></path></g></svg>
                            E-Mail senden
                        `);
          }
        },
        error: function () {
          self.showError(
            "Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut."
          );
          $(".dc-mail-btn-send").prop("disabled", false).html(`
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M17.33 3H6.66c-.89-.01-1.6-.01-2.17.04 -.59.04-1.1.14-1.58.38 -.76.38-1.37.99-1.75 1.74 -.24.47-.35.98-.39 1.57C.72 7.3.72 8.01.72 8.89v2.33c0 .41.33.75.75.75 .41 0 .75-.34.75-.75l0-2.49 7.46 5.68c.58.44 1.05.8 1.57.94 .46.12.95.12 1.41 0 .52-.15.99-.5 1.57-.95l7.46-5.69v6.28c0 .92-.01 1.56-.05 2.07 -.05.49-.12.78-.24 1.01 -.24.47-.63.85-1.1 1.09 -.23.11-.52.19-1.02.23 -.51.04-1.16.04-2.08.04H4.9c-.42 0-.75.33-.75.75 0 .41.33.75.75.75h12.33c.88 0 1.59 0 2.16-.05 .58-.05 1.09-.15 1.57-.39 .75-.39 1.36-1 1.74-1.75 .24-.48.34-.99.38-1.58 .04-.58.04-1.29.04-2.17V8.82c0-.64 0-1.19-.02-1.66 0-.02-.01-.04-.01-.06 -.01-.16-.02-.31-.03-.46 -.05-.59-.15-1.1-.39-1.58 -.39-.76-1-1.37-1.75-1.75 -.48-.24-.99-.35-1.58-.39 -.58-.05-1.29-.05-2.17-.05Zm4.37 3.9l-.01-.04c-.05-.5-.12-.79-.24-1.02 -.24-.48-.63-.86-1.1-1.1 -.23-.12-.52-.2-1.02-.24 -.51-.05-1.16-.05-2.08-.05H6.65c-.93 0-1.57 0-2.08.04 -.5.04-.79.11-1.02.23 -.48.23-.86.62-1.1 1.09 -.12.22-.2.51-.24 1.01l-.01.03 8.22 6.26c.74.57.95.71 1.16.76 .21.05.43.05.64 0 .2-.06.41-.2 1.16-.77l8.22-6.27Z"></path><g><path d="M5 15H1.5c-.42 0-.75-.34-.75-.75 0-.42.33-.75.75-.75H5c.41 0 .75.33.75.75 0 .41-.34.75-.75.75Z"></path><path d="M3.5 16.5c-.42 0-.75.33-.75.75 0 .41.33.75.75.75h6c.41 0 .75-.34.75-.75 0-.42-.34-.75-.75-.75h-6Z"></path></g></svg>
                        E-Mail senden
                    `);
        },
      });
    },

    /**
     * Show error message
     */
    showError: function (message) {
      Swal.showValidationMessage(message);
    },

    /**
     * Escape HTML
     */
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },

    /**
     * Format file size
     */
    formatFileSize: function (bytes) {
      if (bytes === 0) return "0 Bytes";
      const k = 1024;
      const sizes = ["Bytes", "KB", "MB", "GB"];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    },
  };

  /**
   * Mail Preview Module
   */
  const MailPreview = {
    /**
     * Initialize mail preview triggers
     */
    init: function () {
      this.bindTriggers();
    },

    /**
     * Bind click events to swal-email-preview elements
     */
    bindTriggers: function () {
      $(document).on("click", ".swal-email-preview", function (e) {
        e.preventDefault();
        const mailId = $(this).data("mail-id");
        if (mailId) {
          MailPreview.open(mailId);
        }
      });
    },

    /**
     * Open mail preview modal
     */
    open: function (mailId) {
      const self = this;

      // Show loading
      Swal.fire({
        title: null,
        html: '<div class="dc-mail-preview-loading"><span class="spinner"></span> Laden...</div>',
        showConfirmButton: false,
        showCancelButton: false,
        width: "640px",
        padding: 0,
        customClass: {
          popup: "dc-mail-popup dc-mail-preview-popup",
          htmlContainer: "dc-mail-container",
        },
        allowOutsideClick: false,
      });

      // Fetch mail data
      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_get_mail_preview",
          nonce: deepClarityFrontend.nonce,
          mail_id: mailId,
        },
        success: function (response) {
          if (response.success) {
            self.render(response.data);
          } else {
            Swal.fire({
              icon: "error",
              title: "Fehler",
              text: response.data.message || "E-Mail konnte nicht geladen werden.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Fehler",
            text: "Ein Fehler ist aufgetreten.",
          });
        },
      });
    },

    /**
     * Render mail preview modal
     */
    render: function (data) {
      Swal.fire({
        title: null,
        html: this.getTemplate(data),
        showConfirmButton: false,
        showCancelButton: false,
        width: "640px",
        padding: 0,
        customClass: {
          popup: "dc-mail-popup dc-mail-preview-popup",
          htmlContainer: "dc-mail-container",
        },
        didOpen: function () {
          $(".dc-mail-close").on("click", function () {
            Swal.close();
          });
        },
      });
    },

    /**
     * Get mail preview template
     */
    getTemplate: function (data) {
      return `
        <div class="dc-mail-compose dc-mail-preview">
          <div class="dc-mail-header">
            <span class="dc-mail-title">E-Mail Vorschau</span>
            <button type="button" class="dc-mail-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-mail-body">
            <div class="dc-mail-field dc-mail-field-readonly">
              <label>An</label>
              <div class="dc-mail-field-value">
                <strong>${this.escapeHtml(data.client_name)}</strong>
                ${data.client_email ? `&lt;${this.escapeHtml(data.client_email)}&gt;` : ""}
              </div>
            </div>
            <div class="dc-mail-field dc-mail-field-readonly">
              <label>Betreff</label>
              <div class="dc-mail-field-value">${this.escapeHtml(data.subject)}</div>
            </div>
            <div class="dc-mail-field dc-mail-field-readonly">
              <label>Datum</label>
              <div class="dc-mail-field-value">${this.escapeHtml(data.date)}</div>
            </div>
            <div class="dc-mail-field dc-mail-field-message">
              <label>Nachricht</label>
              <div class="dc-mail-preview-content">${data.message}</div>
            </div>
          </div>
        </div>
      `;
    },

    /**
     * Escape HTML
     */
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  /**
   * Notes Module
   */
  const Notes = {
    /**
     * Initialize notes functionality
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind events for note actions
     */
    bindEvents: function () {
      // Delete note
      $(document).on("click", ".dc-note-delete", function (e) {
        e.preventDefault();
        e.stopPropagation();
        const noteId = $(this).data("note-id");
        if (noteId) {
          Notes.confirmDelete(noteId);
        }
      });

      // Edit note
      $(document).on("click", ".dc-note-edit", function (e) {
        e.preventDefault();
        e.stopPropagation();
        const noteId = $(this).data("note-id");
        if (noteId) {
          Notes.openEditModal(noteId);
        }
      });
    },

    /**
     * Confirm and delete note
     */
    confirmDelete: function (noteId) {
      Swal.fire({
        title: "Notiz löschen?",
        text: "Diese Aktion kann nicht rückgängig gemacht werden.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Ja, löschen",
        cancelButtonText: "Abbrechen",
      }).then(function (result) {
        if (result.isConfirmed) {
          Notes.delete(noteId);
        }
      });
    },

    /**
     * Delete note via AJAX
     */
    delete: function (noteId) {
      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_delete_note",
          nonce: deepClarityFrontend.nonce,
          note_id: noteId,
        },
        success: function (response) {
          if (response.success) {
            // Remove note from DOM with animation
            const $note = $('.dc-note[data-note-id="' + noteId + '"]');
            $note.fadeOut(300, function () {
              $(this).remove();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Fehler",
              text: response.data.message || "Notiz konnte nicht gelöscht werden.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Fehler",
            text: "Ein Fehler ist aufgetreten.",
          });
        },
      });
    },

    /**
     * Open edit modal
     */
    openEditModal: function (noteId) {
      const self = this;

      // Show loading
      Swal.fire({
        title: null,
        html: '<div class="dc-mail-preview-loading"><span class="spinner"></span> Laden...</div>',
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-note-popup",
        },
        allowOutsideClick: false,
      });

      // Fetch note content
      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_get_note",
          nonce: deepClarityFrontend.nonce,
          note_id: noteId,
        },
        success: function (response) {
          if (response.success) {
            self.renderEditModal(noteId, response.data);
          } else {
            Swal.fire({
              icon: "error",
              title: "Fehler",
              text: response.data.message || "Notiz konnte nicht geladen werden.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Fehler",
            text: "Ein Fehler ist aufgetreten.",
          });
        },
      });
    },

    /**
     * Render edit modal
     */
    renderEditModal: function (noteId, data) {
      const self = this;

      Swal.fire({
        title: null,
        html: self.getEditTemplate(noteId, data),
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-note-popup",
        },
        didOpen: function () {
          self.bindEditModalEvents(noteId);
        },
      });
    },

    /**
     * Get edit modal template
     */
    getEditTemplate: function (noteId, data) {
      return `
        <div class="dc-note-edit-modal">
          <div class="dc-note-edit-header">
            <span class="dc-note-edit-title">Notiz bearbeiten</span>
            <button type="button" class="dc-note-edit-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-note-edit-body">
            <textarea id="dc-note-content" placeholder="Notiz eingeben...">${this.escapeHtml(data.content)}</textarea>
          </div>
          <div class="dc-note-edit-footer">
            <button type="button" class="dc-note-btn dc-note-btn-cancel">Abbrechen</button>
            <button type="button" class="dc-note-btn dc-note-btn-save">Speichern</button>
          </div>
        </div>
      `;
    },

    /**
     * Bind edit modal events
     */
    bindEditModalEvents: function (noteId) {
      const self = this;

      $(".dc-note-edit-close, .dc-note-btn-cancel").on("click", function () {
        Swal.close();
      });

      $(".dc-note-btn-save").on("click", function () {
        const content = $("#dc-note-content").val().trim();
        if (content) {
          self.save(noteId, content);
        }
      });
    },

    /**
     * Save note via AJAX
     */
    save: function (noteId, content) {
      const $btn = $(".dc-note-btn-save");
      $btn.prop("disabled", true).text("Speichern...");

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_update_note",
          nonce: deepClarityFrontend.nonce,
          note_id: noteId,
          content: content,
        },
        success: function (response) {
          if (response.success) {
            // Update note content in DOM
            const $note = $('.dc-note[data-note-id="' + noteId + '"]');
            $note.find(".dc-note-content").html(response.data.content);

            Swal.fire({
              icon: "success",
              title: "Gespeichert!",
              timer: 1500,
              showConfirmButton: false,
            });
          } else {
            $btn.prop("disabled", false).text("Speichern");
            Swal.showValidationMessage(
              response.data.message || "Notiz konnte nicht gespeichert werden."
            );
          }
        },
        error: function () {
          $btn.prop("disabled", false).text("Speichern");
          Swal.showValidationMessage("Ein Fehler ist aufgetreten.");
        },
      });
    },

    /**
     * Escape HTML
     */
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  /**
   * Header Scroll Effect Module
   */
  const HeaderScroll = {
    header: null,
    frontpageLogo: null,
    heroLogo: null,

    /**
     * Initialize header scroll effect
     */
    init: function () {
      this.header = document.getElementById("header-visitor");
      this.frontpageLogo = document.getElementById("frontpage-logo");
      this.heroLogo = document.getElementById("hero-logo");

      this.bindEvents();
      // Check initial scroll position (for anchor links or page reload)
      this.checkScroll();
    },

    /**
     * Get threshold from CSS variable
     */
    getThreshold: function (varName, fallback) {
      const value = getComputedStyle(document.documentElement)
        .getPropertyValue(varName)
        .trim();
      return value ? parseInt(value, 10) : fallback;
    },

    /**
     * Bind scroll events
     */
    bindEvents: function () {
      const self = this;
      let ticking = false;

      window.addEventListener("scroll", function () {
        if (!ticking) {
          window.requestAnimationFrame(function () {
            self.checkScroll();
            ticking = false;
          });
          ticking = true;
        }
      });
    },

    /**
     * Check scroll position and toggle classes
     */
    checkScroll: function () {
      const scrollY = window.scrollY || window.pageYOffset;
      const headerThreshold = this.getThreshold("--dc-scroll-header-threshold", 120);
      const logoThreshold = this.getThreshold("--dc-scroll-logo-threshold", 68);

      // Debug: Log scroll position for logged-in users
      if (deepClarityFrontend.isLoggedIn) {
        console.log("Scroll Y:", scrollY);
      }

      // Header background effect
      if (this.header) {
        if (scrollY > headerThreshold) {
          this.header.classList.add("header-scrolled");
        } else {
          this.header.classList.remove("header-scrolled");
        }
      }

      // Logo switching
      if (scrollY > logoThreshold) {
        if (this.frontpageLogo) {
          this.frontpageLogo.classList.add("logo-visible");
        }
        if (this.heroLogo) {
          this.heroLogo.classList.add("logo-hidden");
        }
      } else {
        if (this.frontpageLogo) {
          this.frontpageLogo.classList.remove("logo-visible");
        }
        if (this.heroLogo) {
          this.heroLogo.classList.remove("logo-hidden");
        }
      }
    },
  };

  /**
   * Form Entry Viewer Module
   */
  const FormEntryViewer = {
    /**
     * Initialize form entry viewer
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind click events to form entry elements
     */
    bindEvents: function () {
      $(document).on("click", ".dc-form-entry", function (e) {
        e.preventDefault();
        const entryId = $(this).data("entry-id");
        const formId = $(this).data("form-id");
        if (entryId && formId) {
          FormEntryViewer.open(entryId, formId);
        }
      });
    },

    /**
     * Open form entry modal
     */
    open: function (entryId, formId) {
      const self = this;

      // Show loading
      Swal.fire({
        title: null,
        html: '<div class="dc-form-entry-loading"><span class="spinner"></span> Laden...</div>',
        showConfirmButton: false,
        showCancelButton: false,
        width: "640px",
        padding: 0,
        customClass: {
          popup: "dc-form-entry-popup",
          htmlContainer: "dc-form-entry-container",
        },
        allowOutsideClick: false,
      });

      // Fetch entry data
      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_get_form_entry",
          nonce: deepClarityFrontend.nonce,
          entry_id: entryId,
          form_id: formId,
        },
        success: function (response) {
          if (response.success) {
            self.render(response.data);
          } else {
            Swal.fire({
              icon: "error",
              title: "Fehler",
              text: response.data.message || "Eintrag konnte nicht geladen werden.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Fehler",
            text: "Ein Fehler ist aufgetreten.",
          });
        },
      });
    },

    /**
     * Render form entry modal
     */
    render: function (data) {
      Swal.fire({
        title: null,
        html: this.getTemplate(data),
        showConfirmButton: false,
        showCancelButton: false,
        width: "640px",
        padding: 0,
        customClass: {
          popup: "dc-form-entry-popup",
          htmlContainer: "dc-form-entry-container",
        },
        didOpen: function () {
          $(".dc-form-entry-close").on("click", function () {
            Swal.close();
          });
        },
      });
    },

    /**
     * Get form entry template
     */
    getTemplate: function (data) {
      let qaHtml = "";
      if (data.qa_pairs && data.qa_pairs.length > 0) {
        data.qa_pairs.forEach(function (qa) {
          qaHtml += `
            <div class="dc-form-entry-qa">
              <div class="dc-form-entry-question">${FormEntryViewer.escapeHtml(qa.question)}</div>
              <div class="dc-form-entry-answer">${FormEntryViewer.escapeHtml(qa.answer) || '<em class="dc-form-entry-empty">Keine Antwort</em>'}</div>
            </div>
          `;
        });
      } else {
        qaHtml = '<p class="dc-form-entry-empty">Keine Daten verfügbar.</p>';
      }

      return `
        <div class="dc-form-entry-modal">
          <div class="dc-form-entry-header">
            <div class="dc-form-entry-header-info">
              <span class="dc-form-entry-title">${this.escapeHtml(data.form_name)}</span>
              <span class="dc-form-entry-date">${this.escapeHtml(data.created_at)}</span>
            </div>
            <button type="button" class="dc-form-entry-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-form-entry-body">
            ${qaHtml}
          </div>
        </div>
      `;
    },

    /**
     * Escape HTML
     */
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  /**
   * Dossier Creator Module
   */
  const DossierCreator = {
    currentStep: 1,
    clientId: null,
    selectedSessionId: null,
    selectedFormIds: [],

    /**
     * Initialize dossier creator
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind click events to create-dossier button
     */
    bindEvents: function () {
      $(document).on("click", "#create-dossier", function (e) {
        e.preventDefault();
        const clientId = $(this).data("client-id");
        if (clientId) {
          DossierCreator.reset();
          DossierCreator.clientId = clientId;
          DossierCreator.openStep1();
        }
      });
    },

    /**
     * Reset state
     */
    reset: function () {
      this.currentStep = 1;
      this.clientId = null;
      this.selectedSessionId = null;
      this.selectedFormIds = [];
    },

    /**
     * Open Step 1: Session selection
     */
    openStep1: function () {
      const self = this;

      // Show loading
      Swal.fire({
        title: null,
        html: '<div class="dc-dossier-loading"><span class="spinner"></span> Sessions werden geladen...</div>',
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        allowOutsideClick: false,
      });

      // Fetch sessions for this client
      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_get_client_sessions",
          nonce: deepClarityFrontend.nonce,
          client_id: self.clientId,
        },
        success: function (response) {
          if (response.success) {
            self.renderStep1(response.data.sessions);
          } else {
            Swal.fire({
              icon: "error",
              title: "Fehler",
              text: response.data.message || "Sessions konnten nicht geladen werden.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Fehler",
            text: "Ein Fehler ist aufgetreten.",
          });
        },
      });
    },

    /**
     * Render Step 1: Session selection
     */
    renderStep1: function (sessions) {
      const self = this;

      if (!sessions || sessions.length === 0) {
        Swal.fire({
          icon: "info",
          title: "Keine Sessions",
          text: "Für diesen Client sind keine Sessions vorhanden.",
        });
        return;
      }

      Swal.fire({
        title: null,
        html: this.getStep1Template(sessions),
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          self.bindStep1Events();
        },
      });
    },

    /**
     * Get Step 1 template
     */
    getStep1Template: function (sessions) {
      let sessionsHtml = "";
      sessions.forEach(function (session) {
        const isSelected = DossierCreator.selectedSessionId === session.id ? " selected" : "";
        sessionsHtml += `
          <div class="dc-dossier-item${isSelected}" data-session-id="${session.id}">
            <div class="dc-dossier-item-info">
              <span class="dc-dossier-item-title">${DossierCreator.escapeHtml(session.title)}</span>
              <span class="dc-dossier-item-meta">${DossierCreator.escapeHtml(session.date)}</span>
            </div>
            <div class="dc-dossier-item-check">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
          </div>
        `;
      });

      return `
        <div class="dc-dossier-modal" data-step="1">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Schritt 1: Session auswählen</span>
            <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-dossier-body">
            <p class="dc-dossier-hint">Wählen Sie die Session aus, die für das Dossier verwendet werden soll:</p>
            <div class="dc-dossier-items">
              ${sessionsHtml}
            </div>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps">Schritt 1 von 2</div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn dc-dossier-btn-cancel">Abbrechen</button>
              <button type="button" class="dc-dossier-btn dc-dossier-btn-next" disabled>Weiter</button>
            </div>
          </div>
        </div>
      `;
    },

    /**
     * Bind Step 1 events
     */
    bindStep1Events: function () {
      const self = this;

      // Close button
      $(".dc-dossier-close, .dc-dossier-btn-cancel").on("click", function () {
        Swal.close();
      });

      // Session selection (single select)
      $(".dc-dossier-item").on("click", function () {
        $(".dc-dossier-item").removeClass("selected");
        $(this).addClass("selected");
        self.selectedSessionId = $(this).data("session-id");
        $(".dc-dossier-btn-next").prop("disabled", false);
      });

      // Next button
      $(".dc-dossier-btn-next").on("click", function () {
        if (self.selectedSessionId) {
          self.openStep2();
        }
      });
    },

    /**
     * Open Step 2: Form selection
     */
    openStep2: function () {
      const self = this;

      // Show loading
      Swal.fire({
        title: null,
        html: '<div class="dc-dossier-loading"><span class="spinner"></span> Formulare werden geladen...</div>',
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        allowOutsideClick: false,
      });

      // Fetch forms for this client
      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_get_client_forms",
          nonce: deepClarityFrontend.nonce,
          client_id: self.clientId,
        },
        success: function (response) {
          if (response.success) {
            self.renderStep2(response.data.forms);
          } else {
            Swal.fire({
              icon: "error",
              title: "Fehler",
              text: response.data.message || "Formulare konnten nicht geladen werden.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Fehler",
            text: "Ein Fehler ist aufgetreten.",
          });
        },
      });
    },

    /**
     * Render Step 2: Form selection
     */
    renderStep2: function (forms) {
      const self = this;

      Swal.fire({
        title: null,
        html: this.getStep2Template(forms),
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          self.bindStep2Events();
        },
      });
    },

    /**
     * Get Step 2 template
     */
    getStep2Template: function (forms) {
      let formsHtml = "";

      if (!forms || forms.length === 0) {
        formsHtml = '<p class="dc-dossier-empty">Keine Formulare vorhanden.</p>';
      } else {
        forms.forEach(function (form) {
          const isSelected = DossierCreator.selectedFormIds.includes(form.entry_id) ? " selected" : "";
          formsHtml += `
            <div class="dc-dossier-item dc-dossier-item-multi${isSelected}" data-entry-id="${form.entry_id}" data-form-id="${form.form_id}">
              <div class="dc-dossier-item-checkbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
              <div class="dc-dossier-item-info">
                <span class="dc-dossier-item-title">${DossierCreator.escapeHtml(form.form_name)}</span>
                <span class="dc-dossier-item-meta">ID: ${form.entry_id} • ${DossierCreator.escapeHtml(form.date)}</span>
              </div>
            </div>
          `;
        });
      }

      return `
        <div class="dc-dossier-modal" data-step="2">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Schritt 2: Formulare auswählen</span>
            <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-dossier-body">
            <p class="dc-dossier-hint">Wählen Sie die Formulare aus, die im Dossier enthalten sein sollen (optional):</p>
            <div class="dc-dossier-items">
              ${formsHtml}
            </div>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps">Schritt 2 von 2</div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn dc-dossier-btn-back">Zurück</button>
              <button type="button" class="dc-dossier-btn dc-dossier-btn-create">Dossier erstellen</button>
            </div>
          </div>
        </div>
      `;
    },

    /**
     * Bind Step 2 events
     */
    bindStep2Events: function () {
      const self = this;

      // Close button
      $(".dc-dossier-close").on("click", function () {
        Swal.close();
      });

      // Back button
      $(".dc-dossier-btn-back").on("click", function () {
        self.openStep1();
      });

      // Form selection (multi select)
      $(".dc-dossier-item-multi").on("click", function () {
        $(this).toggleClass("selected");
        const entryId = $(this).data("entry-id");

        if ($(this).hasClass("selected")) {
          if (!self.selectedFormIds.includes(entryId)) {
            self.selectedFormIds.push(entryId);
          }
        } else {
          self.selectedFormIds = self.selectedFormIds.filter(function (id) {
            return id !== entryId;
          });
        }
      });

      // Create button
      $(".dc-dossier-btn-create").on("click", function () {
        self.create();
      });
    },

    /**
     * Create dossier
     */
    create: function () {
      const self = this;
      const $btn = $(".dc-dossier-btn-create");
      $btn.prop("disabled", true).text("Wird erstellt...");

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_create_dossier",
          nonce: deepClarityFrontend.nonce,
          client_id: self.clientId,
          session_id: self.selectedSessionId,
          form_ids: self.selectedFormIds,
        },
        success: function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Gestartet!",
              text: response.data.message,
              timer: 2000,
              showConfirmButton: false,
            });
          } else {
            $btn.prop("disabled", false).text("Dossier erstellen");
            Swal.showValidationMessage(response.data.message || "Fehler beim Erstellen des Dossiers.");
          }
        },
        error: function () {
          $btn.prop("disabled", false).text("Dossier erstellen");
          Swal.showValidationMessage("Ein Fehler ist aufgetreten.");
        },
      });
    },

    /**
     * Escape HTML
     */
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  /**
   * Session Analyzer Module
   */
  const SessionAnalyzer = {
    sessionId: null,
    selectedFields: [],
    requestId: null,
    pollTimer: null,
    pollInterval: 3000, // Poll every 3 seconds

    // Available fields for selection
    availableFields: [
      { key: "session_transcript", label: "Transkript" },
      { key: "session_diagnosis", label: "Diagnose" },
      { key: "session_note", label: "Interne Notiz" },
    ],

    /**
     * Initialize session analyzer
     */
    init: function () {
      console.log("SessionAnalyzer: Initialized");
      this.bindEvents();
    },

    /**
     * Bind click events to analyze_session button
     */
    bindEvents: function () {
      $(document).on("click", "#analyze_session", function (e) {
        e.preventDefault();
        console.log("SessionAnalyzer: Button clicked");
        const sessionId = $(this).data("session-id");
        console.log("SessionAnalyzer: Session ID =", sessionId);
        if (sessionId) {
          SessionAnalyzer.reset();
          SessionAnalyzer.sessionId = sessionId;
          SessionAnalyzer.open();
        } else {
          console.log("SessionAnalyzer: No session-id found on button");
        }
      });
    },

    /**
     * Reset state
     */
    reset: function () {
      this.sessionId = null;
      this.selectedFields = [];
      this.requestId = null;
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    },

    /**
     * Open modal
     */
    open: function () {
      const self = this;

      Swal.fire({
        title: null,
        html: this.getTemplate(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          self.bindModalEvents();
        },
      });
    },

    /**
     * Get template
     */
    getTemplate: function () {
      let fieldsHtml = "";

      this.availableFields.forEach(function (field) {
        const isSelected = SessionAnalyzer.selectedFields.includes(field.key)
          ? " selected"
          : "";
        fieldsHtml += `
          <div class="dc-dossier-item dc-dossier-item-multi${isSelected}" data-field="${field.key}">
            <div class="dc-dossier-item-checkbox">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="dc-dossier-item-info">
              <span class="dc-dossier-item-title">${field.label}</span>
            </div>
          </div>
        `;
      });

      return `
        <div class="dc-dossier-modal">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Session analysieren</span>
            <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-dossier-body">
            <p class="dc-dossier-hint">Wählen Sie die Felder aus, die für die Analyse verwendet werden sollen:</p>
            <div class="dc-dossier-items">
              ${fieldsHtml}
            </div>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps"></div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn dc-dossier-btn-cancel">Abbrechen</button>
              <button type="button" class="dc-dossier-btn dc-dossier-btn-analyze" disabled>Analyse starten</button>
            </div>
          </div>
        </div>
      `;
    },

    /**
     * Get loading template
     */
    getLoadingTemplate: function () {
      return `
        <div class="dc-dossier-modal dc-analyzer-loading">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Analyse läuft...</span>
          </div>
          <div class="dc-dossier-body dc-analyzer-loading-body">
            <div class="dc-analyzer-spinner">
              <span class="dc-analyzer-loader"></span>
            </div>
            <p class="dc-analyzer-loading-text">Die Analyse wird durchgeführt. Bitte warten...</p>
            <p class="dc-analyzer-loading-hint">Das Modal schließt sich automatisch, sobald die Analyse abgeschlossen ist.</p>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps"></div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn dc-dossier-btn-cancel-analysis">Abbrechen</button>
            </div>
          </div>
        </div>
      `;
    },

    /**
     * Bind modal events
     */
    bindModalEvents: function () {
      const self = this;

      // Close button
      $(".dc-dossier-close, .dc-dossier-btn-cancel").on("click", function () {
        Swal.close();
      });

      // Field selection (multi select)
      $(".dc-dossier-item-multi").on("click", function () {
        $(this).toggleClass("selected");
        const fieldKey = $(this).data("field");

        if ($(this).hasClass("selected")) {
          if (!self.selectedFields.includes(fieldKey)) {
            self.selectedFields.push(fieldKey);
          }
        } else {
          self.selectedFields = self.selectedFields.filter(function (key) {
            return key !== fieldKey;
          });
        }

        // Enable/disable analyze button
        $(".dc-dossier-btn-analyze").prop(
          "disabled",
          self.selectedFields.length === 0
        );
      });

      // Analyze button
      $(".dc-dossier-btn-analyze").on("click", function () {
        self.analyze();
      });
    },

    /**
     * Analyze session
     */
    analyze: function () {
      const self = this;
      const $btn = $(".dc-dossier-btn-analyze");
      $btn.prop("disabled", true).text("Wird gestartet...");

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_analyze_session",
          nonce: deepClarityFrontend.nonce,
          session_id: self.sessionId,
          fields: self.selectedFields,
        },
        success: function (response) {
          if (response.success) {
            // Store request ID and start polling
            self.requestId = response.data.request_id;
            self.showLoadingState();
            self.startPolling();
          } else {
            $btn.prop("disabled", false).text("Analyse starten");
            Swal.showValidationMessage(
              response.data.message || "Fehler beim Starten der Analyse."
            );
          }
        },
        error: function () {
          $btn.prop("disabled", false).text("Analyse starten");
          Swal.showValidationMessage("Ein Fehler ist aufgetreten.");
        },
      });
    },

    /**
     * Show loading state in modal
     */
    showLoadingState: function () {
      const self = this;

      Swal.fire({
        title: null,
        html: this.getLoadingTemplate(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          $(".dc-dossier-btn-cancel-analysis").on("click", function () {
            self.stopPolling();
            Swal.close();
          });
        },
      });
    },

    /**
     * Start polling for status
     */
    startPolling: function () {
      const self = this;

      this.pollTimer = setInterval(function () {
        self.checkStatus();
      }, this.pollInterval);
    },

    /**
     * Stop polling
     */
    stopPolling: function () {
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    },

    /**
     * Check analysis status
     */
    checkStatus: function () {
      const self = this;

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_check_analysis_status",
          nonce: deepClarityFrontend.nonce,
          request_id: self.requestId,
        },
        success: function (response) {
          if (response.success) {
            if (response.data.status === "complete") {
              self.stopPolling();
              self.showResult(response.data.result);
            } else if (response.data.status === "error") {
              self.stopPolling();
              self.showError(response.data.result || "Ein Fehler ist aufgetreten.");
            }
            // If status is 'pending', continue polling
          } else {
            // Request not found or expired
            self.stopPolling();
            self.showError(response.data.message || "Anfrage nicht gefunden.");
          }
        },
        error: function () {
          self.stopPolling();
          self.showError("Verbindungsfehler beim Prüfen des Status.");
        },
      });
    },

    /**
     * Show result
     */
    showResult: function (result) {
      Swal.fire({
        icon: "success",
        title: "Analyse abgeschlossen!",
        html: result
          ? '<div class="dc-analyzer-result">' + this.escapeHtml(result) + "</div>"
          : "Die Analyse wurde erfolgreich abgeschlossen.",
        confirmButtonText: "Schließen",
        allowOutsideClick: false,
        allowEscapeKey: false,
      }).then(function () {
        window.location.reload();
      });
    },

    /**
     * Show error
     */
    showError: function (message) {
      Swal.fire({
        icon: "error",
        title: "Fehler",
        text: message,
        confirmButtonText: "Schließen",
      });
    },

    /**
     * Escape HTML
     */
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  /**
   * Note Creator Module
   */
  const NoteCreator = {
    clientId: null,

    /**
     * Initialize note creator
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind click events to add-client-note button
     */
    bindEvents: function () {
      $(document).on("click", "#add-client-note", function (e) {
        e.preventDefault();
        const clientId = $(this).data("client-id");
        if (clientId) {
          NoteCreator.clientId = clientId;
          NoteCreator.open();
        }
      });
    },

    /**
     * Open modal
     */
    open: function () {
      const self = this;

      Swal.fire({
        title: null,
        html: this.getTemplate(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "500px",
        padding: 0,
        customClass: {
          popup: "dc-note-popup",
        },
        didOpen: function () {
          self.bindModalEvents();
          $("#dc-note-content").focus();
        },
      });
    },

    /**
     * Get template
     */
    getTemplate: function () {
      return `
        <div class="dc-note-edit-modal">
          <div class="dc-note-edit-header">
            <span class="dc-note-edit-title">Neue Notiz erstellen</span>
            <button type="button" class="dc-note-edit-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-note-edit-body">
            <textarea id="dc-note-content" placeholder="Notiz eingeben..."></textarea>
          </div>
          <div class="dc-note-edit-footer">
            <button type="button" class="dc-note-btn dc-note-btn-cancel">Abbrechen</button>
            <button type="button" class="dc-note-btn dc-note-btn-save">Speichern</button>
          </div>
        </div>
      `;
    },

    /**
     * Bind modal events
     */
    bindModalEvents: function () {
      const self = this;

      $(".dc-note-edit-close, .dc-note-btn-cancel").on("click", function () {
        Swal.close();
      });

      $(".dc-note-btn-save").on("click", function () {
        const content = $("#dc-note-content").val().trim();
        if (content) {
          self.save(content);
        } else {
          Swal.showValidationMessage("Bitte geben Sie einen Inhalt ein.");
        }
      });
    },

    /**
     * Save note via AJAX
     */
    save: function (content) {
      const self = this;
      const $btn = $(".dc-note-btn-save");
      $btn.prop("disabled", true).text("Speichern...");

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_create_note",
          nonce: deepClarityFrontend.nonce,
          client_id: self.clientId,
          content: content,
        },
        success: function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Gespeichert!",
              text: response.data.message,
              confirmButtonText: "Schließen",
            }).then(function () {
              window.location.reload();
            });
          } else {
            $btn.prop("disabled", false).text("Speichern");
            Swal.showValidationMessage(
              response.data.message || "Notiz konnte nicht erstellt werden."
            );
          }
        },
        error: function () {
          $btn.prop("disabled", false).text("Speichern");
          Swal.showValidationMessage("Ein Fehler ist aufgetreten.");
        },
      });
    },
  };

  /**
   * Copy Clipboard Module
   */
  const CopyClipboard = {
    /**
     * Initialize copy clipboard functionality
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind click events to copy-clipboard elements
     */
    bindEvents: function () {
      // Handle click on the wrapper element or any child (including the anchor)
      $(document).on("click", ".copy-clipboard, .copy-clipboard a", function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Get the wrapper element
        const $wrapper = $(this).hasClass("copy-clipboard")
          ? $(this)
          : $(this).closest(".copy-clipboard");

        // Find the anchor inside and get href
        const $anchor = $wrapper.find("a").first();
        const url = $anchor.length ? $anchor.attr("href") : $(this).attr("href");

        if (url) {
          CopyClipboard.copyToClipboard(url);
        }
      });
    },

    /**
     * Copy text to clipboard and show toast
     */
    copyToClipboard: function (text) {
      // Use modern clipboard API if available
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(text)
          .then(function () {
            CopyClipboard.showToast();
          })
          .catch(function () {
            CopyClipboard.fallbackCopy(text);
          });
      } else {
        CopyClipboard.fallbackCopy(text);
      }
    },

    /**
     * Fallback copy method for older browsers
     */
    fallbackCopy: function (text) {
      const textarea = document.createElement("textarea");
      textarea.value = text;
      textarea.style.position = "fixed";
      textarea.style.left = "-9999px";
      document.body.appendChild(textarea);
      textarea.select();

      try {
        document.execCommand("copy");
        CopyClipboard.showToast();
      } catch (err) {
        console.error("Copy failed:", err);
      }

      document.body.removeChild(textarea);
    },

    /**
     * Show toast notification
     */
    showToast: function () {
      if (typeof Toastify === "function") {
        Toastify({
          text: "Link wurde in die Zwischenablage kopiert",
          duration: 5000,
          gravity: "top",
          position: "center",
          stopOnFocus: true,
          className: "dc-toast",
          style: {
            background: "linear-gradient(to right, #b9ae9b, #e0d9d0)",
            color: "#333",
            fontWeight: "500",
            borderRadius: "8px",
            boxShadow: "0 4px 12px rgba(0, 0, 0, 0.15)",
          },
        }).showToast();
      }
    },
  };

  /**
   * Deep Clarity Frontend Module
   */
  const DeepClarityFrontend = {
    /**
     * Initialize
     */
    init: function () {
      this.bindEvents();
      MailCompose.init();
      MailPreview.init();
      Notes.init();
      HeaderScroll.init();
      FormEntryViewer.init();
      DossierCreator.init();
      SessionAnalyzer.init();
      NoteCreator.init();
      CopyClipboard.init();
    },

    /**
     * Bind events
     */
    bindEvents: function () {
      // Referal link - go back in history or use referrer
      $(".referal-link a").on("click", function (e) {
        e.preventDefault();
        if (document.referrer && document.referrer !== "") {
          window.location.href = document.referrer;
        } else {
          window.history.back();
        }
      });
    },

    /**
     * AJAX request helper
     *
     * @param {string} action - AJAX action name
     * @param {object} data - Additional data
     * @param {function} callback - Success callback
     */
    ajax: function (action, data, callback) {
      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: $.extend(
          {
            action: "deep_clarity_" + action,
            nonce: deepClarityFrontend.nonce,
          },
          data
        ),
        success: function (response) {
          if (typeof callback === "function") {
            callback(response);
          }
        },
        error: function (xhr, status, error) {
          console.error("Deep Clarity Error:", error);
        },
      });
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    DeepClarityFrontend.init();
  });

  // Expose to global scope
  window.DeepClarityFrontend = DeepClarityFrontend;
})(jQuery);
