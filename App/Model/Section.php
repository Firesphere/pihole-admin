<?php

namespace App\Model;

class Section extends BaseModel
{
    protected $table = 'permission';

    protected $title;

    protected $permissions;

    public function getPermissions($refresh = false)
    {
        if (!$refresh && count($this->permissions)) {
            return $this->permissions;
        }
        $relationQuery = "SELECT permission_id FROM permission_section
                WHERE permission_section.section_id = :id;";
        $result = self::$db->doQuery($relationQuery, [':id' => $this->id]);

        while ($userId = $result->fetchArray()) {
            $this->permissions[] = $this->byId(Permission::class, $userId['user_id']);
        }

        return $this->permissions;
    }
}
