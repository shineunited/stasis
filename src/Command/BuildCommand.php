<?php

namespace ShineUnited\Stasis\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


class BuildCommand extends BaseCommand {

	protected function configure() {
		$this->setName('build');
		$this->setDescription('Build static site');
		//$this->setHelp('help goes here');

		$this->addArgument('source', InputArgument::REQUIRED, 'Path to source directory.');
		$this->addArgument('target', InputArgument::REQUIRED, 'Path to target directory.');

		//$this->addOption('css', 'c', InputOption::VALUE_NONE, 'Process css files.');
		//$this->addOption('js', 'j', InputOption::VALUE_NONE, 'Process js files.');

		//$this->addOption('include', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Include files that match pattern');
		//$this->addOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Exclude files that match pattern');

		$this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate build');

		$this->addOption('verify', null, InputOption::VALUE_NONE, 'Verify source directory prior to build');
		$this->addOption('clean', null, InputOption::VALUE_NONE, 'Clean target directory prior to build');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$sourceDir = rtrim($input->getArgument('source'), DIRECTORY_SEPARATOR);
		$targetDir = rtrim($input->getArgument('target'), DIRECTORY_SEPARATOR);

		$parseExtensions = ['htm', 'html', 'txt', 'text'];

		//$includePatterns = $input->getOption('include');
		//$excludePatterns = $input->getOption('exclude');

		$dryRun = $input->getOption('dry-run');

		if($input->getOption('verify')) {
			$verifyCommand = $this->getApplication()->find('verify');
			$verifyInput = new ArrayInput([
				'command' => 'verify',
				'source'  => $sourceDir
			]);

			$verifyReturn = $verifyCommand->run($verifyInput, $output);
		}

		if($input->getOption('clean')) {
			$cleanCommand = $this->getApplication()->find('clean');
			$cleanInput = new ArrayInput([
				'command'   => 'clean',
				'target'    => $targetDir,
				'--dry-run' => $dryRun
			]);

			$cleanReturn = $cleanCommand->run($cleanInput, $output);
		}

		$loader = new \Twig_Loader_Filesystem($sourceDir);
		$environment = new \Twig_Environment($loader);

		$filesystem = new Filesystem();

		$finder = new Finder();
		$finder->files();
		$finder->in($sourceDir);

		// ignore vcs files
		$finder->ignoreVCS(true);

		// exclude files that start with hyphen or underscore
		$finder->notName('/^[-_].*/');

		//foreach($includePatterns as $pattern) {
		//	$finder->name($pattern);
		//}

		//foreach($excludePatterns as $pattern) {
		//	$finder->notName($pattern);
		//}

		// follow symbolic links
		$finder->followLinks();

		// sort by name
		$finder->sortByName();

		foreach($finder as $file) {
			// $file->getExtension(); // file extension
			// $file->getRealPath(); // full path (absolute)
			// $file->getRelativePath(); // relative folder path
			// $file->getRelativePathname(); // relative file path

			$sourcePath = $sourceDir . '/' . $file->getRelativePathname();
			$targetPath = $targetDir . '/' . $file->getRelativePathname();

			if(!in_array($file->getExtension(), $parseExtensions)) {
				// asset file, copy directly
				$output->writeln('[static] ' . $file->getRelativePathname());

				if(!$dryRun) {
					$filesystem->copy($sourcePath, $targetPath);
				}
			} else {
				// page file, process with twig
				$output->writeln('[render] ' . $file->getRelativePathname());

				$template = $environment->load($file->getRelativePathname());
				$contents = $template->render();

				if(!$dryRun) {
					$filesystem->dumpFile($targetPath, $contents);
				}
			}
		}
	}
}
