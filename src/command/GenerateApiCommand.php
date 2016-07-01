<?php
namespace keeko\tools\command;

use gossi\swagger\Swagger;
use gossi\swagger\Tag;
use keeko\tools\generator\api\ApiGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateApiCommand extends AbstractKeekoCommand {

	private $needsResourceIdentifier = false;
	private $needsPagedMeta = false;

	protected function configure() {
		$this
			->setName('generate:api')
			->setDescription('Generates the api for the module')
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the actions should be generated, when there is no name argument (if ommited all models will be generated)'
			)
		;

		$this->configureGenerateOptions();

		parent::configure();
	}

	/**
	 * Checks whether api can be generated at all by reading composer.json and verify
	 * all required information are available
	 */
	private function check() {
		$module = $this->packageService->getModule();
		if ($module === null) {
			throw new \DomainException('No module definition found in composer.json - please run `keeko init`.');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->check();

		$module = $this->package->getKeeko()->getModule();
		$swagger = new Swagger();
		$swagger->setVersion('2.0');
		$swagger->getInfo()->setTitle($module->getTitle() . ' API');
		$swagger->getTags()->add(new Tag(['name' => $module->getSlug()]));

		// generate api from package
		$apigen = new ApiGenerator($this->service);
		$apigen->generatePaths($swagger);
		$apigen->generateDefinitions($swagger);

		// add custom entries from generator.json
		$custom = new Swagger($this->project->getGeneratorDefinition()->getApi());
		$swagger->getPaths()->addAll($custom->getPaths());
		$swagger->getDefinitions()->setAll($swagger->getDefinitions());

		// dump to file
		$filename = $this->project->getApiFileName();
		$this->jsonService->write($filename, $swagger->toArray());
		$this->io->writeln(sprintf('API for <info>%s</info> written at <info>%s</info>', $this->package->getFullName(), $filename));
	}
}