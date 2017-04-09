function registerUser(api) {
    jQuery("#register_form").validate({
        meta: "validate",
        submitHandler: function (e) {
            $("#register_form .btn-submit").hide();
            $("#register_form .btn-loading").show();
            var ps = $("#password").val(),
                cps = $("#confirm_pass").val(),
                uid = $("#uid").val();
                path = $("#path").val();
                token = $("#token").val();
                captcha = $("#g-recaptcha-response").val();
            return $.post(api, {
                account: {
                    password: ps,
                    confirm_pass: cps
                },
                _uid: uid,
                _path: path,
                _token: token,
                captcha: captcha
            }, function (response) {
                $("#register_form .btn-submit").show();
                $("#register_form .btn-loading").hide();
                $("#response-message").show();
                (response.status == 'error')
                    ? $("#response-message").addClass('alert-danger')
                    : $("#response-message").addClass('alert-success');
                var message = '<div>';
                if(response.message instanceof Array || response.message instanceof Object){
                    response.message.forEach(function (el) {
                        el.forEach(function (item) {
                            message += '<p>'+item+'</p>';
                        })
                    })
                }else
                    message += response.message;
                $("#response-message span").html(message + '</div>');
            }), !1
        },
        rules: {
            account: {
                password: {
                    required: true,
                    minlength: 5
                },
                confirm_pass: {
                    required: true,
                    minlength: 5,
                    equalTo: '#password'
                }
            },
            captcha: "required",
            _uid: "required",
            _path: "required",
            _token: "required"
        },
        messages: {
            "account[password]": {
                required: "Le mot de passe est requis",
                minlength: "Le mot de passe doit contenir minimum 5 caractères"
            },
            "account[confirm_pass]": {
                required: "La confirmation du mot de passe est requis",
                minlength: "Le mot de passe doit contenir minimum 5 caractères",
                equalTo: "Les mots de passe ne sont pas identiques"
            },
            captcha: "Le captcha est requis",
            _uid: "L'id du service est requis",
            _path: "Le chemin du flux xml est requis",
            _token: "Le token est requis"
        }
    })
}
