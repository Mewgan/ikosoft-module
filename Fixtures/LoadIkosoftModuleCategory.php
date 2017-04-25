<?php
namespace Jet\Modules\Ikosoft\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Jet\Services\LoadFixture;

/**
 * Class LoadIkosoftModuleCategory
 * @package Jet\Modules\Ikosoft\Fixtures
 */
class LoadIkosoftModuleCategory extends AbstractFixture implements OrderedFixtureInterface
{
    use LoadFixture;

    /**
     * @var array
     */
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

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->loadModuleCategory($manager);
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1000;
    }
}