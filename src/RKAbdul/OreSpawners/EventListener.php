<?php

declare(strict_types=1);

namespace RKAbdul\OreSpawners;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\scheduler\ClosureTask;
use pocketmine\math\Vector3;
use pocketmine\level\sound\FizzSound;
use pocketmine\utils\Config;
use DenielWorld\EzTiles\tile\SimpleTile;
use DenielWorld\EzTiles\data\TileInfo;
use pocketmine\utils\TextFormat as TF;

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
        
        foreach(array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        };
        
        if (in_array($bbelow->getId(), $blocks)) {
            $ore = $this->checkBlock($bbelow);
            $delay = $this->getDelay($bbelow);
            if (!$event->isCancelled()) {
		$event->setCancelled(true);
                if ($event->getBlock()->getId() == $ore->getId()) return;
                $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask( function (int $currentTick) use ($event, $ore): void {
                    if ($event->getBlock()->getLevel() !== null){
                        $event->getBlock()->getLevel()->setBlock($event->getBlock()->floor(), $ore, false, true);
			            if ($this->cfg["fizz-sound"] == true) $event->getBlock()->getLevel()->addSound(new FizzSound($event->getBlock()->asVector3()));
                    }
                }), intval($delay));
            }
        }
	}
    
    public function onPlayerInteract(PlayerInteractEvent $event) {
        if($this->cfg["stacking"] == false) return;
        $item = $event->getItem();
        $player = $event->getPlayer();
        $blocks = [];
        foreach(array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID){
            array_push($blocks, $blockID);
        };
        if (in_array($event->getBlock()->getId(), $blocks)) {
            $tile = $event->getPlayer()->getLevel()->getTile($event->getBlock()->asVector3());
            if($player->getGamemode() == 1) return $player->sendMessage(TF::RED . "You can only use stacking system in survival");
            $stacked = $tile instanceof SimpleTile ? $tile->getData("stacked")->getValue() : 1;
            if (!in_array($item->getId(), $blocks) || $event->getBlock()->getId() != $item->getId()) return $player->sendMessage("§aThere are currently " . TF::YELLOW . $stacked. " §aStacked orespawners");
            if ($tile instanceof SimpleTile) {
                if ($stacked >= intval($this->cfg["max"])) return $player->sendMessage(str_replace("&", "§", $this->cfg["limit-reached"] ?? "&cYou can't stack anymore orespawners, you have reached the limit"));
                $tile->setData("stacked", $stacked + 1);
            } else {
                $tileinfo = new TileInfo($event->getBlock(), ["id" => "simpleTile", "stacked" => 2]);
	            new SimpleTile($event->getPlayer()->getLevel(), $tileinfo);
            }
            $event->setCancelled(true);
            $item->setCount($item->getCount() - 1);
            $player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
            $player->sendMessage(str_replace("&", "§", $this->cfg["gen-added"] ?? "&aSuccessfully stacked a orespawner"));
        }
    }
    
    public function getDelay(Block $block) {
        $tile = $block->getLevel()->getTile($block->asVector3());
        if ($tile instanceof SimpleTile) {
            $stacked = $tile->getData("stacked")->getValue();
        } else {
            $stacked = 1;
        }
        $base = intval($this->cfg["base-delay"]);
        $delay = ($base / $stacked) * 20;
        return $delay;
    }
    
    public function getTile(Vector3 $pos) : ?Tile {
	    return $this->getTileAt((int) floor($pos->x), (int) floor($pos->y), (int) floor($pos->z));
    }
	
    public function onBlockBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $blocks = [];
        foreach(array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID){
            array_push($blocks, $blockID);
        };
        if (!in_array($event->getBlock()->getId(), $blocks)) return;
        $tile = $player->getLevel()->getTile($block->asVector3());
        $type = $this->checkSpawner($block);
        $count = $tile instanceof SimpleTile ? $tile->getData("stacked")->getValue() : 1;
        
        if ($event->isCancelled()) return;
	    $event->setDrops([]);
            $orespawner = $this->plugin->createOreSpawner($type, $count);
	    if($player->getInventory()->canAddItem($orespawner)){
                $player->getInventory()->addItem($orespawner);
            } else {
                $player->getLevel()->dropItem($event->getBlock()->asVector3(), $orespawner);
            }
    }
    
    public function checkBlock(Block $bbelow) {
        $bbid = $bbelow->getId();
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
    
    public function checkSpawner(Block $bbelow) {
        $bbid = $bbelow->getId();
        $coalid = intval($this->cfg["ore-generator-blocks"]["coal"]);
	    $ironid = intval($this->cfg["ore-generator-blocks"]["iron"]);
	    $goldid = intval($this->cfg["ore-generator-blocks"]["gold"]);
	    $diamondid = intval($this->cfg["ore-generator-blocks"]["diamond"]);
	    $emeraldid = intval($this->cfg["ore-generator-blocks"]["emerald"]);
	    $lapizid = intval($this->cfg["ore-generator-blocks"]["lapis"]);
	    $redstoneid = intval($this->cfg["ore-generator-blocks"]["redstone"]);
	    switch ($bbid) {
	        case $coalid:
	            $ore = "coal";
	            break;
	        case $ironid:
	            $ore = "iron";
	            break;
	        case $goldid:
	            $ore = "gold";
	            break;
	        case $diamondid:
	            $ore = "diamond";
	            break;
	        case $emeraldid:
	            $ore = "emerald";
	            break;
	        case $lapizid:
	            $ore = "lapis";
	            break;
	        case $redstoneid:
	            $ore = "redstone";
	            break;
	    }
	    return $ore;
    }
}
