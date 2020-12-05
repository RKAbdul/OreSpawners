<?php

declare(strict_types=1);

namespace RKAbdul\OreSpawners\Events;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

use RKAbdul\OreSpawners\Main;

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
     * @param  SimpleTile $spawnerTile
     * @param  Player $player
     * @return void
     */
    public function __construct(SimpleTile $spawnerTile, $player)
    {
        $this->spawnerTile = $spawnerTile;
        $this->player = $player;
        
        /** @phpstan-ignore-next-line */
        parent::__construct(Main::getInstance());
    }
    
    /**
     * getPlayer
     *
     * @return Player
     */
    public function getPlayer() 
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