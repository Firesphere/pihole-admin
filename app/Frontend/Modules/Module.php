<?php

namespace App\Frontend\Modules;

class Module
{
    public $sort = 0;

    protected static $modules = [];

    public static function registerModule($className)
    {
        static::$modules[] = $className;
    }

    /**
     * @return int
     */
    public function getSortOrder($obj): int
    {
        return $obj->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getBaseFolder($path = '')
    {
        return sprintf('Partials/%s', $path);
    }

    /**
     * @return array
     */
    public function getModules()
    {
        $class = [];
        foreach (static::$modules as $module) {
            $class[] = new $module();
        }
        usort($class, [self::class, 'getSortOrder']);
        return $class;
    }
}