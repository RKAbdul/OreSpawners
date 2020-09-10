<?php

declare(strict_types=1);

namespace RKAbdul\OreSpawners;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\block\Block;

class EventListener implements Listener{

    /**
     * @var Main
     */
    private $plugin;
    
    private $cfg;
    
    public function __construct(Main $plugin){
        $this->plugin = $plugin;
        $this->cfg = $this->plugin->getConfig()->getAll();
    }
    
    public function onBlockBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        $bbelow = $event->getPlayer()->getLevel()->getBlock($event->getBlock()->floor()->down(1));
        $blocks = [];
        foreach(array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID){
            array_push($blocks, $blockID);
        };
        if (in_array($bbelow->getId(), $blocks)) {
            $ore = $this->checkBlock($bbelow);
            if (!$event->isCancelled()) {
                $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask( function (int $currentTick) use ($event, $ore): void {
                    $event->getPlayer()->getLevel()->setBlock($event->getBlock()->floor(), $ore);
                }), 20 * intval($this->cfg["delay"]));
            }
        }
 
    }
    
    public function onBlockPlace(BlockPlaceEvent $event){
        $block = $event->getBlock();
        $babove = $event->getPlayer()->getLevel()->getBlock($event->getBlock()->floor()->up(1));
        if ($babove->getId() == 0) {
            $blocks = [];
            foreach(array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID){
                array_push($blocks, $blockID);
            };
            if (in_array($block->getId(), $blocks)) {
                $ore = $this->checkBlock($block);
                if (!$event->isCancelled()) {
                    $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask( function (int $currentTick) use ($event, $ore): void {
                        $event->getPlayer()->getLevel()->setBlock($event->getBlock()->floor()->up(1), $ore);
                    }), 20 * intval($this->cfg["delay"]));
                }
            }
        }
    }
    
    public function checkBlock(Block $bbelow) {
        $bbid = $bbelow->getID();
        $coalid = intval($this->cfg["ore-generator-blocks"]["coal"]);
	    $ironid = intval($this->cfg["ore-generator-blocks"]["iron"]);
	    $goldid = intval($this->cfg["ore-generator-blocks"]["gold"]);
	    $diamondid = intval($this->cfg["ore-generator-blocks"]["diamond"]);
	    $emeraldid = intval($this->cfg["ore-generator-blocks"]["emerald"]);
	    $lapizid = intval($this->cfg["ore-generator-blocks"]["lapis"]);
	    $redstoneid = intval($this->cfg["ore-generator-blocks"]["redstone"]);
	    switch ($bbid) {
	        case $coalid:
	            $ore = Block::get(Block::COAL_ORE);
	            break;
	        case $ironid:
	            $ore = Block::get(Block::IRON_ORE);
	            break;
	        case $goldid:
	            $ore = Block::get(Block::GOLD_ORE);
	            break;
	        case $diamondid:
	            $ore = Block::get(Block::DIAMOND_ORE);
	            break;
	        case $emeraldid:
	            $ore = Block::get(Block::EMERALD_ORE);
	            break;
	        case $lapizid:
	            $ore = Block::get(Block::LAPIS_ORE);
	            break;
	        case $redstoneid:
	            $ore = Block::get(Block::REDSTONE_ORE);
	            break;
	    }
	    return $ore;
    }
}

