<?php

namespace Syltaen;

/**
 * Wrap each result of a model in a class that is used to retrieve dynamic fields defined by the model
 */

class ModelItemUser extends ModelItem
{
    const FIELD_PREFIX = "user_";

    /**
     * Get a specific meta data
     *
     * @param string
     * @return mixed
     */
    public function getMeta($meta_key, $multiple = false)
    {
        return get_user_meta($this->getID(), $meta_key, !$multiple);
    }

    /**
     * Update a meta value in the database
     *
     * @param int $id
     * @param string $key
     * @param mixed $value
     * @return mixed Meta ID if the key didn't exist, true on successful update, false on failure
     */
    public function setMeta($key, $value)
    {
        return update_user_meta($this->getID(), $key, $value);
    }

    /**
     * Set the attributes of an item
     *
     * @param array $attributes
     * @return int|WP_Error The updated user's ID or a WP_Error object if the user could not be updated.
     */
    public function updateAttrs($attrs, $merge = false)
    {
        if (empty($attrs)) return false;
        $attrs = $this->parseAttrs($attrs, $merge);
        $attrs["ID"] = $this->getID();
        return wp_update_user($attrs);
    }

    /**
     * Alias for updateRoles()
     *
     * @param [type] $tax
     * @param boolean $merge
     * @return void
     */
    public function updateTaxonomies($roles, $merge = false)
    {
        $user = get_user_by("id", $this->ID);

        if (empty($user)) return false;

        // No merge : remove all current roles
        if (!$merge) $user->set_role("");

        // Add all new roles
        foreach ((array) $roles as $role) {
            $user->add_role($role);
        }
    }

    /**
     * Alias for updateTaxonomies()
     *
     * @return void
     */
    public function updateRoles($roles, $merge = false)
    {
        $this->updateTaxonomies($roles, $mege);
    }

    /**
     * Delete a single user
     *
     * @param bool|int $reassign Reassign posts and links to new User ID.
     * @return void
     */
    public function delete($reassign = null)
    {
        require_once(ABSPATH . "wp-admin/includes/user.php");
        return wp_delete_user($this->ID, $reassign);
    }

    /**
     * Expose each default value of the wp_object
     *
     * @param object $user
     * @param Model $model
     */
    public function __construct($user, $model = false)
    {
        if (is_int($user)) {
            return parent::__construct($user, $model);
        }

        // Keep only the some data
        $item = (object) [
            "ID"         => $user->ID,
            "roles"      => $user->roles,
            "caps"       => $user->allcaps,
            "first_name" => $user->first_name,
            "last_name"  => $user->last_name
        ];

        // Remove "user_" prefix for some data
        foreach ($user->data as $key=>$value) {
            $item->{str_replace("user_", "", $key)} = $value;
        }

        parent::__construct($item, $model);
    }
}