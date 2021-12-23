<?php

namespace Syltaen;

abstract class UsersModel extends Model
{
    /**
     * The slug that define what this model is used for
     */
    const TYPE = "users";

    /**
     * Query arguments used by the model's methods
     */
    const QUERY_CLASS  = "WP_User_Query";
    const OBJECT_CLASS = "WP_User";
    const ITEM_CLASS   = "\Syltaen\User";
    const OBJECT_KEY   = "results";
    const QUERY_IS     = "include";
    const QUERY_ISNT   = "exclude";
    const QUERY_LIMIT  = "number";
    const QUERY_HOOK   = "pre_user_query";
    const META_TABLE   = "usermeta";
    const META_ID      = "umeta_id";
    const META_OBJECT  = "user_id";

    // =============================================================================
    // > QUERY MODIFIERS
    // =============================================================================

    /* Update parent method */
    /**
     * @param  $terms
     * @param  array     $columns
     * @param  $strict
     * @return mixed
     */
    public function search($terms, $columns = [], $strict = false)
    {
        $this->filters["search"] = $strict ? $terms : "*$terms*";
        if (!empty($columns)) {
            $this->filters["search_columns"] = $columns;
        }

        return $this;
    }

    /**
     * Filter to the current logged user
     *
     * @return self
     */
    public function logged()
    {
        return $this->is(wp_get_current_user()->ID);
    }

    /**
     * Check if there is a logged user
     *
     * @return boolean
     */
    public static function isLogged()
    {
        return is_user_logged_in();
    }

    /**
     * Get the target ID, fallback to the current logged-in user
     *
     * @param  mixed         $user
     * @return int|boolean
     */
    public static function getTargetID($target = false)
    {
        if ($user) {
            return Data::extractIds($user)[0] ?? false;
        }

        return get_current_user_id();
    }

    /**
     * Filter users by roles
     *
     * @param  array|string $roles  An array or a comma-separated list of role names that users must match to be included in results.
     * @param  $relation    Specify if the matches should have : any, all or none of the roles
     * @return self
     */
    public function role($roles, $relation = "all")
    {
        switch ($relation) {
            case "any":
                $this->filters["role__in"] = $roles;
                break;
            case "none":
                $this->filters["role__not_in"] = $roles;
                break;
            case "all":
            default:
                $this->filters["role"] = $roles;
        }
        return $this;
    }

    /**
     * Set a default filter because runing WP_User_Query without any argument return no result
     *
     * @param  boolean $filter_keys
     * @param  array   $default_filters
     * @return self
     */
    public function clearFilters($filter_keys = false, $default_filters = null)
    {
        return parent::clearFilters($filter_keys, [
            "prevent_empty" => true,
        ]);
    }

    // =============================================================================
    // > GETTERS
    // =============================================================================
    /* Update parent method */
    /**
     * @param  $paginated
     * @return mixed
     */
    public function count($paginated = true)
    {
        if ($paginated) {
            return count($this->getQuery()->results);
        } else {
            return $this->getQuery()->total_users;
        }

    }

    /* Update parent method */
    /**
     * @return int
     */
    public function getPagesCount()
    {
        // No limit, everything in one page
        if (!isset($this->filters[static::QUERY_LIMIT])) {
            return 1;
        }

        // Else, divide total by limit
        return ceil($this->getQuery()->total_users / $this->filters[static::QUERY_LIMIT]);
    }

    /**
     * Get all the IDs of this model's objects
     *
     * @return array
     */
    public static function getAllIDs()
    {
        return (array) Database::get_col("SELECT ID FROM users");
    }

    // =============================================================================
    // > ROLES AND PERMISSIONS
    // =============================================================================
    /**
     * Check is the matched users have a capability or a role
     *
     * @param  string|array $capability Capability or Role to check, or an array of them
     * @param  string       $relation   If $capability is an array, specify if the users should have any or all capacility (any|all)
     * @return void
     */
    public function can($capability, $relation = "all")
    {
        if (!$this->found()) {
            return false;
        }

        foreach ($this->get() as $user) {
            if (is_array($capability)) {
                switch ($relation) {
                    case "any":
                        $user_can = false;
                        foreach ($capability as $cap) {
                            if (isset($user->caps[$cap]) && $user->caps[$cap]) {
                                $user_can = true;
                                break;
                            }
                        }
                        if (!$user_can) {
                            return false;
                        }

                        break;
                    case "all":
                    default:
                        foreach ($capability as $cap) {
                            if (!isset($user->caps[$cap]) || !$user->caps[$cap]) {
                                return false;
                            }
                        }
                        break;
                }
            } else {
                if (!isset($user->caps[$capability]) || !$user->caps[$capability]) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Remove unused roles
     *
     * @param  array  $roles
     * @return void
     */
    public static function unregisterRoles($roles)
    {
        foreach ($roles as $role) {
            if (get_role($role)) {
                remove_role($role);
            }
        }
    }

    /**
     * Register custom capablilities
     *
     * @param  array  $capablilities
     * @return void
     */
    public static function registerCapabilities($capablilities)
    {
        $role = get_role("administrator");

        foreach ($capablilities as $cap) {
            $role->add_cap($cap);
        }
    }

    /**
     * Get an object instance
     *
     * @return WP_...
     */
    public static function getObject($id)
    {
        $class = static::OBJECT_CLASS;
        return new $class($id);
    }

    // =============================================================================
    // > ACTIONS
    // =============================================================================
    /**
     * Create a new user
     *
     * @param  string $login
     * @param  string $password
     * @param  string $email
     * @param  array  $roles
     * @return self   A new model instance containing the new item
     */
    public static function add($login, $password, $email, $attrs = [], $fields = [], $roles = [])
    {
        $user_id = wp_insert_user(array_merge([
            "user_login"           => $login,
            "user_pass"            => $password,
            "user_email"           => $email,
            "show_admin_bar_front" => "false",
        ], $attrs));

        if ($user_id instanceof \WP_Error) {
            return $user_id;
        }

        return static::getItem($user_id)->update(false, $fields, $roles);
    }

    /**
     * Alias for the updateTaxonomy method
     *
     * @param  array  $roles Roles to set
     * @param  bool   $merge Wether to merge or set the values
     * @return self
     */
    public function updateRoles($roles, $merge = false)
    {
        return $this->updateTaxonomies($roles, $merge);
    }

    /**
     * Logout any logged user
     *
     * @param  string $redirection URL to which redirect when logged out successfully
     * @return void
     */
    public static function logout($redirection = false)
    {
        wp_logout();
        if ($redirection) {
            Route::redirect($redirection);
        }
    }
}