<?php
/**
 * Query workflows based on auguements.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to query workflows based on arguements.
 *
 * @class Workflow_Query
 *
 * @since 4.4.1
 */
class ES_Workflow_Query {

	/**
	 * Trigger name
	 *
	 * @var string|ES_Workflow_Trigger
	 */
	public $trigger;
	
	/**
	 * Trigger names
	 *
	 * @var array|ES_Workflow_Trigger
	 */
	public $triggers;

	/**
	 * Query arguements
	 *
	 * @var array
	 */
	public $args;

	/**
	 * Return result type
	 *
	 * @var string
	 */
	public $return = 'objects';

	/**
	 * Construct
	 */
	public function __construct() {
		$this->args = array(
			'status'   => 1,
			'type'     => 0, // Fetch only user defined workflows.
			'order'    => 'ASC',
			'order_by' => 'priority',
		);
	}


	/**
	 * Set trigger name or array of names to query.
	 *
	 * @param string|ES_Workflow_Trigger $trigger Workflow trigger object|name.
	 */
	public function set_trigger( $trigger ) {
		if ( $trigger instanceof ES_Workflow_Trigger ) {
			$this->trigger = $trigger->get_name();
		} else {
			$this->trigger = $trigger;
		}
	}

	/**
	 * Set trigger name or array of names to query.
	 *
	 * @param string|ES_Workflow_Trigger $trigger Workflow trigger object|name.
	 */
	public function set_triggers( $triggers ) {
		if ( ! empty( $triggers ) ) {
			foreach ( $triggers as $trigger ) {
				if ( $trigger instanceof ES_Workflow_Trigger ) {
					$this->triggers[] = $trigger->get_name();
				} else {
					$this->triggers[] = $trigger;
				}
			}
		}
	}

	/**
	 * Get workflows by status
	 * 
	 * @since 4.6.5
	 * @param int $status
	 * @return $this
	 */
	public function where_status( $status ) {
		$this->args['status'] = $status;
	}

	/**
	 * Set return object
	 *
	 * @param objects|ids $return Result format ids or objects.
	 */
	public function set_return( $return ) {
		$this->return = $return;
	}


	/**
	 * Get workflows based on query arguements
	 *
	 * @return ES_Workflow[] $workflows Workflow object
	 */
	public function get_results() {

		if ( $this->trigger ) {
			$this->args['trigger_name'] = $this->trigger;
		}

		if ( $this->triggers ) {
			$this->args['trigger_names'] = $this->triggers;
		}

		$this->args['fields'] = array();
		if ( 'ids' === $this->return ) {
			$this->args['fields'][] = 'id';
		}

		$results = ES()->workflows_db->get_workflows( $this->args );

		if ( ! $results ) {
			return array();
		}

		$workflows = array();

		foreach ( $results as $post ) {

			if ( 'ids' === $this->return ) {
				$workflows[] = $post;
			} else {
				$workflow    = new ES_Workflow( $post );
				$workflows[] = $workflow;
			}
		}

		return $workflows;
	}
}
