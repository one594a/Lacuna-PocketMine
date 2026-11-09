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

namespace pocketmine\command\utils;

use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Parses Minecraft target selectors (@a, @p, @r, @s, @e) and resolves them
 * against the entities currently loaded on the server.
 *
 * Supported arguments: x, y, z, dx, dy, dz, r, rm, c, l, lm, m, name,
 * rx, rxm, ry, rym and type. Values may be prefixed with "!" to invert
 * a match, and x/y/z also accept relative coordinates ("~", "~5", "~-3").
 *
 * This implementation is intentionally self-contained: it only relies on
 * APIs present in this core, so it also works for plugin commands.
 */
final class EntitySelector{

	/** Prefix used to pass a non-player entity through a command argument. */
	public const ENTITY_TARGET_PREFIX = "#";

	private const ARGUMENTS = [
		"x", "y", "z",
		"dx", "dy", "dz",
		"r", "rm", "c",
		"l", "lm", "m",
		"name",
		"rx", "rxm", "ry", "rym",
		"type"
	];

	/** Arguments which restrict the search to the sender's level. */
	private const LEVEL_ARGUMENTS = ["x", "y", "z", "dx", "dy", "dz", "r", "rm"];

	private const GAMEMODES = [
		"survival" => 0, "s" => 0, "0" => 0,
		"creative" => 1, "c" => 1, "1" => 1,
		"adventure" => 2, "a" => 2, "2" => 2,
		"spectator" => 3, "sp" => 3, "3" => 3
	];

	private function __construct(){
		//static only
	}

	/**
	 * Returns whether the given command argument looks like a target selector.
	 */
	public static function isSelector(string $token) : bool{
		return \preg_match('/^@[aeprs](?:\[[^\]]*\])?$/', $token) === 1;
	}

	/**
	 * Resolves a selector into the entities it matches. An empty array is
	 * returned for invalid selectors or when nothing matched.
	 *
	 * @return Entity[]
	 */
	public static function matchEntities(CommandSender $sender, string $token) : array{
		if(!self::isSelector($token)){
			return [];
		}

		$type = $token[1];
		$open = \strpos($token, "[");
		$arguments = self::parseArguments($open === \false ? "" : \substr($token, $open + 1, -1));
		$base = self::getBaseVector($sender, $arguments);

		if($type === "s"){
			if(!($sender instanceof Entity) || !self::matchesAll($sender, $arguments, $type, $base)){
				return [];
			}
			return [$sender];
		}

		$levels = self::getLevels($sender, $arguments);
		$aabb = self::getAABB($arguments, $base);
		$matches = [];

		foreach($levels as $level){
			if(!($level instanceof Level) || $level->isClosed()){
				continue;
			}

			// Merge both lists by entity id: some cores keep players in a
			// separate map, others keep them in the entity list as well.
			$candidates = [];
			foreach($level->getEntities() as $entity){
				$candidates[$entity->getId()] = $entity;
			}
			foreach($level->getPlayers() as $player){
				$candidates[$player->getId()] = $player;
			}

			foreach($candidates as $entity){
				if(!self::matchesAll($entity, $arguments, $type, $base)){
					continue;
				}
				if($aabb !== \null){
					$bb = $entity->getBoundingBox();
					if($bb === \null || !$aabb->intersectsWith($bb)){
						continue;
					}
				}
				$matches[] = $entity;
			}
		}

		if($type === "r"){
			\shuffle($matches);
		}else{
			\usort($matches, function(Entity $a, Entity $b) use ($base) : int{
				$da = $base->distanceSquared($a);
				$db = $base->distanceSquared($b);
				return $da === $db ? 0 : ($da < $db ? -1 : 1);
			});
		}

		$count = self::getInt($arguments, "c", \in_array($type, ["a", "e"], \true) ? 0 : 1);
		if($count !== 0){
			if($count < 0){
				$matches = \array_reverse($matches);
				$count = \abs($count);
			}
			$matches = \array_slice($matches, 0, $count);
		}

		return $matches;
	}

	/**
	 * Converts an entity into a command argument. Players use their name;
	 * other entities use a "#<entity id>" marker which commands can resolve
	 * with resolveTarget().
	 */
	public static function getTargetName(Entity $entity) : string{
		if($entity instanceof Player){
			return $entity->getName();
		}
		return self::ENTITY_TARGET_PREFIX . $entity->getId();
	}

	/**
	 * Resolves a "#<entity id>" argument back into an entity.
	 *
	 * @return Entity|null
	 */
	public static function resolveTarget(CommandSender $sender, string $name){
		if(\strlen($name) < 2 || $name[0] !== self::ENTITY_TARGET_PREFIX){
			return \null;
		}
		$id = \substr($name, 1);
		if(!\is_numeric($id)){
			return \null;
		}
		return $sender->getServer()->findEntity((int) $id);
	}

	/**
	 * A display name for any entity (used by command output).
	 */
	public static function getEntityName(Entity $entity) : string{
		if($entity instanceof Player){
			return $entity->getName();
		}
		$tag = $entity->getNameTag();
		if(\is_string($tag) && $tag !== ""){
			return $tag;
		}
		return (new \ReflectionClass($entity))->getShortName();
	}

	/**
	 * @return string[] key => value
	 */
	private static function parseArguments(string $raw) : array{
		$arguments = [];
		if($raw === ""){
			return $arguments;
		}
		foreach(\explode(",", $raw) as $part){
			$eq = \strpos($part, "=");
			if($eq === \false){
				continue;
			}
			$key = \strtolower(\trim(\substr($part, 0, $eq)));
			$value = \trim(\substr($part, $eq + 1));
			if(\in_array($key, self::ARGUMENTS, \true)){
				$arguments[$key] = $value;
			}
		}
		return $arguments;
	}

	private static function matchesAll(Entity $entity, array $arguments, string $type, Vector3 $base) : bool{
		if(!$entity->isAlive()){
			return \false;
		}

		// @a and @p only ever select players. @r selects players by default,
		// but may select other entities when a type argument is present.
		if($type === "a" || $type === "p" || ($type === "r" && !isset($arguments["type"]))){
			if(!($entity instanceof Player)){
				return \false;
			}
		}

		if(!self::matchesType($entity, $arguments)){
			return \false;
		}
		if(!self::matchesName($entity, $arguments)){
			return \false;
		}
		if(!self::matchesGamemode($entity, $arguments)){
			return \false;
		}
		if(!self::matchesXpLevel($entity, $arguments)){
			return \false;
		}
		if(!self::matchesRadius($entity, $arguments, $base)){
			return \false;
		}
		return self::matchesRotation($entity, $arguments);
	}

	private static function matchesType(Entity $entity, array $arguments) : bool{
		if(!isset($arguments["type"])){
			return \true;
		}
		$type = $arguments["type"];
		$inverted = self::splitInverted($type);
		$key = \strtolower($type);

		$classes = self::getEntityClasses();
		$class = $classes[$key] ?? \null;
		if($class === \null){
			$colon = \strrpos($key, ":");
			if($colon !== \false){
				$class = $classes[\substr($key, $colon + 1)] ?? \null;
			}
		}

		if(\is_numeric($type)){
			$entityClass = \get_class($entity);
			$matched = ($entityClass::NETWORK_ID === (int) $type);
		}elseif($class !== \null){
			$matched = $entity instanceof $class;
		}elseif($key === "player"){
			$matched = $entity instanceof Player;
		}else{
			$matched = (\strtolower((new \ReflectionClass($entity))->getShortName()) === $key);
		}

		return $matched !== $inverted;
	}

	/**
	 * Lower-cased entity registry from Entity::$knownEntities: save names,
	 * namespaced ids and network ids mapped to the implementing class.
	 *
	 * @return string[]
	 */
	private static function getEntityClasses() : array{
		$classes = [];
		$property = new \ReflectionProperty(Entity::class, "knownEntities");
		$property->setAccessible(\true);
		foreach((array) $property->getValue() as $name => $class){
			if(\is_string($class)){
				$classes[\strtolower((string) $name)] = $class;
			}
		}
		return $classes;
	}

	private static function matchesName(Entity $entity, array $arguments) : bool{
		if(!isset($arguments["name"])){
			return \true;
		}
		$name = $arguments["name"];
		$inverted = self::splitInverted($name);
		return (\strtolower(self::getEntityName($entity)) === \strtolower($name)) !== $inverted;
	}

	private static function matchesGamemode(Entity $entity, array $arguments) : bool{
		if(!isset($arguments["m"])){
			return \true;
		}
		$mode = $arguments["m"];
		$inverted = self::splitInverted($mode);
		$mode = \strtolower($mode);
		if(!isset(self::GAMEMODES[$mode])){
			return \false;
		}
		return ($entity instanceof Player && $entity->getGamemode() === self::GAMEMODES[$mode]) !== $inverted;
	}

	private static function matchesXpLevel(Entity $entity, array $arguments) : bool{
		$lm = self::getInt($arguments, "lm", -1);
		$l = self::getInt($arguments, "l", -1);
		if($lm < 0 && $l < 0){
			return \true;
		}
		//Advanced config: player.experience. When XP is disabled all players count as level 0.
		$level = -1;
		if($entity instanceof Player){
			$playerLevel = $entity->getLevel();
			$server = $playerLevel !== \null ? $playerLevel->getServer() : \null;
			if($server !== \null and !$server->expEnabled){
				$level = 0;
			}else{
				try{
					$level = $entity->getXpLevel();
				}catch(\Throwable $e){ //offline mocks without an attribute map
					$level = 0;
				}
			}
		}
		return ($lm < 0 || $level >= $lm) && ($l < 0 || $level <= $l);
	}

	private static function matchesRadius(Entity $entity, array $arguments, Vector3 $base) : bool{
		$rm = self::getInt($arguments, "rm", -1);
		$r = self::getInt($arguments, "r", -1);
		if($rm < 0 && $r < 0){
			return \true;
		}
		$squared = $base->distanceSquared($entity);
		return ($rm < 0 || $squared >= $rm * $rm) && ($r < 0 || $squared <= $r * $r);
	}

	private static function matchesRotation(Entity $entity, array $arguments) : bool{
		if(isset($arguments["ry"]) || isset($arguments["rym"])){
			$min = self::clampAngle(self::getInt($arguments, "rym", 0));
			$max = self::clampAngle(self::getInt($arguments, "ry", 359));
			$yaw = self::clampAngle((int) \floor($entity->getYaw()));
			if(!self::inAngleRange($yaw, $min, $max)){
				return \false;
			}
		}
		if(isset($arguments["rx"]) || isset($arguments["rxm"])){
			$min = self::clampAngle(self::getInt($arguments, "rxm", 0));
			$max = self::clampAngle(self::getInt($arguments, "rx", 359));
			$pitch = self::clampAngle((int) \floor($entity->getPitch()));
			if(!self::inAngleRange($pitch, $min, $max)){
				return \false;
			}
		}
		return \true;
	}

	private static function inAngleRange(int $value, int $min, int $max) : bool{
		return $min > $max ? ($value >= $min || $value <= $max) : ($value >= $min && $value <= $max);
	}

	private static function clampAngle(int $angle) : int{
		$angle %= 360;
		if($angle >= 180){
			$angle -= 360;
		}
		if($angle < -180){
			$angle += 360;
		}
		return $angle;
	}

	/** Removes a leading "!" from a value and reports whether it was present. */
	private static function splitInverted(string &$value) : bool{
		if(\strlen($value) > 0 && $value[0] === "!"){
			$value = \substr($value, 1);
			return \true;
		}
		return \false;
	}

	private static function getBaseVector(CommandSender $sender, array $arguments) : Vector3{
		$x = 0.0;
		$y = 0.0;
		$z = 0.0;
		if($sender instanceof Position){
			$x = (float) $sender->x;
			$y = (float) $sender->y;
			$z = (float) $sender->z;
		}
		return new Vector3(
			self::getCoordinate($arguments, "x", $x, \true),
			self::getCoordinate($arguments, "y", $y, \false),
			self::getCoordinate($arguments, "z", $z, \true)
		);
	}

	private static function getCoordinate(array $arguments, string $key, float $default, bool $center) : float{
		if(!isset($arguments[$key])){
			return $default;
		}
		$value = $arguments[$key];
		if(\strlen($value) > 0 && $value[0] === "~"){
			$offset = \substr($value, 1);
			return $default + (\is_numeric($offset) ? (float) $offset : 0.0);
		}
		if(!\is_numeric($value)){
			return $default;
		}
		return (float) $value + ($center ? 0.5 : 0.0);
	}

	/**
	 * @return Level[]
	 */
	private static function getLevels(CommandSender $sender, array $arguments) : array{
		foreach(self::LEVEL_ARGUMENTS as $argument){
			if(isset($arguments[$argument]) && $sender instanceof Position){
				$level = $sender->getLevel();
				if($level instanceof Level){
					return [$level];
				}
			}
		}
		return \array_values($sender->getServer()->getLevels());
	}

	private static function getAABB(array $arguments, Vector3 $base){
		$hasBox = isset($arguments["dx"]) || isset($arguments["dy"]) || isset($arguments["dz"]);
		$r = self::getInt($arguments, "r", -1);
		if(!$hasBox && $r < 0){
			return \null;
		}

		if($hasBox){
			$dx = self::getInt($arguments, "dx", 0);
			$dy = self::getInt($arguments, "dy", 0);
			$dz = self::getInt($arguments, "dz", 0);
			// Only clamp to the radius if one was given; otherwise dx/dy/dz
			// describe the whole search volume (vanilla behaviour).
			if($r >= 0){
				$dx = \min($dx, $r);
				$dy = \min($dy, $r);
				$dz = \min($dz, $r);
			}
			return new AxisAlignedBB(
				$base->x + \min(0, $dx), $base->y + \min(0, $dy), $base->z + \min(0, $dz),
				$base->x + \max(0, $dx) + 1, $base->y + \max(0, $dy) + 1, $base->z + \max(0, $dz) + 1
			);
		}

		return new AxisAlignedBB(
			$base->x - $r, $base->y - $r, $base->z - $r,
			$base->x + $r + 1, $base->y + $r + 1, $base->z + $r + 1
		);
	}

	private static function getInt(array $arguments, string $key, int $default) : int{
		if(!isset($arguments[$key]) || !\is_numeric($arguments[$key])){
			return $default;
		}
		return (int) $arguments[$key];
	}
}
