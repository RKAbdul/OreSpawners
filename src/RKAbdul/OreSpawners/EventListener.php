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
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\level\sound\FizzSound;
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
	
	public function onBlockUpdate(BlockUpdateEvent $event){
        $block = $event->getBlock();
        $bbelow = $block->getLevel()->getBlock($event->getBlock()->floor()->down(1));
        $blocks = [];
		
			foreach(array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID){
				array_push($blocks, $blockID);
			};
				if (in_array($bbelow->getId(), $blocks)) {
					$ore = $this->checkBlock($bbelow);
					if (!$event->isCancelled()) {
						if ($event->getBlock()->getId() == $ore->getId()) return;
						$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask( function (int $currentTick) use ($event, $ore): void {
							if ($event->getBlock()->getLevel() !== null){
								$event->getBlock()->getLevel()->setBlock($event->getBlock()->floor(), $ore, false, true);
								if ($this->cfg["fizz-sound"] = true) $event->getBlock()->getLevel()->addSound(new FizzSound($event->getBlock()->asVector3()));
							}
						}), 20 * intval($this->cfg["delay"]));
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
