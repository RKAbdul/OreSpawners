<?php

declare(strict_types=1);

namespace RKAbdul\OreSpawners;

use pocketmine\level\sound\FizzSound;
use pocketmine\block\Block;
use pocketmine\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Server;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;

use DenielWorld\EzTiles\data\TileInfo;
use DenielWorld\EzTiles\tile\SimpleTile;

use RKAbdul\OreSpawners\Events\OreSpawnerGenerateEvent;
use RKAbdul\OreSpawners\Events\OreSpawnerStackEvent;
use RKAbdul\OreSpawners\Events\OreSpawnerPlaceEvent;
use RKAbdul\OreSpawners\Events\OreSpawnerBreakEvent;

class EventListener implements Listener
{

    /**
     * @var Main
     */
    private $plugin;

    private $cfg;
    
    /**
     * Initialize objects.
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->cfg = $this->plugin->getConfig()->getAll();
    }

    /**
     * Checks if a block is updated and if it is an OreSpawner.
     * If so, a new ore will be created.
     *
     * @param  BlockUpdateEvent $event
     * @return void
     */
    public function onBlockUpdate(BlockUpdateEvent $event)
    {
        $block = $event->getBlock();
        $bbelow = $block->getLevel()->getBlock($event->getBlock()->floor()->down(1));
        $blocks = [];

        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }

        if (in_array($bbelow->getId(), $blocks)) {
            $tile = $event->getBlock()->getLevel()->getTile($bbelow);
            if (!$tile instanceof SimpleTile) return;
            $ore = $this->checkBlock($bbelow);
            $delay = $this->getDelay($bbelow);
            if (!$event->isCancelled()) {
                $event->setCancelled(true);
                /** @phpstan-ignore-next-line */
                if ($event->getBlock()->getId() == $ore->getId()) return;
                $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($event, $ore): void {
                    if ($event->getBlock()->getLevel() !== null) {
                        Server::getInstance()->getPluginManager()->callEvent(new OreSpawnerGenerateEvent($event->getBlock()->getLevel()->getTile($bbelow), $event->getBlock()));
                        $event->getBlock()->getLevel()->setBlock($event->getBlock()->floor(), $ore, false, true);
                        if ($this->cfg["fizz-sound"] == true) $event->getBlock()->getLevel()->addSound(new FizzSound($event->getBlock()->asVector3()));
                    }
                }), intval($delay));
            }
        }
    }
    
    /**
     * Checks if a block is placed and if it is an OreSpawner.
     * If so, a new tile is created.
     *
     * @param  BlockPlaceEvent $event
     * @return void
     */
    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $block = $event->getBlock();
        $item = $event->getItem();
        $blocks = [];

        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }

        if (in_array($block->getId(), $blocks)) {
            if ($item->getNamedTag()->hasTag("orespawner")) {
                $tile = $event->getPlayer()->getLevel()->getTile($event->getBlock()->asVector3());
                if (!$tile instanceof SimpleTile) {
                    Server::getInstance()->getPluginManager()->callEvent(new OreSpawnerPlaceEvent($event->getPlayer(), $tile, 1));
                    $tileinfo = new TileInfo($event->getBlock(), ["id" => "simpleTile", "stacked" => 1]);
                    new SimpleTile($event->getPlayer()->getLevel(), $tileinfo);
                }
            }
        }
    }

    /**
     * Checks if a block is broken and if it is an OreSpawner.
     * If so, the player gets back their OreSpawner(s).
     *
     * @param  BlockBreakEvent $event
     * @return void
     */
    public function onBlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $bbelow = $block->getLevel()->getBlock($event->getBlock()->floor()->down(1));
        $blocks = [];
        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }
        if ($event->isCancelled()) return;
        if (in_array($event->getBlock()->getId(), $blocks)) {
            $tile = $event->getBlock()->getLevel()->getTile($block);
            if (!$tile instanceof SimpleTile) return;
            $tile = $player->getLevel()->getTile($block->asVector3());
            $type = $this->checkSpawner($block);
            $count = $tile instanceof SimpleTile ? $tile->getData("stacked")->getValue() : 1;
            $orespawner = $this->plugin->createOreSpawner($type, $count);
            Server::getInstance()->getPluginManager()->callEvent(new OreSpawnerBreakEvent($player, $tile, $count));
            $drops = array();
            $drops[] = $orespawner;
            $event->setDrops($drops);
        } else if (in_array($bbelow->getId(), $blocks)) {
            if ($this->cfg["drop-xp"] == false) {
                $event->setXpDropAmount(0);
            }
        }
    }
    
    /**
     * Checks if a player has interacted and if the interacted
     * block is an OreSpawner through tiles. If so, OreSpawners
     * can be stacked and/or OreSpawners in the block can be
     * checked.
     *
     * @param  PlayerInteractEvent $event
     * @return bool
     */
    public function onPlayerInteract(PlayerInteractEvent $event): bool
    {
        if ($this->cfg["stacking"] == false || $event->isCancelled()) return false;
        $item = $event->getItem();
        $player = $event->getPlayer();
        $blocks = [];
        foreach (array_values($this->plugin->getConfig()->get("ore-generator-blocks")) as $blockID) {
            array_push($blocks, $blockID);
        }
        if (in_array($event->getBlock()->getId(), $blocks)) {
            $tile = $event->getPlayer()->getLevel()->getTile($event->getBlock());
            if ($tile instanceof SimpleTile) {
                if (!$player->getGamemode() == 1) {
                    $stacked = $tile->getData("stacked")->getValue();
                    if ($item->getNamedTag()->hasTag("orespawner")) {
                        if ($event->getBlock()->getId() == $item->getId()) {
                            if (!($stacked >= intval($this->cfg["max"]))) {
                                Server::getInstance()->getPluginManager()->callEvent(new OreSpawnerStackEvent($player, $tile, 1));
                                $event->setCancelled(true);
                                $tile->setData("stacked", $stacked + 1);
                                $item->setCount($item->getCount() - 1);
                                $player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
                                $player->sendMessage(str_replace("&", "§", $this->cfg["gen-added"] ?? "&aSuccessfully stacked a orespawner"));
                                return true;
                            }
                            $player->sendMessage(str_replace("&", "§", $this->cfg["limit-reached"] ?? "&cYou can't stack anymore orespawners, you have reached the limit"));
                            return false;
                        }
                        $player->sendMessage("§cPlease hold the right type of OreSpawner to stack");
                        return false;
                    }
                    $player->sendMessage("§aThere are currently " . TF::YELLOW . $stacked . " §astacked OreSpawners");
                    return false;
                }
                $player->sendMessage(TF::RED . "You can only using stacking system in Survival.");
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * Checks the OreSpawner spawning block type.
     *
     * @param  Block $bbelow
     * @return object|bool
     */
    public function checkBlock(Block $bbelow)
    {
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
        if (isset($ore)) {
            return $ore;
        }
        return false;
    }
    
    /**
     * Checks the OreSpawner type.
     *
     * @param  Block $bbelow
     * @return string|bool
     */
    public function checkSpawner(Block $bbelow)
    {
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
        if (isset($ore)) {
            return $ore;
        }
        return false;
    }

    /**
     * Calculates delay till the next ore spawns.
     *
     * @param  Block $block
     * @return int
     */
    public function getDelay(Block $block)
    {
        $tile = $block->getLevel()->getTile($block->asVector3());
        /** @phpstan-ignore-next-line */
        $stacked = $tile->getData("stacked")->getValue();
        $base = intval($this->cfg["base-delay"]);
        return ($base / $stacked) * 20;
    }
    
    /**
     * Returns the tile from a Vector3 position.
     *
     * @param  Vector3 $pos
     * @return Tile
     */
    public function getTile(Vector3 $pos): ?Tile
    {
        /** @phpstan-ignore-next-line */
        return $this->getTileAt((int)floor($pos->x), (int)floor($pos->y), (int)floor($pos->z));
    }
}
