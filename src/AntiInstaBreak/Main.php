<?php

namespace AntiInstaBreak;

use pocketmine\event\entity\EntityEvent;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

    /** @var int[] */
    private $breakTimes = [];

    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK){
            $this->breakTimes[$event->getPlayer()->getRawUniqueId()] = floor(microtime(true) * 20);
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $uuid = $player->getRawUniqueId();

        if(!isset($this->breakTimes[$uuid])){
            $this->getLogger()->debug("Player " . $player->getName() . " tried to break a block too fast");
            $event->setCancelled();
            return;
        }

        $target = $event->getBlock();
        $item = $event->getItem();

        $expectedTime = ceil($target->getBreakTime($item) * 20);

        if($player->hasEffect(Effect::HASTE)){
            $expectedTime *= 1 - (0.2 * $player->getEffect(Effect::HASTE)->getEffectLevel());
        }

        if($player->hasEffect(Effect::MINING_FATIGUE)){
            $expectedTime *= 1 + (0.3 * $player->getEffect(Effect::MINING_FATIGUE)->getEffectLevel());
        }

        $expectedTime -= 1; //1 tick compensation

        $actualTime = ceil(microtime(true) * 20) - $this->breakTimes[$uuid];

        if($actualTime < $expectedTime){
            $this->getLogger()->debug("Player " . $player->getName() . " tried to break a block too fast, expected $expectedTime ticks, got $actualTime ticks");
            $event->setCancelled();
        }

        unset($this->breakTimes[$uuid]);
    }

    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        unset($this->breakTimes[$event->getPlayer()->getRawUniqueId()]);
    }
}
