<?php

/*
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

namespace pocketmine\block;

use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\PoweredByRedstoneTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;

class Observer extends Opaque {
    use AnyFacingTrait;
    use PoweredByRedstoneTrait;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->facing($this->facing);
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player !== null){
			if(abs($player->getPosition()->x - $this->position->x) < 2 && abs($player->getPosition()->z - $this->position->z) < 2){
				$y = $player->getEyePos()->y;

				if($y - $this->position->y > 2){
					$this->facing = Facing::DOWN;
				}elseif($this->position->y - $y > 0){
					$this->facing = Facing::UP;
				}else{
					$this->facing = $player->getHorizontalFacing();
				}
			}else{
				$this->facing = $player->getHorizontalFacing();
			}
		}

		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onNearbyBlockChange() : void{
		$this->activate();
	}

	public function activate() : void{
		if(!$this->isPowered()){
			$this->setPowered(true);
			$facingOpposite = Facing::opposite($this->facing);
			$neighborBlockPos = $this->position->getSide($facingOpposite);
			$world = $this->position->getWorld();
			$world->scheduleDelayedBlockUpdate($this, 2);
			$world->setBlock($neighborBlockPos, $world->getBlock($neighborBlockPos)->setPowered(true));
			$world->scheduleDelayedTask(function() use ($neighborBlockPos, $world) {
				$this->setPowered(false);
				$world->setBlock($neighborBlockPos, $world->getBlock($neighborBlockPos)->setPowered(false));
			}, 2);
		}
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->stretch($this->getWidth(), $this->getHeight())];
	}
}
