<?php

namespace Jet\Modules\Ikosoft\Requests;

use JetFire\Framework\System\Request;

class IkosoftRequest extends Request
{

    public static $messages = [
        'required' => 'Tout les champs précédé d\'un astérix doivent être remplis',
        'noWhitespace' => 'Le mot de passe ne doit pas contenir d\'espace',
        'same' => 'Les 2 mots de passe doivent être identiques',
    ];


    public static function rules()
    {
        return [
            'account.confirm_pass' => 'required|noWhitespace',
            'account.password' => 'required|noWhitespace|same:account.confirm_pass|assign:crypt,password_hash',
            'captcha' => 'required',
            '_uid|_pat|_token' => 'required',
            'token' => 'assign:' . md5(uniqid(rand(), true)),
        ];
    }

}