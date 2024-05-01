<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

const APP_DIR = __DIR__;
const WWW_DIR = APP_DIR . '/../www';
class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appDir = dirname(__DIR__);
		$configurator->addStaticParameters([
				"wwwDir"        => WWW_DIR
			]
		);

		//$configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
		$configurator->enableTracy($appDir . '/log');

		$configurator->setTempDirectory($appDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig($appDir . '/config/common.neon');
		$configurator->addConfig($appDir . '/config/local.neon');
		$configurator->addConfig($appDir . '/config/services.neon');

		return $configurator;
	}
}
