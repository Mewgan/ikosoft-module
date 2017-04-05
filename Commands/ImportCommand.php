<?php

namespace Jet\Modules\Ikosoft\Commands;

use Jet\Modules\Ikosoft\Controllers\ImportController;
use JetFire\Framework\App;
use JetFire\Framework\Commands\Command;
use JetFire\Framework\Providers\LogProvider;
use JetFire\Framework\System\Controller;
use Monolog\Logger;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class ImportCommand
 * @package Jet\Modules\Ikosoft\Commands
 */
class ImportCommand extends Command
{

    /**
     * @var Logger mixed
     */
    private $logger;

    /**
     * ImportController constructor.
     * @param App $app
     * @param LogProvider $logProvider
     */
    public function __construct(App $app, LogProvider $logProvider)
    {
        parent::__construct($app);
        $this->logger = $logProvider->getLogger('import');
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('import:ikosoft:data')
            ->setDescription('Import data from xml files')
            ->addArgument('path', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The directory or file path.')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Select the website theme', null)
            ->addOption('activate', 'a', InputOption::VALUE_NONE, 'Activate the instance')
            ->setHelp(<<<EOT
The <info>jet import:data path/to/load</info> command import xml data to your database 
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $responses = $this->loadData($input, $output);
        $output->writeln('');

        if (is_array($responses) && !$output->isQuiet()) {
            if (isset($responses['message'])) $responses = [$responses];
            foreach ($responses as $response) {
                if (isset($response['message']))
                    $output->writeln(sprintf(' <comment>></comment> <' . $response['status'] . '>%s</' . $response['status'] . '>', $response['message']));
            }
        }

        return null;

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array|bool|mixed
     */
    private function loadData(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $files = is_dir($path[0]) ? glob_recursive($path[0] . '/*.zip', GLOB_BRACE) : $path;

        /** @var Controller $controller */
        $controller = $this->app->get('JetFire\Framework\System\Controller');

        $totalFiles = count($files);
        $importedFiles = $updatedFiles = 0;
        $progress = new ProgressBar($output, $totalFiles);
        $progress->setFormat('verbose');
        $progress->start();

        /** @var ImportController $import */
        $import = $controller->callController('Jet\Modules\Ikosoft\Controllers\ImportController');
        if ($input->getOption('activate')) $import->setParams(['activate' => 1]);
        if ($input->getOption('theme')) $import->setParams(['theme' => $input->getOption('theme')]);

        $import->loadGlobalData();

        $responses = [];

        foreach ($files as $file) {
            $response = $import->load($file);
            if (is_array($response) && $response['status'] == 'error') {
                if ($output->isVerbose()) {
                    $responses[] = $response;
                } else {
                    $this->logger->addError('Ikosoft => ' . $response['message']);
                }
            }
            if ($response == 'create') ++$importedFiles;
            elseif ($response == 'update') ++$updatedFiles;
            $progress->advance();
        }

        $progress->finish();

        $responses[] = ['status' => 'comment', 'message' => '<info>' . $importedFiles . '</info> created | <info>' . $updatedFiles . '</info> updated | <info>' . ($importedFiles + $updatedFiles) . '/' . $totalFiles . '</info> files loaded'];

        return $responses;
    }

} 