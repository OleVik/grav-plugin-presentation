/**
 * Save raw Markdown via API
 */
function saveRawMarkdown() {
  var now = new Date();
  var autoSaveButton = document.querySelector("#presentation-save");
  var autoSaveButtonContent = autoSaveButton.innerHTML;
  var markdownContent = document.querySelector('textarea[name="data[content]"]')
    .value;
  var lastSaved = document.querySelector("#last-saved");
  var lastSavedValue = document.querySelector("#last-saved-value");
  autoSaveButton.style.background = presentationColors.update;
  autoSaveButton.innerHTML =
    '<i class="fa fa-circle-o-notch fa-spin fa-fw"></i> Saving';
  autoSaveButton.disabled = true;
  axios
    .post(
      presentationAPIRoute + "?action=save", {
        content: Base64.encode(markdownContent),
        route: window.GravAdmin.config.route
      }, {
        headers: {
          "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
        }
      }
    )
    .then(function (response) {
      if (response.status == 200) {
        console.info("Saved at", now, response.status, "OK");
        autoSaveButton.style.background = presentationColors.notice;
        autoSaveButton.innerHTML = '<i class="fa fa-check"></i> Saved';
        lastSaved.style.display = "block";
        lastSavedValue.innerHTML = now.toLocaleDateString("en-US", {
          year: "2-digit",
          month: "2-digit",
          day: "2-digit",
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit"
        });
      }
    })
    .catch(function (error) {
      console.error(error);
      autoSaveButton.style.background = presentationColors.critical;
      autoSaveButton.innerHTML = '<i class="fa fa-close"></i> Failed';
      autoSaveButton.disabled = false;
    })
    .then(function () {
      setTimeout(function () {
        autoSaveButton.style.background = presentationColors.button;
        autoSaveButton.innerHTML = autoSaveButtonContent;
        autoSaveButton.disabled = false;
      }, 500);
    });
}

var presentationPollingErrors = 0;
var presentationColors = {
  button: "#0090D9",
  notice: "#06A599",
  update: "#77559D",
  critical: "#F45857"
};
window.addEventListener(
  "load",
  function (event) {
    document.querySelector("#presentation-save").addEventListener(
      "click",
      function (event) {
        saveRawMarkdown();
        event.preventDefault();
      },
      false
    );

    if (presentationAdminAsyncSaveTyping === 1) {
      var markdownContent = document.querySelector(
        'textarea[name="data[content]"]  + .CodeMirror'
      );

      /* Debounce and throttle */
      /* @see https://codepen.io/dreit/pen/gedMez?editors=0010 */
      var forLastExec = 100;
      var delay = 500;
      var throttled = false;
      var calls = 0;

      markdownContent.onkeyup = function () {
        if (!throttled) {
          throttled = true;
          setTimeout(function () {
            throttled = false;
          }, delay);
        }
        clearTimeout(forLastExec);
        forLastExec = setTimeout(saveRawMarkdown, delay);
      };
    }
  },
  false
);