<?php

namespace App\Frontend\Modules;

class Module
{
    /**
     * @var array
     */
    protected static $modules = [];
    /**
     * @var int
     */
    public $sort = 0;

    /**
     * Register a Module
     * @param string $className
     * @return void
     */
    public static function registerModule($className)
    {
        static::$modules[] = $className;
    }

    /**
     * Used to sort the modules
     * @param $obj
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
        $modules = [];
        foreach (static::$modules as $module) {
            $modules[] = new $module();
        }
        usort($modules, [self::class, 'getSortOrder']);

        return $modules;
    }
}
