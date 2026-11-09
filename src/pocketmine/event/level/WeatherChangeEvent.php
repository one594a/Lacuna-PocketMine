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

namespace pocketmine\event\level;

use pocketmine\event\Cancellable;
use pocketmine\level\Level;

/**
 * Called when the weather of a level is about to be changed.
 */
class WeatherChangeEvent extends LevelEvent implements Cancellable{

	/** @var int */
	private $weather;
	/** @var int */
	private $duration;

	public function __construct(Level $level, int $weather, int $duration){
		parent::__construct($level);
		$this->weather = $weather;
		$this->duration = $duration;
	}

	public function getWeather() : int{
		return $this->weather;
	}

	public function setWeather(int $weather){
		$this->weather = $weather;
	}

	public function getDuration() : int{
		return $this->duration;
	}

	public function setDuration(int $duration){
		$this->duration = $duration;
	}
}
