function showAlert(title, text, icon) {
    Swal.fire({
        title: title,
        html: text,
        icon: icon,
    });
}


function logoutAndDeleteFunction(e) {
    var msg = e.getAttribute("data-msg");
    var method = e.getAttribute("data-method");
    var url = e.getAttribute("data-url");

    swal.fire({
        title: "Are you sure?",
        text: msg,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'continue',
        cancelButtonText: 'cancel',
        dangerMode: true,
    })
    .then((result) => {
        if (result.isConfirmed) {
            yourFunction(url,method);
        } else {
            swal("Your account is safe!");
        }
    });

}
function yourFunction(url,method) {
        $.ajax({
            url: url,
            type: method,
            headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                     },
            success: function(response) {
                if (response['reload'] != undefined) {
                    showAlert("Success", response.success, "success");
                    window.location.reload();
                }
                if (response['redirect'] != undefined) {
                    showAlert("Success", response.success, "success");
                    window.location.href = response['redirect'];
                }
            },
            error: function(xhr, status, error) {
                // Handle errors
            }
        });
    }

    function multipleerrorshandle(errors) {
        let message = '<ul style="text-align: left; list-style-type: none; padding-left: 0;">';
        for (var errorkey in errors) {
            // Laravel errors are usually arrays of messages
            let errorMessages = Array.isArray(errors[errorkey]) ? errors[errorkey] : [errors[errorkey]];
            errorMessages.forEach(msg => {
                message += '<li style="margin-bottom: 8px;"><i class="fas fa-exclamation-circle" style="color: #e74c3c; margin-right: 8px;"></i>' + msg + '</li>';
            });
        }
        message += '</ul>';
        
        Swal.fire({
            title: 'Validation Errors',
            html: message,
            icon: 'error',
            confirmButtonColor: '#3498db',
            customClass: {
                popup: 'rounded-lg shadow-xl'
            }
        });
    }

    function ajaxErrorHandling(data, msg){
        if (data.hasOwnProperty("responseJSON")) {
            var resp = data.responseJSON;
            if (resp.message == 'CSRF token mismatch.') {
                showAlert("Page has been expired and will reload in 2 seconds", "Page Expired!", "error");
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
                return;
            }
            if (resp.error) {
                var msg = (resp.error == '') ? 'Something went wrong!' : resp.error;
                showAlert(msg, "Error!", "error");
                return;
            }
            if (resp.message != 'The given data was invalid.') {
                showAlert(resp.message, "Error!", "error");
                return;
            }
            multipleerrorshandle(resp.errors);
        } else {
            showAlert(msg + "!", "Error!", 'error');
        }
        return;
    }
    //post
    function showAlert(title, text, icon) {
    Swal.fire({
        title: title,
        html: text,
        icon: icon,
    });
}

function logoutAndDeleteFunction(e) {
    var msg = e.getAttribute("data-msg");
    var method = e.getAttribute("data-method");
    var url = e.getAttribute("data-url");

    Swal.fire({
        title: "Are you sure?",
        text: msg,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (result.isConfirmed) {
            yourFunction(url, method);
        }

    });
}

function yourFunction(url, method) {

    $.ajax({
        url: url,
        type: method,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

        success: function(response) {

            if (response.reload !== undefined) {
                showAlert("Success", response.success, "success");
                window.location.reload();
                return;
            }

            if (response.redirect !== undefined) {
                showAlert("Success", response.success, "success");
                window.location.href = response.redirect;
                return;
            }
        },

        error: function(xhr) {
            ajaxErrorHandling(xhr, 'Delete Failed');
        }
    });
}

function multipleerrorshandle(errors) {

    let message =
        '<ul style="text-align:left;list-style-type:none;padding-left:0;">';

    for (var errorkey in errors) {

        let errorMessages = Array.isArray(errors[errorkey])
            ? errors[errorkey]
            : [errors[errorkey]];

        errorMessages.forEach(msg => {
            message +=
                '<li style="margin-bottom:8px;"><i class="fas fa-exclamation-circle" style="color:#e74c3c;margin-right:8px;"></i>' +
                msg +
                '</li>';
        });
    }

    message += '</ul>';

    Swal.fire({
        title: 'Validation Errors',
        html: message,
        icon: 'error',
        confirmButtonColor: '#3498db'
    });
}

function ajaxErrorHandling(data, msg) {

    if (data.hasOwnProperty("responseJSON")) {

        var resp = data.responseJSON;

        if (resp.message === 'CSRF token mismatch.') {

            showAlert(
                "Page has expired and will reload in 2 seconds",
                "Page Expired!",
                "error"
            );

            setTimeout(function() {
                window.location.reload();
            }, 2000);

            return;
        }

        if (resp.error) {
            showAlert("Error", resp.error, "error");
            return;
        }

        if (resp.errors) {
            multipleerrorshandle(resp.errors);
            return;
        }

        if (resp.message) {
            showAlert("Error", resp.message, "error");
            return;
        }
    }

    showAlert("Error", msg, "error");
}

function myAjax(url, formData, method = 'POST', callback = null, options = {}) {

    $.ajax({

        url: url,
        type: method,

        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },

        data: formData,

        contentType: false,
        processData: false,
        dataType: "json",

        beforeSend: function() {
            $('.save-btn').prop('disabled', true);
        },

        success: function(data) {

            if (data.reload !== undefined) {

                showAlert("Success", data.success, "success");

                setTimeout(function() {
                    window.location.reload();
                }, 1000);

                return;
            }

            if (data.redirect !== undefined) {

                showAlert("Success", data.success, "success");

                setTimeout(function() {
                    window.location.href = data.redirect;
                }, 1000);

                return;
            }

            if (data.status === true) {

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Saved successfully',
                    timer: 1200,
                    showConfirmButton: false
                });

                $('.modal').modal('hide');

                $('.myform').each(function() {
                    this.reset();
                });

                $('#id').val('');

                setTimeout(function() {
                    window.location.reload();
                }, 800);

                return;
            }

            if (typeof callback === 'function') {
                callback(data);
            }
        },

        error: function(jqXHR, textStatus, errorThrown) {

            console.log(jqXHR.responseText);

            ajaxErrorHandling(jqXHR, errorThrown);
        },

        complete: function() {

            $('.save-btn').prop('disabled', false);

            $(':submit').prop('disabled', false);
        }
    });
}





