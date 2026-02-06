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
            <div class="dc-note-edit-header-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            </div>
            <div class="dc-note-edit-header-content">
              <div class="dc-note-edit-title">Notiz bearbeiten</div>
              <div class="dc-note-edit-subtitle">Ändere den Inhalt deiner Notiz</div>
            </div>
            <button type="button" class="dc-note-edit-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-note-edit-body">
            <label for="dc-note-content">Notiz</label>
            <textarea id="dc-note-content" placeholder="Schreibe hier deine Notiz...">${this.escapeHtml(data.content)}</textarea>
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
   *
   * New flow:
   * Step 1: Select Anamnesebogen (Form ID 3) - required
   * Step 2: Select Session + optional DCPI (Form ID 23)
   * Step 3: Comparison values (only for 2nd+ dossier) - previous session + DCPI
   * Step 4: Summary and confirmation
   */
  const DossierCreator = {
    clientId: null,
    data: null, // Holds all fetched data

    // Selected values
    selectedAnamneseEntryId: null,
    selectedSessionId: null,
    selectedDcpiEntryId: null,
    selectedComparisonSessionId: null,
    selectedComparisonDcpiEntryId: null,

    // Polling state
    requestId: null,
    pollTimer: null,
    pollInterval: 3000, // Poll every 3 seconds
    processingStep: 1, // 1 = Strukturanalyse, 2 = Dossier-Generierung, 3 = PDF-Generierung
    lastDossierId: null, // Store dossier ID for PDF generation
    pdfUrl: null, // Store PDF URL after generation

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
          DossierCreator.loadData();
        }
      });
    },

    /**
     * Reset state
     */
    reset: function () {
      this.clientId = null;
      this.data = null;
      this.selectedAnamneseEntryId = null;
      this.selectedSessionId = null;
      this.selectedDcpiEntryId = null;
      this.selectedComparisonSessionId = null;
      this.selectedComparisonDcpiEntryId = null;
      this.requestId = null;
      this.processingStep = 1;
      this.lastDossierId = null;
      this.pdfUrl = null;
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    },

    /**
     * Load all data needed for dossier creation
     */
    loadData: function () {
      const self = this;

      Swal.fire({
        title: null,
        html: '<div class="dc-dossier-loading"><span class="spinner"></span> Daten werden geladen...</div>',
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        allowOutsideClick: false,
      });

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_init_dossier",
          nonce: deepClarityFrontend.nonce,
          client_id: self.clientId,
        },
        success: function (response) {
          if (response.success) {
            self.data = response.data;
            self.processData();
          } else {
            Swal.fire({
              icon: "error",
              title: "Fehler",
              text: response.data.message || "Daten konnten nicht geladen werden.",
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
     * Process loaded data and determine flow
     */
    processData: function () {
      const isFirstDossier = this.data.dossier_count === 0;

      // Anamnese is always required (for all dossiers)
      if (!this.data.anamnese_forms || this.data.anamnese_forms.length === 0) {
        this.showNoAnamneseError();
        return;
      }

      // Check if sessions exist
      if (!this.data.sessions || this.data.sessions.length === 0) {
        Swal.fire({
          icon: "info",
          title: "Keine Sessions",
          text: "Es sind noch keine Sessions für diesen Klienten vorhanden. Bitte erstellen Sie zuerst eine Session.",
        });
        return;
      }

      // First dossier: Start with Anamnesebogen selection (Step 1)
      // 2nd+ dossier: Auto-select first anamnese, start directly with Session + DCPI
      if (isFirstDossier) {
        this.renderStep1();
      } else {
        // For follow-up dossiers: auto-select the first (most recent) anamnese
        this.selectedAnamneseEntryId = this.data.anamnese_forms[0].entry_id;
        this.renderStep2();
      }
    },

    /**
     * Show error when no Anamnesebogen exists
     */
    showNoAnamneseError: function () {
      Swal.fire({
        icon: "warning",
        title: "Anamnesebogen fehlt",
        html: '<p style="text-align: left; margin: 0;">Ein Dossier kann erst erstellt werden, wenn das <strong>Anamneseformular</strong> ausgefüllt wurde.</p><p style="text-align: left; margin: 16px 0 0 0;">Bitte bitten Sie den Klienten, zuerst das Anamneseformular auszufüllen.</p>',
        confirmButtonText: "Verstanden",
        customClass: {
          popup: "dc-dossier-popup",
        },
      });
    },

    /**
     * Get total steps based on dossier count
     * First dossier: Anamnese → Session+DCPI → Summary = 3 steps
     * 2nd+ dossier: Session+DCPI → Comparison → Summary = 3 steps
     */
    getTotalSteps: function () {
      return 3;
    },

    /**
     * Check if this is the first dossier (Anamnesebogen required)
     */
    isFirstDossier: function () {
      return this.data.dossier_count === 0;
    },

    /**
     * Check if comparison step is needed
     */
    needsComparisonStep: function () {
      return this.data.dossier_count > 0;
    },

    // =====================================================
    // STEP 1: Anamnesebogen Selection
    // =====================================================

    renderStep1: function () {
      const self = this;
      const totalSteps = this.getTotalSteps();

      Swal.fire({
        title: null,
        html: this.getStep1Template(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
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

    getStep1Template: function () {
      const forms = this.data.anamnese_forms;
      const totalSteps = this.getTotalSteps();
      let formsHtml = "";

      forms.forEach((form) => {
        const isSelected = this.selectedAnamneseEntryId === form.entry_id ? " selected" : "";
        formsHtml += `
          <div class="dc-dossier-item${isSelected}" data-entry-id="${form.entry_id}">
            <div class="dc-dossier-item-info">
              <span class="dc-dossier-item-title">${this.escapeHtml(form.form_name)}</span>
              <span class="dc-dossier-item-meta">${this.escapeHtml(form.date)}</span>
            </div>
            <div class="dc-dossier-item-check">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
          </div>
        `;
      });

      // If only one form, pre-select it
      if (forms.length === 1) {
        this.selectedAnamneseEntryId = forms[0].entry_id;
        formsHtml = formsHtml.replace('class="dc-dossier-item"', 'class="dc-dossier-item selected"');
      }

      return `
        <div class="dc-dossier-modal" data-step="1">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Schritt 1: Anamnesebogen</span>
            <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-dossier-body">
            <p class="dc-dossier-hint">Wählen Sie den Anamnesebogen, der für das Dossier verwendet werden soll:</p>
            <div class="dc-dossier-items">
              ${formsHtml}
            </div>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps">Schritt 1 von ${totalSteps}</div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn dc-dossier-btn-cancel">Abbrechen</button>
              <button type="button" class="dc-dossier-btn dc-dossier-btn-next" ${this.selectedAnamneseEntryId ? "" : "disabled"}>Weiter</button>
            </div>
          </div>
        </div>
      `;
    },

    bindStep1Events: function () {
      const self = this;

      $(".dc-dossier-close, .dc-dossier-btn-cancel").on("click", function () {
        Swal.close();
      });

      $(".dc-dossier-item").on("click", function () {
        $(".dc-dossier-item").removeClass("selected");
        $(this).addClass("selected");
        self.selectedAnamneseEntryId = $(this).data("entry-id");
        $(".dc-dossier-btn-next").prop("disabled", false);
      });

      $(".dc-dossier-btn-next").on("click", function () {
        if (self.selectedAnamneseEntryId) {
          self.renderStep2();
        }
      });
    },

    // =====================================================
    // STEP 2: Session + DCPI Selection
    // =====================================================

    renderStep2: function () {
      const self = this;

      Swal.fire({
        title: null,
        html: this.getStep2Template(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
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

    getStep2Template: function () {
      const sessions = this.data.sessions;
      const dcpiForms = this.data.dcpi_forms;
      const totalSteps = this.getTotalSteps();
      const isFirst = this.isFirstDossier();
      // For first dossier: Step 2 of 3, for 2nd+: Step 1 of 3
      const currentStep = isFirst ? 2 : 1;
      const stepTitle = isFirst ? "Schritt 2: Session & DCPI" : "Schritt 1: Session & DCPI";
      // For first dossier: show "Zurück", for 2nd+: show "Abbrechen"
      const backButtonText = isFirst ? "Zurück" : "Abbrechen";
      const backButtonClass = isFirst ? "dc-dossier-btn-back" : "dc-dossier-btn-cancel";

      let sessionsHtml = "";
      sessions.forEach((session) => {
        const isSelected = this.selectedSessionId === session.id ? " selected" : "";
        sessionsHtml += `
          <div class="dc-dossier-item dc-dossier-session${isSelected}" data-session-id="${session.id}">
            <div class="dc-dossier-item-info">
              <span class="dc-dossier-item-title">${this.escapeHtml(session.title)}</span>
              <span class="dc-dossier-item-meta">${this.escapeHtml(session.date)}</span>
            </div>
            <div class="dc-dossier-item-check">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
          </div>
        `;
      });

      let dcpiHtml = "";
      if (dcpiForms && dcpiForms.length > 0) {
        dcpiForms.forEach((form) => {
          const isSelected = this.selectedDcpiEntryId === form.entry_id ? " selected" : "";
          dcpiHtml += `
            <div class="dc-dossier-item dc-dossier-dcpi${isSelected}" data-entry-id="${form.entry_id}">
              <div class="dc-dossier-item-info">
                <span class="dc-dossier-item-title">${this.escapeHtml(form.form_name)}</span>
                <span class="dc-dossier-item-meta">${this.escapeHtml(form.date)}</span>
              </div>
              <div class="dc-dossier-item-check">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
            </div>
          `;
        });
      } else {
        dcpiHtml = '<p class="dc-dossier-empty-small">Kein DCPI-Formular vorhanden.</p>';
      }

      return `
        <div class="dc-dossier-modal" data-step="2">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">${stepTitle}</span>
            <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-dossier-body">
            <p class="dc-dossier-hint"><strong>Session auswählen</strong> (erforderlich):</p>
            <div class="dc-dossier-items dc-dossier-items-small">
              ${sessionsHtml}
            </div>
            <p class="dc-dossier-hint dc-dossier-hint-mt"><strong>DCPI-Formular auswählen</strong> (erforderlich):</p>
            <div class="dc-dossier-items dc-dossier-items-small">
              ${dcpiHtml}
            </div>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps">Schritt ${currentStep} von ${totalSteps}</div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn ${backButtonClass}">${backButtonText}</button>
              <button type="button" class="dc-dossier-btn dc-dossier-btn-next" ${this.selectedSessionId && this.selectedDcpiEntryId ? "" : "disabled"}>Weiter</button>
            </div>
          </div>
        </div>
      `;
    },

    bindStep2Events: function () {
      const self = this;

      $(".dc-dossier-close").on("click", function () {
        Swal.close();
      });

      // For first dossier: "Zurück" goes to Step 1
      $(".dc-dossier-btn-back").on("click", function () {
        self.renderStep1();
      });

      // For 2nd+ dossier: "Abbrechen" closes the modal
      $(".dc-dossier-btn-cancel").on("click", function () {
        Swal.close();
      });

      // Helper to update next button state
      function updateNextButton() {
        const canProceed = self.selectedSessionId && self.selectedDcpiEntryId;
        $(".dc-dossier-btn-next").prop("disabled", !canProceed);
      }

      // Session selection (single select, required)
      $(".dc-dossier-session").on("click", function () {
        $(".dc-dossier-session").removeClass("selected");
        $(this).addClass("selected");
        self.selectedSessionId = $(this).data("session-id");
        updateNextButton();
      });

      // DCPI selection (single select, required)
      $(".dc-dossier-dcpi").on("click", function () {
        $(".dc-dossier-dcpi").removeClass("selected");
        $(this).addClass("selected");
        self.selectedDcpiEntryId = $(this).data("entry-id");
        updateNextButton();
      });

      $(".dc-dossier-btn-next").on("click", function () {
        if (self.selectedSessionId && self.selectedDcpiEntryId) {
          if (self.needsComparisonStep()) {
            self.renderStep3();
          } else {
            self.renderSummary();
          }
        }
      });
    },

    // =====================================================
    // STEP 3: Comparison Values (only for 2nd+ dossier)
    // =====================================================

    renderStep3: function () {
      const self = this;

      Swal.fire({
        title: null,
        html: this.getStep3Template(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          self.bindStep3Events();
        },
      });
    },

    getStep3Template: function () {
      const sessions = this.data.sessions;
      const dcpiForms = this.data.dcpi_forms;
      const totalSteps = this.getTotalSteps();
      // Step 3 only runs for 2nd+ dossiers, so this is always Step 2 of 3
      const currentStep = 2;

      // Filter out already selected session and DCPI
      const availableSessions = sessions.filter((s) => s.id !== this.selectedSessionId);
      const availableDcpi = dcpiForms ? dcpiForms.filter((f) => f.entry_id !== this.selectedDcpiEntryId) : [];

      let sessionsHtml = "";
      if (availableSessions.length > 0) {
        availableSessions.forEach((session) => {
          const isSelected = this.selectedComparisonSessionId === session.id ? " selected" : "";
          sessionsHtml += `
            <div class="dc-dossier-item dc-dossier-comp-session${isSelected}" data-session-id="${session.id}">
              <div class="dc-dossier-item-info">
                <span class="dc-dossier-item-title">${this.escapeHtml(session.title)}</span>
                <span class="dc-dossier-item-meta">${this.escapeHtml(session.date)}</span>
              </div>
              <div class="dc-dossier-item-check">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
            </div>
          `;
        });
      } else {
        sessionsHtml = '<p class="dc-dossier-empty-small">Keine weitere Session zum Vergleich vorhanden.</p>';
      }

      let dcpiHtml = "";
      if (availableDcpi.length > 0) {
        availableDcpi.forEach((form) => {
          const isSelected = this.selectedComparisonDcpiEntryId === form.entry_id ? " selected" : "";
          dcpiHtml += `
            <div class="dc-dossier-item dc-dossier-comp-dcpi${isSelected}" data-entry-id="${form.entry_id}">
              <div class="dc-dossier-item-info">
                <span class="dc-dossier-item-title">${this.escapeHtml(form.form_name)}</span>
                <span class="dc-dossier-item-meta">${this.escapeHtml(form.date)}</span>
              </div>
              <div class="dc-dossier-item-check">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
            </div>
          `;
        });
      } else {
        dcpiHtml = '<p class="dc-dossier-empty-small">Kein weiteres DCPI-Formular zum Vergleich vorhanden.</p>';
      }

      return `
        <div class="dc-dossier-modal" data-step="3">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Schritt 2: Vergleichswerte</span>
            <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-dossier-body">
            <div class="dc-dossier-info-box">
              <p>Da bereits ein Dossier für diesen Klienten erstellt wurde, müssen Sie <strong>Vergleichswerte</strong> auswählen. Diese ermöglichen es, die Entwicklung des Klienten im neuen Dossier darzustellen.</p>
            </div>
            <p class="dc-dossier-hint"><strong>Vergleichs-Session</strong> (erforderlich):</p>
            <div class="dc-dossier-items dc-dossier-items-small">
              ${sessionsHtml}
            </div>
            <p class="dc-dossier-hint dc-dossier-hint-mt"><strong>Vergleichs-DCPI</strong> (erforderlich):</p>
            <div class="dc-dossier-items dc-dossier-items-small">
              ${dcpiHtml}
            </div>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps">Schritt ${currentStep} von ${totalSteps}</div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn dc-dossier-btn-back">Zurück</button>
              <button type="button" class="dc-dossier-btn dc-dossier-btn-next" ${this.selectedComparisonSessionId && this.selectedComparisonDcpiEntryId ? "" : "disabled"}>Weiter</button>
            </div>
          </div>
        </div>
      `;
    },

    bindStep3Events: function () {
      const self = this;

      $(".dc-dossier-close").on("click", function () {
        Swal.close();
      });

      $(".dc-dossier-btn-back").on("click", function () {
        self.renderStep2();
      });

      // Helper to update next button state
      function updateNextButton() {
        const canProceed = self.selectedComparisonSessionId && self.selectedComparisonDcpiEntryId;
        $(".dc-dossier-btn-next").prop("disabled", !canProceed);
      }

      // Comparison session selection (single select, required)
      $(".dc-dossier-comp-session").on("click", function () {
        $(".dc-dossier-comp-session").removeClass("selected");
        $(this).addClass("selected");
        self.selectedComparisonSessionId = $(this).data("session-id");
        updateNextButton();
      });

      // Comparison DCPI selection (single select, required)
      $(".dc-dossier-comp-dcpi").on("click", function () {
        $(".dc-dossier-comp-dcpi").removeClass("selected");
        $(this).addClass("selected");
        self.selectedComparisonDcpiEntryId = $(this).data("entry-id");
        updateNextButton();
      });

      $(".dc-dossier-btn-next").on("click", function () {
        if (self.selectedComparisonSessionId && self.selectedComparisonDcpiEntryId) {
          self.renderSummary();
        }
      });
    },

    // =====================================================
    // SUMMARY STEP
    // =====================================================

    renderSummary: function () {
      const self = this;

      Swal.fire({
        title: null,
        html: this.getSummaryTemplate(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          self.bindSummaryEvents();
        },
      });
    },

    getSummaryTemplate: function () {
      const totalSteps = this.getTotalSteps();
      const currentStep = totalSteps;
      const isFirst = this.isFirstDossier();

      // Find selected items for display
      const anamnese = this.selectedAnamneseEntryId && this.data.anamnese_forms
        ? this.data.anamnese_forms.find((f) => f.entry_id === this.selectedAnamneseEntryId)
        : null;
      const session = this.data.sessions.find((s) => s.id === this.selectedSessionId);
      const dcpi = this.selectedDcpiEntryId ? this.data.dcpi_forms.find((f) => f.entry_id === this.selectedDcpiEntryId) : null;
      const compSession = this.selectedComparisonSessionId ? this.data.sessions.find((s) => s.id === this.selectedComparisonSessionId) : null;
      const compDcpi = this.selectedComparisonDcpiEntryId ? this.data.dcpi_forms.find((f) => f.entry_id === this.selectedComparisonDcpiEntryId) : null;

      let summaryHtml = "";

      // Only show Anamnesebogen for first dossier
      if (isFirst && anamnese) {
        summaryHtml += `
          <div class="dc-dossier-summary-item">
            <span class="dc-dossier-summary-label">Anamnesebogen</span>
            <span class="dc-dossier-summary-value">${this.escapeHtml(anamnese.form_name)} <small>(${this.escapeHtml(anamnese.date)})</small></span>
          </div>
        `;
      }

      summaryHtml += `
        <div class="dc-dossier-summary-item">
          <span class="dc-dossier-summary-label">Session</span>
          <span class="dc-dossier-summary-value">${this.escapeHtml(session.title)} <small>(${this.escapeHtml(session.date)})</small></span>
        </div>
      `;

      if (dcpi) {
        summaryHtml += `
          <div class="dc-dossier-summary-item">
            <span class="dc-dossier-summary-label">DCPI-Formular</span>
            <span class="dc-dossier-summary-value">${this.escapeHtml(dcpi.form_name)} <small>(${this.escapeHtml(dcpi.date)})</small></span>
          </div>
        `;
      }

      if (compSession) {
        summaryHtml += `
          <div class="dc-dossier-summary-item dc-dossier-summary-comparison">
            <span class="dc-dossier-summary-label">Vergleichs-Session</span>
            <span class="dc-dossier-summary-value">${this.escapeHtml(compSession.title)} <small>(${this.escapeHtml(compSession.date)})</small></span>
          </div>
        `;
      }

      if (compDcpi) {
        summaryHtml += `
          <div class="dc-dossier-summary-item dc-dossier-summary-comparison">
            <span class="dc-dossier-summary-label">Vergleichs-DCPI</span>
            <span class="dc-dossier-summary-value">${this.escapeHtml(compDcpi.form_name)} <small>(${this.escapeHtml(compDcpi.date)})</small></span>
          </div>
        `;
      }

      return `
        <div class="dc-dossier-modal" data-step="summary">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Zusammenfassung</span>
            <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-dossier-body">
            <p class="dc-dossier-hint">Folgende Daten werden für die Dossier-Generierung verwendet:</p>
            <div class="dc-dossier-summary">
              ${summaryHtml}
            </div>
          </div>
          <div class="dc-dossier-footer">
            <div class="dc-dossier-steps">Schritt ${currentStep} von ${totalSteps}</div>
            <div class="dc-dossier-actions">
              <button type="button" class="dc-dossier-btn dc-dossier-btn-back">Zurück</button>
              <button type="button" class="dc-dossier-btn dc-dossier-btn-create">Dossier-Generierung starten</button>
            </div>
          </div>
        </div>
      `;
    },

    bindSummaryEvents: function () {
      const self = this;

      $(".dc-dossier-close").on("click", function () {
        Swal.close();
      });

      $(".dc-dossier-btn-back").on("click", function () {
        if (self.needsComparisonStep()) {
          self.renderStep3();
        } else {
          self.renderStep2();
        }
      });

      $(".dc-dossier-btn-create").on("click", function () {
        self.create();
      });
    },

    // =====================================================
    // CREATE DOSSIER
    // =====================================================

    create: function () {
      const self = this;

      // Show loading state in modal
      self.renderProcessing();

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_create_dossier",
          nonce: deepClarityFrontend.nonce,
          client_id: self.clientId,
          anamnese_entry_id: self.selectedAnamneseEntryId,
          session_id: self.selectedSessionId,
          dcpi_entry_id: self.selectedDcpiEntryId || 0,
          comparison_session_id: self.selectedComparisonSessionId || 0,
          comparison_dcpi_entry_id: self.selectedComparisonDcpiEntryId || 0,
        },
        success: function (response) {
          if (response.success && response.data.request_id) {
            // Store request ID and start polling
            self.requestId = response.data.request_id;
            self.startPolling();
          } else {
            // Show error if webhook failed
            self.renderErrors(response.data.message || "Ein Fehler ist aufgetreten.", [], []);
          }
        },
        error: function () {
          self.renderErrors("Ein Verbindungsfehler ist aufgetreten. Bitte versuchen Sie es erneut.", [], []);
        },
      });
    },

    /**
     * Start polling for dossier status
     */
    startPolling: function () {
      const self = this;

      // Clear any existing timer
      if (self.pollTimer) {
        clearInterval(self.pollTimer);
      }

      // Start polling - step progression is now controlled by backend status
      self.pollTimer = setInterval(function () {
        self.checkStatus();
      }, self.pollInterval);
    },

    /**
     * Check dossier status via AJAX
     */
    checkStatus: function () {
      const self = this;

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "deep_clarity_check_dossier_status",
          nonce: deepClarityFrontend.nonce,
          request_id: self.requestId,
        },
        success: function (response) {
          if (response.success) {
            const status = response.data.status;

            if (status === "complete") {
              // Stop polling and start PDF generation
              self.stopPolling();
              self.lastDossierId = response.data.dossier_id;
              self.updateProcessingStep(3);
              self.generatePdf(response.data.dossier_id);
            } else if (status === "processing") {
              // Structural analysis done, now generating dossier - update to step 2
              if (self.processingStep !== 2) {
                self.updateProcessingStep(2);
              }
              // Continue polling for completion
            } else if (status === "error") {
              // Stop polling and show errors
              self.stopPolling();
              self.renderErrors(
                response.data.error || "Ein Fehler ist aufgetreten.",
                response.data.errors || [],
                response.data.warnings || []
              );
            }
            // If status is "pending", continue polling (step 1)
          } else {
            // Error checking status - stop polling and show error
            self.stopPolling();
            self.renderErrors(response.data.message || "Statusabfrage fehlgeschlagen.", [], []);
          }
        },
        error: function () {
          // Network error - stop polling and show error
          self.stopPolling();
          self.renderErrors("Verbindungsfehler bei der Statusabfrage.", [], []);
        },
      });
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
     * Generate PDF for the dossier
     */
    generatePdf: function (dossierId) {
      const self = this;

      $.ajax({
        url: deepClarityFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "dc_create_dossier_pdf",
          nonce: deepClarityFrontend.pdfNonce || deepClarityFrontend.nonce,
          dossier_id: dossierId,
        },
        success: function (response) {
          if (response.success && response.data.pdf_url) {
            self.pdfUrl = response.data.pdf_url;
            self.renderCompleted(dossierId, self.pdfUrl);
          } else {
            // PDF generation failed, but dossier was created
            self.renderCompleted(dossierId, null, response.data?.message || "PDF-Generierung fehlgeschlagen");
          }
        },
        error: function () {
          // Network error during PDF generation
          self.renderCompleted(dossierId, null, "Verbindungsfehler bei PDF-Generierung");
        },
      });
    },

    /**
     * Render completed state with all steps green
     */
    renderCompleted: function (dossierId, pdfUrl, pdfError) {
      const self = this;

      // Build buttons
      let buttonsHtml = '';
      if (pdfUrl) {
        buttonsHtml = `
          <a href="${self.escapeHtml(pdfUrl)}" target="_blank" class="dc-dossier-btn dc-dossier-btn-primary">Dossier PDF ansehen</a>
          <button type="button" class="dc-dossier-btn dc-dossier-btn-secondary dc-dossier-btn-reload">Seite neu laden</button>
        `;
      } else {
        buttonsHtml = `
          <button type="button" class="dc-dossier-btn dc-dossier-btn-primary dc-dossier-btn-reload">Seite neu laden</button>
        `;
      }

      // Build error message if PDF failed
      let errorHtml = '';
      if (pdfError) {
        errorHtml = `<p class="dc-dossier-processing-error">${self.escapeHtml(pdfError)}</p>`;
      }

      const completedHtml = `
        <div class="dc-dossier-modal" data-step="completed">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Dossier erstellt</span>
          </div>
          <div class="dc-dossier-body dc-dossier-body-centered">
            <div class="dc-dossier-processing-steps">
              <div class="dc-processing-step done">
                <div class="dc-processing-step-indicator">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div class="dc-processing-step-text">
                  <span class="dc-processing-step-title">Strukturanalyse abgeschlossen</span>
                  <span class="dc-processing-step-desc">Daten wurden analysiert und vorbereitet</span>
                </div>
              </div>
              <div class="dc-processing-step done">
                <div class="dc-processing-step-indicator">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div class="dc-processing-step-text">
                  <span class="dc-processing-step-title">Dossier-Generierung abgeschlossen</span>
                  <span class="dc-processing-step-desc">KI hat das personalisierte Dossier erstellt</span>
                </div>
              </div>
              <div class="dc-processing-step ${pdfUrl ? 'done' : 'error'}">
                <div class="dc-processing-step-indicator">
                  ${pdfUrl
                    ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
                  }
                </div>
                <div class="dc-processing-step-text">
                  <span class="dc-processing-step-title">${pdfUrl ? 'PDF-Generierung abgeschlossen' : 'PDF-Generierung fehlgeschlagen'}</span>
                  <span class="dc-processing-step-desc">${pdfUrl ? 'PDF-Dokument wurde erzeugt' : 'Das Dossier wurde trotzdem erstellt'}</span>
                </div>
              </div>
            </div>
            ${errorHtml}
          </div>
          <div class="dc-dossier-footer dc-dossier-footer-centered">
            ${buttonsHtml}
          </div>
        </div>
      `;

      Swal.fire({
        title: null,
        html: completedHtml,
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          $(".dc-dossier-btn-reload").on("click", function () {
            Swal.close();
            window.location.reload();
          });
        },
      });
    },

    /**
     * Render processing/loading state with three-step progress
     */
    renderProcessing: function () {
      const self = this;
      self.processingStep = 1;

      Swal.fire({
        title: null,
        html: self.getProcessingTemplate(),
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        allowOutsideClick: false,
        allowEscapeKey: false,
      });
    },

    /**
     * Get processing template HTML
     */
    getProcessingTemplate: function () {
      const step = this.processingStep;
      const step1Active = step === 1 ? " active" : "";
      const step1Done = step > 1 ? " done" : "";
      const step2Active = step === 2 ? " active" : "";
      const step2Done = step > 2 ? " done" : "";
      const step3Active = step === 3 ? " active" : "";

      const step1Text = step === 1 ? "Strukturanalyse läuft..." : "Strukturanalyse abgeschlossen";
      const step2Text = step === 2 ? "Dossier wird generiert..." : (step > 2 ? "Dossier-Generierung abgeschlossen" : "Dossier-Generierung");
      const step3Text = step === 3 ? "PDF wird erstellt..." : "PDF-Generierung";

      return `
        <div class="dc-dossier-modal" data-step="processing">
          <div class="dc-dossier-header">
            <span class="dc-dossier-title">Dossier wird erstellt</span>
          </div>
          <div class="dc-dossier-body dc-dossier-body-centered">
            <div class="dc-dossier-processing-steps">
              <div class="dc-processing-step${step1Active}${step1Done}">
                <div class="dc-processing-step-indicator">
                  ${step > 1 ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>' : '<span class="dc-processing-spinner"></span>'}
                </div>
                <div class="dc-processing-step-text">
                  <span class="dc-processing-step-title">${step1Text}</span>
                  <span class="dc-processing-step-desc">Daten werden analysiert und vorbereitet</span>
                </div>
              </div>
              <div class="dc-processing-step${step2Active}${step2Done}">
                <div class="dc-processing-step-indicator">
                  ${step > 2 ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>' : (step === 2 ? '<span class="dc-processing-spinner"></span>' : '<span class="dc-processing-step-number">2</span>')}
                </div>
                <div class="dc-processing-step-text">
                  <span class="dc-processing-step-title">${step2Text}</span>
                  <span class="dc-processing-step-desc">KI erstellt das personalisierte Dossier</span>
                </div>
              </div>
              <div class="dc-processing-step${step3Active}">
                <div class="dc-processing-step-indicator">
                  ${step === 3 ? '<span class="dc-processing-spinner"></span>' : '<span class="dc-processing-step-number">3</span>'}
                </div>
                <div class="dc-processing-step-text">
                  <span class="dc-processing-step-title">${step3Text}</span>
                  <span class="dc-processing-step-desc">PDF-Dokument wird erzeugt</span>
                </div>
              </div>
            </div>
            <p class="dc-dossier-processing-hint">Bitte warten Sie, dieser Vorgang kann einen Moment dauern.</p>
          </div>
        </div>
      `;
    },

    /**
     * Update processing step display
     */
    updateProcessingStep: function (step) {
      this.processingStep = step;
      const $container = $(".dc-dossier-modal[data-step='processing']");
      if ($container.length) {
        $container.replaceWith($(this.getProcessingTemplate()));
      }
    },

    /**
     * Render success state
     */
    renderSuccess: function (message, dossierId, pdfUrl) {
      const self = this;

      // Build buttons based on PDF availability
      let buttonsHtml = '';
      if (pdfUrl) {
        buttonsHtml = `
          <a href="${self.escapeHtml(pdfUrl)}" target="_blank" class="dc-dossier-btn dc-dossier-btn-primary">PDF ansehen</a>
          <button type="button" class="dc-dossier-btn dc-dossier-btn-secondary dc-dossier-btn-reload">Seite neu laden</button>
        `;
      } else {
        buttonsHtml = `
          <button type="button" class="dc-dossier-btn dc-dossier-btn-primary dc-dossier-btn-reload">Seite neu laden</button>
        `;
      }

      Swal.fire({
        title: null,
        html: `
          <div class="dc-dossier-modal" data-step="success">
            <div class="dc-dossier-header">
              <span class="dc-dossier-title">Dossier erstellt</span>
            </div>
            <div class="dc-dossier-body dc-dossier-body-centered">
              <div class="dc-dossier-success">
                <svg class="dc-dossier-success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="16 10 11 15 8 12"></polyline></svg>
                <p>${self.escapeHtml(message || "Das Dossier wurde erfolgreich erstellt.")}</p>
              </div>
            </div>
            <div class="dc-dossier-footer dc-dossier-footer-centered">
              ${buttonsHtml}
            </div>
          </div>
        `,
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          $(".dc-dossier-btn-reload").on("click", function () {
            Swal.close();
            // Reload page to show new dossier
            window.location.reload();
          });
        },
      });
    },

    /**
     * Render validation errors
     */
    renderErrors: function (message, errors, warnings) {
      const self = this;

      let errorsHtml = "";

      // Build errors list
      if (errors && Array.isArray(errors) && errors.length > 0) {
        errorsHtml += '<div class="dc-dossier-errors-section"><strong>Fehler:</strong><ul class="dc-dossier-errors-list">';
        errors.forEach(function (error) {
          errorsHtml += `<li>${self.escapeHtml(error)}</li>`;
        });
        errorsHtml += "</ul></div>";
      }

      // Build warnings list
      if (warnings && Array.isArray(warnings) && warnings.length > 0) {
        errorsHtml += '<div class="dc-dossier-warnings-section"><strong>Warnungen:</strong><ul class="dc-dossier-warnings-list">';
        warnings.forEach(function (warning) {
          errorsHtml += `<li>${self.escapeHtml(warning)}</li>`;
        });
        errorsHtml += "</ul></div>";
      }

      Swal.fire({
        title: null,
        html: `
          <div class="dc-dossier-modal" data-step="error">
            <div class="dc-dossier-header">
              <span class="dc-dossier-title">Validierungsfehler</span>
              <button type="button" class="dc-dossier-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
            </div>
            <div class="dc-dossier-body">
              <div class="dc-dossier-error-message">
                <svg class="dc-dossier-error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <p>${self.escapeHtml(message)}</p>
              </div>
              ${errorsHtml}
            </div>
            <div class="dc-dossier-footer">
              <div class="dc-dossier-steps"></div>
              <div class="dc-dossier-actions">
                <button type="button" class="dc-dossier-btn dc-dossier-btn-back">Zurück zur Auswahl</button>
                <button type="button" class="dc-dossier-btn dc-dossier-btn-cancel">Abbrechen</button>
              </div>
            </div>
          </div>
        `,
        showConfirmButton: false,
        showCancelButton: false,
        width: "550px",
        padding: 0,
        customClass: {
          popup: "dc-dossier-popup",
          htmlContainer: "dc-dossier-container",
        },
        didOpen: function () {
          $(".dc-dossier-close, .dc-dossier-btn-cancel").on("click", function () {
            Swal.close();
          });

          $(".dc-dossier-btn-back").on("click", function () {
            // Go back to summary step
            self.renderSummary();
          });
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
            <div class="dc-note-edit-header-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            </div>
            <div class="dc-note-edit-header-content">
              <div class="dc-note-edit-title">Neue Notiz erstellen</div>
              <div class="dc-note-edit-subtitle">Füge eine persönliche Notiz hinzu</div>
            </div>
            <button type="button" class="dc-note-edit-close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.29 19.7c.39.39 1.02.39 1.41 0 .39-.4.39-1.03 0-1.42l-6.3-6.3 6.29-6.3c.39-.4.39-1.03 0-1.42 -.4-.4-1.03-.4-1.42 0l-6.3 6.29 -6.3-6.3c-.4-.4-1.03-.4-1.42 0 -.4.39-.4 1.02 0 1.41l6.29 6.29 -6.3 6.29c-.4.39-.4 1.02 0 1.41 .39.39 1.02.39 1.41 0l6.29-6.3 6.29 6.29Z"></path></svg></button>
          </div>
          <div class="dc-note-edit-body">
            <label for="dc-note-content">Notiz</label>
            <textarea id="dc-note-content" placeholder="Schreibe hier deine Notiz..."></textarea>
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
