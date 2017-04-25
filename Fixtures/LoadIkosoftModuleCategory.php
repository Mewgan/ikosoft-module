<?php
namespace Jet\Modules\Ikosoft\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Jet\Services\LoadFixture;

class LoadIkosoftModuleCategory extends AbstractFixture
{
    use LoadFixture;

    protected $data = [
        'name' => 'Ikosoft',
        'title' => 'Ikosoft',
        'slug' => 'ikosoft',
        'nav' => true,
        'description' => 'Module dédié pour ikosoft',
        'icon' => 'fa fa-scissors',
        'author' => 'S.Sumugan',
        'version' => '0.1',
        'update_available' => false,
        'access_level' => 4
    ];

    public function load(ObjectManager $manager)
    {
        $this->loadModuleCategory($manager);
    }
}