<?php

/*
 * Iron Golem entity, ported from GenisysPro (iTX Technologies)
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

class IronGolem extends Animal{

	public const NETWORK_ID = self::IRON_GOLEM;

	public $width = 1.4;
	public $height = 2.9;

	protected function initEntity(){
		parent::initEntity();
		$this->setMaxHealth(100);
		$this->setHealth($this->getMaxHealth());
	}

	public function getName() : string{
		return "Iron Golem";
	}

	public function getDrops() : array{
		return [
			Item::get(Item::IRON_INGOT, 0, \mt_rand(3, 5)),
			Item::get(38, 0, \mt_rand(0, 2)) //poppy / red flower
		];
	}
}
