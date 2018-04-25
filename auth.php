<?php
/**
 * DokuWiki Plugin authjoomla (Auth Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class auth_plugin_authjoomla extends auth_plugin_authpdo
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->initializeConfiguration();
        parent::__construct(); // PDO setup
    }


    /**
     * Initialize database configuration
     */
    protected function initializeConfiguration()
    {
        $prefix = $this->getConf('tableprefix');

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlResolve */
        $this->conf['select-user'] = '
            SELECT `id` AS `uid`,
                   `username` AS `user`,
                   `name` AS `name`,
                   `password` AS `hash`,
                   `email` AS `mail`
              FROM `' . $prefix . 'users`
             WHERE `username` = :user
               AND `block` = 0
               AND `activation` = 0        
        ';

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlResolve */
        $this->conf['select-user-groups'] = '
            SELECT g.`title` AS `group`
              FROM `' . $prefix . 'user_usergroup_map` AS m,
                   `' . $prefix . 'usergroups` AS g
             WHERE m.`group_id` = g.`id`
               AND m.`user_id` = :uid
        ';

    }
}

// vim:ts=4:sw=4:et:
