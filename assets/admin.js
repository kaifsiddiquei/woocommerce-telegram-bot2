const jQuery = window.jQuery
const wc_telegram_ajax = window.wc_telegram_ajax

jQuery(document).ready(($) => {
  // Test Telegram connection
  $("#test-connection").on("click", function () {
    var button = $(this)
    var resultDiv = $("#connection-result")

    button.prop("disabled", true).text("Testing...")

    $.ajax({
      url: wc_telegram_ajax.ajax_url,
      type: "POST",
      data: {
        action: "test_telegram_connection",
        nonce: wc_telegram_ajax.nonce,
      },
      success: (response) => {
        if (response.success) {
          resultDiv.html('<div class="notice notice-success"><p>' + response.message + "</p></div>")
        } else {
          resultDiv.html('<div class="notice notice-error"><p>' + response.message + "</p></div>")
        }
      },
      error: () => {
        resultDiv.html('<div class="notice notice-error"><p>Connection test failed</p></div>')
      },
      complete: () => {
        button.prop("disabled", false).text("Test Connection")
      },
    })
  })

  // Add new group
  $("#add-group-form").on("submit", function (e) {
    e.preventDefault()

    var formData = $(this).serialize()
    formData += "&action=add_telegram_group&nonce=" + wc_telegram_ajax.nonce

    $.ajax({
      url: wc_telegram_ajax.ajax_url,
      type: "POST",
      data: formData,
      success: (response) => {
        if (response.success) {
          alert("Group added successfully!")
          location.reload()
        } else {
          alert("Error: " + response.data)
        }
      },
      error: () => {
        alert("Failed to add group")
      },
    })
  })

  // Delete group
  $(".delete-group").on("click", function () {
    if (!confirm("Are you sure you want to delete this group?")) {
      return
    }

    var groupId = $(this).data("id")
    var row = $(this).closest("tr")

    $.ajax({
      url: wc_telegram_ajax.ajax_url,
      type: "POST",
      data: {
        action: "delete_telegram_group",
        group_id: groupId,
        nonce: wc_telegram_ajax.nonce,
      },
      success: (response) => {
        if (response.success) {
          row.fadeOut()
        } else {
          alert("Error: " + response.data)
        }
      },
      error: () => {
        alert("Failed to delete group")
      },
    })
  })
})
