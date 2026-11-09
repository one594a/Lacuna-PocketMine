<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class Sponge extends Solid{

	protected $id = self::SPONGE;

	/** Meta value of a dry sponge. */
	public const DRY = 0;
	/** Meta value of a wet sponge (minecraft:sponge data 1). */
	public const WET = 1;

	/** Maximum taxicab distance from the sponge at which water can still be absorbed. */
	private const ABSORB_RADIUS = 7;
	/** Maximum number of water blocks a single sponge can absorb. */
	private const ABSORB_LIMIT = 65;

	/** Offsets of the six adjacent blocks (down, up, north, south, west, east). */
	private const ADJACENT_SIDES = [
		[0, -1, 0], [0, 1, 0],
		[0, 0, -1], [0, 0, 1],
		[-1, 0, 0], [1, 0, 0]
	];

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 0.6;
	}

	public function getName() : string{
		return $this->meta === self::WET ? "Wet Sponge" : "Sponge";
	}

	public function isWet() : bool{
		return $this->meta === self::WET;
	}

	/**
	 * This core has no dimension API yet (Player's dimension is hardcoded to OVERWORLD), so a Nether
	 * world is identified by its folder / display name. Plugins may override this method.
	 */
	protected function isNetherWorld(Level $level) : bool{
		$name = \strtolower($level->getFolderName() . " " . $level->getName());
		return \strpos($name, "nether") !== \false or \preg_match('/(^|[^a-z])hell([^a-z]|$)/', $name) === 1;
	}

	private function isInNether() : bool{
		return $this->isValid() and $this->isNetherWorld($this->getLevel());
	}

	/**
	 * Called when the sponge is placed, and whenever a neighbouring block changes (for example when
	 * flowing water reaches it). A dry sponge only does anything if water is actually touching it.
	 */
	public function onNearbyBlockChange() : void{
		parent::onNearbyBlockChange();

		if($this->meta === self::WET and $this->isInNether()){
			// Vanilla behaviour: wet sponges dry out immediately in the Nether.
			$level = $this->getLevel();
			$level->setBlock($this, BlockFactory::get(self::SPONGE, self::DRY), \true, \true);
			$level->broadcastLevelSoundEvent($this->add(0.5, 0.5, 0.5), LevelSoundEventPacket::SOUND_FIZZ, 1000);
			return;
		}

		$this->tryAbsorbWater();
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = \null) : bool{
		$replacedWater = $blockReplace instanceof Water;
		$nether = $this->isInNether();

		if($nether and $this->meta === self::WET){
			// Placing a wet sponge in the Nether immediately dries it out.
			$this->setDamage(self::DRY);
		}

		if(!parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player)){
			return \false;
		}

		// parent::place() fires onNearbyBlockChange(), which already handles water on the six sides.
		// Placing a sponge directly into a water block with no other water around still counts as touching water.
		// In the Nether the sponge dries out immediately, so it never becomes wet there.
		if($replacedWater and !$nether and $this->isDryInWorld()){
			$this->getLevel()->setBlock($this, BlockFactory::get(self::SPONGE, self::WET), \true, \true);
		}

		return \true;
	}

	/**
	 * Reads the real block at this position, so a second update in the same tick cannot absorb twice.
	 */
	private function isDryInWorld() : bool{
		if(!$this->isValid()){
			return \false;
		}

		$block = $this->getLevel()->getBlockAt((int) $this->x, (int) $this->y, (int) $this->z);
		return $block instanceof self and $block->getDamage() === self::DRY;
	}

	/**
	 * Absorbs connected water blocks around the sponge and turns the sponge wet.
	 * Only called when at least one of the six adjacent blocks is water.
	 * In the Nether the sponge stays dry (it would dry out again immediately anyway).
	 */
	private function tryAbsorbWater() : void{
		//Advanced config: server.absorb-water
		if(!$this->getLevel()->getServer()->absorbWater){
			return;
		}
		if($this->meta !== self::DRY or !$this->isDryInWorld()){
			return; // wet sponges don't absorb water
		}

		$level = $this->getLevel();
		$queue = [];
		$visited = [];
		$nether = $this->isInNether();

		foreach(self::ADJACENT_SIDES as $side){
			$x = (int) $this->x + $side[0];
			$y = (int) $this->y + $side[1];
			$z = (int) $this->z + $side[2];

			if($level->isInWorld($x, $y, $z) and $level->getBlockAt($x, $y, $z) instanceof Water){
				$visited["$x:$y:$z"] = \true;
				$queue[] = [$x, $y, $z, 1];
			}
		}

		if(empty($queue)){
			return; // no water is touching the sponge -> nothing happens
		}

		// Become wet BEFORE draining: removing water fires block updates which must not trigger another absorption.
		// In the Nether we skip this: the sponge dries out immediately, so it can keep soaking up water.
		if(!$nether){
			$level->setBlock($this, BlockFactory::get(self::SPONGE, self::WET), \true, \true);
		}

		$air = BlockFactory::get(self::AIR);
		$absorbed = 0;

		for($i = 0; $i < \count($queue) and $absorbed < self::ABSORB_LIMIT; ++$i){
			list($x, $y, $z, $distance) = $queue[$i];

			$block = $level->getBlockAt($x, $y, $z);
			if(!($block instanceof Water)){
				continue; // may have been drained already
			}

			$level->setBlock(new Vector3($x, $y, $z), $air, \true, \true);
			++$absorbed;

			if($distance >= self::ABSORB_RADIUS){
				continue;
			}

			foreach(self::ADJACENT_SIDES as $side){
				$nx = $x + $side[0];
				$ny = $y + $side[1];
				$nz = $z + $side[2];

				if(!$level->isInWorld($nx, $ny, $nz)){
					continue;
				}

				$key = "$nx:$ny:$nz";
				if(isset($visited[$key])){
					continue;
				}
				$visited[$key] = \true;
				$queue[] = [$nx, $ny, $nz, $distance + 1];
			}
		}
	}

}
