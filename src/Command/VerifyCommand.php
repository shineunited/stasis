<?php

namespace ShineUnited\Stasis\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;



class VerifyCommand extends BaseCommand {

	protected function configure() {
		$this->setName('verify');
		$this->setDescription('Verify site config');
		//$this->setHelp('help goes here');

		$this->addArgument('source', InputArgument::REQUIRED, 'Path to source directory.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$sourceDir = rtrim($input->getArgument('source'), DIRECTORY_SEPARATOR);

		$parseExtensions = ['htm', 'html'];

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

		// follow symbolic links
		$finder->followLinks();

		// sort by name
		$finder->sortByName();

		foreach($finder as $file) {
			// $file->getExtension(); // file extension
			// $file->getRealPath(); // full path (absolute)
			// $file->getRelativePath(); // relative folder path
			// $file->getRelativePathname(); // relative file path

			if(!in_array($file->getExtension(), $parseExtensions)) {
				// asset file, skip
				continue;
			}

			// page file, process with twig
			$output->writeln('[verify] ' . $file->getRelativePathname());

			$template = $environment->load($file->getRelativePathname());
			$contents = $template->render();

			$crawler = new Crawler($contents);

			$urlMapping = [
				'a'      => 'href',
				'img'    => 'src',
				'link'   => 'href',
				'script' => 'src'
			];

			foreach($urlMapping as $type => $attribute) {
				foreach($crawler->filter($type) as $node) {
					if(!$node->hasAttribute($attribute)) {
						continue;
					}

					$url = $node->getAttribute($attribute);

					$components = parse_url($url);

					if(!isset($components['path'])) {
						// nothing to do here
						continue;
					}

					if(isset($components['host']) || isset($components['scheme'])) {
						// not a local url, skip
						continue;
					}

					// check path
					$checkPath = false;
					if($components['path']{0} == '/') {
						// root relative
						$checkPath = $components['path'];
					} else {
						// dir relative
						$checkPath = $file->getRelativePath() . '/' . $components['path'];
					}

					if(substr($checkPath, -1) == '/') {
						// check for index.html
						$checkPath .= 'index.html';
					}

					if(!$filesystem->exists($sourceDir . '/' . $checkPath)) {
						throw new \Exception(sprintf("Missing path '%s' in '%s'", $checkPath, $file->getRelativePathname()));
					}
				}
			}
		}
	}
}
