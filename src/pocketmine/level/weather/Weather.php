<?php

/*
 * Weather system ported from GenisysPro (iTX Technologies, https://itxtech.org)
 * and adapted to the PocketMine-MP API used by this server.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

declare(strict_types=1);

namespace pocketmine\level\weather;

use pocketmine\event\level\WeatherChangeEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class Weather{

	public const CLEAR = 0;
	public const SUNNY = 0;
	public const RAIN = 1;
	public const RAINY = 1;
	public const RAINY_THUNDER = 2;
	public const THUNDER = 3;

	/** @var Level */
	private $level;

	/** @var int */
	private $weatherNow = self::SUNNY;
	/** @var int */
	private $strength1 = 0;
	/** @var int */
	private $strength2 = 0;
	/** @var int */
	private $duration;
	/** @var bool */
	private $canCalculate;
	/** @var int */
	private $lastUpdate = 0;

	/** @var int[] */
	private $randomWeatherData = [0, 1, 0, 1, 0, 1, 0, 2, 0, 3];

	public function __construct(Level $level){
		$this->level = $level;
		$this->lastUpdate = $level->getServer()->getTick();
		$this->duration = $this->getRandomDuration();
		//Advanced config (lacuna.yml): level.weather
		$this->canCalculate = (bool) $level->getServer()->weatherEnabled;
	}

	public function canCalculate() : bool{
		return $this->canCalculate;
	}

	public function setCanCalculate(bool $canCalculate){
		$this->canCalculate = $canCalculate;
	}

	/**
	 * Returns a random duration (in ticks) for a naturally occurring weather period.
	 */
	private function getRandomDuration() : int{
		$server = $this->level->getServer();
		//Advanced config (lacuna.yml): level.weather-random-duration-min / max
		$min = \max(1, (int) $server->weatherRandomDurationMin);
		$max = \max(1, (int) $server->weatherRandomDurationMax);

		return \mt_rand(\min($min, $max), \max($min, $max));
	}

	/**
	 * Called once per level tick. Handles automatic weather changes when enabled.
	 */
	public function calcWeather(int $currentTick){
		if($this->canCalculate){
			$this->duration -= $currentTick - $this->lastUpdate;

			if($this->duration <= 0){
				$duration = $this->getRandomDuration();
				if($this->weatherNow === self::SUNNY){
					$weather = $this->randomWeatherData[\array_rand($this->randomWeatherData)];
				}else{
					$weather = self::SUNNY;
				}

				if(!$this->setWeather($weather, $duration)){
					//A plugin cancelled the change, try again after this period.
					$this->duration = $duration;
				}
			}
		}

		//Advanced config (lacuna.yml): level.lightning-time (ported from GenisysPro)
		$server = $this->level->getServer();
		if($this->weatherNow >= self::RAINY_THUNDER and $server->lightningTime > 0 and $this->duration % $server->lightningTime === 0){
			$players = \array_values($this->level->getPlayers());
			if(\count($players) > 0){
				$p = $players[\array_rand($players)];
				$x = (int) $p->x + \mt_rand(-64, 64);
				$z = (int) $p->z + \mt_rand(-64, 64);
				$y = $this->level->getHighestBlockAt($x, $z);
				$this->level->spawnLightning(new Vector3($x + 0.5, $y, $z + 0.5));
			}
		}

		$this->lastUpdate = $currentTick;
	}

	/**
	 * Changes the weather. Returns false when a plugin cancelled the change.
	 */
	public function setWeather(int $weather, int $duration = 12000) : bool{
		$this->level->getServer()->getPluginManager()->callEvent($ev = new WeatherChangeEvent($this->level, $weather, $duration));
		if($ev->isCancelled()){
			return \false;
		}

		$this->weatherNow = \min(self::THUNDER, \max(self::SUNNY, $ev->getWeather()));
		$this->duration = \max(1, $ev->getDuration());
		$this->strength1 = \mt_rand(90000, 110000);
		$this->strength2 = \mt_rand(30000, 40000);
		$this->sendWeatherToAll();

		return \true;
	}

	public function getWeather() : int{
		return $this->weatherNow;
	}

	public function getDuration() : int{
		return $this->duration;
	}

	public function getStrength() : array{
		return [$this->strength1, $this->strength2];
	}

	public function getRandomWeatherData() : array{
		return $this->randomWeatherData;
	}

	public function setRandomWeatherData(array $randomWeatherData){
		$this->randomWeatherData = $randomWeatherData;
	}

	public function isSunny() : bool{
		return $this->weatherNow === self::SUNNY;
	}

	public function isRainy() : bool{
		return $this->weatherNow === self::RAINY;
	}

	public function isRainyThunder() : bool{
		return $this->weatherNow === self::RAINY_THUNDER;
	}

	public function isThunder() : bool{
		return $this->weatherNow === self::THUNDER;
	}

	/**
	 * Returns the weather id for a command argument, or -1 when invalid.
	 */
	public static function getWeatherFromString(string $weather) : int{
		switch(\strtolower(\trim($weather))){
			case "0":
			case "clear":
			case "sunny":
			case "sun":
			case "fine":
				return self::SUNNY;
			case "1":
			case "rain":
			case "rainy":
				return self::RAINY;
			case "2":
			case "storm":
			case "thunderstorm":
			case "rain_thunder":
			case "rainy_thunder":
				return self::RAINY_THUNDER;
			case "3":
			case "thunder":
				return self::THUNDER;
			default:
				return -1;
		}
	}

	public static function getWeatherName(int $weather) : string{
		switch($weather){
			case self::RAINY:
				return "rain";
			case self::RAINY_THUNDER:
				return "storm";
			case self::THUNDER:
				return "thunder";
			default:
				return "clear";
		}
	}

	/**
	 * Sends the current weather to the given players, or to every player in the level when empty.
	 */
	public function sendWeather(Player ...$targets){
		$players = \count($targets) > 0 ? $targets : \array_values($this->level->getPlayers());
		if(\count($players) === 0){
			return;
		}

		//By default stop both rain and thunder. Overwrite below when the weather is not clear.
		$rain = new LevelEventPacket();
		$rain->evid = LevelEventPacket::EVENT_STOP_RAIN;
		$rain->data = $this->strength1;
		$rain->position = \null;

		$thunder = new LevelEventPacket();
		$thunder->evid = LevelEventPacket::EVENT_STOP_THUNDER;
		$thunder->data = $this->strength2;
		$thunder->position = \null;

		switch($this->weatherNow){
			case self::RAINY:
				$rain->evid = LevelEventPacket::EVENT_START_RAIN;
				break;
			case self::RAINY_THUNDER:
				$rain->evid = LevelEventPacket::EVENT_START_RAIN;
				$thunder->evid = LevelEventPacket::EVENT_START_THUNDER;
				break;
			case self::THUNDER:
				$thunder->evid = LevelEventPacket::EVENT_START_THUNDER;
				break;
		}

		$server = $this->level->getServer();
		$server->broadcastPacket($players, $rain);
		$server->broadcastPacket($players, $thunder);
	}

	public function sendWeatherToAll(){
		$this->sendWeather();
	}
}
