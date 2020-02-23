<?php

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * Category class
 *
 * @since      1.1.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Category {
    const taxonomy = 'acts_cat';

    /**
     * Add category specific actions
     */
    static function init() {
        add_action( 'init', array( __CLASS__, 'register_category_taxonomy' ) );
    }

    /**
     * Added the uncategorized category
     *
     * @return bool
     */
    static function add_uncategorized() {
        $insert = wp_insert_term(
            'Uncategorized',
            self::taxonomy
        );

        return !is_wp_error( $insert );
    }

    /**
     * Register acts category
     */
    static function register_category_taxonomy() {
        register_taxonomy(
            self::taxonomy,
            'acts_activity',
            array(
                'label'        => __( 'Activity Categories' ),
                'rewrite'      => false,
                'hierarchical' => true,
                'sort'         => true
            )
        );
    }

    /**
     * Check if a category exists
     *
     * @param int $id Term id
     *
     * @return  bool
     */
    static function exists( $id ) {
        if ( $id <= 0 ) {
            return false;
        }

        return term_exists( $id, self::taxonomy );
    }

    /**
     * Get categories for an activity
     *
     * @param int $act_id Activity id
     * @param bool $display True for names, false for ids
     *
     * @return  array
     */
    static function get_act_categories( $act_id, $display = false ) {
        if ( $display ) {
            return wp_get_object_terms( $act_id, self::taxonomy, array(
                'orderby' => 'term_order',
                'fields'  => 'names'
            ) );
        }

        return wp_get_object_terms( $act_id, self::taxonomy, array( 'orderby' => 'term_order', 'fields' => 'ids' ) );
    }

    /**
     * Get activities with category or categories
     *
     * @param int|array $cat_id Category id or list of categories
     *
     * @return  array         List of activity ids
     */
    static function get_activities_with_category( $cat_id ) {
        $acts = get_objects_in_term( $cat_id, self::taxonomy );

        //Remove possible duplicates
        if ( is_array( $cat_id ) ) {
            $filtered_acts = array();

            foreach ( $acts as $id ) {
                if ( !isset( $filtered_acts[ $id ] ) ) {
                    $filtered_acts[ $id ] = $id;
                }
            }

            $acts = array_keys( $filtered_acts );
        }

        return $acts;
    }

    /**
     * Get all categories
     *
     * @return array
     */
    static function get_categories( $fields = 'all' ) {
        return get_terms( array(
            'taxonomy'   => self::taxonomy,
            'hide_empty' => false,
            'fields'     => $fields
        ) );
    }

    /**
     * Insert a new category into the db
     *
     * @param array $values Category info
     *
     * @return  array|WP_Error   The Term ID and Term Taxonomy ID.
     */
    static function insert( $values ) {
        $args = array(
            'slug' => $values['slug']
        );

        if ( array_key_exists( 'desc', $values ) ) {
            $args['description'] = $values['desc'];
        }

        $parent_term = term_exists( $values['parent'], self::taxonomy );
        if ( !empty( $parent_term ) ) {
            $args['parent'] = $parent_term['term_id'];
        }

        return wp_insert_term(
            $values['name'],
            self::taxonomy,
            $args
        );
    }

    /**
     * Update category into the db
     *
     * @param array $values Category info
     *
     * @return  array|WP_Error   The Term ID and Term Taxonomy ID.
     */
    static function update( $values ) {
        $args = array(
            'name'        => $values['name'],
            'description' => $values['desc']
        );

        $parent_term = term_exists( $values['parent'], self::taxonomy );
        if ( !empty( $parent_term ) ) {
            $args['parent'] = $parent_term['term_id'];
        }

        return wp_update_term(
            $values['id'],
            self::taxonomy,
            $args
        );
    }

    /**
     * Delete a category
     *
     * @param int $id Category id
     *
     * @return  bool|WP_Error   Returns false if not term; true if completes delete action.
     */
    static function delete( $id ) {
        return wp_delete_term(
            $id,
            self::taxonomy
        );
    }

    /**
     * Create a relation between activity and category
     *
     * The first ordered category is the the primary category.
     *
     * @param int $act_id Activity id
     * @param array $cats All Categories ('primary' and 'additional')
     *
     * @return  array|WP_Error   The Term ID and Term Taxonomy ID.
     */
    static function change_category_relations( $act_id, $cats ) {
        return wp_set_object_terms( $act_id, $cats, self::taxonomy );
    }
}

Activities_Category::init();
