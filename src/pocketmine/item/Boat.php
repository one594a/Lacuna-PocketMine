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

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class Boat extends Item{
	/** @var float[] keyed by lowercase player name, used to deduplicate a single right click */
	private static $lastPlaceTime = [];

	public function __construct(int $meta = 0){
		parent::__construct(self::BOAT, $meta, "Boat");
	}

	public function getFuelTime() : int{
		return 1200; //400 in PC
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		$pos = $blockReplace->add(0.5, 0, 0.5);

		if($blockReplace instanceof Water or $blockReplace->isSolid()){
			//Put the boat on top of the water/block instead of inside it.
			if($blockReplace->getSide(Vector3::SIDE_UP)->isSolid()){
				return \false;
			}
			$pos->y = $blockReplace->getFloorY() + 1;
		}

		return $this->placeBoat($player, $pos);
	}

	/**
	 * Clients may report a right click on water as a click in the air (water isn't a solid target),
	 * so trace the line of sight to find a suitable place for the boat.
	 */
	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		$start = $player->add(0, $player->getEyeHeight(), 0);
		$level = $player->getLevel();
		$lastAir = $start;

		for($i = 1; $i <= 30; ++$i){
			$point = $start->add(
				$directionVector->x * $i * 0.2,
				$directionVector->y * $i * 0.2,
				$directionVector->z * $i * 0.2
			);
			$gridY = Math::floorFloat($point->y);
			$block = $level->getBlockAt(Math::floorFloat($point->x), $gridY, Math::floorFloat($point->z));

			if($block instanceof Water){
				return $this->placeBoat($player, new Vector3($point->x, $gridY + 1, $point->z));
			}
			if($block->isSolid()){
				return $this->placeBoat($player, $lastAir);
			}

			$lastAir = $point;
		}

		return $this->placeBoat($player, $lastAir);
	}

	private function placeBoat(Player $player, Vector3 $pos) : bool{
		$key = $player->getLowerCaseName();
		$now = \microtime(\true);
		if(isset(self::$lastPlaceTime[$key]) and $now - self::$lastPlaceTime[$key] < 0.2){
			//One right click can arrive as both CLICK_BLOCK and CLICK_AIR; don't spawn two boats.
			return \false;
		}

		$nbt = Entity::createBaseNBT($pos, \null, $player->yaw, 0);
		$nbt->setInt("Variant", $this->meta);

		$entity = Entity::createEntity("Boat", $player->getLevel(), $nbt);
		if(!($entity instanceof \pocketmine\entity\object\Boat)){
			return \false;
		}

		self::$lastPlaceTime[$key] = $now;
		$entity->spawnToAll();

		if(!$player->isCreative()){
			--$this->count;
		}

		$player->getLevel()->broadcastLevelEvent($pos, LevelEventPacket::EVENT_SOUND_ITEMFRAME_PLACE);

		return \true;
	}
}
