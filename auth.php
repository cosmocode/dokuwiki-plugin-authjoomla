<?php
/**
 * DokuWiki Plugin authjoomla (Auth Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */

/**
 * Class auth_plugin_authjoomla
 */
class auth_plugin_authjoomla extends auth_plugin_authpdo
{

    /** @inheritdoc */
    public function __construct()
    {
        $this->initializeConfiguration();
        parent::__construct(); // PDO setup
        $this->ssoByCookie();
    }

    /** @inheritdoc */
    public function checkPass($user, $pass)
    {
        // username already set by SSO
        if ($_SERVER['REMOTE_USER'] &&
            $_SERVER['REMOTE_USER'] == $user &&
            !empty($this->getConf('cookiename'))
        ) return true;

        return parent::checkPass($user, $pass);
    }

    /**
     * Check if Joomla Cookies exist and use them to auto login
     */
    protected function ssoByCookie()
    {
        global $INPUT;

        if (!empty($_COOKIE[DOKU_COOKIE])) return; // DokuWiki auth cookie found
        if (empty($_COOKIE['joomla_user_state'])) return;
        if ($_COOKIE['joomla_user_state'] !== 'logged_in') return;
        if (empty($this->getConf('cookiename'))) return;
        if (empty($_COOKIE[$this->getConf('cookiename')])) return;

        // check session in Joomla DB
        $session = $_COOKIE[$this->getConf('cookiename')];
        $sql = $this->getConf('select-session');
        $result = $this->_query($sql, ['session' => $session]);
        if ($result === false) return;

        // force login
        $_SERVER['REMOTE_USER'] = $result[0]['user'];
        $INPUT->set('u', $_SERVER['REMOTE_USER']);
        $INPUT->set('p', 'sso_only');
    }

    /**
     * Remove the joomla cookie
     */
    public function logOff()
    {
        parent::logOff();
        setcookie('joomla_user_state', '', time() - 3600, '/');
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
            SELECT
              p.id AS `gid`,
              (
                SELECT GROUP_CONCAT(xp.`title` ORDER BY xp.`lft` SEPARATOR \'/\')
                  FROM `' . $prefix . 'usergroups` AS xp
                WHERE p.`lft` BETWEEN xp.`lft` AND xp.`rgt`
              ) AS `group`
              FROM `' . $prefix . 'user_usergroup_map` AS m,
                   `' . $prefix . 'usergroups` AS g,
                   `' . $prefix . 'usergroups` AS p
             WHERE m.`user_id`  = :uid
               AND g.`id` = m.`group_id`
               AND p.`lft` <= g.`lft`
               AND p.`rgt` >= g.`rgt`
        ';

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlResolve */
        $this->conf['select-groups'] = '
            SELECT n.id AS `gid`, GROUP_CONCAT(p.`title` ORDER BY p.lft SEPARATOR \'/\') as `group`
              FROM `' . $prefix . 'usergroups` AS n, `' . $prefix . 'usergroups` AS p
             WHERE n.lft BETWEEN p.lft AND p.rgt
          GROUP BY n.id
          ORDER BY n.id
        ';

        #FIXME we probably want to limit the time here
        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlResolve */
        $this->conf['select-session'] = '
            SELECT s.`username` as `user`
              FROM `' . $prefix . 'session` AS s,
                   `' . $prefix . 'users` AS u
             WHERE s.`session_id` = :session
               AND s.`userid` = u.`id`
               AND `block` = 0
               AND `activation` = 0
        ';
    }
}

// vim:ts=4:sw=4:et:
