<?php
/**
 * Aurora Heatmap Options Class
 *
 * @package aurora-heatmap
 * @copyright 2019-2020 R3098 <info@seous.info>
 * @version 1.4.11
 */

/**
 * Aurora_Heatmap_Options
 */
class Aurora_Heatmap_Options implements ArrayAccess {

	/**
	 * Options
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Validation callback
	 *
	 * @var callable|null $checker
	 */
	private $checker = null;

	/**
	 * Constructor
	 *
	 * @param callable|null $checker Validation callback.
	 */
	public function __construct( $checker = null ) {
		$this->checker = $checker;
		$this->load();
	}

	/**
	 * Load options
	 */
	public function load() {
		$options = maybe_unserialize( get_option( 'aurora_heatmap_option' ) );
		if ( ! $options ) {
			$options = array();
		}
		$this->options = $this->check( $options, null );
	}

	/**
	 * Save options
	 *
	 * @param array $options Save options.
	 */
	public function save( $options = array() ) {
		// Keep old option value instead of null.
		foreach ( $options as $key => $value ) {
			if ( null === $value && isset( $this->options[ $key ] ) ) {
				$options[ $key ] = $this->options[ $key ];
			}
		}
		// Keep missing option values.
		$options = array_merge( $this->options, $options );

		$this->update( $options, true );
	}

	/**
	 * Check options
	 *
	 * @param array      $new_options New Options.
	 * @param array|null $old         Old options, otherwise skip the trigger on change.
	 * @return array
	 */
	private function check( $new_options, $old = null ) {
		if ( is_callable( $this->checker ) ) {
			$checker = $this->checker;
			return $checker( $new_options, $old );
		} else {
			return $new_options;
		}
	}

	/**
	 * Update options
	 *
	 * @param array $options Options.
	 * @param bool  $trigger Trigger.
	 */
	private function update( $options, $trigger = true ) {
		$this->options = $this->check( $options, $trigger ? $this->options : null );
		update_option( 'aurora_heatmap_option', maybe_serialize( $this->options ), 'yes' );
	}

	/**
	 * ArrayAccess::offsetExists()
	 *
	 * @param string $offset Option key.
	 * @return mixed
	 */
	public function offsetExists( $offset ) {
		return array_key_exists( $offset, $this->options ) && isset( $this->options[ $offset ] );
	}

	/**
	 * ArrayAccess::offsetGet()
	 *
	 * @param string $offset Option key.
	 * @return bool
	 */
	public function offsetGet( $offset ) {
		return array_key_exists( $offset, $this->options ) ? $this->options[ $offset ] : null;
	}

	/**
	 * ArrayAccess::offsetSet()
	 *
	 * @param string $offset Option key.
	 * @param mixed  $value  Option value.
	 */
	public function offsetSet( $offset, $value ) {
		$options            = $this->options;
		$options[ $offset ] = $value;
		$this->update( $options, false );
	}

	/**
	 * ArrayAccess::offsetUnset()
	 *
	 * @param string $offset Option key.
	 */
	public function offsetUnset( $offset ) {
		$options = $this->options;
		unset( $options[ $offset ] );
		$this->update( $options, false );
	}
}

/* vim: set ts=4 sw=4 sts=4 noet : */
