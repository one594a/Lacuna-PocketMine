<?php

/*
 * Lightning bolt entity, ported from GenisysPro (iTX Technologies)
 * and adapted to the entity/packet API used by this core.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

declare(strict_types=1);

namespace pocketmine\entity\object;

use pocketmine\entity\Entity;

class Lightning extends Entity{

	public const NETWORK_ID = self::LIGHTNING_BOLT;

	public $width = 0.3;
	public $height = 1.8;

	protected $gravity = 0.0;
	protected $drag = 0.0;

	/** @var int */
	private $life = 0;

	public function getName() : string{
		return "Lightning Bolt";
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->closed){
			return \false;
		}

		//A bolt only lives for about one second; Level::spawnLightning() does the rest.
		if(++$this->life > 20){
			$this->close();
			return \false;
		}

		return parent::onUpdate($currentTick);
	}
}
