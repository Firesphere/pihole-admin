<?php

namespace App\Model;

class Permission extends BaseModel
{
    protected $table = 'permission';

    protected $name;

    protected $users;

    protected $sections;

    public function getUsers($refresh = false)
    {
        if (!$refresh && count($this->users)) {
            return $this->users;
        }
        $relationQuery = "SELECT user_id FROM user_permissions
                WHERE user_permissions.permission_id = :id;";
        $result = self::$db->doQuery($relationQuery, [':id' => $this->id]);

        while ($userId = $result->fetchArray()) {
            $this->users[] = $this->byId($userId['user_id'], User::class);
        }

        return $this->users;
    }


    public function getSections($refresh = false)
    {
        if (!$refresh && count($this->sections)) {
            return $this->sections;
        }
        $relationQuery = "SELECT section_id FROM permission_section
                WHERE permission_section.permission_id = :id;";
        $result = self::$db->doQuery($relationQuery, [':id' => $this->id]);

        while ($sectionId = $result->fetchArray()) {
            $this->sections[] = $this->byId($sectionId['id'], Section::class);
        }

        return $this->sections;
    }

    /**
     * @param $name
     * @param User $user
     * @return false|\SQLite3Result
     */
    public static function check($name, $user)
    {
        $userId = $user->getId();
        $query = "
        SELECT user.id,section.title FROM user
            INNER JOIN user_permissions ON user_permissions.user_id = user.id
            INNER JOIN permission_section ON user_permissions.permission_id = permission_section.permission_id
            INNER JOIN section ON permission_section.section_id = section.id
        WHERE user.id = :id AND section.title = :name";

        return self::$db->doQuery($query, [':id' => $userId, ':name' => $name])->fetchArray();
    }
}
