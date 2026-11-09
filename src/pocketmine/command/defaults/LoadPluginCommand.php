<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___/     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\plugin\Plugin;

class LoadPluginCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.loadplugin.description",
			"%pocketmine.command.loadplugin.usage"
		);
		$this->setPermission("pocketmine.command.loadplugin");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return \true;
		}

		if(\count($args) === 0){
			throw new InvalidCommandSyntaxException();
		}

		$name = \trim(\implode(" ", $args));
		$server = $sender->getServer();
		$plugin = \null;

		if(\strpos($name, "..") === \false){
			$path = \rtrim($server->getPluginPath(), "\\/") . DIRECTORY_SEPARATOR . $name;
			try{
				$plugin = $server->getPluginManager()->loadPlugin($path);
			}catch(\Throwable $e){
				$server->getLogger()->logException($e);
			}
		}

		if($plugin instanceof Plugin){
			$server->getPluginManager()->enablePlugin($plugin);
			$sender->sendMessage(new TranslationContainer("pocketmine.command.loadplugin.success", [$plugin->getDescription()->getFullName()]));
		}else{
			$sender->sendMessage(new TranslationContainer("pocketmine.command.loadplugin.failure", [$name]));
		}

		return \true;
	}
}
