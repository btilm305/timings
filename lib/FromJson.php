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
namespace Starlis\Timings;

/**
 * Provides method to translate JSON Data into an object using PHP Doc comments.
 *
 * @see https://gist.github.com/aikar/52d2ef0870483696f059 for usage
 */
trait FromJson {
    /**
     * @param                $rootData
     * @param FromJsonParent $parentObj
     *
     * @return $this
     */
    public static function createObject($rootData, $parentObj = null) {
        $class = __CLASS__;
        $ref = new \ReflectionClass($class);
        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        if (Util::has_trait($class, 'Starlis\Timings\Singleton')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $obj = self::getInstance();
        } else {
            $obj = new self();
        }

        $classDoc = $ref->getDocComment();
        if (preg_match('/@mapper\s+(.+?)\s/', $classDoc, $matches)) {
            $cb = $matches[1];
            if (!strstr($cb, "::")) {
                $cb = __CLASS__ . "::$cb";
            }
            if (!strstr($cb, "\\")) {
                $cb = Util::getNamespace(__CLASS__) . "\\$cb";
            }
            $cb = explode("::", $cb, 2);
            $rootData = call_user_func($cb, $rootData, $parentObj);
        }


        foreach ($props as $prop) {
            $name = $prop->getName();
            $comment = $prop->getDocComment();
            $parent = new FromJsonParent($name, $comment, $obj, $parentObj, null);

            $isExpectingArray = false;
            if (preg_match('/@var .+?\[.*?\]/', $comment, $matches)) {
                $isExpectingArray = true;
            }

            $index = $name;
            if (preg_match('/@index\s+([@\w\-]+)/', $comment, $matches)) {
                $index = $matches[1];

            }
            $vars = is_object($rootData) ? get_object_vars($rootData) : $rootData;

            if ($index == '@key') {
                $data = $parentObj->name;
            } else if ($index == '@value') {
                $data = $rootData;
            } else if (isset($vars[$index])) {
                $data = $vars[$index];
            } else {
                $data = null;
            }

            if ($data || $isExpectingArray) {
                if ($isExpectingArray) {
                    $result = [];
                    if ($data && !is_scalar($data)) {
                        $data = is_object($data) ? get_object_vars($data) : $data;

                        foreach ($data as $key => $entry) {
                            $arrParent = new FromJsonParent($key, $comment, $result, $parent);
                            $thisData = self::getData($entry, $arrParent);

                            $keyName = $arrParent->name;
                            if (preg_match('/@keymapper\s+(.+?)\s/', $parent->comment, $matches)) {
                                $cb = $matches[1];
                                if (!strstr($cb, "::")) {
                                    $cb =  __CLASS__ . "::$cb";;
                                }
                                if (!strstr($cb, "\\")) {
                                    $cb = Util::getNamespace(__CLASS__) . "\\$cb";
                                }
                                $cb = explode("::", $cb, 2);
                                $keyName = call_user_func($cb, $keyName, $thisData, $parent);
                            }

                            $result[$keyName] = $thisData;
                        }
                    }
                    $data = $result;
                } else {
                    $data = self::getData($data, $parent);
                }
                $prop->setValue($obj, $data);
            }
        }
        $obj->init();

        return $obj;
    }

    public function init() {

    }

    /**
     * @param                $data
     * @param FromJsonParent $parent
     * @return mixed
     */
    private static function getData($data, FromJsonParent $parent) {
        $className = null;
        if (preg_match('/@var\s+([\w_]+)(\[.*?\])?/', $parent->comment, $matches)) {
            $className = $matches[1];
            if (!strstr($className, "\\")) {
                $className = Util::getNamespace(__CLASS__)."\\$className";
            }
        }

        if (preg_match('/@mapper\s+(.+?)\s/', $parent->comment, $matches)) {
            $cb = $matches[1];
            if (!strstr($cb, "::")) {
                $cb =  __CLASS__ . "::$cb";;
            }
            if (!strstr($cb, "\\")) {
                $cb = Util::getNamespace(__CLASS__) . "\\$cb";
            }
            $cb = explode("::", $cb, 2);
            $data = call_user_func($cb, $data, $parent);
        } else if ($className && Util::has_trait($className, __TRAIT__)) {
            $data = call_user_func("$className::createObject", $data, $parent);
        } else if (!is_scalar($data)) {
            $data = Util::flattenObject($data);
        }

        return $data;
    }
}

class FromJsonParent {
    /**
     * @var FromJsonParent
     */
    public $parent;
    public $comment, $obj, $name, $root;

    function __construct($name, $comment, $obj, $parent) {
        $this->name = $name;
        $this->comment = $comment;
        $this->obj = $obj;
        $this->parent = $parent;
        $this->root = $parent == null || $parent->root == null ? $obj : $parent->root;
    }
}
