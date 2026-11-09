<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___/     |_|  |_|_|
 *
 * Weather command ported from GenisysPro and adapted to this server's command API.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\Level;
use pocketmine\level\weather\Weather;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class WeatherCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.weather.description",
			"%pocketmine.command.weather.usage"
		);
		$this->setPermission("pocketmine.command.weather");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return \true;
		}

		if(\count($args) === 0){
			throw new InvalidCommandSyntaxException();
		}

		// /weather <weather> [duration] - affects the sender's level (or the default level on console)
		// /weather <level-name> <weather> [duration] - affects a specific level
		$weather = Weather::getWeatherFromString($args[0]);
		if($weather >= 0){
			$level = $sender instanceof Player ? $sender->getLevel() : $sender->getServer()->getDefaultLevel();
			$durationIndex = 1;
		}else{
			if(\count($args) < 2){
				$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%pocketmine.command.weather.invalid"));

				return \true;
			}

			$level = $sender->getServer()->getLevelByName($args[0]);
			if(!$level instanceof Level){
				$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%pocketmine.command.weather.invalid.level"));

				return \true;
			}

			$weather = Weather::getWeatherFromString($args[1]);
			if($weather < 0){
				$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%pocketmine.command.weather.invalid"));

				return \true;
			}

			$durationIndex = 2;
		}

		if(!$level instanceof Level){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%pocketmine.command.weather.invalid.level"));

			return \true;
		}

		$duration = 12000;
		if(isset($args[$durationIndex])){
			if(!\is_numeric($args[$durationIndex]) or (int) $args[$durationIndex] < 1){
				$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%pocketmine.command.weather.invalid"));

				return \true;
			}
			$duration = (int) $args[$durationIndex];
		}

		if(!$level->getWeather()->setWeather($weather, $duration)){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%pocketmine.command.weather.cancelled"));

			return \true;
		}

		$sender->sendMessage(new TranslationContainer("pocketmine.command.weather.changed", [
			$level->getFolderName(),
			Weather::getWeatherName($weather)
		]));

		return \true;
	}
}
