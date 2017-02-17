<?php

namespace ShineUnited\Stasis\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


class CleanCommand extends BaseCommand {

	protected function configure() {
		$this->setName('clean');
		$this->setDescription('Clean static output');
		//$this->setHelp('help goes here');

		$this->addArgument('target', InputArgument::REQUIRED, 'Path to target directory.');

		$this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate clean');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$targetDir = $input->getArgument('target');

		$dryRun = $input->getOption('dry-run');

		$filesystem = new Filesystem();

		// remove files
		$fileFinder = new Finder();
		$fileFinder->files();
		$fileFinder->ignoreVCS(true);
		$fileFinder->in($targetDir);
		$fileFinder->sortByName();

		foreach($fileFinder as $file) {
			// $file->getExtension(); // file extension
			// $file->getRealPath(); // full path (absolute)
			// $file->getRelativePath(); // relative folder path
			// $file->getRelativePathname(); // relative file path

			$output->writeln('[delete] ' . $file->getRelativePathname());
			if(!$dryRun) {
				$filesystem->remove($file);
			}
		}

		// remove directories
		$dirFinder = new Finder();
		$dirFinder->directories();
		$fileFinder->ignoreVCS(true);
		$dirFinder->in($targetDir);
		$dirFinder->sortByName();

		foreach($dirFinder as $dir) {
			// $file->getExtension(); // file extension
			// $file->getRealPath(); // full path (absolute)
			// $file->getRelativePath(); // relative folder path
			// $file->getRelativePathname(); // relative file path

			$output->writeln('[delete] ' . $dir->getRelativePathname());
			if(!$dryRun) {
				$filesystem->remove($dir);
			}
		}
	}
}
