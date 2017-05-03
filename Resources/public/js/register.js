jQuery(document).ready(function(){
    jQuery("#register_form").validate({
        meta: "validate",
        submitHandler: function (e) {
            $("#register_form .btn-submit").hide();
            $("#register_form .btn-loading").show();
            var api = $('#register_form').attr('action'),
                st = $("#society").val(),
                em = $("#email").val(),
                ps = $("#password").val(),
                cps = $("#confirm_pass").val(),
                uid = $("#uid").val();
            path = $("#path").val();
            token = $("#token").val();
            captcha = $("#g-recaptcha-response").val();
            return $.post(api, {
                account: {
                    email: em,
                    password: ps,
                    confirm_pass: cps
                },
                society: st,
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
                if (response.message instanceof Array || response.message instanceof Object) {
                    $.each(response.message, function (k, el) {
                        $.each(el, function (l, item) {
                            message += '<p>' + item + '</p>';
                        })
                    })
                } else
                    message += response.message;
                $("#response-message span").html(message + '</div>');
            }), !1
        },
        rules: {
            account: {
                email: {
                    required: true,
                    email: true
                },
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
            society: "required",
            captcha: "required",
            _uid: "required",
            _path: "required",
            _token: "required"
        },
        messages: {
            "account[email]": {
                required: "L'email est requis",
                email: "Le format du mail est incorrect"
            },
            "account[password]": {
                required: "Le mot de passe est requis",
                minlength: "Le mot de passe doit contenir minimum 5 caractères"
            },
            "account[confirm_pass]": {
                required: "La confirmation du mot de passe est requis",
                minlength: "Le mot de passe doit contenir minimum 5 caractères",
                equalTo: "Les mots de passe ne sont pas identiques"
            },
            society: "Le nom de la société est requis",
            captcha: "Le captcha est requis",
            _uid: "L'id du service est requis",
            _path: "Le chemin du flux xml est requis",
            _token: "Le token est requis"
        }
    });
});
