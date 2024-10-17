<?php

namespace pocketmine\block;

use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\PoweredByRedstoneTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;
use pocketmine\math\AxisAlignedBB;

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

    public function onUpdate(int $type) : bool{
        if($type === World::BLOCK_UPDATE_NORMAL || $type === World::BLOCK_UPDATE_REDSTONE){
            $this->activate();
        }
        return parent::onUpdate($type);
    }

    public function activate() : void{
        if(!$this->isPowered()){
            $this->setPowered(true);

            $facingOpposite = Facing::opposite($this->facing);
            $neighborBlockPos = $this->position->getSide($facingOpposite);
            $this->getWorld()->scheduleDelayedBlockUpdate($this, 2);

            $this->getWorld()->setBlock($neighborBlockPos, $this->getWorld()->getBlock($neighborBlockPos)->setPowered(true));

            $this->getWorld()->scheduleDelayedTask(function() use ($neighborBlockPos) {
                $this->setPowered(false);
                $this->getWorld()->setBlock($neighborBlockPos, $this->getWorld()->getBlock($neighborBlockPos)->setPowered(false));
            }, 2);
        }
    }

    protected function recalculateCollisionBoxes() : array{
        return [AxisAlignedBB::one()->stretch($this->getFacingVector())];
    }

}
