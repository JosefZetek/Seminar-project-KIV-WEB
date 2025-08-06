//Warning for group selection
document.getElementById('groupSelect').addEventListener('change', function () {

    $('.alert-warning').remove();

    //check what was set it the dropdown
    if(this.value === "0")
        return;

    showWarningAlert('Vaši registraci se zvolenou skupinou musí schválit nejprve již registrovaný uživatel zařazený do stejné nebo vyšší skupiny', "alert-warning");
});

//First name validation
document.getElementById("firstName").addEventListener('keyup', function () {
   if(this.value == null || this.value === "") {
       changeLabelText("firstnameLabel", "Jméno ❌");
       return;
   }
   changeLabelText("firstnameLabel", "Jméno ✅");
});

//Last name validation
document.getElementById("lastName").addEventListener('keyup', function () {
    if(this.value == null || this.value === "") {
        changeLabelText("lastnameLabel", "Příjmení ❌");
        return;
    }
    changeLabelText("lastnameLabel", "Příjmení ✅");
});

//Email validation
document.getElementById("email").addEventListener('change', function () {

    if(!matchEmail(this.value)) {
        changeLabelText("emailLabel", "E-mail ❌");
        return;
    }

    checkEmail(this.value).then(result => {
        if(result === true) {
            changeLabelText("emailLabel", "E-mail ✅");
            return;
        }
        changeLabelText("emailLabel", "E-mail ❌");
    });
});

//passwordValidation
document.getElementById("password").addEventListener('keyup', function () {
    passwordsMatchingHandler();
});

document.getElementById("passwordRepeated").addEventListener('keyup', function () {
    passwordsMatchingHandler();
});

function passwordsMatchingHandler() {
    const valuePassword = document.getElementById("password").value;
    const valuePasswordRepeated = document.getElementById("passwordRepeated").value;

    if(valuePassword === valuePasswordRepeated &&valuePassword.length >= 8) {
        changeLabelText("passwordRepeatedLabel", "Heslo znovu ✅");
        $(".alert-danger").remove();
        return;
    }
    changeLabelText("passwordRepeatedLabel", "Heslo znovu ❌");

    showWarningAlert("Heslo musí obsahovat alespoň 8 znaků a musí se shodovat.", "alert-danger");
}

function matchEmail(input) {
    return /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(input);
}

function checkEmail(input) {
    const data = {
        "email": input,
    };
    const strData = new URLSearchParams(data).toString();

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const jsonResult = JSON.parse(this.responseText).isEmailUsed;

                        if (jsonResult !== undefined) {
                            resolve(!jsonResult);
                        } else {
                            reject(new Error('Invalid JSON response'));
                        }
                    } catch (error) {
                        reject(new Error('Error parsing JSON: ' + error.message));
                    }
                } else {
                    reject(new Error('Error: ' + this.status + ' ' + this.statusText));
                }
            }
        };

        xhr.onerror = function () {
            reject(false);
        };

        xhr.open('POST', 'email_check.php');
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send(strData);
    });
}

function changeLabelText(labelID, newText) {
    let label = document.getElementById(labelID);
    if(label == null)
        return;

    label.innerText = newText;
}

/**
 * Shows bootstrap alert with information from the text
 * @param text Content of the alert
 * @param alertGroup Alert group (alert-warning/alert-danger, ...)
 */
function showWarningAlert(text, alertGroup) {
    $("." + alertGroup).remove();
    const alertElement = '<div class="alert ' + alertGroup + ' alert-dismissible fade show" role="alert">\n' +
        text +
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>\n' +
        '</div>';

    var alert = $(alertElement);
    $('body').append(alert);
}


/*
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong> guacamole!</strong> You should check in on some of those fields below.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
 */