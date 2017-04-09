<?php

namespace Jet\Modules\Ikosoft\Controllers;

use Jet\Modules\Ikosoft\Models\IkosoftImport;
use Jet\Modules\Ikosoft\Requests\IkosoftRequest;
use Jet\Services\Recaptcha;
use JetFire\Framework\Providers\ConsoleProvider;
use JetFire\Framework\System\Controller;
use JetFire\Framework\System\Mail;
use JetFire\Framework\System\View;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class FrontImportController
 * @package Jet\Modules\Ikosoft\Controllers
 */
class FrontImportController extends Controller
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
            $data['path'] = $this->findInstancePath($this->app->data['app']['imports']['ikosoft']['path'], $data['uid']);
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
        foreach ($files as $file) {
            $instance = pathinfo($file);
            if ($instance['filename'] == $uid) return $file;
        }
        return null;
    }

    /**
     * @param IkosoftRequest $request
     * @param View $view
     * @param Mail $mail
     * @param Recaptcha $captcha
     * @param $theme
     * @return array
     */
    public function register(IkosoftRequest $request, View $view, Mail $mail, Recaptcha $captcha, ConsoleProvider $console, NullOutput $output, $theme)
    {
        if ($request->method() == 'POST') {
            $response = $request->validate();
            if ($response === true) {
                $values = $request->values();
                if ($captcha->isValid($values['captcha'])) {
                    if(is_file($values['_path'])) {

                        if(IkosoftImport::where('uid', $values['_uid'])->count() == 0){
                            $cli = $console->getCli();
                            $cli->setAutoExit(false);

                            $input = new ArrayInput(['command' => 'import:ikosoft:data', '-a', $values['_path']]);
                            $cli->run($input, $output);
                            if(IkosoftImport::where('uid', $values['_uid'])->count() == 1){
                                $domain = (isset($this->app->data['setting']['domain'])) ? $this->app->data['setting']['domain'] : '';
                              
                                $content = $this->render('Mail/account_created', compact('full_url'));
                                return (!$mail->sendTo($values['account']['email'], 'Confirmation d\'inscription', $content))
                                    ? ['status' => 'error', 'message' => 'Erreur lors de l\'envoie du mail']
                                    : ['status' => 'success', 'message' => 'Merci de votre inscription ! Vous allez recevoir un mail pour valider votre inscription'];       
                            }
                            return ['status' => 'error', 'message' => 'Erreur lors de l\'import de vos données. Veuillez contacter l\'administrateur pour en savoir plus.'];
                        }
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