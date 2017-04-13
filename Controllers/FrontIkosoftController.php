<?php

namespace Jet\Modules\Ikosoft\Controllers;

use Jet\Models\Account;
use Jet\Modules\Ikosoft\Models\IkosoftImport;
use Jet\Modules\Ikosoft\Requests\IkosoftRequest;
use Jet\Services\Recaptcha;
use JetFire\Framework\System\Controller;
use JetFire\Framework\System\Mail;

/**
 * Class FrontIkosoftController
 * @package Jet\Modules\Ikosoft\Controllers
 */
class FrontIkosoftController extends Controller
{

    /**
     * @param IkosoftRequest $request
     * @return array|null
     */
    public function registration(IkosoftRequest $request)
    {
        $template = ROOT . '/src/Modules/Ikosoft/Views/Registration/index.html.twig';
        if ($request->has('uid')) {
            $data['uid'] = $request->get('uid');
            $data['path'] = $this->findInstancePath($this->app->data['setting']['imports']['ikosoft']['path'], $data['uid']);
            if (!is_null($data['path'])) {
                return compact('template', 'data');
            }
        }
        return null;
    }

    /**
     * @param $path
     * @param $uid
     * @return null
     */
    private function findInstancePath($path, $uid)
    {
        $path = rtrim($path, '/') . '/';
        $files = glob_recursive($path . '*.zip', GLOB_BRACE);
        $files = array_reverse($files);
        foreach ($files as $file) {
            $instance = pathinfo($file);
            if ($instance['filename'] == $uid) return $file;
        }
        return null;
    }

    /**
     * @param IkosoftRequest $request
     * @param Mail $mail
     * @param Recaptcha $captcha
     * @param $theme
     * @return array
     */
    public function register(IkosoftRequest $request, Mail $mail, Recaptcha $captcha, $theme)
    {
        if ($request->method() == 'POST') {
            $response = $request->validate();
            if ($response === true) {
                $values = $request->values();
                if ($captcha->isValid($values['captcha'])) {
                    if (is_file($values['_path'])) {

                        if (IkosoftImport::where('uid', $values['_uid'])->count() == 0) {
                            exec('php jet import:ikosoft:data ' . $values['_path'] . ' -a --theme=' . $theme);
                            if (IkosoftImport::where('uid', $values['_uid'])->count() == 1) {

                                $import = IkosoftImport::repo()->getImportAccount($values['_uid']);
                                if (isset($import['website']['society']['account']['email']) && isset($import['website']['domain'])) {

                                    if(Account::where('id', $import['website']['society']['account']['id'])->set(['password' => $values['account']['password']])) {

                                        $full_url = (substr($import['website']['domain'], 0, 4) === 'http')
                                            ? $import['website']['domain']
                                            : rtrim($this->app->data['setting']['domain'], '/') . '/site/' . $import['website']['domain'];

                                        $content = $this->render('Mail/account_created', [
                                            'full_url' => $full_url,
                                            'account' => $import['website']['society']['account'],
                                            'admin_domain' => $this->app->data['setting']['admin_domain']
                                        ]);
                                        return (!$mail->sendTo($import['website']['society']['account']['email'], 'Confirmation d\'inscription', $content))
                                            ? ['status' => 'error', 'message' => 'Erreur lors de l\'envoie du mail']
                                            : ['status' => 'success', 'message' => 'Merci de votre inscription ! Vous allez recevoir un mail de confirmation d\'inscription'];
                                    }
                                }
                            }
                            return ['status' => 'error', 'message' => 'Erreur lors de l\'import de vos données. Veuillez contacter l\'administrateur pour en savoir plus.'];
                        }
                        return ['status' => 'error', 'message' => 'Compte existant'];
                    }
                return ['status' => 'error', 'message' => 'Impossible de trouver le fichier d\'import'];
                }
                $response = ['status' => 'error', 'message' => 'Captcha invalide !'];
            }
            return $response;
        }
        return ['status' => 'error', 'message' => 'Requête non autorisée !'];
    }

}