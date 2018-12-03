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
   * Register acts category
   */
  static function register_category_taxonomy() {
    register_taxonomy(
      self::taxonomy,
      'acts_activity',
      array(
          'label' => __( 'Activity Categories' ),
          'public' => false,
          'rewrite' => false,
          'hierarchical' => true,
      )
    );
  }

  static function insert( $values ){
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
}
Activities_Category::init();
