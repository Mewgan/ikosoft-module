<?php

namespace Jet\Modules\Ikosoft\Import;

use Jet\Modules\Ikosoft\Controllers\ImportController;

/**
 * Class LoadCustomField
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadFixture
{
    /**
     * @var ImportController
     */
    protected $import;

    /**
     * @param ImportController $import
     */
    public function __construct(ImportController $import)
    {
        $this->import = $import;
    }

    /**
     * @param $module
     * @return bool
     */
    protected function hasModule($module)
    {
        if (isset($this->import->data['website_modules'])) {
            $modules = is_string($this->import->data['website_modules']) ? json_decode($this->import->data['website_modules'], true) : $this->import->data['website_modules'];
            return (isset($this->import->global_data['modules'][$module]))
                ? (in_array($this->import->global_data['modules'][$module], $modules))
                : false;
        }
        return false;
    }

}