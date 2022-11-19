<?php

namespace App\Model;

/**
 * A section is a static part of the Pi-hole admin interface
 * and can not be created or removed, unless there is an active
 * change in the sections of the interface.
 *
 * Adding or removing, should be done via a migration.
 */
class Section extends BaseModel
{
    protected $table = 'section';

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
            $this->permissions[] = $this->byId($userId['user_id'], Permission::class);
        }

        return $this->permissions;
    }
}
