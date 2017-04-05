<?php
namespace Jet\Modules\Ikosoft\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Jet\Services\LoadFixture;

class LoadIkosoftModule extends AbstractFixture implements DependentFixtureInterface
{
    use LoadFixture;

    protected $data = [
        'module_ikosoft' => [
            'name' => 'Ikosoft',
            'slug' => 'ikosoft',
            'callback' => '',
            'description' => 'Module dédié pour Ikosoft',
            'category' => 'ikosoft',
            'access_level' => 4
        ],
    ];

    public function load(ObjectManager $manager)
    {
        $this->loadModule($manager);
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    function getDependencies()
    {
        return [
            'Jet\Modules\Ikosoft\Fixtures\LoadIkosoftModuleCategory'
        ];
    }
}