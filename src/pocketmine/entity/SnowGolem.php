<?php

/*
 * Snow Golem entity, ported from GenisysPro (iTX Technologies)
 * and adapted to the entity API used by this core.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

declare(strict_types=1);

namespace pocketmine\entity;

use pocketmine\item\Item;

class SnowGolem extends Animal{

	public const NETWORK_ID = self::SNOW_GOLEM;

	public $width = 0.7;
	public $height = 1.9;

	protected function initEntity(){
		parent::initEntity();
		$this->setMaxHealth(4);
		$this->setHealth($this->getMaxHealth());
	}

	public function getName() : string{
		return "Snow Golem";
	}

	public function getDrops() : array{
		return [
			Item::get(Item::SNOWBALL, 0, \mt_rand(0, 15))
		];
	}
}
