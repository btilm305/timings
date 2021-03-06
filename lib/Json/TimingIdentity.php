<?php
/*
 * Aikar's Minecraft Timings Parser
 *
 * Written by Aikar <aikar@aikar.co>
 * http://aikar.co
 * http://starlis.com
 *
 * @license MIT
 */
namespace Starlis\Timings\Json;
use Starlis\Timings\FromJson;

class TimingIdentity {
    use FromJson;

    /**
     * @index @key
     * @var int
     */
    public $id;
    /**
     * @index 1
     * @var string
     */
    public $name;
    /**
     * @mapper TimingsMap::getGroupName
     * @index 0
     * @var string
     */
    public $group;

    /**
     * @var TimingHandler
     */
    private $handler;

    public function __toString() {
        return $this->group . "::" . $this->name;
    }

    /**
     * @return TimingHandler
     */
    public function getHandler() {
        return $this->handler;
    }

    /**
     * @param TimingHandler $handler
     */
    public function setHandler(TimingHandler $handler) {
        $this->handler = $handler;
    }
} 
