<?php

declare(strict_types=1);

namespace RKAbdul\OreSpawners\Events;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

use DenielWorld\EzTiles\EzTiles;
use DenielWorld\EzTiles\tile\SimpleTile;

class OreSpawnerEvent extends PluginEvent
{
    /**
     * @var Player
     */
    private $player;
    
    /**
     * @var SimpleTile
     */
    private $spawnerTile;
    
    /**
     * Initialize objects.
     *
     * @param  Player $player
     * @param  SimpleTile $spawnerTile
     * @return void
     */
    public function __construct(Player $player, SimpleTile $spawnerTile)
    {
        $this->player = $player;
        $this->spawnerTile = $spawnerTile;

        parent::__construct(Main::getInstance());
    }
    
    /**
     * getPlayer
     *
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }
    
    /**
     * getSpawnerTile
     *
     * @return SimpleTile
     */
    public function getSpawnerTile(): SimpleTile
    {
        return $this->spawnerTile;
    }
}