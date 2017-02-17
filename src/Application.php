<?php

namespace ShineUnited\Stasis;

use ShineUnited\Stasis\Command\BuildCommand;
use ShineUnited\Stasis\Command\CleanCommand;
use ShineUnited\Stasis\Command\VerifyCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Debug\ErrorHandler;


class Application extends BaseApplication {
	const NAME = 'stasis';
	const VERSION = '1.0.0';

	public function __construct() {
		parent::__construct(static::NAME, static::VERSION);

		ErrorHandler::register()->throwAt(E_ALL, true);

		$this->add(new BuildCommand());
		$this->add(new CleanCommand());
		$this->add(new VerifyCommand());
	}
}
