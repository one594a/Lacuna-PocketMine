<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|
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

namespace pocketmine\entity\object;

use pocketmine\block\Water;
use pocketmine\entity\Vehicle;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Math;

class Boat extends Vehicle{
	public const NETWORK_ID = self::BOAT;

	public $width = 1.4;
	public $height = 0.6;

	protected $baseOffset = 0.3;

	protected $gravity = 0.04;
	protected $drag = 0.05;

	/** @var int Wood variant, stored in NBT so the boat keeps its type across restarts */
	private $variant = 0;

	protected function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(40);
		$this->setHealth($this->getMaxHealth());

		$this->variant = $this->namedtag->getInt("Variant", 0);
		$this->propertyManager->setInt(self::DATA_VARIANT, $this->variant);
		$this->propertyManager->setFloat(self::DATA_PADDLE_TIME_LEFT, 0.0);
		$this->propertyManager->setFloat(self::DATA_PADDLE_TIME_RIGHT, 0.0);
	}

	public function getVariant() : int{
		return $this->variant;
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->setInt("Variant", $this->variant);
	}

	/**
	 * Returns the Y coordinate of the surface of the water the boat is floating in,
	 * or null when there is no water under/around the boat.
	 */
	private function getWaterSurfaceY() : ?float{
		$x = Math::floorFloat($this->x);
		$z = Math::floorFloat($this->z);
		$minY = Math::floorFloat($this->y) - 1;

		for($y = Math::floorFloat($this->y + $this->height); $y >= $minY; --$y){
			if($this->level->getBlockAt($x, $y, $z) instanceof Water){
				return (float) ($y + 1);
			}
		}

		return \null;
	}

	protected function applyGravity(){
		$surface = $this->getWaterSurfaceY();
		if($surface !== \null){
			//Boats float: pull them gently towards the water surface instead of letting gravity sink them to the bottom.
			$targetY = $surface - 0.1;
			$this->motionY = \max(-0.1, \min(0.1, ($targetY - $this->y) * 0.2));
		}else{
			parent::applyGravity();
		}
	}

	protected function onDeathUpdate(int $tickDiff) : bool{
		$this->level->dropItem($this, ItemFactory::get(Item::BOAT, $this->variant));

		return \true;
	}
}
