<?php

namespace App\Auth;

use App\DB\SQLiteDB;
use App\Model\User;
use SlimSession\Helper;

class Permission
{
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
        $db = new SQLiteDB('USER');

        $result = $db->doQuery($query, [':id' => $userId, ':name' => $name])->fetchArray();

        return $result;
    }

    /**
     * @param string $name
     * @param int $user
     * @param array $sections
     * @return void
     */
    public function addPermission($name, $user = 0, $sections = [])
    {
        $db = new SQLiteDB('USER', SQLITE3_OPEN_READWRITE);
        $userExists = $db->doQuery('SELECT id,username FROM user WHERE id=:id', [':id' => $user])->fetchArray();
        if ($userExists && $userExists['id'] === $user) {
            $sectionIds = [];
            $db->doQuery(
                'INSERT INTO permission (name) VALUES(:permissionName)',
                [':permissionName' => $name]
            );
            $permission = $db->doQuery(
                'SELECT id FROM permission WHERE name=:permissionName',
                [':permissionName' => $name]
            );
            foreach ($sections as $section) {
                if (is_numeric($section)) {
                    $sectionIds[] = $section;
                } else {
                    $sectionResult = $db->doQuery(
                        'SELECT id FROM section WHERE title=:section',
                        [':section' => $section]
                    )
                        ->fetchArray();
                    if ($sectionResult) {
                        $sectionIds[] = $sectionResult['id'];
                    }
                }
            }
            $dbQuery = "INSERT INTO user_permission (user_id, permission_id) VALUES (:user, :permission)";
            $db->doQuery($dbQuery, [':user' => $user, ':permission' => $permission['id']]);
            $insertArr = [];
            foreach ($sectionIds as $sectionId) {
                $insertArr[] = sprintf('(%d,%d)', $permission['id'], $sectionId);
                $db->doQuery(sprintf('INSERT INTO permission_section (permission_id, section_id) VALUES %s;', implode(',', $insertArr)));
            }
        }
    }
}
